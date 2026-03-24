<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->job(new \App\Jobs\RefreshTokenJob)->dailyAt('02:00')->timezone('UTC');

        // Dispatch due scheduled posts every minute
        $schedule->call(function () {
            app(\App\Modules\Publisher\SchedulerService::class)->dispatchDuePosts();
        })->everyMinute()->name('publisher:dispatch-due-posts')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
