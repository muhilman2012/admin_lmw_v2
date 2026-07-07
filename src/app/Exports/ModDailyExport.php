<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\ModSummarySheet;
use App\Exports\Sheets\ModDetailSheet;

class ModDailyExport implements WithMultipleSheets
{
    protected $date;

    public function __construct($date)
    {
        $this->date = $date;
    }

    public function sheets(): array
    {
        return [
            new ModSummarySheet($this->date),
            new ModDetailSheet($this->date),
        ];
    }
}