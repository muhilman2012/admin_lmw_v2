<?php

namespace App\Http\Controllers\Api;

use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reporter;
use App\Models\Report;
use App\Models\ActivityLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ReporterController extends Controller
{
    /**
     * Memeriksa eligibilitas NIK untuk laporan baru.
     * Aturan: Pengadu hanya bisa membuat 1 laporan dalam 20 hari.
     *
     * @param  string  $nik
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkEligibility(string $nik)
    {
        // Cek apakah NIK memiliki format yang benar
        if (strlen($nik) !== 16 || !is_numeric($nik)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format NIK tidak valid.'
            ], 400);
        }

        $reporter = Reporter::where('nik', $nik)->first();

        if (!$reporter) {
            return response()->json([
                'status' => 'success',
                'message' => 'NIK eligible untuk membuat laporan baru.',
                'eligible' => true
            ]);
        }

        $latestReport = Report::where('reporter_id', $reporter->id)
                            ->latest('created_at')
                            ->first();

        if (!$latestReport) {
            return response()->json([
                'status' => 'success',
                'message' => 'NIK eligible untuk membuat laporan baru.',
                'eligible' => true
            ]);
        }

        $daysSinceLastReport = Carbon::parse($latestReport->created_at)->diffInDays();
        $isEligible = $daysSinceLastReport > 20;

        $message = $isEligible ? 'NIK eligible untuk membuat laporan baru.' : 'NIK tidak eligible karena laporan terakhir dibuat dalam 20 hari terakhir.';

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'eligible' => $isEligible
        ]);
    }

    /**
     * Menerima dan menyimpan data reporter.
     * Jika pengadu dengan NIK yang sama sudah ada, kembalikan ID-nya.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkOrStore(Request $request)
    {
        // Sanitasi input terlebih dahulu
        $this->sanitizeInput($request);

        try {
            // Validasi input
            $validatedData = $request->validate([
                'nik' => 'required|string|max:16',
                'name' => 'required|string',
                'phone_number' => 'nullable|string',
                'email' => 'nullable|email',
                'kk_number' => 'nullable|string|max:16',
                'address' => 'nullable|string',
                'ktp_document_id' => 'nullable|exists:documents,id',
            ]);

            // Cek sumber API dari header
            $apiSource = $request->header('X-API-SOURCE');
            $isFromCheckin = $apiSource === 'checkin';
            
            $reporter = Reporter::where('nik', $validatedData['nik'])->first();
        
            if ($reporter) {
                // Pengadu sudah ada, perbarui datanya
                $reporter->fill($validatedData)->save();
                
                // Tambahkan logika checkin_status HANYA jika request dari mesin check-in
                if ($isFromCheckin) {
                    $reporter->checkin_status = 'pending_report_creation';
                    $reporter->save();

                    ActivityLog::create([
                        'user_id' => Auth::id(),
                        'action' => 'check_in_reporter',
                        'description' => "Pengadu dengan NIK {$reporter->nik} check-in kembali melalui mesin.",
                        'loggable_id' => $reporter->id,
                        'loggable_type' => Reporter::class,
                    ]);
                } else {
                    // Log aktivitas untuk update data dari bot
                    ActivityLog::create([
                        'user_id' => Auth::id(),
                        'action' => 'update_reporter_data',
                        'description' => "Data pengadu dengan NIK {$reporter->nik} diperbarui melalui {$apiSource}.",
                        'loggable_id' => $reporter->id,
                        'loggable_type' => Reporter::class,
                    ]);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Data Pengadu sudah terdaftar dan diperbarui.',
                    'reporter_id' => $reporter->id
                ], 200);
            }

            // ... (logika untuk pengadu baru, logika ini juga harus dibedakan) ...
            $tanggalLahirRaw = substr($validatedData['nik'], 6, 2);
            $genderDigit = intval($tanggalLahirRaw);
            $validatedData['gender'] = ($genderDigit > 40) ? 'P' : 'L';
            
            // Set status checkin berdasarkan sumber API
            $checkinStatus = $isFromCheckin ? 'pending_report_creation' : 'not_checked_in';
            $validatedData['checkin_status'] = $checkinStatus;

            $newReporter = Reporter::create($validatedData);

            // Tambahkan log aktivitas untuk CREATE
            $action = $isFromCheckin ? 'create_reporter_checkin' : 'create_reporter_whatsapp';
            $description = "Pengadu baru dengan NIK {$newReporter->nik} berhasil dibuat dari {$apiSource}.";

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'description' => $description,
                'loggable_id' => $newReporter->id,
                'loggable_type' => Reporter::class,
            ]);

            return response()->json([
                'status' => 'success',
                'code'  => '200',
                'message' => 'Data Pengadu berhasil disimpan dan dimasukkan ke antrean.',
                'reporter_id' => $newReporter->id
            ], 200);
            
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Data tidak valid.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Gagal memproses data pengadu: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Gagal memproses data pengadu: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Helper: Sanitasi input untuk mencegah XSS dan SQL Injection.
     * Menggunakan strip_tags dan htmlspecialchars.
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
}
