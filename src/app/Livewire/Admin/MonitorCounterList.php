<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\RegistrationQueue;
use Illuminate\Support\Facades\DB;

class MonitorCounterList extends Component
{
    protected $listeners = [
        'echo:kiosk-channel,AntreanDipanggil' => '$refresh',
        'refreshComponent' => '$refresh'
    ];

    public function render()
    {
        // Ambil data loket aktif dan jumlah yang dilayani hari ini
        $counters = RegistrationQueue::whereDate('visit_date', now())
            ->whereIn('status', ['calling', 'serving', 'served'])
            ->select('counter_number', DB::raw('count(*) as total'))
            ->groupBy('counter_number')
            ->orderBy('counter_number', 'asc')
            ->get();

        return view('livewire.admin.monitor-counter-list', compact('counters'));
    }
}
