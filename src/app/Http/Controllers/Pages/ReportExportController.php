<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Category;
use App\Models\UnitKerja;
use App\Models\Deputy;
use App\Exports\ReportsExport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\Builder;

class MigrationTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                '1403990003', // NIK
                '09230987',   // NOMOR TIKET
                'Ahmad Abdul', 
                '0812345678', 
                'ahmad@lama.com', 
                'Perubahan Status Tanah', 
                'Detail aduan lama...', 
                'Whatsapp', 
                'Administrasi', 
                'Proses verifikasi dan telaah', 
                'Jl. Raya Lama No. 10', 
                'Dokumen KTP Path',                  // Path Dokumen KTP
                '/path/ke/file/dokumen1.pdf|/path/ke/file/dokumen2.jpg', // Contoh: Path Dokumen Pendukung (Dipisah dengan |)
                'Hasil tanggapan lama...',          // Konten Tanggapan
                '2024-01-15 10:00:00', 
                '2024-01-14', 
                'Deputi A', 
                'AnalisA', 
                'Hasil analisis lama...',
            ]
        ];
    }
    
    public function headings(): array
    {
        return [
            'nik',
            'nomor_tiket',              // Wajib diisi
            'nama_lengkap',
            'nomor_pengadu',
            'email',
            'judul',
            'detail',
            'sumber_pengaduan',
            'kategori',
            'status',
            'alamat_lengkap',
            'path_dokumen_ktp_lama',    // BARU
            'path_dokumen_pendukung_lama', // BARU (Multi-path dipisahkan dengan |)
            'tanggapan_lama',           // BARU: Konten Tanggapan
            'waktu_dibuat',
            'tanggal_kejadian',
            'deputi_tujuan',
            'analis_username_lama',
            'lembar_kerja_analis',
        ];
    }
}

class ReportExportController extends Controller
{
    protected array $middleware = ['auth'];

    public function index()
    {
        // 1. Ambil data yang dibutuhkan untuk dropdown filter
        $categories = Category::mainCategories()->active()->get();
        $statuses = Report::select('status')->distinct()->get()->pluck('status');
        $sources = Report::select('source')->distinct()->get()->pluck('source');
        $unitKerjas = UnitKerja::all();
        $deputies = Deputy::all();
        
        // Data statis yang diserahkan ke view
        $analysisStatuses = [
            'Analisis', 'Disetujui', 'Perlu Perbaikan', 'Selesai', 'Ditolak'
        ];
        $classifications = [
            'Pengaduan berkadar pengawasan', 'Pengaduan tidak berkadar pengawasan', 'Aspirasi'
        ];

        // 2. Teruskan semua data ke view
        return view('pages.export.index', compact(
            'categories', 
            'statuses', 
            'sources', 
            'unitKerjas', 
            'deputies', 
            'analysisStatuses', 
            'classifications'
        ));
    }

