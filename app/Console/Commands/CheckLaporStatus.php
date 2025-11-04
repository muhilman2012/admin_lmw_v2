<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LaporanForwarding;
use App\Models\DisposisiLapor;
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
        $CHECK_FREQUENCY_HOURS = 3;
        $RETRY_FREQUENCY_MINUTES = 60;

        // 1. Ambil laporan untuk di proses
        $forwardings = LaporanForwarding::query()
            ->where('status', 'terkirim') 
            ->whereNotNull('complaint_id')
            ->where(function ($query) {
                $query->where('next_check_at', '<=', Carbon::now())
                      ->orWhereNull('next_check_at');
            })
            ->orderBy('next_check_at', 'asc')
            ->limit(100)
            ->get();

        $count = $forwardings->count();
        $this->info("Found {$count} reports to check in this batch.");

        foreach ($forwardings as $forwarding) {
            $result = $service->getLaporDetail($forwarding->complaint_id);

            if ($result['success']) {
                $results = $result['results'];
                $data = $results['data'] ?? [];
                $dataLogs = $results['data_logs'] ?? []; // Ini adalah log yang berisi Disposisi dan Content
                
                // 1. Ambil Status Code, Status Name, dan Disposition Name dari ROOT DATA
                $laporStatusCode = $data['status_code'] ?? $forwarding->lapor_status_code;
                $laporStatusName = $data['status_name'] ?? $forwarding->lapor_status_name;
                $dispositionName = $data['disposition_name'] ?? 'Belum Terdisposisi';
                
                // 2. Ambil CONTENT log terakhir
                // Gunakan log yang memiliki content di dalamnya (biasanya di data_logs array, diurutkan terbaru)
                // Karena data_logs Anda tampaknya terurut dari yang tertua/tidak jelas, kita ambil yang paling akhir (index tertinggi).
                $latestLog = end($dataLogs); 
                $content = null;

                if ($latestLog) {
                    $content = $service->renderLogContent($latestLog, 'content'); 
                }


                // 3. Update laporan forwarding (Status dan Content)
                $forwarding->update([
                    'lapor_status_code' => $laporStatusCode,
                    'lapor_status_name' => $laporStatusName,
                    'content' => $content, // <<< SIMPAN CONTENT TERAKHIR
                    'next_check_at' => Carbon::now()->addHours($CHECK_FREQUENCY_HOURS), 
                ]);

                // 4. Simpan/Update disposisi ke tabel disposisi_lapor
                
                // Cek apakah ada log disposisi yang valid (misalnya, log ke-2/3)
                $disposisiLogData = $dataLogs[1] ?? null; 
                $institutionTo = $disposisiLogData['institution_to'] ?? null;
                
                // Fallback: Jika log tidak ada, gunakan disposisi name dari root data (meski kurang akurat)
                if (!$institutionTo && $dispositionName !== 'Belum Terdisposisi') {
                    // Coba ambil ID dari data utama (disposition_id) jika log tidak ada
                    $institutionTo = ['id' => $data['disposition_id'], 'name' => $dispositionName];
                }


                if ($institutionTo && ($institutionTo['id'] ?? null) && ($institutionTo['name'] ?? null)) {
                    DisposisiLapor::firstOrCreate(
                        ['laporan_forwarding_id' => $forwarding->id],
                        [
                            'institution_id' => $institutionTo['id'],
                            'institution_name' => $institutionTo['name'],
                        ]
                    );
                    $this->line("Disposisi saved for Complaint ID: {$forwarding->complaint_id} to {$institutionTo['name']}");
                }

                $this->line("Updated status and content for Complaint ID: {$forwarding->complaint_id} to {$laporStatusName}");
            } else {
                // Jika gagal (error API/koneksi), jadwalkan cek ulang lebih cepat
                Log::warning("Failed to check status for Complaint ID: {$forwarding->complaint_id}. Error: {$result['error']}");
                $forwarding->update(['next_check_at' => Carbon::now()->addMinutes($RETRY_FREQUENCY_MINUTES)]);
            }
        }

        $this->info('Status check finished for this batch.');
    }
}
