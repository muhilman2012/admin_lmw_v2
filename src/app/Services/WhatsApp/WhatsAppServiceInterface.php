<?php

namespace App\Services\WhatsApp;

interface WhatsAppServiceInterface
{
    /**
     * Mengirim pesan teks ke nomor tertentu
     */
    public function sendMessage(string $phone, string $message): bool;

    /**
     * Mengambil status koneksi dan QR Code (jika ada)
     */
    public function getConnectionStatus(): array;
}