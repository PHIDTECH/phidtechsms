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
        // Process scheduled campaigns every minute
        $schedule->command('campaigns:process-scheduled')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground();
                 
        // Synchronize Beem SMS balance every hour
        $schedule->command('beem:sync-balance')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Check SMS delivery reports every 5 minutes
        $schedule->command('sms:check-delivery-reports')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->runInBackground();
                 
        // Clean up old logs and temporary files daily
        $schedule->command('queue:prune-batches --hours=48')
                 ->daily();
                 
        // Generate daily reports at midnight
        $schedule->call(function () {
            // Add daily report generation logic here if needed
        })->dailyAt('00:00');
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
