<?php

namespace App\Jobs;

use App\Mail\CampaignMail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Swift_TransportException;

class ProcessCampaignDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $campaign;
    public $subscriber;
    public $sendImmediately;

    /**
     * If set externally, used for finalizeIfLast target count.
     */
    protected ?int $listSize = null;

    /**
     * Seconds to sleep between deliveries.
     */
    private int $perDeliverySleep = 10;

    public function __construct($campaign, $subscriber, bool $sendImmediately)
    {
        $this->campaign        = $campaign;
        $this->subscriber      = $subscriber;
        $this->sendImmediately = $sendImmediately;
        
        // Set queue based on user ID (vendor)
        $userId = $campaign->created_by ?? 1; // Default to 1 if not set
        $this->queue = 'campaigns-user-' . $userId;
    }

    public function handle(): void
    {
        $uuid            = $this->campaign->campaign_uuid;
        $subId           = $this->subscriber->id;
        $status          = null;
        $rawError        = null;
        $friendlyMessage = null;

        // Re-load campaign to get fresh status & flags
        $liveCampaign = DB::table('campaigns')
            ->where('id', $this->campaign->id)
            ->select('id','status','is_active','has_run','list_id','scheduled_at','created_by')
            ->first();

        if (! $liveCampaign || ! $liveCampaign->is_active) {
            Log::warning('Job abort: campaign inactive or missing', [
                'campaign_id'   => $this->campaign->id,
                'subscriber_id' => $subId,
            ]);
            return;
        }

        if (! in_array($liveCampaign->status, ['running','completed'], true)) {
            Log::info('Job skip: campaign not in running/completed state', [
                'campaign_id'   => $this->campaign->id,
                'status'        => $liveCampaign->status,
                'subscriber_id' => $subId,
            ]);
            return;
        }

        //
        // Duplicate guard BY EMAIL:
        // If this email address has already been delivered for this campaign, skip.
        //
        $duplicate = DB::table('campaign_deliveries')
            ->join('list_users', 'campaign_deliveries.subscriber_id', '=', 'list_users.id')
            ->where('campaign_deliveries.campaign_uuid', $uuid)
            ->where('list_users.email', $this->subscriber->email)
            ->exists();

        if ($duplicate) {
            $status          = 'skipped';
            $friendlyMessage = 'Duplicate email; skipped.';
            DB::table('campaign_skips')->insert([
                'campaign_uuid'      => $uuid,
                'subscriber_id'      => $subId,
                'reason'             => 'duplicate_email',
                'error_user_message' => $friendlyMessage,
                'created_at'         => now(),
            ]);
        }
        // Inactive subscriber guard
        elseif (property_exists($this->subscriber, 'is_active') && ! $this->subscriber->is_active) {
            $status          = 'skipped';
            $friendlyMessage = 'Subscriber inactive; skipped.';
            DB::table('campaign_skips')->insert([
                'campaign_uuid'      => $uuid,
                'subscriber_id'      => $subId,
                'reason'             => 'inactive',
                'error_user_message' => $friendlyMessage,
                'created_at'         => now(),
            ]);
        }
        else {
            // Attempt to send
            try {
                $mailable = new CampaignMail($this->campaign, $this->subscriber);

                if ($this->sendImmediately) {
                    Mail::to($this->subscriber->email)->send($mailable);
                } else {
                    $delaySeconds = Carbon::parse($liveCampaign->scheduled_at)
                        ->diffInSeconds(now(), false);

                    if ($delaySeconds <= 0) {
                        Mail::to($this->subscriber->email)->later(abs($delaySeconds), $mailable);
                    } else {
                        Mail::to($this->subscriber->email)->send($mailable);
                    }
                }

                $status          = 'sent';
                $friendlyMessage = 'Sent successfully.';
            }
            catch (Swift_TransportException $e) {
                $rawError   = $e->getMessage();
                $bounceType = stripos($rawError, '550') !== false ? 'hard' : 'soft';
                $status     = $bounceType . '_bounce';
                $friendlyMessage = $bounceType === 'hard'
                    ? 'Permanent bounce.'
                    : 'Temporary bounce.';

                DB::table('campaign_bounces')->insert([
                    'campaign_uuid'      => $uuid,
                    'subscriber_id'      => $subId,
                    'bounce_type'        => $bounceType,
                    'reason'             => $rawError,
                    'error_user_message' => $friendlyMessage,
                    'created_at'         => now(),
                ]);
            }
            catch (\Exception $e) {
                $rawError        = $e->getMessage();
                $status          = 'failed';
                $friendlyMessage = 'Send failure.';

                DB::table('campaign_failures')->insert([
                    'campaign_uuid'      => $uuid,
                    'subscriber_id'      => $subId,
                    'reason'             => $rawError,
                    'error_user_message' => $friendlyMessage,
                    'created_at'         => now(),
                ]);
            }
        }

        // Record delivery (or skip) outcome
        DB::table('campaign_deliveries')->insert([
            'campaign_uuid'      => $uuid,
            'subscriber_id'      => $subId,
            'status'             => $status,
            'error_user_message' => $friendlyMessage,
            'error_message'      => $rawError,
            'created_at'         => now(),
        ]);

        // Increment campaign counters if columns exist
        if ($status && Schema::hasColumn('campaigns', "{$status}_count")) {
            DB::table('campaigns')
                ->where('id', $this->campaign->id)
                ->increment("{$status}_count");
        }

        // Check for last recipient and finalize if done
        $this->finalizeIfLast($uuid, $this->campaign->id, $liveCampaign->list_id, $liveCampaign->created_by);

        // Throttle before next job
        try {
            sleep($this->perDeliverySleep);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * If we've processed every distinct email in the list, mark campaign complete.
     */
    protected function finalizeIfLast(string $campaignUuid, int $campaignId, int $listId, int $userId): void
    {
        $row = DB::table('campaigns')
            ->where('id', $campaignId)
            ->select('status','has_run')
            ->first();

        if (! $row || $row->status !== 'running' || ! $row->has_run) {
            return;
        }

        $target = $this->listSize
            ?? DB::table('list_users')->where('list_id', $listId)->count();

        if ($target === 0) {
            $this->completeCampaign($campaignId, 'empty list', $userId);
            return;
        }

        $processedDistinct = DB::table('campaign_deliveries')
            ->where('campaign_uuid', $campaignUuid)
            ->distinct()
            ->count('subscriber_id');

        if ($processedDistinct >= $target) {
            $this->completeCampaign($campaignId, 'all subscribers processed', $userId, [
                'target'             => $target,
                'processed_distinct' => $processedDistinct,
            ]);
        } else {
            Log::debug('Finalize check: still pending', [
                'campaign_id'        => $campaignId,
                'user_id'            => $userId,
                'target'             => $target,
                'processed_distinct' => $processedDistinct,
                'remaining'          => $target - $processedDistinct,
            ]);
        }
    }

    /**
     * Mark a running campaign as completed and dispatch the next one if waiting.
     */
    protected function completeCampaign(int $campaignId, string $reason, int $userId, array $extra = []): void
    {
        $updated = DB::table('campaigns')
            ->where('id', $campaignId)
            ->where('status', 'running')
            ->update([
                'status'     => 'completed',
                'updated_at' => now(),
            ]);

        if ($updated) {
            Log::info('Campaign completed', array_merge([
                'campaign_id' => $campaignId,
                'user_id'     => $userId,
                'reason'      => $reason,
            ], $extra));

            $this->promoteNextWaiting($userId);
        }
    }

    /**
     * Find the next waiting campaign for the same user, mark it running, and dispatch its deliveries.
     */
    protected function promoteNextWaiting(int $userId): void
    {
        $next = DB::table('campaigns')
            ->where('status', 'waiting')
            ->where('is_active', true)
            ->where('created_by', $userId) // Only campaigns from the same user
            ->orderBy('scheduled_at', 'asc')
            ->first();

        if (! $next) {
            Log::info('Promotion: no waiting campaigns for user', ['user_id' => $userId]);
            return;
        }

        $updated = DB::table('campaigns')
            ->where('id', $next->id)
            ->where('status', 'waiting')
            ->update([
                'status'     => 'running',
                'updated_at' => now(),
            ]);

        if (! $updated) {
            Log::info('Promotion race lost', [
                'campaign_id' => $next->id,
                'user_id'     => $userId,
            ]);
            return;
        }

        Log::info('Promotion: waiting → running', [
            'campaign_id' => $next->id,
            'user_id'     => $userId,
        ]);

        $full = DB::table('campaigns')
            ->join('templates', 'campaigns.template_id', '=', 'templates.id')
            ->select(
                'campaigns.*',
                'templates.subject as template_subject',
                'templates.body_html'
            )
            ->where('campaigns.id', $next->id)
            ->first();

        if (! $full) {
            Log::warning('Promotion fetch failed (vanished)', [
                'campaign_id' => $next->id,
                'user_id'     => $userId,
            ]);
            return;
        }

        // Dispatch to user-specific queue
        DB::table('list_users')
            ->where('list_id', $full->list_id)
            ->orderBy('id')
            ->chunk(500, function ($chunk) use ($full) {
                foreach ($chunk as $sub) {
                    ProcessCampaignDelivery::dispatch($full, $sub, true)
                        ->onQueue('campaigns-user-' . $full->created_by);
                }
            });

        DB::table('campaigns')
            ->where('id', $full->id)
            ->update([
                'has_run'    => true,
                'updated_at' => now(),
            ]);

        Log::info('Promotion dispatch started', [
            'campaign_id' => $full->id,
            'user_id'     => $full->created_by,
            'queue'       => 'campaigns-user-' . $full->created_by,
        ]);
    }
}