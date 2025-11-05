<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reporter;

class ReportersController extends Controller
{
    protected array $middleware = ['auth'];

    public function index()
    {
        $pendingReporters = Reporter::where('checkin_status', 'pending_report_creation')->get();
                                     
        return view('pages.reporters.index', compact('pendingReporters'));
    }
}
