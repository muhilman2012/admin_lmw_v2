<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\V1ReportsImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

class PreviewImport implements ToCollection, WithHeadingRow, WithLimit
{
    public $data = [];
    
    // Batasi hingga 5 baris + header
    public function limit(): int
    {
        return 5; 
    }
    
    public function collection(Collection $rows)
    {
        // Konversi collection ke array
        $this->data = $rows->toArray();
    }
}

class ReportImportController extends Controller
{
    protected array $middleware = ['auth'];

    public function importReports(Request $request)
    {
        set_time_limit(0); 
        
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $skipValidation = $request->has('skip_validation');
        $redirectTarget = $request->input('active_tab_hash') ?? '#tab-import'; // Ambil hash tab

        $reportImport = new V1ReportsImport($skipValidation);

        try {
            // Jalankan import menggunakan instance
            Excel::import($reportImport, $request->file('import_file'));
            
            $successCount = $reportImport->getImportedCount();
            $failedTickets = $reportImport->getFailedTickets();
            $failCount = count($failedTickets);
            
            $message = "Import selesai! {$successCount} laporan berhasil diimpor.";
            if ($failCount > 0) {
                $message .= " ({$failCount} laporan gagal diproses. Lihat detail di bawah.)";
                // Simpan detail error ke session untuk ditampilkan di view
                session()->flash('import_errors', $failedTickets);
            }

            return redirect()->to(route('settings.index') . $redirectTarget)
                ->with('success', $message);

        } catch (\Exception $e) {
            // Tangani kegagalan fatal (misalnya: file rusak atau error Maatwebsite)
            Log::error('Import Reports Gagal Total: ' . $e->getMessage());
            
            return redirect()->to(route('settings.index') . $redirectTarget)
                ->with('error', 'Import Gagal Total: File tidak dapat diproses. ' . $e->getMessage());
        }
    }
    
    /**
     * Menyediakan template Excel/CSV untuk diunduh.
     */
    public function downloadTemplate()
    {
        $headers = [
            'ticket_number', 'subject', 'description', 
            'created_at', 'event_date', 'location', 'source', 'status', 'response', 
            'nik', 'reporter_name', 'phone_number', 'email', 'address',
            'category_name', 
            'deputy_name', 'unit_kerja_name', 
            'analyst_email', 
            'assignment_status', 
            'assignment_notes',
        ];

        $exampleData = [
            [
                '1234567', 
                'Laporan Kerusakan Jalan Cepat', 
                'Terjadi kerusakan parah di ruas jalan X', 
                '2024-05-15 10:30:00',
                '2024-05-10',
                'Jalan ABC No. 5',
                'Website', 
                'Selesai', 
                'Tindak lanjut cepat telah dilakukan.',
                "'3301xxxxxxxxxxxx",
                'Budi Santoso', 
                "'0812xxxxxxxx",
                'analis@contoh.com', 
                'Jl. Raya Nomor 10, Jakarta',
                'Infrastruktur',
                'Deputi Bidang A', 
                'Biro Perencanaan',
                'analis@email.com', 
                'completed', 
                'Assignment selesai di V1.' 
            ]
        ];

        $data = Collection::make([$headers, ...$exampleData]);

        return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromArray {
            protected $data;
            public function __construct($data) { $this->data = $data; }
            public function array(): array { return $this->data->toArray(); }
        }, 'template_laporan_import.xlsx');
    }

    /**
     * Menangani proses preview 5 baris pertama dari file import.
     */
    public function previewReports(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);
        
        try {
            $previewImport = new PreviewImport();
            // Baca file
            Excel::import($previewImport, $request->file('import_file'));
            
            return response()->json([
                'status' => 'success',
                'data' => $previewImport->data
            ]);

        } catch (\Exception $e) {
            Log::error('Preview Reports Gagal: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membaca file: ' . $e->getMessage()
            ], 422);
        }
    }
}
