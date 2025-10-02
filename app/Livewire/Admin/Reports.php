<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Report;
use App\Models\Category;
use App\Models\UnitKerja;
use App\Models\Deputy;
use Carbon\Carbon;

class Reports extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 20;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    public $filterKategori = '';
    public $filterStatus = '';
    public $filterKlasifikasi = '';
    public $filterStatusAnalisis = '';
    public $filterDistribusi = '';
    public $filterDisposisi = '';
    public $filterSumber = '';
    public $filterDateRange = '';

    public $categories;
    public $statuses;
    public $sources;
    public $unitKerjas;
    public $deputies;
    public $classifications = [
        'Pengaduan berkadar pengawasan',
        'Pengaduan tidak berkadar pengawasan',
        'Aspirasi',
    ];
    public $analysisStatuses = [
        'Analisis',
        'Disetujui',
        'Perlu Perbaikan',
        'Selesai',
        'Ditolak'
    ];

    public $previewReportData = [];
    public $isModalOpen = false;

    protected $listeners = [
        'deleteReport',
        'updateDateRange' => 'handleDateRangeUpdate'
    ];
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->categories = Category::all()->pluck('name');
        $this->statuses = Report::select('status')->distinct()->get()->pluck('status');
        $this->sources = Report::select('source')->distinct()->get()->pluck('source');
        $this->unitKerjas = UnitKerja::all();
        $this->deputies = Deputy::all();
    }

    public function render()
    {
        $query = Report::with(['reporter', 'category', 'unitKerja', 'deputy']);

        // Logika Pencarian
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('ticket_number', 'like', $this->search . '%')
                  ->orWhere('subject', 'like', '%' . $this->search . '%')
                  ->orWhereHas('reporter', function ($q) {
                      $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('nik', 'like', '%' . $this->search . '%');
                  });
            });
        }
        
        // Logika Filter Kategori
        if ($this->filterKategori) {
            $categoryId = Category::where('name', $this->filterKategori)->value('id');
            $query->where('category_id', $categoryId);
        }
        
        // Logika Filter Status
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        // Logika Filter Klasifikasi (Baru)
        if ($this->filterKlasifikasi) {
            $query->where('classification', $this->filterKlasifikasi);
        }

        // Logika Filter Distribusi (Diperbaiki)
        if ($this->filterDistribusi) {
            $parts = explode('_', $this->filterDistribusi);
            $type = $parts[0] ?? null;
            $id = $parts[1] ?? null;

            if ($type === 'deputy') {
                $query->where('deputy_id', $id);
            } elseif ($type === 'unit') {
                $query->where('unit_kerja_id', $id);
            }
        }

        // Logika Filter Disposisi (Baru)
        if ($this->filterDisposisi) {
            if ($this->filterDisposisi === 'Belum terdisposisi') {
                $query->whereNull('disposition');
            } else { // 'Sudah terdisposisi'
                $query->whereNotNull('disposition');
            }
        }
        
        // Logika Filter Status Analisis (Baru)
        if ($this->filterStatusAnalisis) {
            $query->where('analysis_status', $this->filterStatusAnalisis);
        }
        
        // Logika Filter Sumber
        if ($this->filterSumber) {
            $query->where('source', $this->filterSumber);
        }

        // Logika Filter Tanggal
        if ($this->filterDateRange) {
            list($start, $end) = explode(' - ', $this->filterDateRange);
            $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
            $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        
        $reports = $query->orderBy($this->sortField, $this->sortDirection)
                         ->paginate($this->perPage);

        return view('livewire.admin.reports', ['reports' => $reports]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilters()
    {
        $this->resetPage();
    }

    public function resetPaginasi()
    {
        $this->resetPage();
    }

    // Metode baru untuk merespons event dari JavaScript
    public function handleDateRangeUpdate($dateRange)
    {
        $this->filterDateRange = $dateRange;
        $this->resetPage();
        $this->dispatch('filtersUpdated');
    }
    
    // Metode resetFilters yang diperbaiki
    public function resetFilters()
    {
        $this->reset(['filterKategori', 'filterStatus', 'filterKlasifikasi', 'filterStatusAnalisis', 'filterDistribusi', 'filterDisposisi', 'filterSumber', 'filterDateRange', 'search']);
        $this->resetPage();
        
        // Dispatch event untuk membersihkan input tanggal di front-end
        $this->dispatch('clearDateRange');
        
        // Dispatch event untuk mengupdate UI badge filter count
        $this->dispatch('filtersUpdated');
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }
    
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;
        $this->resetPage();
    }

    public function deleteReportConfirm($reportId)
    {
        $this->dispatch('swal:confirm', [
            'title' => 'Apakah Anda yakin?',
            'text' => 'Data laporan akan dihapus secara permanen!',
            'confirmButtonText' => 'Ya, hapus!',
            'onConfirmed' => 'deleteReport',
            'onConfirmedParams' => [$reportId]
        ]);
    }

    public function deleteReport($reportId)
    {
        if ($report = Report::find($reportId)) {
            $report->delete();
            $this->dispatch('session:success', ['message' => 'Laporan berhasil dihapus.']);
        }
    }

    public function dispatchReportPreview($uuid)
    {
        $report = \App\Models\Report::with(['reporter', 'category', 'unitKerja', 'deputy'])
            ->where('uuid', $uuid)
            ->first();
        
        if (!$report) {
            $this->dispatch('session:success', ['message' => 'Laporan tidak ditemukan.', 'icon' => 'error']);
            return;
        }

        // 1. ISI PROPERTI DENGAN DATA LAPORAN
        $this->previewReportData = [
            'ticket_number' => $report->ticket_number,
            'uuid' => $report->uuid,
            'subject' => $report->subject,
            'reporter_name' => $report->reporter->name ?? '-',
            'status' => $report->status,
            'category' => $report->category->name ?? '-',
            'unit_tujuan' => $report->unitKerja->name ?? '-',
            'deputi_tujuan' => $report->deputy->name ?? '-',
            'disposition' => $report->disposition ?? 'Belum terdisposisi',
            'source' => $report->source,
            'created_at' => $report->created_at->format('d/m/Y H:i'),
        ];
        
        // 2. Memicu event untuk JAVASCRIPT menampilkan modal
        // Gunakan nama event yang unik, misalnya 'show-bootstrap-modal'
        $this->dispatch('show-bootstrap-modal'); 
    }

    // Tambahkan fungsi ini jika Anda ingin menutup modal dari Livewire
    public function resetPreviewData()
    {
        $this->previewReportData = [];
    }
}
