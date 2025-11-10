<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Jobs\ProcessCampaignDelivery;

class CampaignLoop extends Command
{
    /**
     * Run with:
     *   php artisan campaigns:loop --interval=30 --batch=20
     */
    protected $signature = 'campaigns:loop 
                            {--interval=60 : Seconds to sleep between scans} 
                            {--batch=50 : Max campaigns to process per scan} 
                            {--sub-chunk=500 : Subscribers fetch chunk size}';

    protected $description = 'Continuously scan & dispatch due email campaigns (using application timezone).';

    protected bool $running = true;

    public function handle(): int
    {
        $interval   = (int) $this->option('interval');
        $batchLimit = (int) $this->option('batch');
        $subChunk   = (int) $this->option('sub-chunk');

        if ($interval < 10) {
            $this->warn('Interval too low; setting to minimum 10 seconds to avoid hammering DB.');
            $interval = 10;
        }

        $tz = config('app.timezone');
        $this->info("== Campaign Loop Started (tz={$tz}, interval={$interval}s, batch={$batchLimit}, sub-chunk={$subChunk}) ==");

        // Graceful stop on SIGTERM / SIGINT (if pcntl available)
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, function () {
                $this->running = false;
                $this->line("\nReceived SIGTERM → stopping after current cycle.");
            });
            pcntl_signal(SIGINT, function () {
                $this->running = false;
                $this->line("\nReceived SIGINT (Ctrl+C) → stopping after current cycle.");
            });
        }

        while ($this->running) {
            $cycleStarted = microtime(true);
            $this->processDueCampaigns($batchLimit, $subChunk);
            $elapsed = microtime(true) - $cycleStarted;
            $sleep   = max(0, $interval - (int)$elapsed);

            if (! $this->running) {
                break;
            }
            if ($sleep > 0) {
                sleep($sleep);
            }
        }

        $this->info('Campaign loop exited.');
        return Command::SUCCESS;
    }

    /**
     * Fetch & dispatch campaigns whose scheduled_at <= now (local).
     */
    protected function processDueCampaigns(int $batchLimit, int $subChunk): void
    {
        $tz       = config('app.timezone');
        $nowLocal = Carbon::now($tz);

        // Debug log current local time each cycle
        Log::debug('Campaign loop tick', [
            'now_local' => $nowLocal->toDateTimeString(),
            'tz'        => $tz,
        ]);

        $dueCampaigns = DB::table('campaigns')
            ->where('has_run', false)
            ->where('is_active', true)
            ->where('scheduled_at', '<=', $nowLocal)   // compare using local time
            ->orderBy('scheduled_at', 'asc')
            ->limit($batchLimit)
            ->get();

        if ($dueCampaigns->isEmpty()) {
            $this->showNoDueInfo($nowLocal, $batchLimit);
            return;
        }

        $this->info('[' . $nowLocal->toDateTimeString() . " {$tz}] Found {$dueCampaigns->count()} due campaign(s). Dispatching...");

        foreach ($dueCampaigns as $campaignRow) {
            $this->dispatchSingleCampaign($campaignRow, $subChunk, $nowLocal);
        }
    }

    /**
     * Provide richer information when there are no due campaigns.
     */
    protected function showNoDueInfo(Carbon $nowLocal, int $batchLimit): void
    {
        $tz = $nowLocal->timezoneName ?? config('app.timezone');

        $nextCampaign = DB::table('campaigns')
            ->where('has_run', false)
            ->where('is_active', true)
            ->orderBy('scheduled_at', 'asc')
            ->first();

        $this->line('[' . $nowLocal->toDateTimeString() . " {$tz}] No campaigns due right now.");

        if ($nextCampaign) {
            $nextTime = Carbon::parse($nextCampaign->scheduled_at, $tz);
            $diffMin  = $nowLocal->diffInMinutes($nextTime, false);
            $diffStr  = $diffMin >= 0
                ? "{$diffMin} minute(s) from now"
                : ($diffMin * -1) . " minute(s) overdue";

            $this->line("  → Next scheduled campaign: #{$nextCampaign->id} \"{$nextCampaign->title}\" at "
                . $nextTime->toDateTimeString() . " ({$diffStr}).");

            // Also list a few more upcoming for visibility
            $upcoming = DB::table('campaigns')
                ->where('has_run', false)
                ->where('is_active', true)
                ->where('id', '<>', $nextCampaign->id)
                ->orderBy('scheduled_at', 'asc')
                ->limit($batchLimit)
                ->get();

            foreach ($upcoming as $u) {
                $t  = Carbon::parse($u->scheduled_at, $tz);
                $d  = $nowLocal->diffInMinutes($t, false);
                $ds = $d >= 0 ? "+{$d}m" : "{$d}m";
                $this->line("     - Campaign #{$u->id} at " . $t->toDateTimeString() . " ({$ds})");
            }
        } else {
            $this->line('  → There are no future scheduled campaigns.');
        }
    }

    /**
     * Dispatch all subscribers for one campaign & mark it has_run.
     */
    protected function dispatchSingleCampaign($campaignRow, int $subChunk, Carbon $nowLocal): void
    {
        $tz = config('app.timezone');

        // Join template to include body_html & default subject
        $campaign = DB::table('campaigns')
            ->join('templates', 'campaigns.template_id', '=', 'templates.id')
            ->select(
                'campaigns.*',
                'templates.subject as template_subject',
                'templates.body_html'
            )
            ->where('campaigns.id', $campaignRow->id)
            ->first();

        if (! $campaign) {
            $this->warn(" - Campaign ID {$campaignRow->id} disappeared (skipped).");
            return;
        }

        $scheduledAtLocal = Carbon::parse($campaign->scheduled_at, $tz);

        $this->line(" - Dispatching Campaign #{$campaign->id} ({$campaign->title}) "
            . "[scheduled_at={$scheduledAtLocal->toDateTimeString()} now={$nowLocal->toDateTimeString()}]");

        // *** IMPORTANT: set status to 'running' before dispatching jobs ***
        if ($campaign->status !== 'running') {
            DB::table('campaigns')
                ->where('id', $campaign->id)
                ->update([
                    'status'     => 'running',
                    'updated_at' => now(),
                ]);
            $campaign->status = 'running';
        }

        $totalSubs = DB::table('list_users')
            ->where('list_id', $campaign->list_id)
            ->count();

        if ($totalSubs === 0) {
            $this->warn("   • No subscribers; marking as run & completed.");
            DB::table('campaigns')->where('id', $campaign->id)->update([
                'has_run'    => true,
                'status'     => 'completed',
                'updated_at' => now(),
            ]);
            return;
        }

        $this->line("   • Subscribers: {$totalSubs}");

        // Chunk through subscribers and queue jobs
        DB::table('list_users')
            ->where('list_id', $campaign->list_id)
            ->orderBy('id')
            ->chunk($subChunk, function ($chunk) use ($campaign) {
                foreach ($chunk as $sub) {
                    ProcessCampaignDelivery::dispatch($campaign, $sub, true);
                }
            });

        // Mark campaign as has_run after queueing jobs
        DB::table('campaigns')
            ->where('id', $campaign->id)
            ->update([
                'has_run'    => true,
                'updated_at' => now(),
            ]);

        Log::info('Loop dispatched campaign', [
            'campaign_id'   => $campaign->id,
            'campaign_uuid' => $campaign->campaign_uuid,
            'subscribers'   => $totalSubs,
            'scheduled_at'  => $scheduledAtLocal->toDateTimeString(),
            'dispatch_time' => $nowLocal->toDateTimeString(),
            'tz'            => $tz,
        ]);

        $this->line("   • Dispatched & marked has_run=1 (status running).");
    }
}
