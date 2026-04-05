<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Carbon\Carbon;
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
        
        $detailResp = $this->laporService->getLaporDetail($report->lapor_complaint_id ?? $complaintId);
        $followUpResp = $this->laporService->getFollowUpLogs($report->lapor_complaint_id ?? $complaintId);

        if (!$detailResp['success']) {
            return redirect()->back()->with('error', 'Gagal mengambil data dari API.');
        }

        $laporData = $detailResp['results']['data'] ?? [];
        $internalLogs = $detailResp['results']['data_logs'] ?? [];
        $externalLogs = $followUpResp['success'] ? $followUpResp['logs'] : [];

        $allLogs = collect($internalLogs)->merge($externalLogs)->sortBy(function ($log) {
            return $log['date'] ?? $log['created_at'] ?? '';
        });

        $renderedActivities = $allLogs->map(function ($log) {
            // Normalisasi field waktu
            $log['display_date'] = $log['date'] ?? $log['created_at'] ?? '-';
            
            // PENTING: renderLogContent akan mengisi key 'institution_from_name', dll.
            // Karena $log di sini adalah array, kita tangkap kembali hasil perubahannya.
            $log['rendered_content'] = $this->renderLogContent($log, 'content');
            
            return $log;
        })->values()->all();

        return view('pages.reports.forwarding.detail', compact(
            'report', 
            'complaintId', 
            'laporData', 
            'renderedActivities'
        ));
    }

    public function renderLogContent(array &$log, string $contentField): string
    {
        $template = $log[$contentField] ?? $log['content'] ?? 'Pembaruan status/disposisi.';
        
        // 1. Handling Institusi (Prioritas: Flat Key > Nested Object > Default)
        $from = $log['institution_from_name'] ?? $log['institution_from_id']['name'] ?? 'Lapor Mas Wapres';
        $to   = $log['institution_to_name']   ?? $log['institution_to_id']['name']   ?? 'Lapor Mas Wapres';
        $inst = $log['institution']['name']   ?? $log['institution']                 ?? 'Lapor Mas Wapres';

        // 2. Handling Status
        $stOld = $log['status_old']['name'] ?? 'Status Awal';
        $stNew = $log['status_new']['name'] ?? 'Status Baru';

        // 3. Simpan balik ke array $log agar bisa dibaca di Blade
        $log['institution_from_name'] = $from;
        $log['institution_to_name']   = $to;
        $log['status_old_name']       = $stOld;
        $log['status_new_name']       = $stNew;

        // 4. Lengkapi Mapping Placeholder
        $map = [
            '{{institution_from}}'   => "<b>$from</b>",
            '{{ institution_from }}'  => "<b>$from</b>",
            '{{institution_to}}'     => "<b>$to</b>",
            '{{ institution_to }}'    => "<b>$to</b>",
            '{{institution}}'        => "<b>$inst</b>",
            '{{ institution }}'       => "<b>$inst</b>",
            '{{status_old}}'         => "<span class='badge bg-secondary-lt'>$stOld</span>",
            '{{ status_old }}'        => "<span class='badge bg-secondary-lt'>$stOld</span>",
            '{{status_new}}'         => "<span class='badge bg-primary-lt'>$stNew</span>",
            '{{ status_new }}'        => "<span class='badge bg-primary-lt'>$stNew</span>",
        ];

        return strtr($template, $map);
    }

    /**
     * Mengirim tindak lanjut/balasan ke LAPOR!
     */
    public function sendFollowUp(Request $request, $complaintId)
    {
        // 1. Validasi input dari form
        $request->validate([
            'content' => 'required|string|max:1000',
            'template_code' => 'nullable|string',
            'attachment.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120', // Validasi file jika ada
        ]);

        try {
            // 2. Kirim ke Service. 
            // Logic pencarian institution_id dari tabel laporan_forwardings 
            // sebaiknya tetap berada di dalam Service agar Controller tetap ramping.
            $response = $this->laporService->sendFollowUpToLapor($complaintId, $request->all());

            // 3. Handle Response
            if ($response['success']) {
                return redirect()->back()->with('success', 'Tindak lanjut/balasan berhasil dikirim ke LAPOR!.');
            } 
            
            // Handle error spesifik dari API
            $errorMessage = $response['error'] ?? 'Kesalahan API.';
            return redirect()->back()->with('error', 'Gagal mengirim balasan: ' . $errorMessage);

        } catch (\Exception $e) {
            // 4. Log jika terjadi error sistem (coding/koneksi)
            Log::error('Exception saat kirim follow-up: ' . $e->getMessage(), [
                'complaint_id' => $complaintId,
                'user' => auth()->id()
            ]);
            
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
