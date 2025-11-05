<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Helper: Tandai notifikasi spesifik sebagai sudah dibaca berdasarkan ID.
     * Metode ini aman untuk dipanggil secara internal oleh Controller lain.
     * @param string $notificationId
     */
    public function markSpecificAsReadById(string $notificationId): void
    {
        if (Auth::check()) {
            // Temukan notifikasi yang belum dibaca milik pengguna saat ini
            $notification = Auth::user()->unreadNotifications
                         ->where('id', $notificationId)
                         ->first();
                         
            if ($notification) {
                $notification->markAsRead();
            }
        }
    }

    /**
     * Tandai semua notifikasi yang belum dibaca sebagai sudah dibaca.
     */
    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return redirect()->back()
            ->with('success', 'Semua notifikasi telah ditandai sebagai sudah dibaca.');
    }
    
    /**
     * Tandai notifikasi spesifik sebagai sudah dibaca dan mengalihkan ke URL tujuan.
     * Metode ini dipanggil dari route dan menerima objek Request.
     */
    public function markSpecificAsRead(Request $request)
    {
        $notificationId = $request->get('read');
        $reportUuid = $request->get('report_uuid');
        $redirectToUrl = $request->get('url');
        
        $logRedirect = false;

        if ($notificationId && Auth::check()) {
            // Panggil helper untuk menandai notifikasi sebagai sudah dibaca
            $this->markSpecificAsReadById($notificationId); 

            // Setelah ditandai, kita perlu mencari notifikasi lagi untuk mendapatkan data URL
            $notification = Auth::user()->notifications()->where('id', $notificationId)->first();
            
            if ($notification) {
                $data = $notification->data;

                // Prioritaskan pengalihan menggunakan URL yang tersimpan dalam data notifikasi
                if (isset($data['url'])) {
                    $redirectToUrl = $data['url'];
                    $logRedirect = true;
                }
            }
        }

        // --- Logika Pengalihan (Redirect) ---
        if ($logRedirect && $redirectToUrl) {
            return redirect($redirectToUrl);
        }
        
        if ($redirectToUrl) {
            return redirect($redirectToUrl);
        }

        if ($reportUuid) {
            return redirect()->route('reports.show', $reportUuid);
        }

        return redirect()->back();
    }
}
