<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with(['user', 'loggable'])
            ->when($request->search, function($query, $search) {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            })
            ->when($request->start_date, function($query, $start_date) {
                $query->whereDate('created_at', '>=', $start_date);
            })
            ->when($request->end_date, function($query, $end_date) {
                $query->whereDate('created_at', '<=', $end_date);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.logs.activity', compact('logs'));
    }
}