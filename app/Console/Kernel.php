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
        // 1. Sinkronisasi Institusi LAPOR! (Rutin)
        // Disarankan berjalan setiap hari untuk mengambil data K/L/D baru.
        $schedule->command(SyncInstitutions::class)
                 ->daily()
                 ->runInBackground(); // Jalankan di background
                 
        // 2. Cek Status Laporan yang Diteruskan (Polling Cerdas)
        // Berjalan setiap jam untuk memproses batch laporan yang jadwal ceknya sudah tiba.
        $schedule->command(CheckLaporStatus::class)
                 ->everyHour()
                 ->runInBackground();
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