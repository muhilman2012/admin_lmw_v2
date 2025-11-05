<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LaporanForwarding;
use App\Services\LaporForwardingService;
use Illuminate\Support\Facades\Log;

class LaporRetryForwarding extends Command
{
    protected $signature = 'lapor:retry-forwarding'; 

    protected $description = 'Mencoba mengirimkan ulang laporan yang gagal diteruskan ke LAPOR!';

    public function handle()
    {
        $this->info('Memulai proses pengiriman ulang laporan yang gagal...');

        // 1. Ambil semua laporan yang gagal dan sudah melewati waktu scheduled_at
        $failedForwards = LaporanForwarding::where('status', 'gagal_forward')
            ->where('scheduled_at', '<=', now())
            ->get();

        $count = $failedForwards->count();
        $this->info("Ditemukan {$count} laporan yang akan dicoba ulang.");

        $service = new LaporForwardingService();
        $successfulRetries = 0;

        foreach ($failedForwards as $forward) {
            try {
                // 2. Coba kirimkan kembali 'Reject Request' (Langkah 3 di controller)
                $apiResponse = $service->sendRejectRequest(
                    $forward->complaint_id, 
                    $forward->institution_id, 
                    $forward->reason
                );

                if ($apiResponse['success']) {
                    // SUKSES: Update status menjadi 'terkirim'
                    $forward->update([
                        'status' => 'terkirim',
                        'error_message' => null,
                        'sent_at' => now(),
                        'scheduled_at' => null,
                    ]);
                    $this->line("-> Berhasil mengirim ulang Complaint ID: {$forward->complaint_id}");
                    $successfulRetries++;
                } else {
                    // GAGAL KEMBALI: Reschedule (coba 1 jam lagi) dan update error
                    $forward->update([
                        'error_message' => $apiResponse['error'],
                        'scheduled_at' => now()->addHour(),
                    ]);
                    $this->error("-> Gagal mengirim ulang Complaint ID {$forward->complaint_id}: " . $apiResponse['error']);
                }

            } catch (\Exception $e) {
                // EXCEPTION: Log dan reschedule untuk coba lagi
                Log::error('Retry Forwarding Exception: ' . $e->getMessage(), ['id' => $forward->id]);
                $forward->update(['scheduled_at' => now()->addHour()]); // Reschedule
                $this->error("-> Kesalahan sistem saat mencoba Complaint ID {$forward->complaint_id}");
            }
        }

        $this->info("Proses pengiriman ulang selesai. {$successfulRetries} laporan berhasil dikirim.");
        return Command::SUCCESS;
    }
}