    /**
     * Helper untuk mendapatkan query laporan yang sudah difilter.
     */
    protected function getFilteredQuery(Request $request)
    {
        // DD DIHAPUS

        $query = Report::query()->with(['reporter', 'category', 'unitKerja', 'deputy', 'assignments']);

        // 1. Filter Pencarian Universal (q)
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('reporter', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('nik', 'like', "%{$search}%");
                    });
            });
        }
        
        // 2. Filter Kategori (Multi-Select, termasuk Sub-Kategori)
        $filterKategori = $request->input('filterKategori', []);
        
        if (is_array($filterKategori) && !empty($filterKategori)) {
            
            // Ambil ID Parent Category yang dipilih
            $parentIds = Category::whereIn('name', $filterKategori)
                ->where('is_active', true)
                ->pluck('id');

            $targetCategoryIds = collect($parentIds); // Mulai dengan ID Parent

            // Ambil semua Child ID yang terhubung ke Parent yang dipilih
            if ($parentIds->isNotEmpty()) {
                $childIds = Category::whereIn('parent_id', $parentIds)
                    ->where('is_active', true)
                    ->pluck('id');
                    
                $targetCategoryIds = $targetCategoryIds->merge($childIds);
            }
            
            $finalIds = $targetCategoryIds->unique()->all();

            if (!empty($finalIds)) {
                // Menerapkan WHERE IN di query laporan utama
                $query->whereIn('category_id', $finalIds);
            }
        }
        
        // 3. Filter Status
        if ($status = $request->input('filterStatus')) {
            $query->where('status', $status);
        }

        // 4. Filter Klasifikasi
        if ($klasifikasi = $request->input('filterKlasifikasi')) {
            $query->where('classification', $klasifikasi);
        }

        // 5. Filter Distribusi
        if ($distribusi = $request->input('filterDistribusi')) {
            $parts = explode('_', $distribusi);
            $type = $parts[0] ?? null;
            $id = $parts[1] ?? null;

            if ($type === 'deputy') {
                $query->where('deputy_id', $id);
            } elseif ($type === 'unit') {
                $query->where('unit_kerja_id', $id);
            }
        }

        if ($sumber = $request->input('filterSumber')) {
            $query->where('source', $sumber);
        }

        // 6. Filter Status Analisis
        if ($statusAnalisis = $request->input('filterStatusAnalisis')) {
            $query->where('analysis_status', $statusAnalisis);
        }

        // 7. Filter Tanggal
        if ($dateRange = $request->input('filterDateRange')) {
            list($start, $end) = explode(' - ', $dateRange);
            $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
            $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        
        // Default Sorting
        $query->orderBy('created_at', 'desc');

        return $query;
    }

    /**
     * Menangani permintaan export ke Excel.
     */
    public function exportExcel(Request $request)
    {
        // 1. Ambil hanya key yang dipakai filter
        $filters = $request->only([
            'q', 'filterKategori', 'filterStatus', 'filterKlasifikasi',
            'filterDistribusi', 'filterStatusAnalisis', 'filterDateRange',
        ]);
        
        $fileName = 'Laporan_Pengaduan_Export_' . now()->format('Ymd_His') . '_' . \Str::random(10) . '.xlsx';

        // 2. Kirim filters ke Export Class. Biarkan Export Class yang menjalankan Query.
        (new ReportsExport($filters)) 
            ->queue('exports/' . $fileName, 'local');

        return response()->json([
            'success'  => true,
            'message'  => 'Export sedang diproses di background.',
            'fileName' => $fileName,
        ], 202);
    }

    /**
     * Menangani permintaan export ke PDF.
     */
    public function exportPdf(Request $request)
    {
        $query = $this->getFilteredQuery($request);
        
        // Batasi data untuk PDF agar tidak timeout di HTTP request
        $reports = $query->limit(5000)->get(); 

        $fileName = 'Laporan_Pengaduan_Export_' . Carbon::now()->format('Ymd_His') . '.pdf';
        
        // Render view Blade ke PDF
        $pdf = Pdf::loadView('exports.reports_pdf', compact('reports'));
        
        return $pdf->download($fileName);
    }

    public function checkStatus(Request $request)
    {
        $fileName = $request->input('fileName');
        $filePath = 'exports/' . $fileName;

        if (\Illuminate\Support\Facades\Storage::disk('local')->exists($filePath)) {
            return response()->json(['ready' => true, 'path' => $filePath], 200);
        }

        return response()->json(['ready' => false], 200);
    }

    public function download(Request $request): StreamedResponse
    {
        $path = $request->query('path'); // contoh: exports/Laporan_...xlsx
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        // Ubah nama file yang diunduh
        $downloadName = basename($path);

        return Storage::disk('local')->download($path, $downloadName);
    }

    /**
     * Menangani download template Excel untuk Migrasi.
     */
    public function downloadTemplate()
    {
        $fileName = 'LMW_Migrasi_Template_' . now()->format('Ymd') . '.xlsx';
        
        // Menggunakan helper class di atas
        return Excel::download(new MigrationTemplateExport(), $fileName);
    }
}
