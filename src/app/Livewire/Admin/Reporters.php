<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Reporter;
use Hashids\Hashids;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class Reporters extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 20;
    protected $paginationTheme = 'bootstrap';

    public function render()
    {
        $today = Carbon::today();
        $reporters = Reporter::query()
            ->whereDate('created_at', $today)
            ->where('checkin_status', 'pending_report_creation')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('nik', 'like', '%' . $this->search . '%')
                      ->orWhere('phone_number', 'like', '%' . $this->search . '%');
            })
            
            ->paginate($this->perPage);

        return view('livewire.admin.reporters', [
            'reporters' => $reporters,
            'hashids' => new Hashids('your-salt-string', 10),
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

    public function create($reporter_uuid = null)
    {
        $reporter = null;
        if ($reporter_uuid) {
            $reporter = Reporter::where('uuid', $reporter_uuid)->firstOrFail();
        }

        $categories = Category::with('children')->whereNull('parent_id')->get();
        
        return view('pages.reports.create', compact('reporter', 'categories'));
    }
}
