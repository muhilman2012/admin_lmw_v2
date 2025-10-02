<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LaporanForwarding;
use App\Services\LaporForwardingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckLaporStatus extends Command
{
    protected $signature = 'lapor:check-status';
    protected $description = 'Checks and updates status for forwarded reports from LAPOR!';

    public function handle(LaporForwardingService $service)
    {
        $this->info('Starting LAPOR! status check...');
        
        // Konstanta untuk jadwal pengecekan ulang
        $CHECK_FREQUENCY_HOURS = 6; // Cek ulang laporan sukses setiap 6 jam
        $RETRY_FREQUENCY_MINUTES = 60; // Cek ulang laporan yang gagal koneksi setiap 60 menit

        // 1. Ambil laporan untuk di proses
        $forwardings = LaporanForwarding::query()
            // Hanya ambil laporan yang sudah terkirim ke LAPOR!
            ->where('status', 'terkirim') 
            // Pastikan memiliki complaint_id
            ->whereNotNull('complaint_id')
            // Ambil yang jadwal pengecekan ulang (next_check_at) sudah lewat
            ->where(function ($query) {
                $query->where('next_check_at', '<=', Carbon::now())
                      ->orWhereNull('next_check_at');
            })
            ->orderBy('next_check_at', 'asc') // Proses yang paling lama belum dicek dulu
            ->limit(100) // Batasi batch processing per sekali jalankan (Ganti sesuai kebutuhan)
            ->get();

        $count = $forwardings->count();
        $this->info("Found {$count} reports to check in this batch.");

        foreach ($forwardings as $forwarding) {
            $result = $service->getLaporStatus($forwarding->complaint_id);

            if ($result['success']) {
                $data = $result['data'];
                
                // 2. Update status & jadwal cek ulang
                $forwarding->update([
                    'lapor_status_code' => $data['status_code'],
                    'lapor_status_name' => $data['status_name'],
                    // Jika status sudah Selesai/Ditolak, mungkin tidak perlu dicek lagi.
                    // Jika masih proses, jadwalkan cek 6 jam lagi.
                    'next_check_at' => Carbon::now()->addHours($CHECK_FREQUENCY_HOURS), 
                ]);

                $this->line("Updated status for Complaint ID: {$forwarding->complaint_id} to {$data['status_name']}");
            } else {
                // Jika gagal (error API/koneksi), jadwalkan cek ulang lebih cepat
                Log::warning("Failed to check status for Complaint ID: {$forwarding->complaint_id}. Error: {$result['error']}");
                $forwarding->update(['next_check_at' => Carbon::now()->addMinutes($RETRY_FREQUENCY_MINUTES)]);
            }
        }

        $this->info('Status check finished for this batch.');
    }
}
