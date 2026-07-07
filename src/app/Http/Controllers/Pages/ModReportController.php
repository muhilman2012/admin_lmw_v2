<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ModDailyExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ModReportController extends Controller
{
    public function exportDailyReport(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        
        $formattedDate = Carbon::parse($date)->format('d_m_Y');
        $fileName = 'Laporan_MOD_' . $formattedDate . '.xlsx';

        return Excel::download(new ModDailyExport($date), $fileName);
    }
}