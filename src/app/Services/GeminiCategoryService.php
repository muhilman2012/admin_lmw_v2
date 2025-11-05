<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Category;
use App\Models\ApiSetting;
use Exception;

class GeminiCategoryService
{
    private array $categories;
    private array $apiConfig;
    private string $baseEndpoint;
    private string $modelName;
    private array $apiKeys;

    public function __construct()
    {
        // 1. Ambil daftar kategori HANYA yang aktif dari database
        $this->categories = Category::where('is_active', true)->pluck('name')->toArray();
        
        // 2. Ambil konfigurasi API Gemini dari database
        $this->apiConfig = ApiSetting::where('name', 'gemini_api')
                            ->pluck('value', 'key')
                            ->all();

        // 3. Set properti berdasarkan konfigurasi database
        $this->baseEndpoint = rtrim($this->apiConfig['endpoint'] ?? 'https://generativelanguage.googleapis.com/v1beta', '/');
        $this->modelName = $this->apiConfig['model'] ?? 'gemini-2.5-flash';
        
        // Kumpulkan API Keys (Utama dan Cadangan)
        $this->apiKeys = array_filter([
            $this->apiConfig['api_key_primary'] ?? null,
            $this->apiConfig['api_key_fallback'] ?? null,
        ]);

        if (empty($this->apiKeys)) {
             // Ini akan dicatat, tapi kita biarkan service berjalan untuk mengembalikan 'Lainnya'
             Log::warning('GEMINI_API_KEY tidak ditemukan di database.');
        }
    }

    /**
     * Mengklasifikasikan laporan menggunakan API Gemini dengan fuzzy matching.
     *
     * @param string $originalLaporanDetail Isi laporan asli dari pengguna.
     * @return string Nama kategori yang diprediksi atau 'Lainnya' jika gagal.
     */
    public function classifyReport(string $originalLaporanDetail): string
    {
        // Jika tidak ada kunci API yang tersedia, langsung gagal
        if (empty($this->apiKeys)) {
            return 'Lainnya';
        }

        // Jika tidak ada kategori aktif yang dimuat (meskipun sudah difilter)
        if (empty($this->categories)) {
            Log::warning('Tidak ada kategori aktif yang ditemukan di database untuk klasifikasi Gemini.');
            return 'Lainnya';
        }

        $kategoriList = implode('; ', $this->categories);
        $maskedLaporanDetail = $this->maskPii($originalLaporanDetail);
        $prompt = $this->buildPrompt($kategoriList, $originalLaporanDetail);
        
        $lastError = null;

        // Iterasi melalui semua API key yang tersedia (Utama, lalu Cadangan)
        foreach ($this->apiKeys as $apiKey) {
            
            $endpoint = "{$this->baseEndpoint}/models/{$this->modelName}:generateContent?key=" . $apiKey;

            try {
                // Gunakan mekanisme Retry (3 kali, 1 detik jeda) untuk mengatasi error 503
                $response = Http::timeout(30)
                    ->retry(3, 1000) 
                    ->post($endpoint, [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt]
                                ]
                            ]
                        ]
                    ]);

                // Jika respons berhasil (status 2xx)
                if ($response->successful()) {
                    $responseData = $response->json();
                    
                    // Log respons API Gemini untuk debugging
                    Log::info('Gemini API Response (Success):', ['model' => $this->modelName, 'response' => $responseData, 'prompt' => $prompt]);

                    $geminiOutput = 'Lainnya';
                    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                        $geminiOutput = trim($responseData['candidates'][0]['content']['parts'][0]['text']);
                    }

                    // --- Logika Fuzzy Matching ---
                    $bestMatch = 'Lainnya';
                    $highestSimilarity = 0;
                    $threshold = 70; 

                    foreach ($this->categories as $categoryName) {
                        similar_text(strtolower($geminiOutput), strtolower($categoryName), $similarity);
                        
                        if ($similarity > $highestSimilarity && $similarity >= $threshold) {
                            $highestSimilarity = $similarity;
                            $bestMatch = $categoryName;
                        }
                    }
                    
                    return $bestMatch; 
                }
                
                // Jika respons gagal (bukan 2xx, misal 400, 403, 5xx non-retryable)
                $lastError = $response->status();
                Log::error('Gemini API request failed (Status ' . $lastError . '):', [
                    'body' => $response->body(),
                    'endpoint' => $endpoint
                ]);

            } catch (Exception $e) {
                $lastError = 'Exception: ' . $e->getMessage();
                Log::error('Gagal memanggil API Gemini:', [
                    'exception' => $e,
                    'endpoint' => $endpoint
                ]);
            }
        }
        
        // Jika semua API Key gagal
        Log::error('Semua API Key Gemini gagal setelah semua percobaan. Terakhir: ' . $lastError);
        return 'Lainnya';
    }

    /**
     * Helper: Membangun prompt yang spesifik untuk Gemini.
     */
    private function buildPrompt(string $kategoriList, string $maskedLaporanDetail): string
    {
        $prompt = "Klasifikasikan laporan pengaduan berikut ke salah satu kategori yang tersedia. Berikan hanya nama kategori, tidak ada teks tambahan, penjelasan, atau tanda kutip. Jika tidak ada kategori yang cocok, berikan 'Lainnya'.\n\n";

        // -- Instruksi Prioritas yang Diperkuat --
        $prompt .= "**ATURAN PENTING:** Jika laporan utama berkaitan dengan KESULITAN FINANSIAL, TUNGGAKAN, KEBUTUHAN BANTUAN DANA, atau masalah KEMISKINAN, TERLEPAS DARI KONTEKS LAINNYA (misalnya pendidikan, kesehatan), KATEGORIKAN SEBAGAI 'Bantuan Masyarakat'. Kata kunci seperti 'tunggakan spp', 'PIP', 'KIP', 'biaya', 'melunasi', 'bantuan', 'kesulitan ekonomi' adalah indikator kuat untuk 'Bantuan Masyarakat'.\n\n";

        // -- Tambahkan Contoh Few-Shot Learning --
        $prompt .= "Contoh:\n";
        $prompt .= "Laporan: 'Anak saya tidak bisa ambil ijazah karena tunggakan biaya sekolah. Kami butuh bantuan dana.'\n";
        $prompt .= "Kategori: Bantuan Masyarakat\n\n";

        $prompt .= "Laporan: 'Saya butuh bantuan untuk membayar biaya pengobatan ibu saya yang sakit dan tidak mampu.'\n";
        $prompt .= "Kategori: Bantuan Masyarakat\n\n";

        $prompt .= "Daftar Kategori: " . $kategoriList . "\n\n";
        $prompt .= "Isi Laporan: \"" . $maskedLaporanDetail . "\"";

        return $prompt;
    }

    /**
     * Fungsi untuk menyamarkan informasi pengenal pribadi (PII) dari teks.
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
}