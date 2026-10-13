<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RegistrationQueue;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class LegacySyncController extends Controller
{
    public function syncBulk(Request $request)
    {
        $bookings = $request->input('data', []);

        if (empty($bookings)) {
            return response()->json(['status' => 'error', 'message' => 'Data kosong.'], 400);
        }

        $synced = 0;

        foreach ($bookings as $row) {
            RegistrationQueue::updateOrCreate(
                [
                    'registration_number' => $row['booking_code'], 
                ],
                [
                    'uuid'            => (string) Str::uuid(),
                    'nik'             => $row['nik'],
                    'name'            => strtoupper($row['name']),
                    'phone'           => $row['phone'] ?? '-',
                    'email'           => $row['email'] ?? null,
                    'address'         => $row['address'] ?? '-',
                    'subject'         => $row['topic'] ?? '-',
                    'is_disabled'     => (bool) ($row['priority'] ?? false),
                    'companion_name'  => $row['pendamping'] ?? null,
                    'visit_date'      => $row['visit_date'],
                    'visit_time'      => $row['visit_time'] ?? '08:00',
                    'status'          => 'pending',
                ]
            );
            $synced++;
        }

        Log::channel('kiosk')->info("LEGACY_SYNC_SUCCESS: Berhasil menyinkronkan {$synced} data.");

        return response()->json([
            'status'  => 'success',
            'message' => "Berhasil sinkronisasi {$synced} pendaftaran.",
            'count'   => $synced
        ]);
    }
}