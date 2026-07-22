<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RegistrationQueue;
use App\Events\AntreanDipanggil;
use App\Models\Reporter;
use App\Models\Document;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class KioskController extends Controller
{
    public function index()
    {
        return view('pages.kiosk.index');
    }

    public function checkData(Request $request)
    {
        $search = trim($request->search);
    
        \Illuminate\Support\Facades\Log::channel('kiosk')->info('KIOSK_CHECK_REQUEST_IN', ['search' => $search]);

        if (!$search) {
            return response()->json(['status' => 'error', 'message' => 'Input tidak boleh kosong.'], 400);
        }

        $today = \Illuminate\Support\Carbon::today()->toDateString();

        try {
            $data = \App\Models\RegistrationQueue::where('visit_date', $today)
                ->where(function($q) use ($search) {
                    $q->where('registration_number', $search)
                    ->orWhere('nik', $search);
                })->first();

            // 1. Validasi: Data Tidak Ditemukan
            if (!$data) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Kode atau NIK ' . $search . ' tidak ditemukan untuk jadwal hari ini.'
                ], 404);
            }

            // 2. Validasi: Mencegah Double Check-in (Perubahan Utama)
            // Jika status sudah checked_in atau served, tolak pendaftaran ulang
            if ($data->status === 'checked_in' || $data->status === 'served') {
                $waktuCheckin = $data->updated_at ? $data->updated_at->format('H:i') : '--:--';
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data ini sudah melakukan Check-in pada pukul ' . $waktuCheckin . '. Silakan tunggu panggilan petugas di area ruang tunggu.'
                ], 422); // 422 Unprocessable Entity
            }

            // 3. Respon Sukses: Kirim data ke frontend untuk auto-fill form
            return response()->json([
                'status' => 'success', 
                'message' => 'Data ditemukan.',
                'data' => [
                    'registration_number' => $data->registration_number,
                    'nik' => $data->nik,
                    'name' => strtoupper($data->name),
                    'phone' => $data->phone,
                    'email' => $data->email,
                    'address' => $data->address,
                    'subject' => $data->subject,
                ]
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('kiosk')->error('KIOSK_CHECK_DB_ERROR: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'status' => 'error', 
                'message' => 'Terjadi gangguan pada sistem database.'
            ], 500);
        }
    }

    public function finalize(Request $request)
    {
        // 1. Logging Awal untuk Debugging
        Log::channel('kiosk')->info('KIOSK_FINALIZE_START', [
            'nik' => $request->nik,
            'mode' => $request->registration_number === 'OFFLINE' ? 'OFFLINE' : 'RESERVATION'
        ]);

        try {
            return DB::transaction(function () use ($request) {
                
                // 2. Generate Nomor Urut Antrean (001, 002, dst)
                // Lock for update mencegah race condition nomor ganda
                $todayCount = RegistrationQueue::whereDate('visit_date', now()->toDateString())
                    ->whereIn('status', ['checked_in', 'calling', 'serving', 'served'])
                    ->lockForUpdate()
                    ->count();

                $queueNumber = str_pad($todayCount + 1, 3, '0', STR_PAD_LEFT);

                // 3. Proses Upload Foto KTP ke MinIO/Storage
                $documentId = null;
                if ($request->ktp_image && strlen($request->ktp_image) > 100) {
                    $img = str_replace(['data:image/jpeg;base64,', ' '], ['', '+'], $request->ktp_image);
                    $data = base64_decode($img);
                    
                    $fileName = 'KTP_' . $request->nik . '_' . time() . '.jpg';
                    $filePath = 'ktp_kiosk/' . $fileName;

                    if (Storage::disk('uploads')->put($filePath, $data)) {
                        $doc = Document::create([
                            'file_path' => $filePath,
                            'file_type' => 'image/jpeg',
                            'original_name' => $fileName
                        ]);
                        $documentId = $doc->id;
                    }
                }

                // 4. Update atau Create Antrean
                if ($request->registration_number === 'OFFLINE' || empty($request->registration_number)) {
                    // Mode Registrasi Baru (Offline)
                    $registration = RegistrationQueue::create([
                        'uuid' => (string) Str::uuid(),
                        'registration_number' => strtoupper(Str::random(6)),
                        'queue_number' => $queueNumber,
                        'nik' => $request->nik,
                        'name' => $request->name,
                        'phone' => $request->phone ?? '-',
                        'email' => $request->email,
                        'address' => $request->address ?? '-',
                        'subject' => $request->subject,
                        'visit_date' => now()->toDateString(),
                        'visit_time' => now()->format('H:i'),
                        'status' => 'checked_in',
                    ]);
                } else {
                    // Mode Reservasi Online
                    $registration = RegistrationQueue::where('registration_number', $request->registration_number)
                        ->whereDate('visit_date', now()->toDateString())
                        ->first();

                    if (!$registration) {
                        throw new \Exception("Kode Reservasi tidak ditemukan untuk hari ini.");
                    }

                    $registration->update([
                        'status' => 'checked_in',
                        'queue_number' => $queueNumber,
                        'subject' => $request->subject, // Update jika mereka merubah topik di Kiosk
                        'nik' => $request->nik,
                        'name' => $request->name,
                    ]);
                }

                // 5. Sync ke Master Data Reporters (Agar otomatis terisi saat operator buat laporan)
                Reporter::updateOrCreate(
                    ['nik' => $request->nik],
                    [
                        'uuid' => (string) Str::uuid(),
                        'name' => $request->name,
                        'phone_number' => $request->phone,
                        'email' => $request->email,
                        'address' => $request->address,
                        'ktp_document_id' => $documentId ?? null,
                        'checkin_status' => 'pending_report_creation',
                        'last_checkin_at' => now(),
                    ]
                );

                Log::channel('kiosk')->info('KIOSK_FINALIZE_SUCCESS', ['queue' => $queueNumber, 'nik' => $request->nik]);

                return response()->json([
                    'status' => 'success',
                    'print_data' => [
                        'queue_number' => $queueNumber,
                        'name' => strtoupper($request->name),
                        'nik' => $request->nik,
                        'time' => now()->format('d/m/Y H:i'),
                        'subject' => $request->subject
                    ]
                ]);
            });
        } catch (\Exception $e) {
            Log::channel('kiosk')->error('KIOSK_FINALIZE_ERROR', [
                'msg' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function operatorIndex()
    {
        \DB::table('active_counters')
            ->whereDate('updated_at', '<', now()->toDateString())
            ->delete();

        $sessionCounter = session('kiosk_counter_number');
        
        if ($sessionCounter) {
            $dbCheck = \DB::table('active_counters')
                ->where('user_id', auth()->id())
                ->where('counter_number', $sessionCounter)
                ->exists();
            
            if (!$dbCheck) {
                session()->forget('kiosk_counter_number');
                $sessionCounter = null;
            }
        }

        $takenCounters = \DB::table('active_counters')
            ->where('user_id', '!=', auth()->id())
            ->pluck('counter_number')
            ->toArray();

        $activeQueue = RegistrationQueue::where('operator_id', auth()->id())
            ->whereDate('visit_date', now()->toDateString())
            ->whereIn('status', ['calling', 'serving'])
            ->first();

        return view('pages.reporters.operator', compact('activeQueue', 'takenCounters', 'sessionCounter'));
    }

    public function setCounter(Request $request)
    {
        $request->validate(['counter' => 'required|integer']);

        try {
            DB::transaction(function () use ($request) {
                $exists = \DB::table('active_counters')
                    ->where('counter_number', $request->counter)
                    ->where('user_id', '!=', auth()->id())
                    ->exists();

                if ($exists) {
                    throw new \Exception("Loket ini baru saja diambil oleh operator lain.");
                }

                // 2. Simpan atau Update posisi loket user ini
                \DB::table('active_counters')->updateOrInsert(
                    ['user_id' => auth()->id()],
                    [
                        'counter_number' => $request->counter,
                        'updated_at' => now()
                    ]
                );

                session(['kiosk_counter_number' => $request->counter]);
            });

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    public function unsetCounter()
    {
        $isBusy = RegistrationQueue::where('operator_id', auth()->id())
            ->whereIn('status', ['calling', 'serving'])
            ->exists();

        if ($isBusy) {
            return response()->json(['message' => 'Selesaikan pelayanan sebelum meninggalkan loket!'], 403);
        }

        \DB::table('active_counters')->where('user_id', auth()->id())->delete();
        session()->forget('kiosk_counter_number');

        return response()->json(['status' => 'success']);
    }

    public function callNext(Request $request)
    {
        $counterNumber = session('kiosk_counter_number');
        
        if (!$counterNumber) {
            return response()->json(['message' => 'Pilih nomor loket dahulu!'], 403);
        }

        try {
            return DB::transaction(function () use ($counterNumber) {
                
                $nextQueue = \App\Models\RegistrationQueue::whereDate('visit_date', now()->toDateString())
                    ->where('status', 'checked_in')
                    ->orderBy('id', 'asc') 
                    ->lockForUpdate()
                    ->first(); 

                if (!$nextQueue) {
                    return response()->json(['message' => 'Antrean sudah habis.'], 404);
                }

                // Update data antrean
                $nextQueue->update([
                    'status' => 'calling',
                    'counter_number' => $counterNumber,
                    'operator_id' => auth()->id(),
                    'called_at' => now(),
                ]);

                // 1. Ambil Nama (Fallback jika null)
                $displayName = $nextQueue->name ? strtoupper($nextQueue->name) : 'PENGADU';
                $displayNumber = $nextQueue->queue_number ?? $nextQueue->registration_number;

                // 2. TRIGGER BROADCAST (Gunakan broadcast() dan kirim 3 parameter sesuai konstruktor Event)
                broadcast(new \App\Events\AntreanDipanggil(
                    $displayNumber, 
                    $counterNumber, 
                    $displayName
                ))->toOthers();

                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'queue_number' => $displayNumber,
                        'name' => $displayName,
                        'counter' => $counterNumber
                    ]
                ]);
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('kiosk')->error('CALL_NEXT_ERROR: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function startServing(Request $request)
    {
        try {
            $queue = RegistrationQueue::where('queue_number', $request->queue_number)
                ->whereDate('visit_date', now()->toDateString())
                ->where('status', 'calling')
                ->first();

            if (!$queue) {
                return response()->json(['message' => 'Data antrean tidak valid.'], 404);
            }

            // 1. Update status ke serving
            $queue->update([
                'status' => 'serving',
                'served_at' => now(),
            ]);

            // 2. Cari data Reporter berdasarkan NIK
            $reporter = Reporter::where('nik', $queue->nik)->first();
            
            if (!$reporter) {
                throw new \Exception("Data profil pengadu tidak ditemukan.");
            }

            // 3. Hash ID Reporter (Sesuai kebutuhan controller create Anda)
            $hashids = new \Hashids\Hashids('your-salt-string', 10);
            $encodedId = $hashids->encode($reporter->id);

            // 4. Kirim URL redirect dengan parameter
            return response()->json([
                'status' => 'success',
                'redirect_url' => route('reports.create', [
                    'reporter_id' => $encodedId,
                    'source_default' => 'tatap muka'
                ])
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function recall(Request $request)
    {
        $counterNumber = session('kiosk_counter_number');
        
        if (!$counterNumber) {
            return response()->json(['message' => 'Sesi loket tidak ditemukan.'], 403);
        }

        try {
            $queueNumber = $request->queue_number;

            $queue = \App\Models\RegistrationQueue::where('queue_number', $queueNumber)
                        ->where('counter_number', $counterNumber)
                        ->whereDate('visit_date', now()->toDateString())
                        ->first();

            if (!$queue) {
                return response()->json(['message' => 'Data antrean tidak ditemukan di loket Anda.'], 404);
            }

            $displayName = $queue->name ? strtoupper($queue->name) : 'PENGADU';
            $displayNumber = $queue->queue_number ?? $queue->registration_number;

            broadcast(new \App\Events\AntreanDipanggil(
                $displayNumber, 
                $counterNumber, 
                $displayName
            ))->toOthers();

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('kiosk')->error('RECALL_ERROR: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancelQueue(Request $request)
    {
        $request->validate(['queue_number' => 'required']);

        try {
            $queue = RegistrationQueue::where('queue_number', $request->queue_number)
                ->where('operator_id', auth()->id())
                ->whereDate('visit_date', now()->toDateString())
                ->whereIn('status', ['calling', 'serving'])
                ->first();

            if (!$queue) {
                return response()->json(['message' => 'Antrean tidak ditemukan atau sudah diproses.'], 404);
            }

            $queue->update([
                'status' => 'skipped',
                'served_at' => now()
            ]);

            return response()->json(['status' => 'success', 'message' => 'Antrean berhasil dibatalkan.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function monitor()
    {
        $currentCall = RegistrationQueue::whereDate('visit_date', now())
            ->whereIn('status', ['calling', 'serving'])
            ->orderBy('updated_at', 'desc')
            ->first();

        return view('pages.kiosk.monitor', compact('currentCall'));
    }
}