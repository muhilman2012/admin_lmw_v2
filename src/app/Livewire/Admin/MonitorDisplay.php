<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\RegistrationQueue;
use Illuminate\Support\Facades\DB;

class MonitorDisplay extends Component
{
    protected $listeners = ['echo:kiosk-channel,AntreanDipanggil' => '$refresh'];

    public function render()
    {
        $activeCounters = RegistrationQueue::whereDate('visit_date', now())
            ->whereIn('status', ['calling', 'serving', 'served'])
            ->select('counter_number', DB::raw('count(*) as total'))
            ->groupBy('counter_number')
            ->orderBy('counter_number', 'asc')
            ->get();

        return view('livewire.admin.monitor-display', compact('activeCounters'));
    }
}
