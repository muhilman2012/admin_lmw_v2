<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Reporter;

class Reporters extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 20;
    protected $paginationTheme = 'bootstrap';

    public function render()
    {
        $reporters = Reporter::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('nik', 'like', '%' . $this->search . '%')
                      ->orWhere('phone_number', 'like', '%' . $this->search . '%');
            })
            ->where('checkin_status', 'not_checked_in')
            ->paginate($this->perPage);

        return view('livewire.admin.reporters', [
            'reporters' => $reporters,
        ]);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;
        $this->resetPage();
    }
}
