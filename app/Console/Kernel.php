<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Your custom Artisan commands.
     *
     * @var array<int,string>
     */
    protected $commands = [
        \App\Console\Commands\CampaignLoop::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // 1) Dispatch your custom loop every minute in the background
        $schedule
            ->command('campaigns:loop --interval=60 --batch=50 --sub-chunk=500')
            ->everyMinute()
            ->runInBackground()
            ->withoutOverlapping();

        // 2) Run one‑shot queue worker once a minute
        $schedule
            ->command('queue:work --once --sleep=3 --tries=3')
            ->everyMinute()
            ->runInBackground()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
