<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Report;
use App\Models\Reporter;
use App\Models\Assignment;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\UnitKerja;
use App\Models\Deputy;
use App\Models\User;
use Carbon\Carbon;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;

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

    public $unassignedCategoryId = null;
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
        'submitted', 'approved', 'revision_needed'
    ];

    public $previewReportData = [];
    public $isModalOpen = false;
    public $categoryMapping = [];

    public $dispatchReportId;
    public $dispatchReportTicketNumber;
    public $analystId;
    public $dispositionNotes;
    public $currentAssignmentId = null;

    public $selectedReports = [];
    public $selectAll = false;
    public $newCategoryId = null;
    public $newAnalystId = null;
    public $newCategoryDestinationName = 'Belum Dipilih';
    public $newCategoryDestinationDeputy = 'N/A';

    public $confirmDeleteId;
    public $confirmDeleteTicket;
    public $confirmDeleteName;

    public $confirmDeleteAssignmentId;
    public $confirmDeleteAssignmentAnalyst;
    public $confirmDeleteAssignmentTicket;

    protected $listeners = [
        'deleteReport',
        'deleteAssignment',
        'updateDateRange' => 'handleDateRangeUpdate'
    ];
    protected $paginationTheme = 'bootstrap';
    protected $queryString = [
        'search' => ['except' => ''],
        'filterKategori' => ['except' => ''],
        'filterDisposisi' => ['except' => ''], 
        'filterStatus' => ['except' => ''],
        'filterKlasifikasi' => ['except' => ''],
        'filterDistribusi' => ['except' => ''],
    ];

    public function mount()
    {
        $this->categories = Category::all()->pluck('name');
        $this->statuses = Report::select('status')->distinct()->get()->pluck('status');
        $this->sources = Report::select('source')->distinct()->get()->pluck('source');
        $this->unitKerjas = UnitKerja::all();
        $this->deputies = Deputy::all();
        $this->unassignedCategoryId = Category::where('name', 'Lainnya')->value('id');
        $this->categoryMapping = Category::with('unitKerjas.deputy')
            ->get()
            ->pluck('unitKerjas.0.deputy.name', 'id')
            ->toArray();
    }

    public function resetMassActionProperties()
    {
        $this->reset([
            'confirmDeleteAssignmentId', 
            'confirmDeleteAssignmentTicket', 
            'confirmDeleteAssignmentAnalyst',
            'newAnalystId',
            'newCategoryId',
            'currentAssignmentId',
            'dispositionNotes'
        ]);

        $this->dispatch('close-mass-disposition-modal');
        $this->dispatch('close-mass-category-modal');
    }

    private function applyUserScope(Builder $query, User $user): Builder
    {
        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return $query;
        }
        if ($user->hasRole('analyst')) {
            return $query->whereHas('assignments', function ($q) use ($user) {
                $q->where('assigned_to_id', $user->id);
            });
        }
        if ($user->hasRole('deputy') && $user->deputy_id) {
            $unitIds = UnitKerja::where('deputy_id', $user->deputy_id)->pluck('id');
            return $query->whereHas('category', function (Builder $q) use ($unitIds) {
                $q->whereHas('unitKerjas', function ($qUnit) use ($unitIds) {
                    $qUnit->whereIn('unit_kerjas.id', $unitIds);
                });
            });
        }
        if ($user->unit_kerja_id) {
            return $query->whereHas('category', function (Builder $q) use ($user) {
                $q->whereHas('unitKerjas', function ($qUnit) use ($user) {
                    $qUnit->where('unit_kerjas.id', $user->unit_kerja_id);
                });
            });
        }
        return $query->where('id', 0);
    }

    public function getReportsProperty()
    {
        $user = Auth::user();

        // APLIKASIKAN FILTER AKSES BERBASIS PERAN (RBAC Scope)
        $query = Report::query()->with(['reporter', 'category.unitKerjas', 'unitKerja', 'deputy', 'assignments.assignedTo']);

        $query = $this->applyUserScope($query, $user);

        // LOGIKA PENCARIAN
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('ticket_number', 'like', str_replace('%', '', $searchTerm) . '%')
                  ->orWhere('subject', 'like', $searchTerm)
                  ->orWhereHas('reporter', function ($q) use ($searchTerm) {
                      $q->where('name', 'like', $searchTerm)
                        ->orWhere('nik', 'like', $searchTerm);
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

        // Logika Filter Klasifikasi
        if ($this->filterKlasifikasi) {
            $query->where('classification', $this->filterKlasifikasi);
        }

        // Logika Filter Distribusi
        if ($this->filterDistribusi) {
            if ($this->filterDistribusi === 'unassigned') {
                
                if ($this->unassignedCategoryId) {
                    // Tampilkan laporan yang Kategori ID-nya adalah ID Kategori 'Lainnya'
                    $query->where('category_id', $this->unassignedCategoryId);
                } else {
                    // Jika Kategori 'Lainnya' tidak ditemukan, jangan tampilkan apa-apa
                    $query->where('id', 0); 
                }
                
            } else {
                // Logika lama (Deputi atau Unit Kerja)
                list($type, $id) = array_pad(explode('_', $this->filterDistribusi), 2, null);

                if ($type === 'deputy' && $id) {
                    $query->where('deputy_id', $id);
                } elseif ($type === 'unit' && $id) {
                    $query->where('unit_kerja_id', $id);
                }
            }
        }

        // Logika Filter Disposisi
        if ($this->filterDisposisi) {
            if ($this->filterDisposisi === 'Belum terdisposisi') {
                $query->whereDoesntHave('assignments'); 
            } else if ($this->filterDisposisi === 'Sudah terdisposisi') {
                $query->whereHas('assignments');
            } else { 
                $analystName = $this->filterDisposisi;
                
                $query->whereHas('assignments', function (Builder $q) use ($analystName) {
                    $q->whereHas('assignedTo', function (Builder $qUser) use ($analystName) {
                        $qUser->where('name', $analystName);
                    });
                });
            }
        }
        
        // Logika Filter Status Analisis
        if ($this->filterStatusAnalisis) {
            $status = $this->filterStatusAnalisis;
            $query->whereHas('assignments', function ($q) use ($status) {
                $q->where('status', $status)->latest()->limit(1);
            });
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
        
        // 4. ORDER DAN PAGINASI
        $sortField = $this->sortField;
        $sortDirection = $this->sortDirection;

        // === PERBAIKAN SORTING KATEGORI ===
        if ($sortField === 'category') {
            $query->leftJoin('categories', 'reports.category_id', '=', 'categories.id')
                ->orderBy('categories.name', $sortDirection)
                ->select('reports.*');
                
        } elseif ($sortField === 'reporter_name') {
            // JOIN ke tabel reporters
            $query->leftJoin('reporters', 'reports.reporter_id', '=', 'reporters.id')
                ->orderBy('reporters.name', $sortDirection) // Urutkan berdasarkan kolom 'name' di tabel reporters
                ->select('reports.*');
        } 
        
        else {
            $query->orderBy($sortField, $sortDirection);
        }
        
        return $query->paginate($this->perPage);
    }

    public function render()
    {
        $reportsPaginator = $this->getReportsProperty();

        $reportsProcessedPaginator = $this->processReportsForBadge($reportsPaginator);
        $groupedCategoriesSimple = $this->getGroupedCategoriesProperty(); 
        $availableAnalysts = $this->getGroupedAnalystsProperty();

        return view('livewire.admin.reports', [
            'reports' => $reportsProcessedPaginator,
            'groupedCategories' => $this->groupedCategories,
            'categoriesForMassSelect' => $groupedCategoriesSimple,
            'availableAnalysts' => $availableAnalysts,
        ]);
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

    public function closeDeleteModal()
    {
        $this->dispatch('close-confirm-delete-modal'); 
        
        $this->reset(['confirmDeleteId', 'confirmDeleteTicket', 'confirmDeleteName', 'currentAssignmentId']);
        $this->resetPage(); 
    }

    public function deleteReportConfirm($reportId)
    {
        $report = Report::with('reporter')->findOrFail($reportId);

        $this->confirmDeleteId = $reportId;
        $this->confirmDeleteTicket = $report->ticket_number;
        $this->confirmDeleteName = $report->reporter->name ?? 'N/A';

        $this->dispatch('show-delete-confirm-modal', [
            'ticket' => $this->confirmDeleteTicket,
            'name' => $this->confirmDeleteName
        ]);
    }

    public function deleteReport()
    {
        DB::beginTransaction();
        
        try {
            if ($report = Report::find($this->confirmDeleteId)) {
                $ticket = $report->ticket_number;
                
                $report->delete(); 
                
                DB::commit();

                $this->dispatch('swal:toast', message: "Laporan #{$ticket} berhasil dihapus.", icon: 'success');

                $this->closeDeleteModal();
                
            } else {
                DB::rollBack();
                $this->dispatch('swal:toast', message: 'Laporan tidak ditemukan.', icon: 'error');
                $this->closeDeleteModal();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menghapus laporan {$this->confirmDeleteId}: " . $e->getMessage());
            $this->dispatch('swal:toast', message: 'Gagal menghapus laporan sistem.', icon: 'error');
            
            $this->closeDeleteModal();
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
        $this->dispatch('show-report-preview-modal');
    }

    // Tambahkan fungsi ini jika Anda ingin menutup modal dari Livewire
    public function resetPreviewData()
    {
        $this->previewReportData = [];
    }

    public function getGroupedCategoriesProperty()
    {
        // Menggunakan logika yang sama dengan ReportController@create (Parent/Child standard)
        $categories = Category::with(['children' => function ($q) {
                    $q->active();
                }])
                ->whereNull('parent_id')
                ->active()
                ->get();

        $categoriesForSelect = Category::with('children')
            ->whereNull('parent_id')
            ->active()
            ->get();
            
        return $categoriesForSelect;
    }

    public function updatedNewCategoryId($value)
    {
        // Reset properties jika tidak ada nilai
        if (!$value) {
            $this->newCategoryDestinationName = 'Belum Dipilih';
            $this->newCategoryDestinationDeputy = 'N/A';
            return;
        }

        $category = Category::with('unitKerjas.deputy', 'parent.unitKerjas.deputy')->find($value); // Eager Load Parent
        
        if (!$category) {
            $this->newCategoryDestinationName = 'Kategori tidak valid';
            $this->newCategoryDestinationDeputy = 'N/A';
            return;
        }

        // 1. Coba ambil Unit Kerja dari Kategori yang Dipilih saat ini (Sub-Kategori)
        $unit = $category->unitKerjas->first();

        // 2. Jika Unit Kerja KOSONG, cari di PARENT Category
        if (!$unit && $category->parent) {
            $unit = $category->parent->unitKerjas->first();
        }
        
        // 3. Ekstrak Nama tujuan
        if ($unit) {
            $unitName = $unit->name ?? 'TANPA UNIT KERJA';
            $deputyName = $unit->deputy->name ?? 'TANPA DEPUTI';
            
            // Atur properti
            $this->newCategoryDestinationName = $unitName;
            $this->newCategoryDestinationDeputy = $deputyName;
            
        } else {
            // Jika tidak ada Unit Kerja ditemukan di kategori itu sendiri atau Parent-nya
            $this->newCategoryDestinationName = 'Belum Ditugaskan Unit Kerja';
            $this->newCategoryDestinationDeputy = 'Belum Ditugaskan Deputi';
        }
    }

    public function getGroupedAnalystsProperty()
    {
        $user = Auth::user();
        
        $query = User::role('analyst')->with(['unitKerja.deputy']); 

        if (!($user->hasRole('superadmin') || $user->hasRole('admin'))) {
            if ($user->hasRole('deputy') && $user->deputy_id) {
                 $query->whereHas('unitKerja', function($q) use ($user) {
                     $q->where('deputy_id', $user->deputy_id);
                 });
            } elseif ($user->unit_kerja_id) {
                 $query->where('unit_kerja_id', $user->unit_kerja_id);
            } else {
                 $query->where('id', 0);
            }
        }

        $allAnalysts = $query->orderBy('name')->get();

        $grouped = [];
        
        foreach ($allAnalysts as $analyst) {
            $deputyName = $analyst->unitKerja->deputy->name ?? 'TANPA DEPUTI';
            $unitName = $analyst->unitKerja->name ?? 'TANPA UNIT KERJA';
            
            $grouped[$deputyName][$unitName][] = $analyst;
        }

        return $grouped;
    }

    public function openDispositionModal($reportId) 
    {
        $report = Report::select('id', 'ticket_number')->findOrFail($reportId);
        
        $this->dispatchReportId = $reportId;
        $this->dispatchReportTicketNumber = $report->ticket_number;
        
        $this->currentAssignmentId = null; 
        
        $this->reset(['analystId', 'dispositionNotes']); 
        $this->dispatch('show-disposition-modal');
    }

    public function openEditDispositionModal($assignmentId)
    {
        $assignment = Assignment::with(['report'])->findOrFail($assignmentId);
        
        $this->currentAssignmentId = $assignment->id;
        $this->dispatchReportId = $assignment->report_id;
        $this->dispatchReportTicketNumber = $assignment->report->ticket_number;
        $this->analystId = $assignment->assigned_to_id;
        $this->dispositionNotes = $assignment->notes;

        $this->dispatch('show-disposition-modal'); 
    }

    public function resetDispositionData()
    {
        $this->reset([
            'dispatchReportId', 
            'dispatchReportTicketNumber',
            'analystId', 
            'dispositionNotes',
            'currentAssignmentId'
        ]);

        // $this->dispatch('close-disposition-modal');
    }

    public function submitDisposition()
    {
        // Mengambil Laporan dan User yang menugaskan
        $report = Report::findOrFail($this->dispatchReportId);
        $assigner = Auth::user();
        
        $this->validate([
            'analystId' => 'required|exists:users,id',
            'dispositionNotes' => 'nullable|string|max:500',
        ]);
        
        $analyst = \App\Models\User::findOrFail($this->analystId);
        $logDescription = '';
        $actionType = '';
        
        DB::beginTransaction();
        try {
            if ($this->currentAssignmentId) {
                // UPDATE LOGIC
                $assignment = Assignment::findOrFail($this->currentAssignmentId);
                $assignment->update([
                    'assigned_to_id' => $this->analystId,
                    'notes' => $this->dispositionNotes,
                ]);
                
                // --- PESAN TOAST YANG RINGKAS (UPDATE) ---
                $message = "Tugas berhasil diperbarui. Analis: {$analyst->name}.";
                
                $actionType = 'ASSIGNMENT_UPDATED';
                $logDescription = "Tugas laporan diperbarui. Ditugaskan kembali ke: {$analyst->name}. Catatan: {$this->dispositionNotes}";
                
            } else {
                // CREATE LOGIC
                Assignment::create([ 
                    'report_id' => $this->dispatchReportId,
                    'assigned_by_id' => Auth::id(),
                    'assigned_to_id' => $this->analystId,
                    'notes' => $this->dispositionNotes,
                    'status' => 'pending', 
                ]);
                
                // --- PESAN TOAST YANG RINGKAS (CREATE) ---
                $message = "Laporan telah ditugaskan ke Analis {$analyst->name}.";
                
                $actionType = 'REPORT_DISPATCHED';
                $logDescription = "Disposisi baru ke Analis: {$analyst->name}. Catatan: {$this->dispositionNotes}";
            }

            // --- 1. KIRIM NOTIFIKASI KE ANALIS (Pesan detail ada di file notifikasi) ---
            $analyst->notify(new \App\Notifications\NewAssignmentNotification($report, $assigner));

            // --- 2. TAMBAHKAN ACTIVITY LOG (Pesan detail untuk history) ---
            \App\Models\ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $actionType,
                'description' => $logDescription,
                'loggable_id' => $report->id,
                'loggable_type' => get_class($report),
            ]);

            DB::commit();
            $this->reset(['dispatchReportId', 'analystId', 'dispositionNotes', 'currentAssignmentId']);
            $this->dispatch('close-disposition-modal');
            $this->dispatch('swal:toast', message: $message, icon: 'success');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal Disposisi Laporan: ' . $e->getMessage());
            $this->dispatch('swal:toast', message: 'Gagal disposisi: ' . $e->getMessage(), icon: 'error');
        }
    }
    
    public function deleteAssignmentConfirm()
    {
        $assignmentId = $this->currentAssignmentId;
        
        if (!$assignmentId) {
            $this->dispatch('swal:toast', message: 'ID Tugas hilang.', icon: 'error');
            return;
        }
        
        try {
            $assignment = \App\Models\Assignment::with('assignedTo', 'report')->findOrFail($assignmentId);
            
            $this->confirmDeleteAssignmentId = $assignmentId;
            $this->confirmDeleteAssignmentTicket = $assignment->report->ticket_number ?? 'N/A';
            $this->confirmDeleteAssignmentAnalyst = $assignment->assignedTo->name ?? 'Analis';

            $this->dispatch('close-disposition-modal');
            
            $this->dispatch('show-delete-assignment-modal');
            
        } catch (\Exception $e) {
            $this->dispatch('swal:toast', message: 'Tugas tidak ditemukan.', icon: 'error');
            \Illuminate\Support\Facades\Log::error('Assignment not found during confirm: ' . $e->getMessage());
        }
    }

    public function deleteAssignment() 
    {
        $assignmentId = $this->confirmDeleteAssignmentId; 

        if (!$assignmentId) {
            $this->dispatch('swal:toast', message: 'Tugas tidak ditemukan atau ID hilang.', icon: 'error');
            $this->closeDeleteModal();
            return;
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $assignment = \App\Models\Assignment::findOrFail($assignmentId);
            $report = \App\Models\Report::findOrFail($assignment->report_id);
            
            $assignment->delete(); 
            
            // Atur ulang status Report hanya jika tidak ada tugas tersisa
            if ($report->assignments()->count() === 0) {
                $report->update(['status' => 'Proses verifikasi dan telaah']); 
            }

            \Illuminate\Support\Facades\DB::commit();
            $this->dispatch('swal:toast', message: 'Disposisi berhasil dibatalkan.', icon: 'success');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Delete Assignment Gagal: ' . $e->getMessage(), ['id' => $assignmentId]);
            $this->dispatch('swal:toast', message: 'Gagal membatalkan disposisi karena kesalahan sistem.', icon: 'error');
        }
        
        $this->reset(['confirmDeleteAssignmentId', 'confirmDeleteAssignmentTicket', 'confirmDeleteAssignmentAnalyst']);
        $this->resetPage(); 
        $this->dispatch('hide-delete-assignment-modal');
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedReports = $this->reports->pluck('id')->map(fn($item) => (string)$item)->toArray();
        } else {
            $this->selectedReports = [];
        }
    }
    
    public function updatedSelectedReports()
    {
        if (count($this->selectedReports) < $this->reports->count()) {
            $this->selectAll = false;
        }
    }

    public function massOpenDispositionModal()
    {
        if (empty($this->selectedReports)) {
            $this->dispatch('swal:toast', message: 'Pilih setidaknya satu laporan.', icon: 'warning');
            return;
        }
        $this->reset(['newAnalystId', 'dispositionNotes']);
        $this->dispatch('show-mass-disposition-modal');
    }
    
    public function massSubmitDisposition()
    {
        if (empty($this->selectedReports)) {
            $this->dispatch('swal:toast', message: 'Tidak ada laporan yang dipilih.', icon: 'warning');
            return;
        }
        
        $this->validate([
            'newAnalystId' => 'required|exists:users,id',
            'dispositionNotes' => 'nullable|string|max:500',
        ], [
            'newAnalystId.required' => 'Analis harus dipilih.'
        ]);
        
        $assigner = Auth::user();
        $analyst = \App\Models\User::findOrFail($this->newAnalystId);
        $count = 0;
        
        DB::beginTransaction();
        try {
            foreach ($this->selectedReports as $reportId) {
                $report = Report::find($reportId);
                
                if ($report) {
                    // ... (Logika create Assignment)
                    Assignment::create([ 
                        'report_id' => $reportId,
                        'assigned_by_id' => Auth::id(),
                        'assigned_to_id' => $this->newAnalystId,
                        'notes' => $this->dispositionNotes,
                        'status' => 'pending', 
                    ]);
                    
                    // --- 1. KIRIM NOTIFIKASI KE ANALIS ---
                    $analyst->notify(new \App\Notifications\NewAssignmentNotification($report, $assigner));

                    // --- 2. TAMBAHKAN ACTIVITY LOG ---
                    \App\Models\ActivityLog::create([
                        'user_id' => Auth::id(),
                        'action' => 'REPORT_DISPATCHED_MASS',
                        'description' => "Disposisi massal laporan #{$report->ticket_number} ke Analis: {$analyst->name}. Catatan: {$this->dispositionNotes}",
                        'loggable_id' => $report->id,
                        'loggable_type' => get_class($report),
                    ]);
                    
                    $count++;
                }
            }
            
            DB::commit();
            $this->reset(['selectedReports', 'selectAll', 'newAnalystId', 'dispositionNotes']);
            
            $message = "{$count} laporan berhasil ditugaskan ke Analis {$analyst->name}.";
            $this->dispatch('swal:toast', message: $message, icon: 'success');
            $this->dispatch('close-mass-disposition-modal');
            $this->resetPage();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal Disposisi Massal Laporan: ' . $e->getMessage());
            $this->dispatch('swal:toast', message: 'Gagal disposisi massal.' . $e->getMessage(), icon: 'error');
        }
    }

    public function massUpdateCategory()
    {
        if (empty($this->selectedReports)) {
            $this->dispatch('swal:toast', message: 'Pilih setidaknya satu laporan.', icon: 'warning');
            return;
        }
        
        $this->validate(['newCategoryId' => 'required|exists:categories,id'], [
            'newCategoryId.required' => 'Kategori harus dipilih.'
        ]);

        $count = 0;
        DB::beginTransaction();
        try {
            $newCategory = Category::with('unitKerjas.deputy', 'parent')->find($this->newCategoryId);
            
            // Tentukan Unit Kerja dan Deputi Tujuan BARU (Fall-back logic)
            $unitKerjaIdTujuan = $newCategory->unitKerjas->first()->id ?? null;
            $deputyIdTujuan = $newCategory->unitKerjas->first()->deputy->id ?? null;

            // Fallback: Jika Sub-Kategori tidak memiliki Unit Kerjas langsung, cari di Parent
            if (is_null($unitKerjaIdTujuan) && $newCategory->parent_id) {
                $parentCategory = Category::with('unitKerjas.deputy')->find($newCategory->parent_id);
                $unitKerjaIdTujuan = $parentCategory->unitKerjas->first()->id ?? null;
                $deputyIdTujuan = $parentCategory->unitKerjas->first()->deputy->id ?? null;
            }

            // Ambil nama tujuan baru untuk log
            $unitTujuanName = UnitKerja::find($unitKerjaIdTujuan)->name ?? 'T/A';
            $deputiTujuanName = Deputy::find($deputyIdTujuan)->name ?? 'T/A';


            foreach ($this->selectedReports as $reportId) {
                $report = Report::find($reportId);
                
                if ($report) {
                    $oldCategoryName = $report->category->name ?? 'T/A';
                    
                    // 1. Hapus penugasan analis lama
                    $report->assignments()->delete();
                    
                    // 2. Simpan Category, Unit Kerja, dan Deputi yang baru
                    $report->update([
                        'category_id' => $this->newCategoryId,
                        'unit_kerja_id' => $unitKerjaIdTujuan, 
                        'deputy_id' => $deputyIdTujuan, 
                    ]);
                    
                    // --- 3. TAMBAHKAN ACTIVITY LOG UNTUK SETIAP LAPORAN ---
                    $logDescription = "Kategori Laporan diubah dari '{$oldCategoryName}' menjadi '{$newCategory->name}'.";
                    $logDescription .= " Tugas Analis lama dibatalkan. Ditujukan ke Deputi: {$deputiTujuanName}, Unit: {$unitTujuanName}.";

                    \App\Models\ActivityLog::create([
                        'user_id' => Auth::id(),
                        'action' => 'CATEGORY_UPDATE_MASS',
                        'description' => $logDescription,
                        'loggable_id' => $report->id,
                        'loggable_type' => get_class($report),
                    ]);
                    // --- END ACTIVITY LOG ---
                    
                    $count++;
                }
            }
            
            DB::commit();
            $this->reset(['selectedReports', 'selectAll', 'newCategoryId']);
            $this->dispatch('close-mass-category-modal');
            
            $message = "{$count} laporan berhasil diperbarui. Kategori: {$newCategory->name}. Tugas lama dibatalkan.";
            $this->dispatch('swal:toast', message: $message, icon: 'success');
            
            $this->resetPage();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal Update Kategori Massal Laporan: ' . $e->getMessage());
            $this->dispatch('swal:toast', message: 'Gagal update kategori massal: Terjadi kesalahan sistem.', icon: 'error');
        }
    }

    protected function processReportsForBadge($reportsPaginator)
    {
        $reportsCollection = $reportsPaginator->getCollection();

        // Nilai Treshold dan Deadline
        $thresholdDate = Carbon::create(2025, 12, 01); // Treshold baru: 01 Des 2025
        $verificationDeadlineDays = 3;
        $now = Carbon::now();
        // Normalisasi waktu saat ini ke awal hari untuk perbandingan hari yang adil
        $nowStartOfDay = $now->copy()->startOfDay(); 

        $processedCollection = $reportsCollection->map(function ($report) use ($thresholdDate, $verificationDeadlineDays, $now, $nowStartOfDay) {
            
            $report->badge_class = '';
            $report->badge_text = '';
            $report->badge_tooltip = '';

            $createdAt = $report->created_at->copy()->startOfDay(); // Normalisasi CreatedAt
            
            // Kondisi Utama
            if ($createdAt->greaterThanOrEqualTo($thresholdDate) && $report->status === 'Proses verifikasi dan telaah') {
                
                // --- 1. HITUNG DEADLINE (3 HARI KERJA) MENGGUNAKAN LOOP MANUAL ---
                $deadline = $createdAt->copy();
                $daysAdded = 0;
                
                while ($daysAdded < $verificationDeadlineDays) {
                    $deadline->addDay();
                    if (!$deadline->isWeekend()) { 
                        $daysAdded++; 
                    }
                }
                // Deadline telah dihitung
                // ----------------------------------------------------------------------
                
                // Cek apakah waktu saat ini (start of day) belum melewati deadline
                if ($nowStartOfDay->lessThanOrEqualTo($deadline)) {
                    // KONDISI 1: BELUM TERLAMBAT
                    
                    // --- 2. HITUNG SISA HARI KERJA DARI SEKARANG HINGGA DEADLINE (LOOP MANUAL) ---
                    $remainingDays = 1;
                    $currentCheckDay = $nowStartOfDay->copy(); 
                    
                    // Mulai dari hari ini (startOfDay) dan cek sampai TEPAT sebelum $deadline.
                    // Jika $nowStartOfDay sama dengan $deadline, berarti tersisa 1 hari kerja (yaitu hari ini).
                    while ($currentCheckDay->lessThanOrEqualTo($deadline)) {
                        if (!$currentCheckDay->isWeekend()) { 
                            $remainingDays++;
                        }
                        $currentCheckDay->addDay();
                    }
                    
                    // Karena loop ini menghitung HARI INI sebagai sisa 1, kita harus mengurangi 1 
                    // jika kita ingin menghitung sisa hari kerja PENUH.
                    // Tapi untuk tujuan 'dalam X hari', kita biarkan saja (misalnya sisa 3 hari kerja).
                    
                    // SET CLASS & TOOLTIP
                    $report->badge_class = 'bg-info-lt';
                    $report->badge_tooltip = "Butuh Verifikasi dalam $remainingDays hari kerja"; 

                    if ($remainingDays <= 1) { 
                        $report->badge_class = 'bg-warning-lt';
                        $report->badge_text = 'AKHIR';
                    } else {
                        $report->badge_text = "SISA $remainingDays HR";
                    }
                    
                } else {
                    // KONDISI 2: TERLAMBAT
                    
                    // --- 3. HITUNG HARI TERLAMBAT (HARI KERJA YANG TELAH TERLEWAT) ---
                    $overdueDays = 0;
                    $currentCheckDay = $deadline->copy()->addDay(); // Mulai hitung keterlambatan dari hari setelah deadline
                    
                    // Loop hingga TEPAT hari ini
                    while ($currentCheckDay->lessThanOrEqualTo($nowStartOfDay)) {
                        if (!$currentCheckDay->isWeekend()) { 
                            $overdueDays++;
                        }
                        $currentCheckDay->addDay();
                    }

                    if ($overdueDays <= 0) {
                        // Ini kasus edge di hari weekend setelah deadline, kita anggap HARI AKHIR.
                        $report->badge_class = 'bg-warning-lt';
                        $report->badge_text = 'AKHIR';
                        $report->badge_tooltip = "Batas Verifikasi Tepat Hari Ini";
                    } else {
                        // SET CLASS & TOOLTIP
                        $report->badge_class = 'bg-danger-lt';
                        $report->badge_text = "TELAT $overdueDays HR";
                        $report->badge_tooltip = "Terlambat Verifikasi lebih dari $overdueDays hari kerja";
                    }
                }
                
            }
            return $report;
        });

        $reportsPaginator->setCollection($processedCollection);
        return $reportsPaginator;
    }
}
