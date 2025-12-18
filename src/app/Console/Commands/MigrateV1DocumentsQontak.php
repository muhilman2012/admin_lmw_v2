<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Report;
use App\Models\Reporter;
use App\Models\Document;
use App\Models\ApiSetting;
use Exception;

class MigrateV1DocumentsQontak extends Command
{
    /**
     * Nama dan signature command.
     */
    protected $signature = 'migrate:v1-documents-qontak 
                            {--page=1 : Halaman awal API V1} 
                            {--limit=100 : Record per halaman}';

    protected $description = 'Download dokumen dari CDN Qontak V1, simpan ke MinIO, dan catat ke tabel documents V2 (Tanpa kolom file_name).';

    private $qontakPrefix = 'https://cdn.qontak.com/uploads';
    private $targetDisk = 'complaints'; 

    // Pemetaan kolom V1 ke judul deskripsi di V2
    private $docMapping = [
        'dokumen_ktp'       => 'Dokumen KTP',
        'dokumen_kk'        => 'Dokumen KK',
        'dokumen_skuasa'    => 'Dokumen Surat Kuasa',
        'dokumen_pendukung' => 'Dokumen Pendukung',
        'dokumen_tambahan'  => 'Dokumen Tambahan',
    ];

    public function handle()
    {
        $this->info("--- MEMULAI MIGRASI DOKUMEN QONTAK KE MINIO ---");

        $apiName = 'v1_migration_api';
        $settings = ApiSetting::where('name', $apiName)->pluck('value', 'key');
        
        $baseUrl = $settings->get('base_url');
        $auth = $settings->get('authorization');
        $page = (int) $this->option('page');
        $limit = (int) $this->option('limit');

        if (!$baseUrl || !$auth) {
            $this->error("Kredensial API '{$apiName}' tidak lengkap.");
            return 1;
        }

        while (true) {
            $this->line("\nFetching API V1 Halaman {$page}...");

            try {
                $response = Http::withHeaders(['Authorization' => $auth])
                    ->timeout(60)
                    ->get("{$baseUrl}/migration/reports", ['page' => $page, 'limit' => $limit]);

                if ($response->failed()) {
                    $this->error("Gagal ambil data API hlm {$page}.");
                    break;
                }

                $json = $response->json();
                $reportsV1 = $json['data'] ?? [];
                $lastPage = $json['last_page'] ?? $page;

                if (empty($reportsV1)) break;

                foreach ($reportsV1 as $dataV1) {
                    $this->processReport($dataV1);
                }

                if ($page >= $lastPage) break;
                $page++;

            } catch (Exception $e) {
                $this->error("System Error: " . $e->getMessage());
                break;
            }
        }

        $this->info("\n--- MIGRASI DOKUMEN SELESAI ---");
        return 0;
    }

    /**
     * Memproses setiap laporan dari V1 untuk mencari dokumen.
     */
    private function processReport($dataV1)
    {
        $reportV2 = Report::where('ticket_number', $dataV1['nomor_tiket'])->first();

        if (!$reportV2) {
            $this->warn("   ! Ticket {$dataV1['nomor_tiket']} tidak ditemukan di V2. Skip.");
            return;
        }

        foreach ($this->docMapping as $v1Col => $descTitle) {
            $url = $dataV1[$v1Col] ?? null;

            if ($url && is_string($url) && str_starts_with($url, $this->qontakPrefix)) {
                $this->downloadAndSave($url, $reportV2, $descTitle, $v1Col);
            }
        }
    }

    /**
     * Mengunduh file dan menyimpannya ke MinIO serta database.
     */
    private function downloadAndSave($url, $report, $descTitle, $v1Col)
    {
        try {
            $originalFileName = basename(parse_url($url, PHP_URL_PATH));
            
            // Menggabungkan judul deskripsi dan nama file asli untuk identifikasi unik
            $fullDescription = "{$descTitle} - {$originalFileName}";
            
            // Cek apakah dokumen ini sudah pernah diproses
            $exists = Document::where('report_id', $report->id)
                              ->where('description', $fullDescription)
                              ->exists();

            if ($exists) {
                $this->line("     > {$descTitle} sudah ada. Skip.");
                return;
            }

            // Eksekusi pengunduhan file
            $response = Http::timeout(30)->get($url);
            if ($response->failed()) {
                $this->error("     ! Gagal download: {$url}");
                return;
            }

            $content = $response->body();
            $extension = pathinfo($originalFileName, PATHINFO_EXTENSION) ?: 'pdf';
            
            // Gunakan UUID untuk nama file fisik di MinIO agar unik
            $hashedName = Str::uuid() . '.' . $extension;
            $targetPath = "documents/{$hashedName}";

            // Simpan ke MinIO
            Storage::disk($this->targetDisk)->put($targetPath, $content);

            DB::beginTransaction();
            
            // Catat data dokumen ke database V2
            $document = Document::create([
                'report_id'   => $report->id,
                'file_path'   => $targetPath,
                'description' => $fullDescription,
            ]);

            // Jika dokumen adalah KTP, hubungkan ke tabel Reporters
            if ($v1Col === 'dokumen_ktp') {
                $reporter = Reporter::find($report->reporter_id);
                if ($reporter && !$reporter->ktp_document_id) {
                    $reporter->update(['ktp_document_id' => $document->id]);
                    $this->info("     * KTP linked to Reporter ID: {$reporter->id}");
                }
            }

            DB::commit();
            $this->info("     + Berhasil: {$descTitle} ({$originalFileName})");

        } catch (Exception $e) {
            DB::rollBack();
            $this->error("     ! Error: " . $e->getMessage());
            Log::error("Doc Migration Fail: " . $url . " | " . $e->getMessage());
        }
    }
}