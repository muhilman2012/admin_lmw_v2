<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\LaporForwardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ForwardingController extends Controller
{
    protected array $middleware = ['auth'];
    protected LaporForwardingService $laporService;

    public function __construct(LaporForwardingService $laporService)
    {
        $this->laporService = $laporService;
    }
    
    public function index()
    {
        return view('pages.reports.forwarding.index');
    }

    public function showDetail($uuid, $complaintId)
    {
        $report = Report::with('reporter', 'documents')->where('uuid', $uuid)->firstOrFail();
        
        $laporId = $report->lapor_complaint_id ?? $complaintId; 

        $detailResponse = $this->laporService->getLaporDetail($laporId); 
        $logResponse = $this->laporService->getFollowUpLogs($laporId);

        if (!$detailResponse['success']) {
            return redirect()->back()->with('error', 'Gagal mengambil detail status dari LAPOR!: ' . $detailResponse['error']);
        }
        if (!$logResponse['success']) {
            return redirect()->back()->with('error', 'Gagal mengambil riwayat tindak lanjut dari LAPOR!: ' . $logResponse['error']);
        }
        
        $laporData = $detailResponse['results']['data'] ?? [];
        $logActivities = $logResponse['logs'] ?? []; 
        
        $renderedActivities = collect($logActivities)->map(function ($log) {
            $sourceField = !empty($log['template_content']) ? 'template_content' : 'content';
            
            // Panggil method render dari service instance
            $log['rendered_content'] = $this->laporService->renderLogContent($log, $sourceField);
            
            // Tambahkan data status yang terurai (jika ada di data field)
            if (!empty($log['data']) && ($data = json_decode($log['data'], true))) {
                // Asumsi status_old/new mungkin ada di root JSON data
                $log['status_old'] = $data['status_old'] ?? null;
                $log['status_new'] = $data['status_new'] ?? null;
            }
            
            return $log;
        })->all();
        
        return view('pages.reports.forwarding.detail', compact('report', 'complaintId', 'laporData', 'renderedActivities'));
    }

    /**
     * Helper: Mengganti placeholder template dengan nama instansi yang sebenarnya.
     * @param array $log Item log dari API followups
     * @param string $contentField Field yang berisi template string (cth: 'template_content' atau 'content')
     * @return string
     */
    public function renderLogContent(array $log, string $contentField): string
    {
        $template = $log[$contentField] ?? $log['content'] ?? 'Pembaruan status/disposisi.';
        
        // Ambil nama dari kolom yang sudah terurai oleh API
        $institutionFromName = $log['institution_from_name'] ?? 'Sistem';
        $institutionToName = $log['institution_to_name'] ?? 'Sistem';
        
        // 1. Ganti placeholders umum {{institution_from}} dan {{institution_to}}
        $content = str_replace('{{institution_from}}', $institutionFromName, $template);
        $content = str_replace('{{institution_to}}', $institutionToName, $content);

        // 2. Jika template menggunakan link, ganti juga link placeholder-nya (jika tidak ada data link, biarkan kosong)
        $content = str_replace('{{institution_from_link}}', '#', $content);
        $content = str_replace('{{institution_to_link}}', '#', $content);
        
        // 3. (Opsional) Hapus tag HTML <a> jika link tidak diperlukan, atau biarkan.
        // Kita biarkan karena template content biasanya sudah mengandung tag <b> dan <a> yang berguna.
        
        return $content;
    }

    /**
     * Mengirim tindak lanjut/balasan ke LAPOR!
     */
    public function sendFollowUp(Request $request, $complaintId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'template_code' => 'nullable|string',
            // Tambahkan validasi lain sesuai kebutuhan API (rating, dll)
        ]);

        try {
            $response = $this->laporService->sendFollowUpToLapor($complaintId, $request->all());

            if ($response['success']) {
                return redirect()->back()->with('success', 'Tindak lanjut/balasan berhasil dikirim ke LAPOR!.');
            } else {
                return redirect()->back()->with('error', 'Gagal mengirim balasan: ' . ($response['error'] ?? 'Kesalahan API.'));
            }

        } catch (\Exception $e) {
            Log::error('Exception saat kirim follow-up: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Kesalahan sistem saat mengirim balasan.');
        }
    }

    /**
     * Mengambil daftar template dari API LAPOR!.
     */
    public function getTemplates(LaporForwardingService $laporService)
    {
        $response = $laporService->getLaporTemplates();

        if ($response['success']) {
            return response()->json([
                'status' => true,
                'results' => [
                    'data' => $response['templates'] // Menggunakan data yang dikembalikan service
                ],
                'message' => 'Success'
            ]);
        } else {
            // Gagal koneksi API
            Log::error("Failed to fetch templates: " . ($response['error'] ?? 'Unknown API error.'));
            return response()->json(['status' => false, 'message' => $response['error'] ?? 'Gagal koneksi ke API Templates.'], 500);
        }
    }
}
