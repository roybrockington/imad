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
        // Hourly
        $schedule->command('sync:brands')->hourly();
        $schedule->command('sync:categories')->hourly();
        $schedule->command('sync:countries')->hourly();
        $schedule->command('sync:addresses')->hourly();
        $schedule->command('sync:accounts -dc')->everyFiveMinutes();
        $schedule->command('sync:products')->hourly();
        $schedule->command('sync:descriptions')->hourly();
        $schedule->command('sync:xware')->hourly();
        $schedule->command('sync:orders')->hourly();
        $schedule->command('sync:stock')->hourly();

        // At 0 and 30 minutes past each hour
        $schedule->command('export:orders')->cron('0,30 * * * *');
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
