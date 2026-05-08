<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\RegistrationQueue;

class MonitorHistoryList extends Component
{
    protected $listeners = [
        'echo:kiosk-channel,AntreanDipanggil' => '$refresh',
        'refreshComponent' => '$refresh'
    ];

    public function render()
    {
        // Ambil 5 antrean terakhir yang dipanggil (calling/serving/served)
        $history = RegistrationQueue::whereDate('visit_date', now())
            ->whereIn('status', ['calling', 'serving', 'served'])
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        return view('livewire.admin.monitor-history-list', compact('history'));
    }
}
