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
     * [LEGACY] Memeriksa eligibilitas NIK untuk laporan baru (Hanya cek 20 hari).
     * Endpoint: GET /api/reporters/check-eligibility/{nik}
     */
    public function checkEligibility(string $nik)
    {
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
     * [V2] Memeriksa eligibilitas NIK (20 hari), Nomor HP (Maks 5x/Bulan), dan Validasi Dukcapil.
     * Endpoint: POST /api/reporters/check-eligibility-v2
     */
    public function checkEligibilityV2(Request $request, \App\Services\DukcapilService $dukcapilService)
    {
        $validated = $request->validate([
            'nik' => 'required|string|size:16|regex:/^[0-9]+$/',
            'name' => 'required|string',
            'address' => 'required|string',
            'phone_number' => 'required|string|regex:/^628[0-9]+$/'
        ], [
            'nik.size' => 'Format NIK tidak valid, harus 16 digit.',
            'nik.regex' => 'Format NIK hanya boleh berisi angka.',
            'name.required' => 'Parameter Nama diperlukan.',
            'address.required' => 'Parameter Alamat diperlukan.',
            'phone_number.required' => 'Parameter Nomor HP diperlukan.',
            'phone_number.regex' => 'Nomor HP tidak valid. Harus diawali dengan 628 dan hanya berisi angka.'
        ]);

        $nik = $validated['nik'];
        $name = $validated['name'];
        $address = $validated['address'];
        $phoneNumber = $validated['phone_number'];

        $isInternalValid = true;
        $eligibilityMessage = 'Memenuhi syarat rentang waktu (belum ada aduan dalam 20 hari terakhir).';
        $phoneMessage = 'Memenuhi syarat kuota laporan nomor HP.';

        // 1. Pengecekan Aturan 20 Hari (NIK)
        $reporter = Reporter::where('nik', $nik)->first();

        if ($reporter) {
            $latestReport = Report::where('reporter_id', $reporter->id)
                                ->latest('created_at')
                                ->first();

            if ($latestReport) {
                $daysSinceLastReport = Carbon::parse($latestReport->created_at)->diffInDays();
                
                if ($daysSinceLastReport <= 20) {
                    $isInternalValid = false;
                    $eligibilityMessage = 'NIK Anda sudah tercatat membuat laporan dalam 20 hari terakhir.';
                }
            }
        }

        // 2. Pengecekan Kuota Nomor HP (Maks 5x dalam 30 hari terakhir)
        $reportCountThisMonth = Report::whereHas('reporter', function ($query) use ($phoneNumber) {
            $query->where('phone_number', $phoneNumber);
        })->where('created_at', '>=', Carbon::now()->subDays(30))->count();

        // Masukkan count ke dalam pesan sukses default
        $phoneMessage = "Memenuhi syarat kuota laporan nomor HP ({$reportCountThisMonth}/5).";

        if ($reportCountThisMonth >= 5) {
            $isInternalValid = false;
            // Masukkan count ke dalam pesan gagal
            $phoneMessage = "Nomor HP ini telah mencapai batas maksimal ({$reportCountThisMonth}/5 laporan) dalam 30 hari terakhir.";
        }

        // Gagal di aturan internal -> Stop & kembalikan error 422
        if (!$isInternalValid) {
            return response()->json([
                'status' => 'error',
                'eligible' => false,
                'messages' => [
                    'eligibility_validation' => $eligibilityMessage,
                    'phone_validation'       => $phoneMessage,
                    'dukcapil_validation'    => 'Validasi Dukcapil dilewati karena tidak memenuhi syarat internal.'
                ]
            ], 422);
        }

        // 3. Validasi API Dukcapil
        $verification = $dukcapilService->verifyIdentity($nik, $name, $address);

        if (!$verification['is_valid']) {
            return response()->json([
                'status' => 'error',
                'eligible' => false,
                'messages' => [
                    'eligibility_validation' => $eligibilityMessage,
                    'phone_validation'       => $phoneMessage,
                    'dukcapil_validation'    => $verification['message']
                ]
            ], 422); 
        }

        // 4. Lolos Semua Validasi
        return response()->json([
            'status' => 'success',
            'eligible' => true,
            'messages' => [
                'eligibility_validation' => $eligibilityMessage,
                'phone_validation'       => $phoneMessage,
                'dukcapil_validation'    => 'Data kependudukan valid dan terverifikasi.'
            ]
        ], 200);
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
            
            $action = '';
            $description = '';
            $statusMessage = '';
            
            // Atur status checkin_status berdasarkan sumber API
            $checkinStatus = $isFromCheckin ? 'pending_report_creation' : 'not_checked_in';

            if ($reporter) {
                $reporter->fill($validatedData);
                $reporter->checkin_status = $checkinStatus;
                $reporter->save();
                
                if ($isFromCheckin) {
                    $action = 'check_in_reporter';
                    $description = "Pengadu dengan NIK {$reporter->nik} check-in kembali melalui mesin.";
                    $statusMessage = 'Data Pengadu sudah terdaftar dan dimasukkan ke antrean.';
                } else {
                    $action = 'update_reporter_data';
                    $description = "Data pengadu dengan NIK {$reporter->nik} diperbarui melalui {$apiSource}.";
                    $statusMessage = 'Data Pengadu sudah terdaftar dan diperbarui.';
                }

                return response()->json([
                    'status' => 'success',
                    'message' => $statusMessage,
                    'reporter_id' => $reporter->id
                ], 200);

            } else {
                $tanggalLahirRaw = substr($validatedData['nik'], 6, 2);
                $genderDigit = intval($tanggalLahirRaw);
                $validatedData['gender'] = ($genderDigit > 40) ? 'P' : 'L';
                
                $validatedData['checkin_status'] = $checkinStatus;

                $newReporter = Reporter::create($validatedData);

                $action = $isFromCheckin ? 'create_reporter_checkin' : 'create_reporter_whatsapp';
                $description = "Pengadu baru dengan NIK {$newReporter->nik} berhasil dibuat dari {$apiSource}.";
                $statusMessage = 'Data Pengadu berhasil disimpan dan dimasukkan ke antrean.';

                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => $action,
                    'description' => $description,
                    'loggable_id' => $newReporter->id,
                    'loggable_type' => Reporter::class,
                ]);

                return response()->json([
                    'status' => 'success',
                    'code' => '200',
                    'message' => $statusMessage,
                    'reporter_id' => $newReporter->id
                ], 200);
            }
            
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
