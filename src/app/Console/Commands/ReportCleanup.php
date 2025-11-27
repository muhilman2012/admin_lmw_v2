<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use App\Models\ActivityLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportCleanup extends Command
{
    protected $signature = 'report:cleanup';
    protected $description = 'Closes reports that have exceeded the 10 working day limit for document submission.';

    public function handle()
    {
        $this->info('Memulai pengecekan laporan yang menunggu kelengkapan data...');
        
        $targetStatus = 'Menunggu kelengkapan data dukung dari Pelapor';
        $limitWorkingDays = 10;
        $closureStatus = 'Penanganan Selesai';
        $closureResponse = 'Pengaduan diarsipkan karena pengadu tidak memberikan kelengkapan data dalam waktu 10 hari kerja.';

        // 1. Ambil semua laporan yang berstatus target
        $pendingReports = Report::where('status', $targetStatus)->get();
        $reportsClosedCount = 0;

        foreach ($pendingReports as $report) {
            
            // 2. Hitung Hari Kerja yang Berlalu
            $updatedAt = Carbon::parse($report->updated_at);
            $now = Carbon::now();
            
            // KRITIS: Hitung selisih hari kerja (tidak termasuk Sabtu & Minggu)
            $workingDaysPassed = 0;
            $currentDate = $updatedAt->copy();

            while ($currentDate->lessThanOrEqualTo($now)) {
                // Cek apakah hari tersebut bukan Sabtu (6) atau Minggu (0)
                if ($currentDate->dayOfWeek !== Carbon::SATURDAY && $currentDate->dayOfWeek !== Carbon::SUNDAY) {
                    $workingDaysPassed++;
                }
                $currentDate->addDay();
            }
            
            // Karena kita menghitung dari hari updated_at hingga hari ini (inklusif), 
            // kita harus mengurangi 1 atau menyesuaikan perhitungan Carbon Anda.
            // Untuk memastikan ini 10 HARI KERJA PENUH berlalu, kita cek jika >= 11 hari.
            
            if ($workingDaysPassed > $limitWorkingDays) {
                
                DB::beginTransaction();
                try {
                    // 3. Update Status Laporan
                    $report->status = $closureStatus;
                    $report->response = $closureResponse;
                    $report->save();
                    
                    // 4. Buat Log Aktivitas
                    ActivityLog::create([
                        'user_id' => 1,
                        'action' => 'auto_archive_report',
                        'description' => "Laporan diarsipkan otomatis karena pengadu melebihi batas waktu {$limitWorkingDays} hari kerja untuk kelengkapan data.",
                        'loggable_id' => $report->id,
                        'loggable_type' => Report::class,
                    ]);

                    DB::commit();
                    $reportsClosedCount++;

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Gagal mengarsipkan laporan #{$report->ticket_number} secara otomatis: " . $e->getMessage());
                }
            }
        }

        $this->info("Pengecekan selesai. Total laporan diarsipkan: {$reportsClosedCount}");
        return Command::SUCCESS;
    }
}
