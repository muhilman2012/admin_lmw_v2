<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Reporter;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptPdfController extends Controller
{
    public function downloadReceiptUser($uuid)
    {
        $report = Report::with('reporter')->where('uuid', $uuid)->firstOrFail();
        
        $pdf = Pdf::loadView('receipts.user', compact('report'));
        
        $pdf->setPaper('a5', 'landscape');
        
        $fileName = 'Tanda-Terima-Pengadu-' . $report->ticket_number . '.pdf';
        
        return $pdf->download($fileName);
    }

    public function downloadReceiptGovernment($uuid)
    {
        $report = Report::with('reporter')->where('uuid', $uuid)->firstOrFail();
        
        $pdf = Pdf::loadView('receipts.government', compact('report'));

        $fileName = 'Tanda-Terima-KLD-' . $report->ticket_number . '.pdf';

        return $pdf->download($fileName);
    }
}
