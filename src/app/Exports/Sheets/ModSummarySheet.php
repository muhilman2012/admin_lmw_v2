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
        $totalReports = Report::whereDate('created_at', $this->date)
            ->where('source', 'tatap muka') 
            ->count();
        
        $queueData = RegistrationQueue::selectRaw('counter_number, count(*) as total')
            ->whereDate('created_at', $this->date)
            ->groupBy('counter_number')
            ->pluck('total', 'counter_number')
            ->toArray();

        $masterCounters = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10']; 

        $loketStats = collect($masterCounters)->map(function ($counter) use ($queueData) {
            return (object) [
                'counter_number' => $counter,
                'total'          => $queueData[$counter] ?? 0
            ];
        });

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