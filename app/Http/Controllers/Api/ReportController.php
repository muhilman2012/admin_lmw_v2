<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Reporter;
use App\Models\Report;
use App\Models\Document;
use App\Models\Category;
use App\Models\ActivityLog;
use App\Services\GeminiCategoryService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        // 1. Sanitasi Input: Hapus karakter berbahaya dari request
        $this->sanitizeInput($request);

        // 2. Validasi input
        try {
            $validatedData = $request->validate([
                'reporter_id' => 'required|exists:reporters,id',
                'document_ids' => 'required|array',
                'document_ids.*' => 'required|exists:documents,id',
                'report_details.subject' => ['required', 'string', 'max:255', 'regex:/^[\pL\pM\pN\s\-\.\,\(\)]+$/u'],
                'report_details.details' => ['required', 'string', 'max:10000'],
                'report_details.location' => ['nullable', 'string', 'max:255', 'regex:/^[\pL\pM\pN\s\-\.\,\(\)]+$/u'],
                'report_details.event_date' => 'nullable|date',
                'report_details.source' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Data yang dikirim tidak valid.',
                'errors' => $e->errors(),
            ], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $geminiService = new GeminiCategoryService();
            $categoryName = $geminiService->classifyReport(
                $validatedData['report_details']['details']
            );
            
            $category = Category::where('name', $categoryName)->first();
            $categoryId = $category ? $category->id : Category::where('name', 'Lainnya')->first()->id;

            $ticketNumber = $this->generateUniqueTicketNumber();
            $uuid = Str::uuid();

            // 4. Buat entri baru di tabel 'reports'
            $report = Report::create([
                'ticket_number' => $ticketNumber,
                'uuid' => $uuid,
                'reporter_id' => $validatedData['reporter_id'],
                'subject' => $validatedData['report_details']['subject'],
                'details' => $validatedData['report_details']['details'],
                'location' => $validatedData['report_details']['location'],
                'event_date' => $validatedData['report_details']['event_date'],
                'source' => $validatedData['report_details']['source'],
                'status' => 'Proses verifikasi dan telaah',
                'response' => 'Laporan pengaduan Saudara dalam proses verifikasi & penelaahan.',
                'category_id' => $categoryId, // Menggunakan kategori dari Gemini
            ]);
            
            // Ubah status check-in pengadu setelah laporan selesai dibuat
            $reporter = Reporter::find($validatedData['reporter_id']);
            if ($reporter) {
                $reporter->checkin_status = 'report_created';
                $reporter->save();
            }

            // 5. Hubungkan dokumen dengan laporan
            Document::whereIn('id', $validatedData['document_ids'])
                    ->update(['report_id' => $report->id]);
            
            Log::info('Laporan baru berhasil disimpan.', [
                'report_id' => $report->id,
                'ticket_number' => $report->ticket_number,
                'reporter_id' => $report->reporter_id,
                'category' => $categoryName,
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(), // ID user yang melakukan aksi, null jika dari bot
                'action' => 'create_report',
                'description' => "Laporan baru dengan nomor tiket {$report->ticket_number} berhasil dibuat.",
                'loggable_id' => $report->id,
                'loggable_type' => Report::class,
            ]);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Laporan berhasil disimpan.',
                'data' => [
                    'ticket_number' => $report->ticket_number,
                    'uuid' => $report->uuid,
                    'category' => $categoryName,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan laporan: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Terjadi kesalahan internal. Silakan periksa log server untuk detailnya.'
            ], 500);
        }
    }

    /**
     * Helper: Menghasilkan nomor tiket 7 digit yang unik.
     */
    private function generateUniqueTicketNumber(): string
    {
        do {
            $ticketNumber = str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);
        } while (Report::where('ticket_number', $ticketNumber)->exists());

        return $ticketNumber;
    }

    /**
     * Helper: Menyamarkan informasi pengenal pribadi (PII) dari teks.
     * @param string $text Teks yang akan di-masking.
     * @return string Teks yang sudah di-masking.
     */
    private function maskPii(string $text): string
    {
        // PII Masking: NIK (16 digit angka)
        $text = preg_replace('/\b\d{16}\b/', '[NIK_MASKED]', $text);

        // PII Masking: Nomor Telepon (pola umum Indonesia)
        $text = preg_replace('/(\+62|0)\d{2,4}[-.\s]?\d{4}[-.\s]?\d{3,4}/', '[PHONE_MASKED]', $text);

        // PII Masking: Alamat Email
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL_MASKED]', $text);
        
        // PII Masking: Nama (contoh: mengganti nama yang berada di awal teks laporan)
        $text = preg_replace('/(nama|saya)\s+adalah\s+\b([a-zA-Z\s]+)\b/', 'saya adalah [NAMA_MASKED]', $text);

        // PII Masking: Alamat Lengkap (polanya lebih kompleks, ini adalah regex sederhana)
        $text = preg_replace('/\b(jalan|jl|gang|rt|rw|kelurahan|kecamatan|desa)\b.*?\b(no|rumah|blok)\b\s*\d+/i', '[ADDRESS_MASKED]', $text);

        return $text;
    }

    public function checkIfReportExists(string $ticketNumber)
    {
        $exists = Report::where('ticket_number', $ticketNumber)->exists();
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => ['exists' => $exists]
        ]);
    }

    /**
     * Helper: Sanitasi input untuk mencegah XSS dan SQL Injection.
     */
    private function sanitizeInput(Request $request): void
    {
        $input = $request->all();
        array_walk_recursive($input, function(&$item, $key) {
            if (is_string($item)) {
                $item = strip_tags($item);
                $item = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
            }
        });
        $request->replace($input);
    }

    /**
     * Memverifikasi NIK terhadap laporan yang sudah ada.
     */
    public function verifyReporter(string $ticketNumber, Request $request)
    {
        $request->validate([
            'nik' => 'required|digits:16',
        ]);
        
        $report = Report::where('ticket_number', $ticketNumber)
                        ->whereHas('reporter', function ($query) use ($request) {
                            $query->where('nik', $request->nik);
                        })
                        ->first();

        $verified = (bool) $report;
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => ['verified' => $verified]
        ]);
    }

    /**
     * Mengecek status pengaduan setelah verifikasi berhasil.
     */
    public function checkStatus(string $ticketNumber)
    {
        $report = Report::with('reporter')
                        ->where('ticket_number', $ticketNumber)
                        ->first();

        if (!$report) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Laporan tidak ditemukan.'
            ], 404);
        }
    
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => [
                'ticket_number' => $report->ticket_number,
                'nama_pengadu' => $report->reporter->name,
                'tanggal_laporan' => $report->created_at,
                'status_laporan' => $report->status,
                'tanggapan' => $report->response,
            ]
        ], 200);
    }

    /**
     * Mengecek eligibilitas untuk mengirim dokumen tambahan.
     *
     * @param  string  $ticketNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkDocumentEligibility(string $ticketNumber)
    {
        // Cari laporan berdasarkan nomor tiket
        $report = Report::where('ticket_number', $ticketNumber)->first();

        if (!$report) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Laporan dengan nomor tiket tersebut tidak ditemukan.'
            ], 404);
        }

        // Aturan: Dokumen tambahan bisa dikirim jika statusnya "Menunggu kelengkapan data dukung dari Pelapor"
        $isEligible = ($report->status === 'Menunggu kelengkapan data dukung dari Pelapor');

        $message = $isEligible ? 'Laporan ini dapat menerima dokumen tambahan.' : 'Laporan ini tidak dapat menerima dokumen tambahan saat ini.';

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => $message,
            'data' => [
                'eligible' => $isEligible
            ]
        ], 200);
    }
}
