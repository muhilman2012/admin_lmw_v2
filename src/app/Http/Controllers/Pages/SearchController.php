<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class SearchController extends Controller
{
    protected array $middleware = ['auth'];
    
    // Status hardcoded untuk view
    protected array $statusOptions = [
        'Proses verifikasi dan telaah', 
        'Menunggu kelengkapan data dukung dari Pelapor',
        'Diteruskan kepada instansi yang berwenang untuk penanganan lebih lanjut', 
        'Penanganan Selesai'
    ];

    /**
     * Menampilkan halaman utama pencarian.
     */
    public function index()
    {
        return view('pages.search.index'); 
    }

    /**
     * Memproses permintaan pencarian AJAX.
     */
    public function runSearch(Request $request)
    {
        $query = $request->input('q');
        $searchColumn = $request->input('search_column', 'global');
        $dateRange = $request->input('date_range');
        
        $reports = Report::query()->with(['reporter', 'category'])
            ->when($query, function (Builder $q) use ($query, $searchColumn) {
                // 1. Logika Pencarian Terarah atau Global
                $searchTerm = '%' . $query . '%';
                
                if ($searchColumn === 'ticket_number') {
                    $q->where('ticket_number', 'like', $searchTerm);
                } elseif ($searchColumn === 'subject') {
                    $q->where('subject', 'like', $searchTerm);
                } elseif ($searchColumn === 'reporter_name') {
                    $q->whereHas('reporter', function ($r) use ($searchTerm) {
                        $r->where('name', 'like', $searchTerm);
                    });
                } elseif ($searchColumn === 'reporter_nik') {
                    $q->whereHas('reporter', function ($r) use ($searchTerm) {
                        $r->where('nik', 'like', $searchTerm);
                    });
                } else {
                    // Pencarian Global (Default)
                    $q->where(function ($subQuery) use ($searchTerm) {
                        $subQuery->where('ticket_number', 'like', $searchTerm)
                                 ->orWhere('subject', 'like', $searchTerm)
                                 ->orWhereHas('reporter', function ($r) use ($searchTerm) {
                                     $r->where('name', 'like', $searchTerm)
                                       ->orWhere('nik', 'like', $searchTerm);
                                 });
                    });
                }
            })
            ->when($dateRange, function (Builder $q) use ($dateRange) {
                // 2. Logika Filter Tanggal Dibuat
                if (str_contains($dateRange, ' - ')) {
                    list($start, $end) = explode(' - ', $dateRange);
                    try {
                        $startDate = Carbon::createFromFormat('d/m/Y', trim($start))->startOfDay();
                        $endDate = Carbon::createFromFormat('d/m/Y', trim($end))->endOfDay();
                        $q->whereBetween('reports.created_at', [$startDate, $endDate]);
                    } catch (\Exception $e) {
                        // Log error jika format tanggal salah
                        \Log::warning('Search date format error: ' . $dateRange);
                    }
                }
            })
            ->orderBy('reports.created_at', 'desc')
            ->limit(50) // Batasi hasil pencarian agar cepat
            ->get();
            
        return response()->json(['reports' => $reports]);
    }
}
