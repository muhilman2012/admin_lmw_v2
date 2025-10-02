<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\LaporanForwarding;
use App\Models\Report;
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

    protected $queryString = ['search' => ['except' => '']];

    public function render()
    {
        $query = LaporanForwarding::query()
            // Eager load relasi untuk tampilan
            ->with(['laporan.reporter', 'institution']) 
            ->orderBy('sent_at', 'desc');

        // Logika Pencarian
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            
            $query->where(function ($q) use ($searchTerm) {
                
                // Kondisi utama, cari di kolom langsung (Complaint ID)
                $q->where('complaint_id', 'like', $searchTerm);
                  
                // Cari di Nomor Tiket (Relasi Laporan)
                $q->orWhereHas('laporan', function ($qLaporan) use ($searchTerm) {
                    $qLaporan->where('ticket_number', 'like', $searchTerm)
                             // Cari di Nama Pelapor (Relasi Berlapis: Laporan -> Reporter)
                             ->orWhereHas('reporter', function ($qReporter) use ($searchTerm) {
                                 $qReporter->where('name', 'like', $searchTerm);
                             });
                });
                
                // Cari di Nama Institusi Tujuan
                $q->orWhereHas('institution', function ($qInstansi) use ($searchTerm) {
                    $qInstansi->where('name', 'like', $searchTerm);
                });
            });
        }
        
        $forwardings = $query->paginate($this->perPage);

        return view('livewire.admin.forwarded-reports', [
            'forwardings' => $forwardings,
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
}
