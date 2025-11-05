<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LaporanForwarding;
use App\Services\LaporForwardingService;
use Illuminate\Support\Facades\Log;

class CheckLaporFollowups extends Command
{
    protected $signature = 'lapor:check-followups';
    protected $description = 'Mengambil dan menyimpan log tindak lanjut (follow-ups) dari LAPOR! untuk halaman detail.';

    public function handle(LaporForwardingService $service)
    {
        $this->info('Starting LAPOR! follow-up logs check...');

        // Ambil SEMUA laporan terkirim (atau yang baru-baru ini di-update)
        $forwardings = LaporanForwarding::where('status', 'terkirim')
            ->whereNotNull('complaint_id')
            ->limit(100)
            ->get();
        
        $logCount = 0;

        foreach ($forwardings as $forwarding) {
            // Panggil endpoint getFollowUpLogs
            $result = $service->getFollowUpLogs($forwarding->complaint_id);

            if ($result['success']) {
                $logs = $result['logs'] ?? [];
                
                $forwarding->update([
                    'followup_logs_json' => json_encode($logs), 
                ]);
                
                $logCount += count($logs);
            } else {
                Log::warning("Failed to fetch follow-up logs for Complaint ID: {$forwarding->complaint_id}.");
            }
        }

        $this->info("Follow-up logs check finished. Total logs processed: {$logCount}");
        return Command::SUCCESS;
    }
}
