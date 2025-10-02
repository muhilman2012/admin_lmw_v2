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

    public function index()
    {
        return view('pages.reports.forwarding.index');
    }

    public function showDetail($uuid, $complaintId)
    {
        $report = Report::with('reporter')->where('uuid', $uuid)->firstOrFail();
        $service = new LaporForwardingService();

        // 1. Ambil data status dari API LAPOR!
        $apiResponse = $service->getLaporStatus($complaintId);

        if (!$apiResponse['success']) {
            return redirect()->back()->with('error', 'Gagal mengambil status dari LAPOR!: ' . $apiResponse['error']);
        }
        
        $laporData = $apiResponse['data'];

        // 2. Ambil log kegiatan dari API
        $logActivities = $responseData['data_logs'] ?? [];
        
        return view('pages.reports.forwarding.detail', compact('report', 'complaintId', 'laporData', 'logActivities'));
    }

    /**
     * Mengirimkan tanggapan balasan (reply) ke laporan melalui API LAPOR! (PUT request).
     */
    public function submitReply(Request $request, $complaintId)
    {
        $request->validate([
            'admin_content' => 'required|string|max:1000',
        ]);
        
        $service = new LaporForwardingService();
        $replyContent = $request->input('admin_content');
        
        // Asumsi data admin berupa ID user di LMW atau kode tertentu
        $adminId = auth()->id(); 

        $apiResponse = $service->sendReply($complaintId, $replyContent, $adminId);

        if ($apiResponse['success']) {
            return redirect()->back()->with('success', 'Tanggapan berhasil dikirim ke LAPOR!.');
        } else {
            return redirect()->back()->with('error', 'Gagal mengirim tanggapan: ' . $apiResponse['error']);
        }
    }
}
