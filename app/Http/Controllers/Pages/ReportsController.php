<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Report;
use App\Models\Reporter;
use App\Models\Category;
use App\Models\UnitKerja;
use App\Models\Document;
use App\Models\Assignment;
use App\Models\ActivityLog;
use App\Models\StatusTemplate;
use App\Models\DocumentTemplate;
use App\Models\Institution;
use App\Models\LaporanForwarding;
use App\Services\LaporForwardingService;
use Hashids\Hashids;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ReportsController extends Controller
{
    protected array $middleware = ['auth'];

    public function show($uuid)
    {
        // 1. Muat laporan, ActivityLog, dan Assignment yang ditujukan ke user saat ini
        $report = Report::with([
            'reporter', // Contoh relasi lain
            // Memuat log aktivitas normal
            'activityLogs' => function($query) {
                $query->with('user')->orderBy('created_at', 'desc');
            },
            // PENTING: Memuat hanya assignment yang ditugaskan kepada user saat ini
            'assignments' => function($query) {
            $query->with(['assignedBy', 'assignedTo']) // EAGER LOAD USER RELATIONS
                  ->where('assigned_to_id', Auth::id());
            }
        ])->where('uuid', $uuid)->firstOrFail();
        
        // 2. Ambil assignment yang relevan (hanya ada satu)
        $currentAssignment = $report->assignments->first();
        
        // 3. Ambil data statis untuk dropdown/modal
        $institutions = Institution::orderBy('name')->get();
        // Anda mungkin membutuhkan ini untuk modal quick action:
        $statusTemplates = StatusTemplate::all();
        $documentTemplates = DocumentTemplate::all();
        
        // 4. Pisahkan logs untuk kemudahan pemanggilan (jika Anda tidak menggunakan $report->activityLogs langsung di view)
        $reportLogs = $report->activityLogs; 

        return view('pages.reports.show', compact('report', 'reportLogs', 'institutions', 'currentAssignment', 'statusTemplates', 'documentTemplates'));
    }

    public function index()
    {
        return view('pages.reports.index');
    }

    public function create(Request $request)
    {
        $reporter = null;
        if ($request->has('reporter_id')) {
            $hashids = new Hashids('your-salt-string', 10);
            $reporter_id = $hashids->decode($request->reporter_id);
            
            if (!empty($reporter_id)) {
                $reporter = Reporter::findOrFail($reporter_id[0]);
            }
        }

        $statusTemplates = StatusTemplate::all();
        $documentTemplates = DocumentTemplate::all();
        $categories = Category::with('children')->whereNull('parent_id')->get();
        
        return view('pages.reports.create', compact('reporter', 'categories', 'statusTemplates', 'documentTemplates'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'nik' => 'required|string|min:16|max:16',
                'kk_number' => 'nullable|string',
                'source' => 'required|string',
                'email' => 'nullable|email',
                'phone_number' => 'nullable|string|max:20',
                'address' => 'required|string',
                'subject' => 'required|string',
                'location' => 'nullable|string',
                'event_date' => 'nullable|date_format:d/m/Y',
                'category_id' => 'required|exists:categories,id',
                'details' => 'required|string',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:20480',
                'status' => 'nullable|string',
                'response' => 'nullable|string',
            ], [
                'name.required' => 'Kolom Nama Lengkap wajib diisi.',
                'nik.required' => 'Kolom NIK wajib diisi.',
                'nik.min' => 'Nomor NIK harus terdiri dari 16 digit.',
                'nik.max' => 'Nomor NIK harus terdiri dari 16 digit.',
                'kk_number.min' => 'Nomor KK harus terdiri dari 16 digit.',
                'kk_number.max' => 'Nomor KK harus terdiri dari 16 digit.',
                'source.required' => 'Kolom Sumber Pengaduan wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'address.required' => 'Kolom Alamat Lengkap wajib diisi.',
                'subject.required' => 'Kolom Judul Laporan wajib diisi.',
                'event_date.date_format' => 'Format Tanggal Kejadian tidak valid. Gunakan format dd/mm/yyyy.',
                'category_id.required' => 'Kolom Kategori wajib diisi.',
                'category_id.exists' => 'Kategori yang dipilih tidak valid.',
                'details.required' => 'Kolom Detail Laporan wajib diisi.',
                'attachments.array' => 'Lampiran harus berupa array file.',
                'attachments.*.file' => 'Setiap lampiran harus berupa file.',
                'attachments.*.max' => 'Ukuran file lampiran tidak boleh melebihi 20MB.',
            ]);

            Log::info('Validasi form berhasil.', ['validated_data' => $validated]);

            $nikDigits = substr($validated['nik'], 6, 2);
            $gender = ($nikDigits > 40) ? 'P' : 'L';

            DB::beginTransaction();

            try {
                $reporter = Reporter::updateOrCreate(
                    ['nik' => $validated['nik']],
                    array_merge($validated, [
                        'gender' => $gender,
                        'checkin_status' => 'report_created',
                    ])
                );

                Log::info('Data pengadu berhasil disimpan.', ['reporter_id' => $reporter->id]);

                // --- LOGIKA PENENTUAN DISTRIBUSI BARU ---
                $unitKerjaId = null;
                $deputyId = null;

                // 1. Cari Unit Kerja berdasarkan Category ID
                $category = Category::with('unitKerjas.deputy')->find($validated['category_id']);

                if ($category && $category->unitKerjas->count() > 0) {
                    // Ambil Unit Kerja pertama sebagai Unit Distribusi utama
                    $distributionUnit = $category->unitKerjas->first(); 
                    
                    $unitKerjaId = $distributionUnit->id;
                    $deputyId = $distributionUnit->deputy->id ?? null;
                }

                $defaultStatus = 'Proses verifikasi dan telaah';
                $defaultResponse = 'Laporan pengaduan Saudara dalam proses verifikasi & penelaahan.';

                if (isset($validated['event_date'])) {
                    $validated['event_date'] = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['event_date'])->format('Y-m-d');
                }

                $report = Report::create([
                    'reporter_id' => $reporter->id,
                    'ticket_number' => $this->generateTicketNumber(),
                    'uuid' => (string) Str::uuid(),
                    'subject' => $validated['subject'],
                    'details' => $validated['details'],
                    'location' => $validated['location'],
                    'event_date' => $validated['event_date'],
                    'source' => $validated['source'],
                    'status' => $request->status ?? $defaultStatus,
                    'response' => $request->response ?? $defaultResponse,
                    'category_id' => $validated['category_id'],
                    'unit_kerja_id' => $unitKerjaId,
                    'deputy_id' => $deputyId,
                ]);
                Log::info('Laporan utama berhasil dibuat.', ['report_id' => $report->id, 'uuid' => $report->uuid, 'unit_kerja_id' => $unitKerjaId]);

                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        $path = $file->store('documents', 'complaints');
                        
                        Document::create([
                            'report_id' => $report->id,
                            'file_path' => $path,
                            'description' => 'Dokumen Pengaduan',
                        ]);
                        Log::info('Dokumen berhasil diunggah dan disimpan.', ['file_path' => $path]);
                    }
                }

                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'create_report',
                    'description' => 'Berhasil membuat laporan dengan nomor tiket ' . $report->ticket_number,
                    'loggable_id' => $report->id,
                    'loggable_type' => Report::class,
                ]);
                
                DB::commit();
                Log::info('Transaksi database berhasil dicommit.');

                return response()->json(['success' => true, 'uuid' => $report->uuid]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Gagal menyimpan laporan. Transaksi dirollback.', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json(['error' => 'Terjadi kesalahan saat menyimpan laporan.'], 500);
            }

        } catch (ValidationException $e) {
            Log::warning('Validasi form gagal.', ['errors' => $e->errors()]);
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function edit($uuid)
    {
        $report = Report::with(['reporter', 'documents'])->where('uuid', $uuid)->firstOrFail();
        
        $categories = Category::with('children')->whereNull('parent_id')->get();
        
        $statusTemplates = StatusTemplate::all();
        $documentTemplates = DocumentTemplate::all();
        
        return view('pages.reports.edit', compact('report', 'categories', 'statusTemplates', 'documentTemplates'));
    }

    public function update(Request $request, $uuid)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'nik' => 'required|string|min:16|max:16',
                'kk_number' => 'nullable|string',
                'source' => 'required|string',
                'email' => 'nullable|email',
                'phone_number' => 'nullable|string|max:20',
                'address' => 'required|string',
                'subject' => 'required|string',
                'location' => 'nullable|string',
                'event_date' => 'nullable|date_format:d/m/Y',
                'category_id' => 'required|exists:categories,id',
                'details' => 'required|string',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:20480',
            ]);
        } catch (ValidationException $e) {
            Log::warning('Validasi form gagal saat update.', ['errors' => $e->errors()]);
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        DB::beginTransaction();

        try {
            $report = Report::where('uuid', $uuid)->firstOrFail();
            
            $reporter = $report->reporter;
            $reporter->update([
                'name' => $validated['name'],
                'nik' => $validated['nik'],
                'kk_number' => $validated['kk_number'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'address' => $validated['address'],
            ]);

            $eventDate = isset($validated['event_date']) ? Carbon::createFromFormat('d/m/Y', $validated['event_date'])->format('Y-m-d') : null;
            $report->update([
                'subject' => $validated['subject'],
                'details' => $validated['details'],
                'location' => $validated['location'],
                'event_date' => $eventDate,
                'source' => $validated['source'],
                'category_id' => $validated['category_id'],
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('documents', 'complaints');
                    
                    Document::create([
                        'report_id' => $report->id,
                        'file_path' => $path,
                        'description' => 'Dokumen Pengaduan Tambahan',
                    ]);
                    Log::info('Dokumen berhasil diunggah dan disimpan.', ['file_path' => $path]);
                }
            }
            
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'update_report',
                'description' => 'Berhasil mengedit laporan dengan nomor tiket ' . $report->ticket_number,
                'loggable_id' => $report->id,
                'loggable_type' => Report::class,
            ]);

            DB::commit();
            Log::info('Laporan berhasil diperbarui.', ['report_id' => $report->id, 'uuid' => $report->uuid]);

            return redirect()->route('reports.show', ['uuid' => $report->uuid])
                             ->with('success', 'Laporan berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal memperbarui laporan. Transaksi dirollback.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan perubahan laporan.');
        }
    }

    public function updateResponse(Request $request, $uuid)
    {
        // Validasi input dari modal
        $request->validate([
            'status' => 'required|string',
            'classification' => 'required|string',
            'response' => 'required|string',
        ]);

        // Cari laporan
        $report = Report::where('uuid', $uuid)->firstOrFail();

        // Mulai transaksi
        DB::beginTransaction();
        try {
            // Perbarui data laporan
            $report->update([
                'status' => $request->status,
                'classification' => $request->classification,
                'response' => $request->response,
            ]);

            // Buat log aktivitas
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'update_response',
                'description' => 'Berhasil mengedit tanggapan laporan',
                'loggable_id' => $report->id,
                'loggable_type' => Report::class,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Tanggapan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal memperbarui tanggapan laporan: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan perubahan.');
        }
    }

    public function submitAnalysis(Request $request, $uuid)
    {
        // 1. Validasi input
        $validated = $request->validate([
            'classification' => 'required|string',
            'analyst_worksheet' => 'required|string|max:10000',
            'notes' => 'nullable|string|max:1000',
        ], [
            'classification.required' => 'Klasifikasi aduan wajib diisi.',
            'analyst_worksheet.required' => 'Hasil analisis wajib diisi.',
            'analyst_worksheet.max' => 'Hasil analisis tidak boleh melebihi 10000 karakter.',
        ]);

        // 2. Cari Laporan dan Assignment yang ditugaskan kepada user saat ini
        $report = Report::where('uuid', $uuid)->firstOrFail();
        $assignment = Assignment::where('report_id', $report->id)
                                 ->where('assigned_to_id', Auth::id())
                                 ->first();
        
        // Keamanan: Pastikan tugas ditemukan
        if (!$assignment) {
            return redirect()->back()->with('error', 'Anda tidak memiliki tugas aktif untuk laporan ini.');
        }
        
        // 3. Mulai Transaksi dan Update Database
        DB::beginTransaction();
        try {
            // A. Update klasifikasi di tabel reports utama
            $report->update([
                'classification' => $validated['classification'],
            ]);

            // B. Update hasil analisis di tabel assignments
            $assignment->update([
                'analyst_worksheet' => $validated['analyst_worksheet'],
                'notes' => $validated['notes'],
                'status' => 'Menunggu Persetujuan', // Status tugas berubah menjadi siap untuk disetujui
            ]);

            // C. Catat aktivitas
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'submit_analysis',
                'description' => 'Hasil analisis laporan telah dikirim untuk persetujuan.',
                'loggable_id' => $report->id,
                'loggable_type' => Report::class,
            ]);

            DB::commit();
            return redirect()->route('reports.show', $report->uuid)
                             ->with('success', 'Analisis berhasil dikirim untuk persetujuan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan hasil analisis: ' . $e->getMessage(), ['uuid' => $uuid]);
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat menyimpan analisis.');
        }
    }

    public function approveAnalysis(Request $request, $uuid)
    {
        // Validasi input dari modal
        $request->validate([
            'action' => 'required|in:approve,revise',
            'notes' => 'nullable|string',
        ]);

        // Cari laporan
        $report = Report::where('uuid', $uuid)->firstOrFail();

        // Cari tugas (assignment) yang terkait dengan laporan
        // Asumsi: Hanya ada satu tugas aktif per laporan yang sedang dianalisis
        $assignment = Assignment::where('report_id', $report->id)
                                ->where('assigned_to_id', auth()->id()) // Pastikan hanya bisa disetujui oleh user yang ditugaskan
                                ->whereIn('status', ['Analisis', 'Menunggu Persetujuan']) // Asumsi: Status tugas yang relevan
                                ->firstOrFail();

        // Mulai transaksi
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $action = $request->action;
            $note = $request->notes;

            if ($action === 'approve') {
                // Perbarui status tugas menjadi 'Disetujui'
                $assignment->update([
                    'status' => 'Disetujui',
                    'notes' => $note,
                ]);

                // Opsional: Perbarui status analisis di tabel laporan utama jika diperlukan
                $report->update([
                    'analysis_status' => 'Disetujui',
                    'status' => 'Selesai' // Atau status akhir lainnya
                ]);

                $description = 'Analisis laporan disetujui';
            } else { // action === 'revise'
                // Perbarui status tugas menjadi 'Perlu Perbaikan'
                $assignment->update([
                    'status' => 'Perlu Perbaikan',
                    'notes' => $note,
                ]);

                // Opsional: Perbarui status analisis di tabel laporan utama
                $report->update(['analysis_status' => 'Perlu Perbaikan']);

                $description = 'Analisis laporan memerlukan perbaikan';
            }
            
            // Buat log aktivitas
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => $action . '_analysis',
                'description' => $description . '. Catatan: ' . ($note ?? 'Tidak ada catatan.'),
                'loggable_id' => $report->id,
                'loggable_type' => Report::class,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Persetujuan analisis berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal memproses persetujuan analisis: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memproses persetujuan.');
        }
    }

    public function forwardToLapor(Request $request, $uuid)
    {
        // 1. Validasi input dari modal
        $request->validate([
            'institution_id' => 'required|string|exists:institutions,id',
            'additional_notes' => 'nullable|string|max:1000',
            'exclude_reporter_data' => 'nullable|string', // Checkbox mengirim nilai 'on' jika dicentang
        ]);

        // 2. Cari laporan dan relasinya
        $report = Report::with('reporter')->where('uuid', $uuid)->firstOrFail();
        $isAnonymous = $request->filled('exclude_reporter_data'); // Jika checkbox ada isinya ('on'), maka anonim

        $institutionName = Institution::where('id', $request->institution_id)->value('name');

        $service = new LaporForwardingService();

        DB::beginTransaction();
        try {
            // A. Langkah 1: Upload Dokumen
            $uploadedDocumentIds = $service->uploadDocuments($report);

            // B. Langkah 2: Kirim Laporan Utama ke LAPOR!
            $apiFirstResponse = $service->sendToLapor($report, $uploadedDocumentIds, $isAnonymous);

            if ($apiFirstResponse['success']) {
                $complaintId = $apiFirstResponse['complaint_id'];
                
                if (empty($complaintId)) {
                    // Jika sukses dari Service Class TAPI Complaint ID kosong
                    throw new \Exception("API LAPOR! berhasil merespons, namun Complaint ID tidak ditemukan. Penerusan gagal.");
                }

                // Simpan Complaint ID ke laporan utama
                $report->lapor_complaint_id = $complaintId;
                $report->save();

                // C. Langkah 3: Kirim Reject Request (Forward)
                $apiSecondResponse = $service->sendRejectRequest($complaintId, $request->institution_id, $request->additional_notes);

                if ($apiSecondResponse['success']) {
                    // Berhasil: Catat ke Activity Log dan LaporanForwarding
                    
                    ActivityLog::create([
                        'user_id' => auth()->id(),
                        'action' => 'forward_to_lapor',
                        'description' => 'Laporan berhasil diteruskan ke instansi (' . $institutionName . ') melalui LAPOR!',
                        'loggable_id' => $report->id,
                        'loggable_type' => Report::class,
                    ]);

                    // Buat entri forwarding yang sukses
                    LaporanForwarding::create([
                        'laporan_id' => $report->id,
                        'institution_id' => $request->institution_id,
                        'reason' => $request->additional_notes,
                        'status' => 'terkirim',
                        'complaint_id' => $complaintId,
                        'is_anonymous' => $isAnonymous,
                        'sent_at' => now(),
                    ]);

                    DB::commit();
                    return redirect()->back()->with('success', 'Laporan berhasil diteruskan ke instansi tujuan.');
                } else {
                    // Gagal di Langkah 3 (Reject Request)
                    
                    // Buat entri forwarding yang gagal (untuk retry)
                    LaporanForwarding::create([
                        'laporan_id' => $report->id,
                        'institution_id' => $request->institution_id,
                        'reason' => $request->additional_notes,
                        'status' => 'gagal_forward',
                        'error_message' => $apiSecondResponse['error'],
                        'complaint_id' => $complaintId,
                        'is_anonymous' => $isAnonymous,
                        'scheduled_at' => now()->addHour(), // Jadwalkan untuk dicoba lagi
                    ]);
                    
                    DB::commit(); // Commit log dan forwarding gagal
                    return redirect()->back()->with('error', 'Gagal meneruskan ke instansi tujuan: ' . $apiSecondResponse['error']);
                }
            } else {
                // Gagal di Langkah 2 (Send to Lapor)
                DB::rollBack();
                return redirect()->back()->with('error', 'Gagal mengirim laporan utama ke LAPOR!: ' . $apiFirstResponse['error']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Terjadi kesalahan saat teruskan ke instansi: ' . $e->getMessage(), ['uuid' => $uuid]);
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat memproses penerusan laporan.');
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