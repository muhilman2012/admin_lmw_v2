<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\LaporanForwarding;
use App\Models\Institution;
use App\Models\UnitKerja;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ForwardedReports extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 20;
    public $sortField = 'sent_at';
    public $sortDirection = 'desc';
    protected $paginationTheme = 'bootstrap';

    public $filterInstitution = '';
    public $filterLaporStatus = '';
    public $filterInternalStatus = '';
    public $filterDateRange = '';
    public $filterUser = '';

    public $institutions = [];
    public $laporStatuses = ['Belum Terverifikasi', 'Dalam Proses', 'Selesai', 'Diarsipkan', 'Ditolak', 'API Error'];

    protected $queryString = ['search' => ['except' => '']];

    protected $listeners = ['retryForwarding', 'deleteForwarding', 'resetFilters'];

    // Mount untuk inisialisasi data master
    public function mount()
    {
        // Ambil data Institusi dari database
        $this->institutions = Institution::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Method yang dipanggil oleh wire:click untuk mengubah field sorting.
     */
    public function sortBy($field)
    {
        // 1. Cek jika field yang di-klik sama dengan field yang sedang aktif
        if ($this->sortField === $field) {
            // Jika sama, balikkan arah sorting
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // Jika berbeda, atur field baru dan reset arah ke 'asc'
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
        $this->resetPage();
    }

    // Reset filter
    public function resetFilters()
    {
        $this->reset(['filterInstitution', 'filterLaporStatus', 'filterInternalStatus', 'filterDateRange']);
        $this->resetPage();
    }

    // Ambil Data Reports (Property yang akan dipanggil di render)
    public function getForwardingsProperty()
    {
        $user = Auth::user();
        $query = LaporanForwarding::query()
            ->with(['laporan.reporter', 'institution', 'user', 'disposisi']);

        // 1. APLIKASIKAN RBAC SCOPE
        $query = $this->applyForwardingScope($query, $user);

        // 2. LOGIKA FILTER

        // Filter Pencarian Global (Nomor Tiket, Pelapor, Institusi Tujuan)
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('complaint_id', 'like', $searchTerm)
                  ->orWhereHas('laporan', function ($qLaporan) use ($searchTerm) {
                      $qLaporan->where('ticket_number', 'like', $searchTerm)
                               ->orWhereHas('reporter', function ($qReporter) use ($searchTerm) {
                                   $qReporter->where('name', 'like', $searchTerm);
                               });
                  })
                  ->orWhereHas('institution', function ($qInstansi) use ($searchTerm) {
                      $qInstansi->where('name', 'like', $searchTerm);
                  });
            });
        }
        
        // Filter Instansi Tujuan
        if ($this->filterInstitution) {
            $query->where('institution_id', $this->filterInstitution);
        }
        
        // Filter Status LAPOR! (dari kolom lapor_status_name)
        if ($this->filterLaporStatus) {
            $query->where('lapor_status_name', $this->filterLaporStatus);
        }
        
        // Filter Status Internal (dari kolom status)
        if ($this->filterInternalStatus) {
            $query->where('status', $this->filterInternalStatus);
        }

        // Filter Tanggal Diteruskan (sent_at)
        if ($this->filterDateRange) {
            list($start, $end) = explode(' - ', $this->filterDateRange);
            $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
            $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();
            $query->whereBetween('sent_at', [$startDate, $endDate]);
        }

        // 3. ORDER DAN PAGINASI
        $sortField = $this->sortField;
        $sortDirection = $this->sortDirection;

        if ($sortField === 'laporan_id') {
            // Sorting berdasarkan Nomor Tiket
            $query->leftJoin('reports', 'laporan_forwardings.laporan_id', '=', 'reports.id')
                  ->orderBy('reports.ticket_number', $sortDirection)
                  ->select('laporan_forwardings.*');
                  
        } elseif ($sortField === 'institution.name') {
            // Sorting berdasarkan Nama Institusi Tujuan
            $query->leftJoin('institutions', 'laporan_forwardings.institution_id', '=', 'institutions.id')
                  ->orderBy('institutions.name', $sortDirection)
                  ->select('laporan_forwardings.*');
                  
        } elseif ($sortField === 'reporter_name') {
             // Sorting berdasarkan Nama Pelapor (Relasi Berlapis)
             $query->leftJoin('reports', 'laporan_forwardings.laporan_id', '=', 'reports.id')
                   ->leftJoin('reporters', 'reports.reporter_id', '=', 'reporters.id')
                   ->orderBy('reporters.name', $sortDirection)
                   ->select('laporan_forwardings.*');
                   
        } else {
            // Sorting Kolom Langsung (sent_at, next_check_at, dll.)
            $query->orderBy($sortField, $sortDirection);
        }
        
        return $query->paginate($this->perPage);
    }

    // === METODE SCOPE BERBASIS PERAN (RBAC) ===
    private function applyForwardingScope(Builder $query, $user): Builder
    {
        if ($user->hasRole(['superadmin', 'admin'])) {
            return $query; // Lihat semua
        }
        
        // Analis hanya melihat laporan yang dia teruskan
        if ($user->hasRole('analyst')) {
            return $query->where('user_id', $user->id);
        }
        
        // Logika untuk Deputy/Asdep/Karo/Unit
        // Dapatkan unit/deputi ID yang relevan untuk user ini
        
        $unitIds = [];
        $isDeputy = $user->hasRole('deputy');
        
        if ($isDeputy && $user->deputy_id) {
            // Jika Deputi, ambil semua unit di bawahnya
            $unitIds = UnitKerja::where('deputy_id', $user->deputy_id)->pluck('id');
        } elseif (!$isDeputy && $user->unit_kerja_id) {
             // Jika User Unit Biasa (Asdep/Karo), ambil unitnya sendiri
             $unitIds = [$user->unit_kerja_id];
        }

        if ($unitIds) {
            // Ambil semua category_id yang ditugaskan ke Unit Ids ini
            $categoryIds = DB::table('category_unit')->whereIn('unit_kerja_id', $unitIds)->pluck('category_id');
            
            // Filter LaporanForwarding berdasarkan category_id laporan terkait
            return $query->whereHas('laporan', function ($qReport) use ($categoryIds) {
                $qReport->whereIn('category_id', $categoryIds);
            });
        }

        // Jika tidak ada peran atau unit yang relevan
        return $query->where('id', 0);
    }

    public function render()
    {
        $forwardings = $this->getForwardingsProperty();

        return view('livewire.admin.forwarded-reports', [
            'forwardings' => $this->forwardings,
            'institutions' => $this->institutions,
            'laporStatuses' => $this->laporStatuses, 
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;
        $this->resetPage();
    }

    public function retryForwarding($forwardingId)
    {
        try {
            $forwarding = LaporanForwarding::findOrFail($forwardingId);

            $forwarding->update([
                'status' => 'terkirim',
                'error_message' => null,
                'next_check_at' => Carbon::now()->addMinutes(5),
                'sent_at' => Carbon::now(),
            ]);

            $this->dispatch('session:success', [
                'message' => 'Penerusan laporan berhasil dijadwalkan ulang. Status akan diperiksa dalam 5 menit.'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('session:success', [
                'message' => 'Gagal menjadwalkan ulang: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Konfirmasi penghapusan entri forwarding.
     */
    public function deleteForwardingConfirm($forwardId)
    {
        // Menggunakan SweetAlert/Swal dispatch untuk konfirmasi
        $this->dispatch(
            'swal:confirm',
            title: 'Hapus Entri Forwarding?',
            text: 'Entri ini akan dihapus dari daftar. Ini tidak akan membatalkan laporan di API LAPOR!. Yakin?',
            confirmButtonText: 'Ya, Hapus!',
            onConfirmed: 'deleteForwarding',
            onConfirmedParams: [$forwardId]
        );
    }

    /**
     * Menghapus entri forwarding setelah konfirmasi.
     */
    public function deleteForwarding($forwardId)
    {
        try {
            $forward = LaporanForwarding::findOrFail($forwardId); 
            
            $forward->delete();
            
            $this->resetPage();

            $this->dispatch('swal:toast', message: 'Entri forwarding berhasil dihapus.', icon: 'success');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus entri forwarding: ' . $e->getMessage(), ['id' => $forwardId]);
            
            $errorMessage = 'Gagal menghapus entri. Kemungkinan data terkait laporan lain. Detail: ' . $e->getMessage();
            
            $this->dispatch('swal:toast', message: $errorMessage, icon: 'error');
        }
    }
}
