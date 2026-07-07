<?php

namespace App\Exports\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\ModNote;

class ModDetailSheet implements FromView, WithTitle, ShouldAutoSize, WithStyles
{
    protected $date;

    public function __construct($date)
    {
        $this->date = $date;
    }

    public function view(): View
    {
        $notes = ModNote::with(['report', 'actualUser'])
            ->whereDate('created_at', $this->date)
            ->orderBy('created_at', 'asc')
            ->get();

        return view('exports.mod.detail', [
            'date'  => $this->date,
            'notes' => $notes
        ]);
    }

    public function title(): string
    {
        return 'Detail Catatan MOD';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            2 => ['font' => ['bold' => true]],
        ];
    }
}