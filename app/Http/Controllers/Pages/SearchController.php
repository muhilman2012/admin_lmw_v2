<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        return view('pages.search.index', ['statuses' => $this->statusOptions]);
    }

    /**
     * Memproses permintaan pencarian AJAX.
     */
    public function runSearch(Request $request)
    {
        $query = $request->input('q');
        $status = $request->input('status');

        if (empty($query) && empty($status)) {
            return response()->json(['reports' => []]);
        }

        $reports = Report::with(['reporter', 'category'])
            ->where(function ($q) use ($query) {
                // Pencarian berdasarkan Nomor Tiket, Judul, NIK, atau Nama Pelapor
                $q->where('ticket_number', 'like', "%{$query}%")
                  ->orWhere('subject', 'like', "%{$query}%")
                  ->orWhereHas('reporter', function ($q) use ($query) {
                      $q->where('name', 'like', "%{$query}%")
                        ->orWhere('nik', 'like', "%{$query}%");
                  });
            })
            ->when($status, function ($q) use ($status) {
                return $q->where('status', $status);
            })
            ->limit(50) // Batasi hasil untuk performa
            ->get();

        // Mengembalikan hasil sebagai JSON
        return response()->json(['reports' => $reports]);
    }
}
