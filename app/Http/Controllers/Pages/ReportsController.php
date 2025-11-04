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
use App\Notifications\NewAssignmentNotification;
use App\Notifications\AnalysisSubmittedNotification;
use App\Notifications\AnalysisReviewedNotification;
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
                    ->orderBy('created_at', 'desc'); // Urutkan untuk kemudahan mencari yang terbaru
            }
        ])->where('uuid', $uuid)->firstOrFail();

        if (request()->has('read')) {
            app(\App\Http\Controllers\Pages\NotificationController::class)->markSpecificAsReadById(request('read'));
        }
        
        // 2. Ambil assignment yang relevan (hanya ada satu)
        $currentAssignment = $report->assignments->first();
        
        // 3. Ambil data statis untuk dropdown/modal
        $institutions = Institution::orderBy('name')->get();
        // untuk modal quick action:
        $statusTemplates = StatusTemplate::all();
        $documentTemplates = DocumentTemplate::all();
        $user = Auth::user();
        $availableAnalysts = $this->getGroupedAnalysts($user);
        
        // 4. Pisahkan logs untuk kemudahan pemanggilan (jika Anda tidak menggunakan $report->activityLogs langsung di view)
        $reportLogs = $report->activityLogs; 

        return view('pages.reports.show', compact('report', 'reportLogs', 'institutions', 'currentAssignment', 'statusTemplates', 'documentTemplates', 'availableAnalysts'));
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

        $defaultSource = null;
        if ($request->has('source_default')) {
            $defaultSource = $request->source_default; 
        }

        $statusTemplates = StatusTemplate::all();
        $documentTemplates = DocumentTemplate::all();
        $categories = Category::with(['children' => function ($q) {
                $q->active(); // Filter sub-kategori agar hanya yang aktif yang dimuat
            }])
            ->whereNull('parent_id')
            ->active() // Filter kategori utama
            ->get();
        
        return view('pages.reports.create', compact('reporter', 'categories', 'statusTemplates', 'documentTemplates'))->with('defaultSource', $defaultSource);
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

                // --- LOGIKA PENENTUAN DISTRIBUSI ---
                $unitKerjaId = null;
                $deputyId = null;
                $categoryId = $validated['category_id'];

                // 1. Dapatkan kategori yang dipilih dan identifikasi Parent ID
                $selectedCategory = Category::find($categoryId);
                
                $targetCategoryId = $categoryId;
                if ($selectedCategory && $selectedCategory->parent_id) {
                    // Jika ini adalah sub-kategori, gunakan ID Parent-nya untuk distribusi
                    $targetCategoryId = $selectedCategory->parent_id;
                    Log::info('Menggunakan ID kategori Parent untuk distribusi.', ['original_id' => $categoryId, 'parent_id' => $targetCategoryId]);
                }

                // 2. Cari Unit Kerja berdasarkan Target Category ID (Parent atau Child)
                // Lakukan pencarian unit kerja hanya berdasarkan ID target
                $categoryForDistribution = Category::with('unitKerjas.deputy')->find($targetCategoryId);

                if ($categoryForDistribution && $categoryForDistribution->unitKerjas->count() > 0) {
                    // Ambil Unit Kerja pertama sebagai Unit Distribusi utama
                    $distributionUnit = $categoryForDistribution->unitKerjas->first(); 
                    
                    $unitKerjaId = $distributionUnit->id;
                    $deputyId = $distributionUnit->deputy->id ?? null;
                } else {
                    Log::warning('Unit Kerja tidak ditemukan untuk kategori distribusi.', ['target_category_id' => $targetCategoryId]);
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
            'status' => 'required|string|max:255',
            'classification' => 'required|string|max:255',
            'response' => 'required|string',
        ]);

        // Cari laporan
        $report = Report::where('uuid', $uuid)->firstOrFail();

        $isBenefitProvided = $request->has('is_benefit_provided'); // true atau false

        // Mulai transaksi
        DB::beginTransaction();
        try {
            // Perbarui data laporan
            $report->update([
                'status' => $request->status,
                'classification' => $request->classification,
                'response' => $request->response,
                'is_benefit_provided' => $isBenefitProvided, 
            ]);

            // Buat log aktivitas
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'update_response',
                'description' => 'Berhasil mengedit tanggapan laporan' . ($isBenefitProvided ? ' dan menandai pengadu telah menerima manfaat.' : '.'),
                'loggable_id' => $report->id,
                'loggable_type' => Report::class,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Tanggapan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal memperbarui tanggapan laporan: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan perubahan. Silakan coba lagi.');
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
        
        // Dapatkan Assignee (Orang yang menugaskan) untuk dikirimi notifikasi
        $assigner = $assignment->assignedBy; // Asumsi relasi assignedBy() ada di model Assignment
        
        // 3. Mulai Transaksi dan Update Database
        DB::beginTransaction();
        try {
            // A. Update klasifikasi di tabel reports utama
            $report->update([
                'classification' => $validated['classification'],
            ]);

            // B. Update hasil analisis di tabel assignments
            // Menggunakan data_get() untuk akses aman ke 'notes'
            $assignment->update([
                'analyst_worksheet' => $validated['analyst_worksheet'],
                'notes' => data_get($validated, 'notes', null),
                'status' => 'submitted', // Ubah ke status code 'submitted' yang lebih konsisten
            ]);
            
            // C. Catat aktivitas
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'analysis_submitted', // Gunakan action code yang konsisten
                'description' => 'Hasil analisis laporan telah dikirim untuk persetujuan.',
                'loggable_id' => $report->id,
                'loggable_type' => Report::class,
            ]);
            
            // D. KIRIM NOTIFIKASI KE ASSIGNER (Pengirim Tugas)
            if ($assigner) {
                $assigner->notify(new \App\Notifications\AnalysisSubmittedNotification($report));
            }

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
                            ->whereIn('status', ['submitted', 'Menunggu Persetujuan']) // Asumsi: status submitted yang dikirim Analis
                            ->latest() // Ambil assignment yang terbaru
                            ->firstOrFail();

        // Mulai transaksi
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $action = $request->action;
            $note = $request->notes;

            $analyst = $assignment->assignedTo; 
            $notificationAction = '';
            $description = '';

            if ($action === 'approve') {
                $assignment->update([
                    'status' => 'approved',
                    'notes' => $note,
                ]);
                $notificationAction = 'approved';
                $description = 'Analisis laporan disetujui';

            } else { // action === 'revise'
                $assignment->update([
                    'status' => 'Perlu Perbaikan',
                    'notes' => $note,
                ]);
                $notificationAction = 'revision_needed';
                $description = 'Analisis laporan memerlukan perbaikan';
            }

            // 2. KIRIM NOTIFIKASI KEPADA ANALIS
            if ($analyst) {
                $analyst->notify(new AnalysisReviewedNotification($report, $user, $notificationAction));
            }
            
            // 3. Buat log aktivitas
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
                        'user_id' => Auth::id(),
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
                        'user_id' => Auth::id(),
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

    public function getReportsByKk(Request $request)
    {
        $kkNumber = $request->input('kk_number');
        
        // Cek Nomor KK yang tidak valid atau kosong
        if (empty($kkNumber) || $kkNumber === '-') {
            return response()->json([
                'html' => '<div class="alert alert-warning">Nomor KK tidak valid atau kosong.</div>'
            ], 200);
        }

        try {
            // 1. Dapatkan semua NIK terkait KK ini
            $relatedNiks = Reporter::where('kk_number', $kkNumber)->pluck('nik')->unique();
            
            // Jika tidak ada NIK yang terkait dengan KK tersebut, kembalikan array kosong
            if ($relatedNiks->isEmpty()) {
                 $reports = collect();
            } else {
                // 2. Ambil laporan berdasarkan NIK yang terkait
                $reports = Report::whereHas('reporter', function ($query) use ($relatedNiks) {
                    $query->whereIn('nik', $relatedNiks);
                })
                ->with('reporter') // Pastikan relasi reporter dimuat
                ->orderByDesc('created_at')
                ->get();
            }

            return response()->json([
                'html' => view('pages.reports.partials.kk_history_modal_content', compact('reports', 'kkNumber'))->render()
            ]);

        } catch (\Exception $e) {
            // Log error secara detail di sisi server
            Log::error('AJAX Riwayat KK Gagal (HTTP 500): ' . $e->getMessage(), [
                'kk_number' => $kkNumber,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Kembalikan respons error yang akan ditangkap oleh JS fetch()
            return response()->json([
                'html' => '<div class="alert alert-danger">Kesalahan Internal: Gagal memproses data riwayat. Lihat log server.</div>'
            ], 500); 
        }
    }

    /**
     * Helper: Mendapatkan Analis yang berada di bawah cakupan Deputi/Unit Kerja user.
     */
    private function getGroupedAnalysts(User $user)
    {
        $query = User::role('analyst')->with(['unitKerja.deputy']); 

        if ($user->hasRole('deputy') && $user->deputy_id) {
            $query->whereHas('unitKerja', function($q) use ($user) {
                $q->where('deputy_id', $user->deputy_id);
            });
        } elseif ($user->unit_kerja_id) {
            $query->where('unit_kerja_id', $user->unit_kerja_id);
        } elseif (!($user->hasRole('superadmin') || $user->hasRole('admin'))) {
             // Jika bukan Admin/SA dan tidak punya scope Unit/Deputi, tidak tampilkan Analis
             $query->where('id', 0); 
        }

        $allAnalysts = $query->orderBy('name')->get();

        $grouped = [];
        foreach ($allAnalysts as $analyst) {
            $deputyName = $analyst->unitKerja->deputy->name ?? 'TANPA DEPUTI';
            $unitName = $analyst->unitKerja->name ?? 'TANPA UNIT KERJA';
            
            $grouped[$deputyName][$unitName][] = $analyst;
        }

        return $grouped;
    }

    public function assignQuick(Request $request, $uuid)
    {
        $request->validate([
            'analyst_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $report = Report::where('uuid', $uuid)->firstOrFail();
        $analyst = User::findOrFail($request->analyst_id);
        $assigner = Auth::user();

        // Pastikan laporan belum memiliki tugas (race condition check)
        if ($report->assignments()->exists()) {
            return redirect()->back()->with('error', 'Laporan ini sudah memiliki petugas analis yang ditugaskan.');
        }

        DB::beginTransaction();
        try {
            // 1. Buat Assignment Baru
            Assignment::create([ 
                'report_id' => $report->id,
                'assigned_by_id' => $assigner->id,
                'assigned_to_id' => $analyst->id,
                'notes' => $request->notes,
                'status' => 'pending', 
            ]);
            
            // 2. Kirim Notifikasi
            $analyst->notify(new NewAssignmentNotification($report, $assigner));
            
            // 3. Tambahkan Activity Log
            $description = "Disposisi ke Analis: {$analyst->name}. Catatan: " . ($request->notes ?? 'Tidak ada catatan.');
            ActivityLog::create([
                'user_id' => $assigner->id,
                'action' => 'DISPOSITION_QUICK',
                'description' => $description,
                'loggable_id' => $report->id,
                'loggable_type' => Report::class,
            ]);

            DB::commit();
            return redirect()->back()->with('success', "Laporan #{$report->ticket_number} berhasil ditugaskan ke Analis {$analyst->name}.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal Disposisi Cepat: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memproses disposisi cepat.');
        }
    }
}