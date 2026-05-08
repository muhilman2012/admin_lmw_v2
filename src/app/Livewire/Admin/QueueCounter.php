<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\RegistrationQueue;

class QueueCounter extends Component
{
    public function render()
    {
        $count = RegistrationQueue::whereDate('visit_date', now()->toDateString())
            ->where('status', 'checked_in')
            ->count();

        return view('livewire.admin.queue-counter', compact('count'));
    }
}