<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DukcapilService
{
    /**
     * Verifikasi NIK, Nama, dan Alamat via CALL_VERIFY_BY_ELEMEN
     */
    public function verifyIdentity(string $nik, string $nama, string $alamat)
    {
        $cleanNama = strtolower(trim($nama));
        $cleanAlamat = strtolower(trim($alamat));
        $inputHash = md5($nik . $cleanNama . $cleanAlamat);
        
        $cacheKey = "dukcapil_verify_{$inputHash}";
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $baseUrl = env('DUKCAPIL_BASE_URL');
            $customerId = env('DUKCAPIL_CUSTOMER_ID');
            $method = 'CALL_VERIFY_BY_ELEMEN';
            
            $url = "{$baseUrl}/{$customerId}/{$method}";
            $transactionId = 'LMW-' . date('YmdHis') . '-' . Str::random(4);
            
            $payload = [
                "USER_ID" => env('DUKCAPIL_USER_ID'),
                "PASSWORD" => env('DUKCAPIL_PASSWORD'),
                "IP_USER" => "10.160.86.46",
                "TRESHOLD" => env('DUKCAPIL_TRESHOLD', "90"),
                "NIK" => $nik,
                "NAMA_LGKP" => strtoupper(trim($nama)),
                "ALAMAT" => strtoupper(trim($alamat)),
                "transactionId" => $transactionId
            ];

            $response = Http::withoutVerifying()
                ->timeout(15)
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::error('Dukcapil API Error: ' . $response->body());
                return ['is_valid' => false, 'message' => 'Layanan kependudukan sedang tidak tersedia.'];
            }

            $dukcapilData = $response->json();

            // Evaluasi hasil kembalian dari Dukcapil
            $result = $this->evaluateMatch($dukcapilData);

            // Jika valid, cache selama 24 jam agar tidak bolak-balik hit API untuk NIK yang sama hari ini
            if ($result['is_valid']) {
                Cache::put($cacheKey, $result, now()->addHours(24));
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Dukcapil Service Exception: ' . $e->getMessage());
            return ['is_valid' => false, 'message' => 'Terjadi kesalahan sistem saat memverifikasi NIK.'];
        }
    }

    /**
     * Mengekstrak skor dan mencocokkan dengan threshold lokal
     */
    private function evaluateMatch(array $response): array
    {
        if (empty($response['content']) || !isset($response['content'][0])) {
            return ['is_valid' => false, 'message' => 'Format data kependudukan tidak valid atau NIK tidak ditemukan.'];
        }

        $content = $response['content'][0];

        // 1. Cek ketersediaan NIK terlebih dahulu
        $statusNik = $content['NIK'] ?? '';
        if (str_contains($statusNik, 'Tidak Sesuai') || empty($statusNik)) {
            return ['is_valid' => false, 'message' => 'NIK tidak terdaftar dalam database kependudukan.'];
        }

        // 2. Ekstrak skor angka dari string (contoh: "Tidak Sesuai (59)" menjadi 59)
        $namaScore = $this->extractScore($content['NAMA_LGKP'] ?? '');
        $alamatScore = $this->extractScore($content['ALAMAT'] ?? '');

        // 3. Atur Threshold
        $thresholdNama = 95;
        $thresholdAlamat = 70;

        if ($namaScore >= $thresholdNama && $alamatScore >= $thresholdAlamat) {
            return [
                'is_valid' => true, 
                'message' => 'Data kependudukan valid.'
            ];
        }

        // Jika gagal, berikan pesan yang spesifik agar user tahu apa yang salah
        $errorMsg = 'Data tidak sesuai.';
        if ($namaScore < $thresholdNama) {
            $errorMsg = "Nama yang diinput tidak sesuai dengan ejaan KTP.";
        } elseif ($alamatScore < $thresholdAlamat) {
            $errorMsg = "Alamat yang diinput terlalu berbeda dengan catatan KTP.";
        }

        return [
            'is_valid' => false, 
            'message' => $errorMsg
        ];
    }

    /**
     * Helper: Mengambil angka di dalam tanda kurung () menggunakan Regex
     */
    private function extractScore(string $resultString): int
    {
        // Cari angka di dalam kurung, misal "(100)" atau "(59)"
        if (preg_match('/\((\d+)\)/', $resultString, $matches)) {
            return (int) $matches[1];
        }

        // Jika tidak ada kurung (contoh: hanya tulisan "Sesuai" atau "Tidak Sesuai")
        return str_contains($resultString, 'Tidak Sesuai') ? 0 : 100;
    }
}