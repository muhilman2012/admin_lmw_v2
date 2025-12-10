<?php

namespace App\Exports;

use App\Models\Report;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\AfterExport;

class ReportsExport extends DefaultValueBinder implements
    FromQuery, WithHeadings, WithMapping, ShouldQueue,
    WithCustomValueBinder, WithColumnFormatting, WithEvents
{
    use Exportable;

    protected array $filters;
    protected float $startedAt;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function registerEvents(): array
    {
        return [
            BeforeExport::class => function (BeforeExport $event) {
                $this->startedAt = microtime(true);

                Log::info('ReportsExport: export dimulai', [
                    'filters' => $this->filters,
                    'time' => now()->toDateTimeString(),
                ]);
            },

            AfterExport::class => function (AfterExport $event) {
                $duration = microtime(true) - $this->startedAt;

                Log::info('ReportsExport: export selesai', [
                    'duration_seconds' => round($duration, 2),
                    'duration_human'   => gmdate('H:i:s', (int) $duration),
                    'filters'          => $this->filters,
                    'time'             => now()->toDateTimeString(),
                ]);
            },
        ];
    }

    public function query(): Builder
    {
        $q = Report::query();

        // 1. Filter Pencarian Universal (q)
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

        // 2. Filter Kategori (Logika Multi-Select yang sudah diuji)
        $filterKategori = $this->filters['filterKategori'] ?? [];

        if (is_array($filterKategori) && !empty($filterKategori)) {
            
            // Ambil ID Parent Category yang dipilih
            $parentIds = Category::whereIn('name', $filterKategori)
                ->where('is_active', true)
                ->pluck('id');

            $targetCategoryIds = collect($parentIds); // Mulai dengan ID Parent

            // Ambil semua Child ID yang terhubung ke Parent yang dipilih
            if ($parentIds->isNotEmpty()) {
                $childIds = Category::whereIn('parent_id', $parentIds)
                    ->where('is_active', true)
                    ->pluck('id');
                    
                $targetCategoryIds = $targetCategoryIds->merge($childIds);
            }
            
            $finalIds = $targetCategoryIds->unique()->all();

            if (!empty($finalIds)) {
                $q->whereIn('category_id', $finalIds);
            }
        }

        // 3. Filter Status
        if (!empty($this->filters['filterStatus'])) {
            $q->where('status', $this->filters['filterStatus']);
        }

        // 4. Filter Klasifikasi
        if (!empty($this->filters['filterKlasifikasi'])) {
            $q->where('classification', $this->filters['filterKlasifikasi']);
        }

        // 5. Filter Distribusi
        if (!empty($this->filters['filterDistribusi'])) {
            [$type, $id] = array_pad(explode('_', $this->filters['filterDistribusi']), 2, null);
            if ($type === 'deputy') {
                $q->where('deputy_id', $id);
            } elseif ($type === 'unit') {
                $q->where('unit_kerja_id', $id);
            }
        }

        // 6. Filter Status Analisis
        if (!empty($this->filters['filterStatusAnalisis'])) {
            $q->where('analysis_status', $this->filters['filterStatusAnalisis']);
        }

        // 7. Filter Tanggal
        if (!empty($this->filters['filterDateRange']) && str_contains($this->filters['filterDateRange'], ' - ')) {
            [$start, $end] = explode(' - ', $this->filters['filterDateRange']);
            $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
            $endDate   = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();
            $q->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Default Sorting dan Eager Loading
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
            'Alamat Pelapor',
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
            $report->reporter->address ?? 'N/A',
            $report->source,
            $report->category->name ?? 'N/A',
            $report->unitKerja->name ?? '-',
            $report->deputy->name ?? '-',
            optional($report->event_date)->format('d/m/Y') ?? '-',
            $report->created_at->format('d/m/Y H:i:s'),
        ];
    }
}
