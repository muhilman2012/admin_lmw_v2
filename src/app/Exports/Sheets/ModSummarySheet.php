<?php

namespace App\Exports\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use App\Models\Report;
use App\Models\RegistrationQueue;
use Carbon\Carbon;

class ModSummarySheet implements FromView, WithTitle, ShouldAutoSize
{
    protected $date;

    public function __construct($date)
    {
        $this->date = $date;
    }

    public function view(): View
    {
        $totalReports = Report::whereDate('created_at', $this->date)->count();
        
        $loketStats = RegistrationQueue::selectRaw('counter_number, count(*) as total')
            ->whereDate('created_at', $this->date)
            ->groupBy('counter_number')
            ->get();

        return view('exports.mod.summary', [
            'date'         => $this->date,
            'totalReports' => $totalReports,
            'loketStats'   => $loketStats
        ]);
    }

    public function title(): string
    {
        return 'Ringkasan Laporan';
    }
}