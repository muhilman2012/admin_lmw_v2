<?php

namespace App\Imports;

use App\Models\Report;
use App\Models\Reporter;
use App\Models\Category;
use App\Models\Document;
use App\Models\Assignment;
use App\Models\User; 
use App\Models\Deputy; 
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\WithProperties;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class ReportsImport implements ToModel, WithHeadingRow, WithChunkReading, ShouldQueue, WithProperties
{
    private $userId;
    // Cache data relasi di sini
    private $categoryMap = []; 
    private $deputyMap = []; 
    private $userMap = []; 

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        // Cache data yang sering dicari untuk efisiensi
        $this->categoryMap = Category::pluck('id', 'name')->toArray();
        $this->deputyMap = Deputy::pluck('id', 'name')->toArray(); 
        $this->userMap = User::pluck('id', 'name')->toArray(); 
    }

    public function model(array $row)
    {
        // PENTING: Validasi data wajib ada (kolom di Excel harus berhuruf kecil/snake_case)
        if (empty($row['nik']) || empty($row['nomor_tiket']) || empty($row['nama_lengkap'])) {
            return null; 
        }

        // Cek jika laporan sudah ada (PK unik)
        if (Report::where('ticket_number', $row['nomor_tiket'])->exists()) {
            return null; 
        }

        // --- 1. MIGRASI REPORTER ---
        // Menciptakan entri baru Reporter berdasarkan NIK
        $reporter = Reporter::updateOrCreate(
            ['nik' => (string)$row['nik']],
            [
                'name' => $row['nama_lengkap'], 
                'phone_number' => $row['nomor_pengadu'] ?? null,
                'email' => $row['email'] ?? null,
                'address' => $row['alamat_lengkap'] ?? 'Import Lama',
                'gender' => $row['jenis_kelamin'] ?? 'L', // Asumsi kolom ini ada di Excel
            ]
        );

        // --- 2. LOGIKA FK (Foreign Key Lookup) ---
        $categoryName = $row['kategori'] ?? 'Lainnya'; 
        $categoryId = $this->categoryMap[$categoryName] ?? 
                      Category::firstOrCreate(['name' => $categoryName])->id;
        
        $deputyId = $this->deputyMap[$row['deputi_tujuan'] ?? ''] ?? null;
        
        // --- 3. MIGRASI REPORT (Laporan Utama) ---
        try {
            $report = Report::create([
                'ticket_number' => (string)$row['nomor_tiket'], 
                'uuid' => (string) Str::uuid(),
                'reporter_id' => $reporter->id,
                'subject' => $row['judul'],
                'details' => $row['detail'],
                'location' => $row['lokasi'] ?? null, // Asumsi ada kolom 'lokasi'
                'status' => $row['status'],
                'response' => $row['tanggapan_lama'] ?? null, // <- KOLOM TANGGAPAN BARU
                'classification' => null, // KLASIFIKASI DIBIARKAN NULL (belum ada di DB lama)
                'source' => $row['sumber_pengaduan'] ?? 'Import Lama',
                'category_id' => $categoryId,
                'deputy_id' => $deputyId, 
                // Pastikan format date/datetime dikonversi dengan benar
                'event_date' => $row['tanggal_kejadian'] ? Carbon::parse($row['tanggal_kejadian'])->toDateString() : null,
                'created_at' => Carbon::parse($row['waktu_dibuat']), 
            ]);

            // --- 4. MIGRASI DOKUMEN (Multi-Path dan KTP) ---
            $this->migrateDocuments($report, $row);

            // --- 5. MIGRASI ASSIGNMENTS ---
            if (!empty($row['analis_username_lama']) && !empty($row['lembar_kerja_analis'])) { 
                $analisUserId = $this->userMap[$row['analis_username_lama']] ?? null;

                if ($analisUserId) {
                    Assignment::create([
                        'report_id' => $report->id,
                        'assigned_to_id' => $analisUserId,
                        'assigned_by_id' => $this->userId,
                        'analyst_worksheet' => $row['lembar_kerja_analis'],
                        'notes' => $row['catatan_analisis'] ?? null,
                        'status' => $row['status_analisis'] ?? 'Selesai Migrasi', 
                    ]);
                }
            }
            
            return $report; 
            
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error("Gagal mengimpor tiket {$row['nomor_tiket']}: " . $e->getMessage(), ['row_data' => $row]);
            return null;
        }
    }

    public function properties(): array
    {
        // Asumsi file migrasi Anda adalah XLSX.
        return [
            'readDataOnly' => true, // Baca hanya data (lebih cepat)
            'spreadsheet' => [
                'read' => [
                    // Definisikan format ReaderType secara eksplisit
                    'readerType' => \PhpOffice\PhpSpreadsheet\Reader\Xlsx::class, 
                ],
            ],
        ];
    }

    /**
     * Helper untuk memigrasikan dokumen dari storage lama ke Minio (Multi-Path).
     */
    protected function migrateDocuments(Report $report, array $row): void
    {
        // 1. Kumpulkan semua path
        $paths = [];

        // Dokumen KTP (Kolom Tunggal)
        if (!empty($row['path_dokumen_ktp_lama'])) {
            $paths['KTP'][] = $row['path_dokumen_ktp_lama'];
        }

        // Dokumen Pendukung (Multi-path dipisahkan |)
        if (!empty($row['path_dokumen_pendukung_lama'])) {
            $rawPaths = explode('|', $row['path_dokumen_pendukung_lama']);
            foreach ($rawPaths as $path) {
                if (!empty(trim($path))) {
                    $paths['Pendukung'][] = trim($path);
                }
            }
        }
        
        // 2. Proses Migrasi per File
        foreach ($paths as $description => $pathArray) {
            foreach ($pathArray as $oldPath) {
                try {
                    // PENTING: Gunakan disk 'old_local_disk' untuk membaca file lama
                    if (Storage::disk('old_local_disk')->exists($oldPath)) {
                        $fileContents = Storage::disk('old_local_disk')->get($oldPath);
                        $newPath = 'documents/' . $report->ticket_number . '/' . basename($oldPath);
                        
                        // Simpan ke Minio (disk 'complaints')
                        Storage::disk('complaints')->put($newPath, $fileContents); 
                        
                        $document = Document::firstOrCreate([
                            'report_id' => $report->id,
                            'file_path' => $newPath,
                            'description' => ($description === 'KTP' ? 'Dokumen KTP' : 'Dokumen Pendukung'),
                        ]);
                        
                        // Khusus KTP, update FK di model Reporter
                        if ($description === 'KTP') {
                             $report->reporter->update(['ktp_document_id' => $document->id]);
                        }
                    }
                } catch (Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Gagal migrasi file {$oldPath} untuk tiket {$report->ticket_number}: " . $e->getMessage());
                }
            }
        }
    }
    
    public function chunkSize(): int
    {
        return 500;
    }
    
    public function headingRow(): int
    {
        return 1;
    }
}
