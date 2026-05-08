<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\RegistrationQueue;
use Carbon\Carbon;

class DaftarPengaduHariIni extends Component
{
    public $search = '';

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function render()
    {
        $data = RegistrationQueue::whereDate('visit_date', Carbon::today())
            ->where(function($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('queue_number', 'like', '%' . $this->search . '%')
                      ->orWhere('nik', 'like', '%' . $this->search . '%');
            })
            ->orderBy('queue_number', 'asc')
            ->get();

        return view('livewire.admin.daftar-pengadu-hari-ini', [
            'queues' => $data
        ]);
    }
}
