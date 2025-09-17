<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Report;
use App\Models\Reporter;
use App\Models\Category;
use App\Models\Document;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportsController extends Controller
{
    protected array $middleware = ['auth'];

    public function show($uuid)
    {
        $report = Report::where('uuid', $uuid)->firstOrFail();
        
        $reportLogs = ActivityLog::with('user')
                                ->where('loggable_id', $report->id)
                                ->where('loggable_type', Report::class)
                                ->orderBy('created_at', 'desc')
                                ->get();

        return view('pages.reports.show', compact('report', 'reportLogs'));
    }

    public function index()
    {
        return view('pages.reports.index');
    }

    public function create($reporter_id = null)
    {
        $reporter = null;
        if ($reporter_id) {
            $reporter = Reporter::findOrFail($reporter_id);
        }
        
        $categories = Category::with('children')->whereNull('parent_id')->get();
        
        return view('pages.reports.create', compact('reporter', 'categories'));
    }

    public function store(Request $request)
    {
        // 1. Validasi Data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nik' => 'required|string|max:16|unique:reporters,nik',
            'source' => 'required|string',
            'email' => 'nullable|email|unique:reporters,email',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'required|string',
            'subject' => 'required|string',
            'location' => 'required|string',
            'event_date' => 'nullable|date',
            'category_id' => 'required|exists:categories,id',
            'details' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:20048',
        ]);
        
        $nikDigits = substr($validated['nik'], 6, 2);
        $gender = ($nikDigits > 40) ? 'P' : 'L';

        DB::beginTransaction();

        try {
            $reporter = Reporter::firstOrCreate(
                ['nik' => $validated['nik']],
                array_merge($validated, [
                    'gender' => $gender,
                ])
            );

            $report = Report::create([
                'reporter_id' => $reporter->id,
                'ticket_number' => $this->generateTicketNumber(),
                'uuid' => (string) Str::uuid(),
                'subject' => $validated['subject'],
                'details' => $validated['details'],
                'location' => $validated['location'],
                'event_date' => $validated['event_date'],
                'source' => $validated['source'],
                'status' => 'Proses verifikasi dan telaah',
                'response' => 'Laporan pengaduan Saudara dalam proses verifikasi & penelaahan.',
                'category_id' => $validated['category_id'],
            ]);
            
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('reports/attachments/' . $report->id, 'public');
                    
                    Document::create([
                        'report_id' => $report->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                    ]);
                }
            }
            
            DB::commit();

            return redirect()->route('reports.index')->with('success', 'Laporan berhasil dibuat.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat laporan: ' . $e->getMessage())->withInput();
        }
    }

    private function generateTicketNumber()
    {
        do {
            $ticketNumber = random_int(1000000, 9999999);
            
            $existingReport = Report::where('ticket_number', $ticketNumber)->first();
            
        } while ($existingReport);
        
        return (string) $ticketNumber;
    }
}