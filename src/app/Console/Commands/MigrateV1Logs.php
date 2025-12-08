<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Report; 
use App\Models\User; 
use App\Models\ApiSetting;
use App\Models\ActivityLog; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class MigrateV1Logs extends Command
{
    protected $signature = 'migrate:logs {--start-page=1 : Page number to start migration from} {--limit=500 : Records per page}';
    protected $description = 'Migrate activity logs from V1 API to V2, using existing report and user mapping.';

    private $reportTicketCache = [];
    private $userEmailCache = []; 

    public function handle()
    {
        $this->info("--- MEMULAI MIGRASI LOG AKTIVITAS V1 KE V2 ---");
        $apiName = 'v1_migration_api';

        $settings = ApiSetting::where('name', $apiName)->pluck('value', 'key');
        
        $baseUrl = $settings->get('base_url');
        $Authorization = $settings->get('authorization'); 
        $limit = (int) $this->option('limit');
        $startPage = (int) $this->option('start-page'); // Ambil nilai startPage

        if (!$baseUrl || !$Authorization) {
            $this->error("Kredensial API V1 ('{$apiName}') tidak lengkap. Migrasi dibatalkan.");
            return 1;
        }

        $credentials = [
            'base_url' => $baseUrl,
            'authorization' => $Authorization,
            'limit' => $limit
        ];
        
        // Tahap 1: Persiapan Cache
        $this->loadReportTicketCache();
        $this->loadUserEmailCache();
        
        // Tahap 2: Eksekusi Migrasi (Kirim startPage)
        $this->migrateLogs($credentials, $startPage);
        
        $this->info("--- MIGRASI LOG AKTIVITAS SELESAI ---");
        return 0;
    }
    
    // =========================================================
    //                      HELPER FUNCTIONS
    // =========================================================
    
    private function loadReportTicketCache()
    {
        $this->info("Memuat cache ID Laporan V2 berdasarkan Nomor Tiket...");
        $this->reportTicketCache = Report::pluck('id', 'ticket_number')->toArray(); 
        $this->info("Cache Nomor Tiket selesai dimuat. Total: " . count($this->reportTicketCache));
    }
    
    private function loadUserEmailCache()
    {
        $this->info("Memuat cache ID User V2...");
        $this->userEmailCache = User::pluck('id', 'email')->toArray(); 
        $this->info("Cache ID User V2 selesai dimuat. Total: " . count($this->userEmailCache));
    }

    private function fetchApiData(string $endpoint, int $page, array $credentials): array
    {
        // ... (Implementasi fetchApiData dari Command Reports) ...
        $limit = $credentials['limit'];
        $url = $credentials['base_url'] . "/migration/{$endpoint}?page={$page}&limit={$limit}";

        $authHeader = $credentials['authorization'];
        $tokenValue = str_ireplace('Bearer ', '', $authHeader);

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => $authHeader,
        ];
        
        $response = Http::withHeaders($headers)
            ->timeout(120) 
            ->get($url);

        if ($response->failed()) {
            $status = $response->status();
            $this->error("Gagal ambil data V1 ({$endpoint} Halaman {$page}): HTTP {$status}");
            Log::error("Migration API Failed: {$endpoint} - Status: {$status} - Response: {$response->body()}");
            return ['data' => [], 'last_page' => 0];
        }

        return $response->json();
    }


    // =========================================================
    // TAHAP 3: MIGRASI LOGS (Activity Logs)
    // =========================================================
    private function migrateLogs(array $credentials, int $startPage = 1)
    {
        $this->info("Memulai Migrasi Log Aktivitas secara keseluruhan...");
        
        $page = $startPage;
        
        $totalMigrated = 0;

        $reportTicketCache = $this->reportTicketCache; 
        $userEmailCache = $this->userEmailCache;
        
        do {
            $data = $this->fetchApiData('logs', $page, $credentials);
            $logs_v1 = $data['data'] ?? [];
            $lastPage = $data['last_page'] ?? 0;

            if (empty($logs_v1)) break;

            foreach ($logs_v1 as $log_v1) {
                
                // 1. Lookup ID V2
                $report_ticket = $log_v1['nomor_tiket'] ?? null;
                
                $report_id_v2 = $reportTicketCache[$report_ticket] ?? null; 
                $user_id_v2 = $userEmailCache[$log_v1['user_email_v1']] ?? null;
                
                // Pastikan relasi utama tersedia
                if ($report_id_v2 && $user_id_v2) {
                    
                    // 2. Transformasi Aksi dan Waktu
                    $action = $this->deduceActionFromActivity($log_v1['action_description_v1']);
                    
                    try {
                        $createdAt = Carbon::createFromFormat('Y-m-d H:i:s', $log_v1['created_at']);
                    } catch (Exception $e) {
                        $logIdV1 = $log_v1['log_id_v1'] ?? 'N/A';
                        $this->warn("Log Time Parse Failed V1 ID {$logIdV1}. Dilewati.");
                        continue;
                    }
                    
                    // 3. KRITERIA UNIK (UNTUK UPDATEORCREATE)
                    $uniqueCriteria = [
                        'loggable_id' => $report_id_v2,
                        'loggable_type' => Report::class,
                        'action' => $action, 
                        'user_id' => $user_id_v2,
                        'created_at' => $createdAt, 
                    ];

                    // Data yang akan disisipkan/diperbarui
                    $updateData = [
                        'description' => $log_v1['action_description_v1'],
                        'updated_at' => $createdAt, 
                    ];
                    
                    // 4. Simpan Log Aktivitas V2
                    \App\Models\ActivityLog::withoutTimestamps(function () use ($uniqueCriteria, $updateData) {
                        \App\Models\ActivityLog::updateOrCreate($uniqueCriteria, $updateData);
                    });
                    
                    $totalMigrated++;
                } else {
                    $logIdV1 = $log_v1['log_id_v1'] ?? 'N/A';
                    $reportTicket = $log_v1['nomor_tiket'] ?? 'N/A';
                    $this->warn("Log V1 ID {$logIdV1} dilewati. Relasi Laporan/User gagal ditemukan. (Tiket: {$reportTicket})");
                }
            }

            $this->info("Log Halaman {$page}/{$lastPage} selesai. Total: {$totalMigrated}");
            $page++;
            usleep(700000);
            
        } while ($page <= $lastPage);
        
        $this->info("Migrasi Log Aktivitas selesai. Total: {$totalMigrated}");
    }


    // =========================================================
    // HELPER: LOGIC DEDUCTION
    // =========================================================

    private function deduceActionFromActivity(string $activity): string
    {
        $activity = strtolower($activity);
        
        if (Str::contains($activity, ['buat laporan', 'laporan baru'])) {
            return 'create_report';
        } elseif (Str::contains($activity, ['disposisi', 'menugaskan'])) {
            return 'assign_report';
        } elseif (Str::contains($activity, ['perubahan status', 'status diubah'])) {
            return 'update_status';
        } elseif (Str::contains($activity, ['mengubah', 'memperbarui'])) {
            return 'update_data';
        } elseif (Str::contains($activity, ['telah selesai', 'diselesaikan'])) {
            return 'complete_analysis';
        } elseif (Str::contains($activity, ['hapus', 'menghapus'])) {
            return 'delete_record';
        }
        
        return 'misc_activity'; // Default
    }
}