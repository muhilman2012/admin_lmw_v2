<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RegistrationQueue;
use App\Models\Report;
use App\Models\Reporter;
use App\Models\VisitSlotSetting;
use App\Models\HolidaySetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PublicRegistrationController extends Controller
{
    public function getDisabledDates()
    {
        $holidays = HolidaySetting::where('block_registration', true)
            ->pluck('holiday_date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->toArray();

        return response()->json([
            'status' => 'success',
            'data' => [
                'holidays' => $holidays,
                'disable_weekends' => true
            ]
        ]);
    }

    public function getTimeSlots(Request $request)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today'
        ]);

        $date = $request->date;
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        // Cek jika Sabtu/Minggu
        if ($dayOfWeek === 0 || $dayOfWeek === 6) {
            return response()->json(['status' => 'error', 'message' => 'Kantor tutup pada hari akhir pekan.'], 200);
        }

        // Cek jika masuk daftar hari libur (block_registration)
        $isHoliday = HolidaySetting::where('holiday_date', $date)
            ->where('block_registration', true)
            ->exists();

        if ($isHoliday) {
            return response()->json(['status' => 'error', 'message' => 'Tanggal ini adalah hari libur/layanan tutup.'], 200);
        }

        // Ambil pengaturan slot dari database
        $masterSlots = VisitSlotSetting::where('is_active', true)
            ->orderBy('time_start', 'asc')
            ->get();

        $availableSlots = [];

        foreach ($masterSlots as $slot) {
            // Hitung sisa kuota aktual
            $usedQuota = RegistrationQueue::where('visit_date', $date)
                ->where('visit_time', $slot->time_start)
                ->where('status', '!=', 'expired')
                ->count();

            $remaining = $slot->quota - $usedQuota;

            $availableSlots[] = [
                'id' => $slot->id,
                'time' => Carbon::parse($slot->time_start)->format('H:i'),
                'total_quota' => $slot->quota,
                'remaining_quota' => max(0, $remaining),
                'is_full' => $remaining <= 0
            ];
        }

        return response()->json([
            'status' => 'success',
            'date' => $date,
            'slots' => $availableSlots
        ]);
    }

    public function store(Request $request)
    {
        $this->sanitizeInput($request);

        $settings = \App\Models\RegistrationSetting::first();
        if (!$settings) {
            return response()->json(['status' => 'error', 'message' => 'Konfigurasi pendaftaran belum diatur oleh Admin.'], 200);
        }

        $request->validate([
            'nik'         => 'required|digits:16',
            'name'        => 'required|string|max:255',
            'phone'       => 'required|string',
            'email'       => 'nullable|email',
            'address'     => 'required|string',
            'subject'     => 'required|string',
            'is_disabled' => 'required|boolean',
            'visit_date'  => 'required|date',
            'visit_time'  => 'required|date_format:H:i',
            'companion_name'=> 'nullable|string',
        ]);

        $visitDate = \Carbon\Carbon::parse($request->visit_date);
        $today = \Carbon\Carbon::today();

        if ($visitDate->lt($today)) {
            return response()->json(['status' => 'error', 'message' => 'Tidak bisa memilih tanggal yang sudah lewat.'], 200);
        }

        if ($visitDate->lt($settings->open_date) || $visitDate->gt($settings->close_date)) {
            return response()->json([
                'status' => 'error',
                'message' => "Pendaftaran hanya tersedia untuk periode kunjungan " . 
                            $settings->open_date->format('d/m/Y') . " s/d " . 
                            $settings->close_date->format('d/m/Y')
            ], 200);
        }

        if ($visitDate->isWeekend()) {
            return response()->json(['status' => 'error', 'message' => 'Layanan tatap muka tutup di akhir pekan.'], 200);
        }

        $isHoliday = \App\Models\HolidaySetting::where('holiday_date', $request->visit_date)
            ->where('block_registration', true)
            ->exists();

        if ($isHoliday) {
            return response()->json(['status' => 'error', 'message' => 'Layanan tutup pada tanggal terpilih (Hari Libur/Cuti).'], 200);
        }
        
        $limitDays = $settings->eligibility_days ?? 20;
        $dateLimit = \Carbon\Carbon::now()->subDays($limitDays);

        $reporter = \App\Models\Reporter::where('nik', $request->nik)->first();
        if ($reporter) {
            $lastReport = \App\Models\Report::where('reporter_id', $reporter->id)
                ->where('created_at', '>=', $dateLimit)
                ->first();
            
            if ($lastReport) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "NIK Anda sudah terdaftar melakukan pengaduan dalam {$limitDays} hari terakhir."
                ], 200);
            }
        }

        $hasActiveQueue = \App\Models\RegistrationQueue::where('nik', $request->nik)
            ->whereIn('status', ['pending', 'checked_in', 'served'])
            ->where('created_at', '>=', $dateLimit)
            ->exists();

        if ($hasActiveQueue) {
            return response()->json([
                'status'  => 'error',
                'message' => "NIK Anda sudah memiliki reservasi aktif atau telah dilayani dalam {$limitDays} hari terakhir."
            ], 200);
        }

        $slotSetting = \App\Models\VisitSlotSetting::where('time_start', $request->visit_time)
            ->where('is_active', true)
            ->first();

        if (!$slotSetting) {
            return response()->json(['status' => 'error', 'message' => 'Jadwal sesi kunjungan tidak tersedia.'], 200);
        }

        $bookedCount = \App\Models\RegistrationQueue::where('visit_date', $request->visit_date)
            ->where('visit_time', $request->visit_time)
            ->where('status', '!=', 'expired')
            ->count();

        if ($bookedCount >= $slotSetting->quota) {
            return response()->json(['status' => 'error', 'message' => 'Kuota untuk jam tersebut sudah penuh.'], 200);
        }

        try {
            $regNumber = strtoupper(\Illuminate\Support\Str::random(6));
            $qrPath = "registration_qrcodes/qr-{$regNumber}.png";

            $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(300)->margin(1)->generate($regNumber);
            \Illuminate\Support\Facades\Storage::disk('uploads')->put($qrPath, $qrImage);

            $registration = \App\Models\RegistrationQueue::create([
                'registration_number' => $regNumber,
                'nik'         => $request->nik,
                'name'        => $request->name,
                'phone'       => $request->phone,
                'email'       => $request->email,
                'address'     => $request->address,
                'subject'     => $request->subject,
                'is_disabled' => $request->is_disabled,
                'visit_date'  => $request->visit_date,
                'visit_time'  => $request->visit_time,
                'companion_name'=> $request->companion_name,
                'qr_path'     => $qrPath,
                'status'      => 'pending'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Reservasi berhasil disimpan.',
                'data' => [
                    'registration_number' => $registration->registration_number,
                    'qr_url' => signMinioUrlSmart(env('AWS_UPLOADS_BUCKET'), $qrPath, 60),
                    'visit_info' => $registration->visit_date->format('d F Y') . ' jam ' . $request->visit_time
                ]
            ], 201);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error Store Registration: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal memproses pendaftaran. Silakan coba lagi.'], 200);
        }
    }

    /**
     * Helper: Sanitasi input.
     */
    private function sanitizeInput(Request $request): void
    {
        $input = $request->all();
        array_walk_recursive($input, function(&$item) {
            if (is_string($item)) {
                $item = htmlspecialchars(strip_tags($item), ENT_QUOTES, 'UTF-8');
            }
        });
        $request->replace($input);
    }

    /**
    * Membantu Frontend untuk inisialisasi kalender dan slot.
    * Menampilkan tanggal libur dan ketersediaan jam.
    */
    public function getCalendarMeta(Request $request)
    {
        $settings = \App\Models\RegistrationSetting::first();
        $today = Carbon::today();

        // Logika Penentuan Batas Kalender
        // min_date: Ambil yang paling terbaru antara 'hari ini' atau 'tanggal mulai di setting'
        $minDate = $today->gt($settings->open_date) ? $today : $settings->open_date;
        
        // max_date: Sesuai setting di database
        $maxDate = $settings->close_date;

        // Jika hari ini sudah melewati close_date, maka kalender harus terblokir semua
        if ($today->gt($maxDate)) {
            return response()->json(['status' => 'error', 'message' => 'Periode pendaftaran telah berakhir.'], 200);
        }

        $holidays = HolidaySetting::where('block_registration', true)
            ->pluck('holiday_date')
            ->map(fn($d) => $d->format('Y-m-d'))->toArray();

        $masterSlots = VisitSlotSetting::where('is_active', true)->orderBy('time_start', 'asc')->get();
        $totalDailyQuota = $masterSlots->sum('quota');

        $fullDates = RegistrationQueue::select('visit_date')
            ->whereBetween('visit_date', [$minDate->format('Y-m-d'), $maxDate->format('Y-m-d')])
            ->where('status', '!=', 'expired')
            ->groupBy('visit_date')
            ->havingRaw("COUNT(*) >= ?", [$totalDailyQuota])
            ->pluck('visit_date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->toArray();

        return response()->json([
            'status' => 'success',
            'data' => [
                'min_date' => $minDate->format('Y-m-d'),
                'max_date' => $maxDate->format('Y-m-d'),
                'holidays' => $holidays,
                'full_dates' => $fullDates,
                'available_times' => $masterSlots->map(fn($s) => Carbon::parse($s->time_start)->format('H:i')),
            ]
        ]);
    }
}