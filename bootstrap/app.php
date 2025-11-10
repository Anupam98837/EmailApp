<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use App\Console\Commands\CampaignLoop;
use App\Http\Middleware\CheckRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'checkRole' => CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // exceptions...
    })
    // 1) Register your custom console command
    ->withCommands([
        CampaignLoop::class,
    ])
    // 2) Define your scheduled tasks
    ->withSchedule(function (Schedule $schedule): void {

        // Helper to log success / failure for each task (safe for container calls)
        $logResult = function (string $name) {
            return function ($event = null) use ($name) {
                $code = is_object($event) && property_exists($event, 'exitCode')
                    ? $event->exitCode
                    : null;

                if ($code === 0) {
                    Log::info("[$name] SUCCESS");
                } elseif ($code !== null) {
                    Log::error("[$name] ERROR (code {$code})");
                } else {
                    // Fallback when the scheduler/container didn't pass an event instance
                    Log::info("[$name] FINISHED");
                }
            };
        };

        /*
        |--------------------------------------------------------------------------
        | 1) Retry any failed jobs every 5 minutes
        |--------------------------------------------------------------------------
        */
        $schedule->command('queue:retry all')
            ->everyFiveMinutes()
            ->runInBackground()
            ->withoutOverlapping(4)
            ->before(fn () => Log::info('[queue:retry] START'))
            ->onSuccess($logResult('queue:retry'))
            ->onFailure($logResult('queue:retry'))
            ->appendOutputTo(storage_path('logs/queue_retry.log'));

        /*
        |--------------------------------------------------------------------------
        | 2) Dispatch due campaigns (single pass each minute)
        |--------------------------------------------------------------------------
        */
        $schedule->command('campaigns:loop --batch=50 --sub-chunk=500')
            ->everyMinute()
            ->runInBackground()
            ->withoutOverlapping()
            ->before(fn () => Log::info('[campaigns:loop] START'))
            ->onSuccess($logResult('campaigns:loop'))
            ->onFailure($logResult('campaigns:loop'))
            ->appendOutputTo(storage_path('logs/campaign_loop.log'));

        /*
        |--------------------------------------------------------------------------
        | 3) Short-lived queue worker (processes up to max-time then exits)
        |--------------------------------------------------------------------------
        */
        $schedule->command(
                'queue:work database --queue=default,mail --sleep=1 --tries=3 --stop-when-empty --max-time=55'
            )
            ->everyMinute()
            ->runInBackground()
            ->withoutOverlapping()
            ->before(fn () => Log::info('[queue:work] START'))
            ->onSuccess($logResult('queue:work'))
            ->onFailure($logResult('queue:work'))
            ->appendOutputTo(storage_path('logs/queue_worker.log'));

        /*
        |--------------------------------------------------------------------------
        | 4) Restart any long-lived workers hourly
        |--------------------------------------------------------------------------
        */
        $schedule->command('queue:restart')
            ->hourly()
            ->before(fn () => Log::info('[queue:restart] START'))
            ->onSuccess($logResult('queue:restart'))
            ->onFailure($logResult('queue:restart'))
            ->appendOutputTo(storage_path('logs/queue_restart.log'));

        /*
        |--------------------------------------------------------------------------
        | 5) Prune failed jobs older than 24h daily at 02:00
        |--------------------------------------------------------------------------
        */
        $schedule->command('queue:prune-failed --hours=24')
            ->dailyAt('02:00')
            ->runInBackground()
            ->before(fn () => Log::info('[queue:prune-failed] START'))
            ->onSuccess($logResult('queue:prune-failed'))
            ->onFailure($logResult('queue:prune-failed'))
            ->appendOutputTo(storage_path('logs/queue_prune.log'));
    })
    ->create();
