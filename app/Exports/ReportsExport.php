<?php

namespace App\Exports;

use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReportsExport extends DefaultValueBinder implements
    FromQuery, WithHeadings, WithMapping, ShouldQueue,
    WithCustomValueBinder, WithColumnFormatting
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $q = Report::query();

        // --- Terapkan ulang filter dari $this->filters ---
        if (!empty($this->filters['q'])) {
            $search = $this->filters['q'];
            $q->where(function ($sub) use ($search) {
                $sub->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('reporter', function ($r) use ($search) {
                        $r->where('name', 'like', "%{$search}%")
                          ->orWhere('nik', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($this->filters['filterKategori'])) {
            $q->whereHas('category', function ($c) {
                $c->where('name', $this->filters['filterKategori']);
            });
        }

        if (!empty($this->filters['filterStatus'])) {
            $q->where('status', $this->filters['filterStatus']);
        }

        if (!empty($this->filters['filterKlasifikasi'])) {
            $q->where('classification', $this->filters['filterKlasifikasi']);
        }

        if (!empty($this->filters['filterDistribusi'])) {
            [$type, $id] = array_pad(explode('_', $this->filters['filterDistribusi']), 2, null);
            if ($type === 'deputy') {
                $q->where('deputy_id', $id);
            } elseif ($type === 'unit') {
                $q->where('unit_kerja_id', $id);
            }
        }

        if (!empty($this->filters['filterStatusAnalisis'])) {
            $q->where('analysis_status', $this->filters['filterStatusAnalisis']);
        }

        if (!empty($this->filters['filterDateRange']) && str_contains($this->filters['filterDateRange'], ' - ')) {
            [$start, $end] = explode(' - ', $this->filters['filterDateRange']);
            $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
            $endDate   = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();
            $q->whereBetween('created_at', [$startDate, $endDate]);
        }

        $q->with(['reporter', 'category', 'unitKerja', 'deputy'])
          ->orderBy('created_at', 'desc');

        return $q;
    }

    public function bindValue(Cell $cell, $value)
    {
        // Kolom G = "NIK Pelapor"
        $isNikColumn = ($cell->getColumn() === 'G');

        // (Tambahan pengaman) semua angka sangat panjang juga dipaksa string
        $looksLikeLongNumber = is_numeric($value) && strlen((string)$value) >= 13;

        if ($isNikColumn || $looksLikeLongNumber) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function headings(): array
    {
        return [
            'No. Tiket',
            'Status',
            'Klasifikasi',
            'Judul Laporan',
            'Detail Pengaduan',
            'Nama Pelapor',
            'NIK Pelapor',          // <- kolom ini = G
            'Sumber',
            'Kategori',
            'Unit Distribusi',
            'Deputi Distribusi',
            'Tanggal Kejadian',
            'Waktu Dibuat',
        ];
    }

    /** map(): kembalikan NIK biasa (tanpa apostrophe) */
    public function map($report): array
    {
        $details = (string) ($report->details ?? '');
        $short   = mb_strlen($details) > 100 ? mb_substr($details, 0, 100) . '...' : $details;

        $nik = (string) ($report->reporter->nik ?? ''); // pastikan string dari sisi PHP

        return [
            $report->ticket_number,
            $report->status,
            $report->classification ?? 'Belum Diklasifikasi',
            $report->subject,
            $short,
            $report->reporter->name ?? 'N/A',
            $nik, // biarkan tanpa apostrof
            $report->source,
            $report->category->name ?? 'N/A',
            $report->unitKerja->name ?? '-',
            $report->deputy->name ?? '-',
            optional($report->event_date)->format('d/m/Y') ?? '-',
            $report->created_at->format('d/m/Y H:i:s'),
        ];
    }
}
