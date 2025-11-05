<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Report;
use App\Models\Reporter;
use App\Models\Document;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MigrateReportDocuments extends Command
{
    /**
     * Nama dan signature command.
     */
    protected $signature = 'migrate:documents 
                            {--source-disk=v1_local : Nama disk storage tempat dokumen V1 disimpan}';

    protected $description = 'Memindai dokumen laporan V1, memindahkannya ke MinIO/S3 (complaints), dan memigrasinya ke tabel documents V2.';

    public function handle()
    {
        $startTime = microtime(true);

        set_time_limit(0); // Hapus batas waktu eksekusi CLI
        ini_set('memory_limit', '1G'); // Beri memori yang cukup
        
        $sourceDiskName = $this->option('source-disk');
        $targetDiskName = 'complaints'; // Disk MinIO/S3 Anda
        $baseMinioFolder = 'documents'; // Folder utama di MinIO/S3
        
        // Pola deteksi dokumen khusus (sekarang termasuk pola NIK)
        $specialDocumentPatterns = [
            'ktp' => '/_ktp\.(pdf|jpg|jpeg|png)$/i',
            'kk' => '/_kk\.(pdf|jpg|jpeg|png)$/i',
            'skuasa' => '/_skuasa\.(pdf|jpg|jpeg|png|doc|docx)$/i',
            'pengaduan' => '/_pengaduan\.(pdf|jpg|jpeg|png|doc|docx)$/i',
            'ktp_lama' => '/_ktp\.(pdf|jpg|jpeg|png)$/i',
            // PENTING: Pola NIK harus dideteksi secara terpisah
        ];
        
        $this->info("Memulai migrasi dokumen dari disk SOURCE: {$sourceDiskName} ke TARGET: {$targetDiskName}...");
        
        // 1. Muat data Laporan dan Reporter (Menggunakan cursor untuk efisiensi memori)
        $reports = Report::select('id', 'ticket_number', 'reporter_id')->cursor(); 
        $migratedCount = 0;
        $reportsBar = $this->output->createProgressBar(Report::count()); 
        $reportsBar->start();

        // 2. Preload Database V2 Document Paths
        $existingDocumentPaths = Document::pluck('file_path')->flip()->toArray();

        // 3. Ambil daftar file V1 (Bottleneck, tapi diperlukan jika V1 flat structure)
        try {
            $allFiles = Storage::disk($sourceDiskName)->files(); 
        } catch (\Exception $e) {
            $this->error("\nGagal mengakses disk '{$sourceDiskName}'.");
            Log::error("Storage access error: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        // --- LOGIKA UTAMA PER PROSES LAPORAN ---
        foreach ($reports as $report) {
            $ticket = $report->ticket_number;
            $reportReporterId = $report->reporter_id;
            
            if (!$ticket || !$reportReporterId) {
                $reportsBar->advance();
                continue; 
            }
            
            $filesFound = [];
            $reporter = Reporter::find($reportReporterId);
            $reporterNik = $reporter->nik ?? null;

            // Pola regex untuk deteksi file KTP berdasarkan NIK (ktp_[NIK].ext)
            $nikPattern = $reporterNik ? '/^ktp_' . preg_quote($reporterNik, '/') . '\.(pdf|jpg|jpeg|png)$/i' : null;
            
            // Pola Regex Fleksibel untuk Ticket Number (TICKET[_stuff].ext)
            // Ini menangkap TICKET.ext, TICKET_1.ext, TICKET_tambahan_timestamp.ext, TICKET_ktp.ext
            $generalTicketPattern = '/^' . preg_quote($ticket, '/') . '(_.*)?\.(pdf|jpg|jpeg|png|doc|docx|xlsx|csv)$/i';


            foreach ($allFiles as $filePath) {
                $fileName = basename($filePath);
                
                // Cek Pola KTP_NIK (Prioritas tertinggi dan paling spesifik)
                if ($nikPattern && preg_match($nikPattern, $fileName)) {
                    $filesFound[] = $filePath;
                    continue;
                }
                
                // Cek Pola Ticket Number (Termasuk yang ada tambahan apapun)
                if (preg_match($generalTicketPattern, $fileName)) {
                    $filesFound[] = $filePath;
                }
            }


            // 5. Memindahkan dan Mencatat Dokumen
            // Gunakan array unique untuk menghindari duplikasi path dalam filesFound
            foreach (array_unique($filesFound) as $sourcePath) { 
                $originalFileName = basename($sourcePath);
                $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);
                
                $hashedFileName = Str::uuid() . '.' . $extension;
                $targetPath = "{$baseMinioFolder}/{$hashedFileName}"; // Target folder root documents/

                if (isset($existingDocumentPaths[$targetPath])) { continue; }

                // --- Tentukan deskripsi dokumen dan cek apakah ini adalah dokumen KTP utama ---
                $description = 'Dokumen Pendukung';
                $isKtpDocument = false;

                // Cek Pola NIK/KTP
                if ($nikPattern && preg_match($nikPattern, $originalFileName)) {
                    $description = "Dokumen KTP";
                    $isKtpDocument = true;
                } else {
                    // Cek pola khusus lainnya (KK, Surat Kuasa, dll.)
                    foreach ($specialDocumentPatterns as $key => $pattern) {
                        if (preg_match($pattern, $originalFileName)) {
                            $description = "Dokumen " . ucwords(str_replace('_', ' ', $key));
                            if ($key === 'ktp_lama') $isKtpDocument = true;
                            break;
                        }
                    }
                }
                
                try {
                    $fileContent = Storage::disk($sourceDiskName)->get($sourcePath);
                    $mimeType = Storage::disk($sourceDiskName)->mimeType($sourcePath);

                    // Pindahkan/Simpan ke S3/MinIO
                    Storage::disk($targetDiskName)->put($targetPath, $fileContent, [
                        'mimetype' => $mimeType,
                        'ContentDisposition' => 'attachment; filename="' . $originalFileName . '"',
                    ]);

                    // CATAT ke database V2
                    $document = Document::create([
                        'report_id' => $report->id,
                        'file_path' => $targetPath, 
                        'file_name' => $originalFileName, 
                        'description' => $description,
                    ]);
                    
                    $existingDocumentPaths[$targetPath] = 1; 
                    $migratedCount++;
                    
                    // --- TAUTKAN KE TABEL REPORTERS (JIKA DOKUMEN ADALAH KTP) ---
                    if ($isKtpDocument && $reporter && !$reporter->ktp_document_id) {
                        $reporter->update(['ktp_document_id' => $document->id]);
                    }
                    // -----------------------------------------------------------

                } catch (\Exception $e) {
                    Log::warning("Gagal memproses file {$originalFileName} untuk TIKET {$ticket}: " . $e->getMessage());
                }
            }

            $reportsBar->advance();
        }

        $reportsBar->finish();
        $this->newLine();
        
        // --- HITUNG DAN TAMPILKAN WAKTU AKHIR ---
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $durationFormatted = gmdate("H:i:s", (int)$duration); 
        
        $this->info("Migrasi Selesai! Total dokumen yang berhasil dipindahkan dan dicatat: {$migratedCount}.");
        $this->comment("Waktu eksekusi total: {$durationFormatted} (sekitar " . round($duration, 2) . " detik).");
        
        return Command::SUCCESS;
    }
}
