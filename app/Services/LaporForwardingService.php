<?php

namespace App\Services;

use App\Models\ApiSetting;
use App\Models\Report;
use App\Models\Document;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LaporForwardingService
{
    private array $apiSettings;

    public function __construct()
    {
        // Ambil semua setting API LAPOR!
        $this->apiSettings = ApiSetting::where('name', 'lapor_api')
                                       ->pluck('value', 'key')
                                       ->all();
    }

    /**
     * Helper: Mendapatkan nilai setting API berdasarkan kunci.
     */
    private function getApiSetting(string $key): ?string
    {
        return $this->apiSettings[$key] ?? null;
    }

    /**
     * Langkah 1: Mengunggah dokumen laporan ke API LAPOR!.
     *
     * @return array Array berisi ID dokumen yang berhasil diunggah.
     */
    public function uploadDocuments(Report $report): array
    {
        $dokumenIds = [];
        $dokumens = Document::where('report_id', $report->id)->get();
        $storageDisk = Storage::disk('complaints'); // Gunakan disk Minio

        foreach ($dokumens as $dokumen) {
            $filePath = $dokumen->file_path; // Path file di Minio
            $fileName = basename($filePath); // Ambil nama file dari path

            // Cek apakah file benar-benar ada di Minio
            if (!$storageDisk->exists($filePath)) {
                Log::error("File tidak ditemukan di Minio untuk upload: {$filePath}");
                continue;
            }
            
            try {
                // Ambil konten file dari Minio
                $fileContents = $storageDisk->get($filePath);

                // Jika konten berhasil diambil
                if ($fileContents) {
                    $response = Http::withHeaders([
                        'Authorization' => $this->getApiSetting('authorization'),
                        'token' => $this->getApiSetting('token'),
                    ])->attach(
                        'attachments[]',
                        $fileContents, // Konten file biner
                        $fileName, // Nama file asli
                        ['Content-Type' => $storageDisk->mimeType($filePath)] // Tipe MIME
                    )->post($this->getApiSetting('base_url') . '/complaints/complaint/file');

                    $responseData = $response->json();
                    
                    if ($response->successful()) {
                        // Asumsi struktur respons API LAPOR! adalah: results.docs[0].id
                        $dokumenId = $responseData['results']['docs'][0]['id'] ?? null; 
                        if ($dokumenId) {
                            $dokumenIds[] = $dokumenId;
                            // Tambahkan log aktivitas jika diperlukan
                        }
                    } else {
                        Log::error('Gagal mengunggah dokumen ke LAPOR!', [
                            'file' => $filePath, 
                            'response' => $response->body()
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Exception saat upload dokumen dari Minio ke LAPOR!: ' . $e->getMessage(), ['file' => $filePath]);
            }
        }

        return $dokumenIds;
    }

    /**
     * Langkah 2: Mengirim laporan utama ke API LAPOR!.
     *
     * @return array ['success' => bool, 'complaint_id' => string|null, 'error' => string|null]
     */
    public function sendToLapor(Report $report, array $uploadedDocumentIds, bool $isAnonymous): array
    {
        $attachments = implode(',', $uploadedDocumentIds);
        
        $content = $isAnonymous
             ? "Nomor Tiket pada Aplikasi LMW: {$report->ticket_number}\n Detail Laporan: {$report->details}\n Lokasi: {$report->location}"
             : "Nomor Tiket pada Aplikasi LMW: {$report->ticket_number}, Nama Lengkap: {$report->reporter->name}, NIK: {$report->reporter->nik}, Alamat Lengkap: {$report->reporter->address}, Detail Laporan: {$report->details}, Lokasi: {$report->location}";

        $data = [
            'title' => $report->subject,
            'content' => $content,
            'channel' => 13,
            'is_new_user_slider' => false,
            'user_id' => 5218120,
            'is_inbox' => true,
            'is_disposisi_slider' => true,
            'classification_id' => 6,
            'disposition_id' => 151345,
            'category_id' => 436,
            'priority_program_id' => null,
            'location_id' => 34,
            'community_id' => null,
            'date_of_incident' => $report->event_date,
            'copy_externals'=> null,
            'info_disposition' => '-',
            'info_attachments' => "[{$attachments}]",
            'tags_raw' => '#lapormaswapres',
            'is_approval' => false,
            'is_anonymous' => $isAnonymous,
            'is_secret' => true,
            'is_priority' => true,
            'attachments' => "[{$attachments}]",
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->getApiSetting('authorization'),
                'token' => $this->getApiSetting('token'),
                'Content-Type' => 'application/json'
            ])->post($this->getApiSetting('base_url') . '/complaints/complaint-lmw', $data);

            $responseData = $response->json();

            if ($response->successful()) {
                
                $complaintId = $responseData['complaint_id'] ?? null; 
                
                if (!$complaintId) {
                    $complaintId = $responseData['data']['complaint_id'] ?? $responseData['data']['results']['complaint']['id'] ?? null;
                }
                
                $complaintId = $complaintId ? (string)$complaintId : null;

                if (!empty($complaintId)) {
                    return ['success' => true, 'complaint_id' => $complaintId];
                } else {
                    Log::error('API LAPOR! sukses, tapi Complaint ID tidak ditemukan dalam respons.', ['response' => $responseData]);
                    return ['success' => false, 'error' => 'Gagal mendapatkan Complaint ID dari API LAPOR!.'];
                }
            } else {
                Log::error('Gagal mengirim laporan utama ke LAPOR!', ['response' => $response->body()]);
                return ['success' => false, 'error' => $response->body()];
            }
        } catch (\Exception $e) {
            Log::error('Exception saat mengirim laporan utama ke LAPOR!: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Kesalahan sistem saat koneksi API: ' . $e->getMessage()];
        }
    }

    /**
     * Langkah 3: Mengirim permintaan reject (teruskan) ke instansi tujuan.
     *
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function sendRejectRequest(string $complaintId, string $institutionId, ?string $reason): array
    {
        $url = $this->getApiSetting('base_url') . "/complaints/process/{$complaintId}/reject";

        $data = [
            'is_request' => 1,
            'reason' => $institutionId, // ID Instansi Tujuan
            'reason_description' => $reason ?? 'Diteruskan ke Instansi Tujuan',
            'not_authority' => 1
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->getApiSetting('authorization'),
                'token' => $this->getApiSetting('token'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($url, $data);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            } else {
                Log::error('Gagal mengirim reject request (forward) ke LAPOR!', ['response' => $response->body()]);
                return ['success' => false, 'error' => $response->body()];
            }
        } catch (\Exception $e) {
            Log::error('Exception saat mengirim reject request: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Kesalahan sistem saat reject request: ' . $e->getMessage()];
        }
    }

    public function getLaporStatus(string $complaintId): array
    {
        $url = $this->getApiSetting('base_url') . "/complaints/{$complaintId}/complaint";

        try {
            $response = Http::timeout(30)->withHeaders([ // Timeout 30 detik untuk API eksternal
                'Authorization' => $this->getApiSetting('authorization'),
                'token' => $this->getApiSetting('token'),
            ])->get($url);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Cek status code HTTP 200, dan pastikan data di body API tersedia
                $resultData = $responseData['results']['data'] ?? null;
                
                if ($resultData) {
                    return ['success' => true, 'data' => $resultData];
                } else {
                    // Berhasil terhubung, tapi API bilang data tidak ada/gagal
                    $message = $responseData['message'] ?? 'Data detail laporan tidak ditemukan.';
                    return ['success' => false, 'error' => $message];
                }
            } else {
                // HTTP error (4xx atau 5xx)
                return ['success' => false, 'error' => "API error: HTTP status {$response->status()}."];
            }

        } catch (\Exception $e) {
            // Kesalahan koneksi, DNS, atau timeout
            Log::error('LAPOR! API Connection Error: ' . $e->getMessage(), ['complaint_id' => $complaintId]);
            return ['success' => false, 'error' => 'Kesalahan koneksi saat mengambil status: ' . $e->getMessage()];
        }
    }

    public function sendReply(string $complaintId, string $content, $adminId): array
    {
        $url = $this->getApiSetting('base_url') . "/complaints/{$complaintId}/complaint";

        $data = [
            // Asumsi struktur body API LAPOR!
            'admin' => (string)$adminId, // ID admin LMW (Perlu konfirmasi pihak teknis LAPOR!)
            'content' => $content,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->getApiSetting('authorization'),
                'token' => $this->getApiSetting('token'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->put($url, $data); // Menggunakan PUT method

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            } else {
                Log::error('Gagal mengirim tanggapan balasan ke LAPOR!', ['response' => $response->body()]);
                return ['success' => false, 'error' => $response->body()];
            }
        } catch (\Exception $e) {
            Log::error('Exception saat mengirim tanggapan balasan: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Kesalahan sistem saat koneksi API: ' . $e->getMessage()];
        }
    }
}