<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestPingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Number of attempts before failing permanently. */
    public int $tries = 3;

    /** Progressive backoff (seconds) between retries. */
    public array|int $backoff = [5, 15, 60];

    protected bool $forceFail;

    public function __construct(bool $forceFail = false)
    {
        $this->forceFail = $forceFail;
    }

    public function handle(): void
    {
        if ($this->forceFail) {
            throw new \RuntimeException('Intentional failure in TestPingJob.');
        }

        Log::info('TestPingJob handled', [
            'time'      => now()->toDateTimeString(),
            'attempt'   => $this->attempts(),
            'forceFail' => $this->forceFail,
        ]);
    }

    public function tags(): array
    {
        return ['test', 'ping'];
    }
}
