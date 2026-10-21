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
        // Sincronización automática periódica bidireccional con el VPS en nodos satélites locales
        if (config('services.cloud_sync.node_role', env('NODE_ROLE', 'local')) === 'local') {
            $schedule->command('cloud:sync --direction=both')
                ->everyTwoMinutes()
                ->withoutOverlapping()
                ->runInBackground();
        }
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
