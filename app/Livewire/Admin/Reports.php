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
    public $filterDistribusi = '';
    public $filterDisposisi = '';
    public $filterSumber = '';
    public $filterDateRange = '';

    public $categories;
    public $statuses;
    public $distributions;
    public $sources;

    protected $listeners = ['deleteReport'];
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->categories = Category::all()->pluck('name');
        $this->statuses = Report::select('status')->distinct()->get()->pluck('status');
        $this->sources = Report::select('source')->distinct()->get()->pluck('source');

        $units = UnitKerja::all()->pluck('name');
        $deputies = Deputy::all()->pluck('name');
        
        $this->distributions = $units->concat($deputies)->unique()->values()->toArray();
    }

    public function render()
    {
        $query = Report::with('reporter')
            ->when($this->search, function ($query) {
                $query->where('ticket_number', 'like', '%' . $this->search . '%')
                      ->orWhere('title', 'like', '%' . $this->search . '%')
                      ->orWhereHas('reporter', function ($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
            })
            ->when($this->filterKategori, function ($query) {
                $categoryId = Category::where('name', $this->filterKategori)->value('id');
                $query->where('category_id', $categoryId);
            })
            ->when($this->filterStatus, fn ($query) => $query->where('status', $this->filterStatus))
            ->when($this->filterDistribusi, fn ($query) => $query->where('distribution', $this->filterDistribusi))
            ->when($this->filterDistribusi, function ($query) {
                $unit = UnitKerja::where('name', $this->filterDistribusi)->first();
                $deputy = Deputy::where('name', $this->filterDistribusi)->first();
                
                if ($unit) {
                    $query->where('unit_kerja_id', $unit->id);
                } elseif ($deputy) {
                    $query->where('deputy_id', $deputy->id);
                }
            })
            ->when($this->filterSumber, fn ($query) => $query->where('source', $this->filterSumber))
            ->when($this->filterDateRange, function ($query) {
                list($start, $end) = explode(' - ', $this->filterDateRange);
                $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->orderBy($this->sortField, $this->sortDirection);

        $reports = $query->paginate($this->perPage);

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
}
