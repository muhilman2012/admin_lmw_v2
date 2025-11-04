<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Report; 
use App\Models\Reporter; 
use App\Models\Assignment; 
use App\Models\User; 
use App\Models\Category;
use App\Models\UnitKerja;
use App\Models\Deputy;
use App\Models\ApiSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class MigrateV1Data extends Command
{
    protected $signature = 'migrate:v1 {--start-page=1 : Page number to start migration from} {--limit=500 : Records per page}';
    protected $description = 'Migrate data from V1 API (Single step with manual control).';

    private $categoryCache = [];
    private $unitKerjaCache = [];
    private $worksheetCache = []; 

    private $kategoriUnitMapping = [
        'Asisten Deputi Ekonomi, Keuangan, dan Transformasi Digital' => ['Ekonomi dan Keuangan', 'Pemulihan Ekonomi Nasional', 'Teknologi Informasi dan Komunikasi', 'Perpajakan'],
        'Asisten Deputi Industri, Perdagangan, Pariwisata, dan Ekonomi Kreatif' => ['Perlindungan Konsumen', 'Pariwisata dan Ekonomi Kreatif', 'Industri dan Perdagangan'],
        'Asisten Deputi Infrastruktur, Sumber Daya Alam, dan Pembangunan Kewilayahan' => ['Lingkungan Hidup dan Kehutanan', 'Pekerjaan Umum dan Penataan Ruang', 'Pertanian dan Peternakan', 'Energi dan Sumber Daya Alam', 'Mudik', 'Perairan', 'Perhubungan', 'Perumahan', 'Infrastruktur', 'Pembangunan Kewilayahan'],
        'Asisten Deputi Pengentasan Kemiskinan dan Pembangunan Desa' => ['Pembangunan Desa, Daerah Tertinggal, dan Transmigrasi', 'Sosial dan Kesejahteraan'],
        'Asisten Deputi Kesehatan, Gizi, dan Pembangunan Keluarga' => ['Corona Virus', 'Kesehatan', 'Keluarga Berencana', 'Pembangunan Keluarga', 'Kesetaraan Gender dan Sosial Inklusif'],
        'Asisten Deputi Pemberdayaan Masyarakat dan Penanggulangan Bencana' => ['Pemberdayaan Masyarakat, Koperasi, dan UMKM', 'Penanggulangan Bencana', 'Ketenagakerjaan'],
        'Asisten Deputi Pendidikan, Agama, Kebudayaan, Pemuda, dan Olahraga' => ['Agama', 'Pendidikan dan Kebudayaan', 'Kekerasan di Satuan Pendidikan (Sekolah, Kampus, Lembaga Khusus)', 'Kepemudaan dan Olahraga'],
        'Asisten Deputi Hubungan Luar Negeri dan Pertahanan' => ['Luar Negeri', 'TNI'],
        'Asisten Deputi Politik, Keamanan, Hukum, dan Hak Asasi Manusia' => ['Ketentraman, Ketertiban Umum, dan Perlindungan Masyarakat', 'Politik dan Hukum', 'Pencegahan dan Pemberantasan Penyalahgunaan dan Peredaran Gelap Narkotika dan Prekursor Narkotika (P4GN)', 'Pertanahan', 'Polri'],
        'Asisten Deputi Tata Kelola Pemerintahan' => ['SP4N Lapor', 'Manajemen ASN', 'Pelayanan Publik', 'Politisasi ASN', 'Netralitas ASN', 'Daerah Perbatasan', 'Kependudukan'],
        'Biro Perencanaan dan Keuangan' => ['Topik Khusus', 'Topik Lainnya', 'Bantuan Masyarakat'],
        'Biro Tata Usaha dan Sumber Daya Manusia' => [], 
        'Biro Umum' => [], 
        'Biro Protokol dan Kerumahtanggaan' => [], 
        'Biro Pers, Media, dan Informasi' => [], 
    ];

    public function handle()
    {
        $this->info("--- MEMULAI MIGRASI DATA V1 KE V2 ---");
        $apiName = 'v1_migration_api';

        $settings = ApiSetting::where('name', $apiName)->pluck('value', 'key');
        $baseUrl = $settings->get('base_url');
        $Authorization = $settings->get('authorization');
        $limit = (int) $this->option('limit');
        $startPage = (int) $this->option('start-page');

        if (!$baseUrl || !$Authorization) {
            $this->error("Kredensial API V1 ('{$apiName}') tidak lengkap. Migrasi dibatalkan.");
            return 1;
        }

        $credentials = ['base_url' => $baseUrl, 'authorization' => $Authorization, 'limit' => $limit];
        
        $this->loadRelationCaches();

        $nextPageReports = $this->migrateReportsAndReporters($credentials, $startPage);
        
        if ($startPage == 1) { 
            $this->migrateAssignments($credentials);
        }

        if ($nextPageReports > $startPage) {
            $this->info("Halaman {$startPage}/[LAST_PAGE] berhasil diproses. Total records: [COUNT]");
        } else {
            $this->info("Migrasi Reports selesai.");
        }
        
        return 0;
    }
    
    // =========================================================
    //                      HELPER FUNCTIONS
    // =========================================================

    private function loadRelationCaches()
    {
        $this->info("Memuat cache relasi V2...");
        $this->categoryCache = Category::pluck('id', 'name')->toArray();
        $this->unitKerjaCache = UnitKerja::select(['id', 'deputy_id', 'name'])->get()->keyBy('name')->toArray();
        $this->info("Cache relasi V2 selesai dimuat.");
    }

    private function getUnitKerjaNameByV1Category(string $kategori_v1): ?string
    {
        foreach ($this->kategoriUnitMapping as $unitName => $kategoris) {
            if (in_array($kategori_v1, $kategoris)) {
                return $unitName; 
            }
        }
        return null;
    }
    
    private function fetchApiData(string $endpoint, int $page, array $credentials): array
    {
        $limit = $credentials['limit'];
        $url = $credentials['base_url'] . "/migration/{$endpoint}?page={$page}&limit={$limit}";

        $tokenValue = str_ireplace('Bearer ', '', $credentials['authorization']);

        $response = Http::withToken($tokenValue)
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
    // TAHAP 1: MIGRASI REPORTS & REPORTERS (Single Step)
    // =========================================================
    private function migrateReportsAndReporters(array $credentials, int $startPage): int
    {
        $page = $startPage;
        $totalMigrated = 0;
        
        $data = $this->fetchApiData('reports', $page, $credentials);
        $reports_v1 = $data['data'] ?? [];
        $lastPage = $data['last_page'] ?? 0;

        if (empty($reports_v1)) {
            return 0; // Sinyal: Selesai
        }

        $categoryDefaultId = $this->categoryCache['Lainnya'] ?? null;

        foreach ($reports_v1 as $laporan_v1) {
            
            try {
                DB::beginTransaction();
                
                // --- 1. MAPPING DATA REPORTER (Pengadu) ---
                $reporter = Reporter::updateOrCreate(
                    ['nik' => $laporan_v1['nik']], 
                    [
                        'name' => $laporan_v1['nama_lengkap'],
                        'phone_number' => $laporan_v1['nomor_pengadu'],
                        'email' => $laporan_v1['email'],
                        'address' => $laporan_v1['alamat_lengkap'],
                    ]
                );
                
                // --- 2. TRANSFORMASI TANGGAL & UUID ---
                
                // Parse event_date V1 (format DD/MM/YYYY)
                $eventDateFormatted = null;
                if (!empty($laporan_v1['tanggal_kejadian'])) {
                    try {
                        // Gunakan createFromFormat untuk format non-standar
                        $eventDateFormatted = Carbon::createFromFormat('d/m/Y', $laporan_v1['tanggal_kejadian'])->toDateString();
                    } catch (Exception $e) {
                        Log::warning("Gagal parse tanggal kejadian V1 ID {$laporan_v1['id']}: {$laporan_v1['tanggal_kejadian']}");
                    }
                }
                
                // Generate UUID baru (memenuhi persyaratan NOT NULL V2)
                $newUuid = (string) Str::uuid(); 

                // 3. Logika Mapping Relasi V2
                $category_id = $this->categoryCache[$laporan_v1['kategori']] ?? null;
                // Jika category_id adalah null (tidak ditemukan), berikan ID Kategori Default/Lainnya
                if (is_null($category_id)) {
                    // Cari ID untuk Kategori 'Lainnya' atau 'Unknown' di V2,
                    // atau hardcode ID default jika Anda tahu nilainya.
                    $category_id = $this->categoryCache['Lainnya'] ?? 1; // Contoh: Gunakan ID 1 sebagai default
                    
                    // Opsional: Log warning
                    Log::warning("Kategori V1: '{$laporan_v1['kategori']}' (ID V1: {$laporan_v1['id']}) tidak ditemukan di V2. Menggunakan ID Default {$category_id}.");
                }
                
                $unit_kerja_name = $this->getUnitKerjaNameByV1Category($laporan_v1['kategori']);
                
                $unit_kerja_model = $this->unitKerjaCache[$unit_kerja_name] ?? null;
                $unit_kerja_id = $unit_kerja_model['id'] ?? null;
                $deputy_id = $unit_kerja_model['deputy_id'] ?? null;
                
                // 4. Report
                Report::updateOrCreate(
                    ['id' => $laporan_v1['id']], 
                    [
                        'reporter_id' => $reporter->id, 
                        'ticket_number' => $laporan_v1['nomor_tiket'], 
                        'uuid' => $newUuid,
                        'subject' => $laporan_v1['judul'],
                        'details' => $laporan_v1['detail'], 
                        'location' => $laporan_v1['lokasi'],
                        
                        'event_date' => $eventDateFormatted,
                        'source' => $laporan_v1['sumber_pengaduan'],
                        'status' => $laporan_v1['status'], 
                        'response' => $laporan_v1['tanggapan'],
                        // 'classification' => $laporan_v1['klasifikasi'],
                        
                        'category_id' => $category_id,
                        'unit_kerja_id' => $unit_kerja_id,
                        'deputy_id' => $deputy_id, 
                        
                        'created_at' => $laporan_v1['created_at'],
                    ]
                );
                
                // 5. SIMPAN WORKSHEET KE MEMORI CACHE
                if ($laporan_v1['lembar_kerja_analis']) {
                    $this->worksheetCache[$laporan_v1['id']] = $laporan_v1['lembar_kerja_analis'];
                }
                
                $totalMigrated++;
                DB::commit();

            } catch (Exception $e) {
                DB::rollBack();
                // Logika: Gagal sistem atau validasi DB yang tidak tertangkap (misal: ID relasi V2 null)
                Log::error("Migration Error (Reports): " . $e->getMessage(), ['id_v1' => $laporan_v1['id']]);
            }
        }

        $this->info("Laporan Halaman {$page}/{$lastPage} berhasil diproses.");
        
        return ($page < $lastPage) ? ($page + 1) : 0; 
    }


    // =========================================================
    // TAHAP 2: MIGRASI ASSIGNMENTS (Global Run)
    // =========================================================
    private function migrateAssignments(array $credentials)
    {
        $this->info("Memulai Migrasi Assignment (Relasi) secara keseluruhan...");
        $page = 1;
        $totalMigrated = 0;

        do {
            $data = $this->fetchApiData('assignments', $page, $credentials);
            $assignments_v1 = $data['data'] ?? [];
            $lastPage = $data['last_page'] ?? 0;

            if (empty($assignments_v1)) break;

            foreach ($assignments_v1 as $assign_v1) {
                
                $report_v2 = Report::where('id', $assign_v1['laporan_id'])->first(); 
                $analis_v2 = User::where('email', $assign_v1['analis_email'])->first();
                $assigner_v2 = User::where('email', $assign_v1['assigned_by_email'])->first();

                $worksheetData = $this->worksheetCache[$assign_v1['laporan_id']] ?? null;

                if ($report_v2 && $analis_v2 && $assigner_v2) {
                    
                    Assignment::create([
                        'report_id' => $report_v2->id,
                        'assigned_by_id' => $assigner_v2->id,
                        'assigned_to_id' => $analis_v2->id,
                        'notes' => $assign_v1['notes'],
                        'status' => 'approved', 
                        'created_at' => $assign_v1['created_at'],
                        'updated_at' => $assign_v1['updated_at'],
                        'analyst_worksheet' => $worksheetData, 
                    ]);
                    
                    $totalMigrated++;
                } else {
                    $this->warn("Assignment Laporan V1 ID {$assign_v1['laporan_id']} dilewati. Relasi gagal.");
                }
            }

            $this->info("Assignment Halaman {$page}/{$lastPage} selesai. Total: {$totalMigrated}");
            $page++;
            usleep(500000);
            
        } while ($page <= $lastPage);
        $this->info("Migrasi Assignment selesai. Total: {$totalMigrated}");
    }
}
