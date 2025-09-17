<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Category;
use Exception;

class GeminiCategoryService
{
    private array $categories;

    public function __construct()
    {
        // Ambil daftar kategori dari database saat service diinisialisasi
        $this->categories = Category::pluck('name')->toArray();
    }

    /**
     * Mengklasifikasikan laporan menggunakan API Gemini dengan fuzzy matching.
     *
     * @param string $originalLaporanDetail Isi laporan asli dari pengguna.
     * @return string Nama kategori yang diprediksi atau 'Lainnya' jika gagal.
     */
    public function classifyReport(string $originalLaporanDetail): string
    {
        try {
            $apiKey = config('gemini.api_key');
            if (empty($apiKey)) {
                throw new Exception('GEMINI_API_KEY tidak ditemukan di file .env');
            }

            $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;
            
            $kategoriList = implode('; ', $this->categories);
            $prompt = $this->buildPrompt($kategoriList, $originalLaporanDetail);

            $response = Http::timeout(30)->post($endpoint, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            $responseData = $response->json();
            
            // Log respons API Gemini untuk debugging
            Log::info('Gemini API Response:', ['response' => $responseData, 'prompt' => $prompt]);

            $geminiOutput = 'Lainnya';
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $geminiOutput = trim($responseData['candidates'][0]['content']['parts'][0]['text']);
            }

            // --- Logika Fuzzy Matching ---
            $bestMatch = 'Lainnya';
            $highestSimilarity = 0;
            $threshold = 70; // Batasan kemiripan dalam persen (misal: 50%)

            foreach ($this->categories as $categoryName) {
                // Hitung seberapa mirip string dari Gemini dengan kategori di database
                similar_text(strtolower($geminiOutput), strtolower($categoryName), $similarity);
                
                if ($similarity > $highestSimilarity && $similarity >= $threshold) {
                    $highestSimilarity = $similarity;
                    $bestMatch = $categoryName;
                }
            }
            
            return $bestMatch; // Kembalikan kategori terbaik yang ditemukan
        } catch (Exception $e) {
            Log::error('Gagal memanggil API Gemini: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return 'Lainnya';
        }
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