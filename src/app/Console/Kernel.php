<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\SyncInstitutions;
use App\Console\Commands\CheckLaporStatus

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Daftar command yang sudah ada (Opsional, karena sudah dimuat via $this->load)
        SyncInstitutions::class, 
        CheckLaporStatus::class,
    ];
    
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(SyncInstitutions::class)
                 ->daily()
                 ->runInBackground();
                 
        $schedule->command(CheckLaporStatus::class)
                 ->everyHour()
                 ->runInBackground();
                 ->withoutOverlapping()

        $schedule->command('lapor:retry-forwarding')
                 ->everyFiveMinutes()
                 ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        // Baris ini secara otomatis memuat semua Command di folder 'Commands'
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}