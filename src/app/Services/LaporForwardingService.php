<?php

namespace App\Services;

use App\Models\ApiSetting;
use App\Models\Report;
use App\Models\Document;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class LaporForwardingService
{
    private array $apiSettings;

    public function __construct()
    {
        // Ambil semua setting API LAPOR!
        $settings = ApiSetting::where('name', 'lapor_api')
                                       ->get();
        
        // Memastikan $this->apiSettings berisi key/value yang benar dari database
        // Mengubah semua kunci menjadi huruf kecil untuk konsistensi internal
        $this->apiSettings = $settings->pluck('value', 'key')->mapWithKeys(function ($value, $key) {
            return [strtolower($key) => $value];
        })->all();
    }

    /**
     * Helper: Mendapatkan nilai setting API berdasarkan kunci (huruf kecil).
     */
    private function getApiSetting(string $key): ?string
    {
        return $this->apiSettings[strtolower($key)] ?? null;
    }

    /**
     * Helper: Mendapatkan array headers otentikasi yang benar.
     * Menggunakan nama header yang diharapkan API ('Authorization', 'token').
     */
    private function getAuthHeaders(): array
    {
        $authKey = $this->getApiSetting('auth_key') ?? 'Authorization';
        $authValue = $this->getApiSetting('auth_value');
        $additionalToken = $this->getApiSetting('token');

        $headers = [
            'Accept' => 'application/json',
        ];

        if (!empty($authValue)) {
            $headers[$authKey] = $authValue;
        }

        if (!empty($additionalToken)) {
            $headers['token'] = $additionalToken; 
        }

        return $headers;
    }

    /**
     * Langkah 1: Mengunggah dokumen laporan ke API LAPOR!. (Multipart Form Data, Batch Upload)
     */
    public function uploadDocuments(Report $report): array
    {
        $dokumenIds = [];
        $dokumens = $report->documents; 
        $storageDisk = Storage::disk('complaints');

        $authHeaders = $this->getAuthHeaders();
        $uploadUrl = $this->getApiSetting('base_url') . '/complaints/complaint/file';
        
        unset($authHeaders['Content-Type']);

        // 1. Inisialisasi HTTP client dengan headers otentikasi
        $http = Http::withHeaders($authHeaders);
        $filesToAttach = 0;

        // 2. Loop semua dokumen dan lampirkan ke client (BELUM DIKIRIM)
        foreach ($dokumens as $dokumen) {
            $filePath = $dokumen->file_path;

            // 1. Cek file_path kosong / 0 / null
            if (empty($filePath) || $filePath === '0' || $filePath === 0) {
                Log::warning('Dokumen punya file_path kosong / 0, dilewati.', [
                    'document_id' => $dokumen->id ?? null,
                    'file_path'   => $filePath,
                ]);
                continue;
            }

            // pastikan string
            $filePath = (string) $filePath;

            // 2. Cek keberadaan file dengan try/catch
            try {
                if (! $storageDisk->exists($filePath)) {
                    Log::warning("File tidak ditemukan di Minio untuk upload: {$filePath}", [
                        'document_id' => $dokumen->id ?? null,
                    ]);
                    continue;
                }
            } catch (\Throwable $e) {
                Log::error('Gagal mengecek existence file di Minio: ' . $e->getMessage(), [
                    'file_path'   => $filePath,
                    'document_id' => $dokumen->id ?? null,
                ]);
                continue; // jangan hentikan seluruh proses, lanjut ke dokumen berikutnya
            }

            // 3. Ambil isi file dan attach
            try {
                $fileContents = $storageDisk->get($filePath);
                $mimeType = $storageDisk->mimeType($filePath) ?? 'application/octet-stream';

                if ($fileContents !== false && $fileContents !== null) {
                    $http = $http->attach(
                        'attachments[]',
                        $fileContents,
                        basename($filePath),
                        ['Content-Type' => $mimeType]
                    );
                    $filesToAttach++;
                } else {
                    Log::warning('Konten file kosong saat upload ke LAPOR!.', [
                        'file_path'   => $filePath,
                        'document_id' => $dokumen->id ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Exception saat menyiapkan dokumen dari Minio ke LAPOR!: ' . $e->getMessage(), [
                    'file_path'   => $filePath,
                    'document_id' => $dokumen->id ?? null,
                ]);
            }
        }

        // 3. Kirim request HANYA SEKALI
        if ($filesToAttach > 0) {
            try {
                $response = $http->post($uploadUrl);
                $responseData = $response->json();
                
                if ($response->successful()) {
                    $uploadedDocs = $responseData['results']['docs'] ?? [];
                    
                    if (!empty($uploadedDocs)) {
                        foreach ($uploadedDocs as $doc) {
                            
                            $dokumenIdResult = $doc['id'] ?? null; 
                            
                            if ($dokumenIdResult) {
                                $dokumenIds[] = $dokumenIdResult; 
                            } else {
                                Log::error('API LAPOR! sukses, tapi ID dokumen tidak ditemukan di array docs.', ['response' => $response->body()]);
                            }
                        }
                        
                        Log::info('LAPOR! DOC UPLOAD SUCCESS', [
                            'expected'        => $dokumens->count(),
                            'uploaded_count'  => count($dokumenIds),
                            'ids'             => $dokumenIds,
                        ]);

                    } else {
                        Log::error('API LAPOR! sukses, tapi tidak ada ID dokumen ditemukan di array "docs".', ['response' => $response->body()]);
                    }
                } else {
                    Log::error('Gagal mengunggah dokumen ke LAPOR!', [
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Exception saat mengirim request batch dokumen ke LAPOR!: ' . $e->getMessage());
            }
        } else {
            Log::warning('Tidak ada file yang siap di-attach ke LAPOR!.', [
                'dokumen_count' => $dokumens->count(),
            ]);
        }

        // Mengembalikan SEMUA ID yang berhasil di-upload
        return $dokumenIds;
    }

    /**
     * Langkah 2: Mengirim laporan utama ke API LAPOR!. (POST JSON)
     */
    public function sendToLapor(Report $report, array $uploadedDocumentIds, bool $isAnonymous): array
    {
        $attachmentsJson = json_encode($uploadedDocumentIds);
        $finalAttachmentsPayload = $attachmentsJson . ".";
        
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
            'info_disposition' => null,
            'info_attachments' => null,
            'tags_raw' => '#lapormaswapres',
            'is_approval' => false,
            'is_anonymous' => $isAnonymous,
            'is_secret' => true,
            'is_priority' => true,
            'attachments' => $finalAttachmentsPayload,
        ];

        $authHeaders = array_merge($this->getAuthHeaders(), [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json' // Tambahkan Accept
        ]);

        Log::critical('LAPOR! SUBMIT PAYLOAD CHECK', [
            'Total_IDs' => count($uploadedDocumentIds),
            'IDs' => $uploadedDocumentIds,
            'Final_Attachments_Payload' => $finalAttachmentsPayload,
            'Headers' => $authHeaders,
            'PayloadExcerpt' => substr(json_encode($data), 0, 200) . '...',
        ]);
        
        try {
            $response = Http::withHeaders($authHeaders)
                            ->post($this->getApiSetting('base_url') . '/complaints/complaint-lmw', $data);

            $responseData = $response->json();

            if ($response->successful()) {
                $complaintId = $responseData['complaint_id'] ?? null; 

                Log::info('LAPOR! SUBMIT SUCCESS RESPONSE', [
                    'Complaint_ID' => $complaintId,
                    'Attachments_Received' => $responseData['attachments'] ?? 'Field attachments tidak ada di respons',
                    'Response_Data' => $responseData
                ]);

                Log::debug('LAPOR! RAW REQUEST PAYLOAD', [
                    'url' => $this->getApiSetting('base_url') . '/complaints/complaint-lmw',
                    'payload' => $data,
                ]);
                
                $complaintId = $complaintId ? (string)$complaintId : null;

                if (!empty($complaintId)) {
                    return ['success' => true, 'complaint_id' => $complaintId];
                } else {
                    Log::error('API LAPOR! sukses, tapi Complaint ID tidak ditemukan dalam respons.', [
                        'response' => $responseData, 
                        'status' => $response->status()
                    ]);
                    return ['success' => false, 'error' => 'Gagal mendapatkan Complaint ID dari API LAPOR!.'];
                }
            } else {
                Log::error('Gagal mengirim laporan utama ke LAPOR!', [
                    'response' => $response->body(), 
                    'status' => $response->status()
                ]);
                return ['success' => false, 'error' => $response->body()];
            }
        } catch (\Exception $e) {
            Log::error('Exception saat mengirim laporan utama ke LAPOR!: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Kesalahan sistem saat koneksi API: ' . $e->getMessage()];
        }
    }

    /**
     * Langkah 3: Mengirim permintaan reject (teruskan) ke instansi tujuan. (POST JSON)
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

        $authHeaders = array_merge($this->getAuthHeaders(), [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ]);

        try {
            $response = Http::withHeaders($authHeaders)->post($url, $data);

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

    public function renderLogContent(array $log, string $contentField): string
    {
        $template = $log[$contentField] ?? $log['content'] ?? 'Pembaruan status/disposisi.';
        
        $institutionFromName = $log['institution_from_name'] ?? 'Sistem';
        $institutionToName = $log['institution_to_name'] ?? 'Sistem';
        
        // Ambil data status yang mungkin tersimpan di JSON "data" field
        // Ini memastikan status codes dapat dimasukkan ke dalam template string jika diperlukan
        $logData = !empty($log['data']) ? json_decode($log['data'], true) : [];
        $statusOldName = $logData['status_old']['name'] ?? 'Awal';
        $statusNewName = $logData['status_new']['name'] ?? 'Terbaru';
        
        // 1. Ganti placeholders umum
        $content = str_replace('{{institution_from}}', $institutionFromName, $template);
        $content = str_replace('{{institution_to}}', $institutionToName, $content);
        
        // 2. Ganti link placeholders (menggunakan '#' karena link eksternal tidak dibutuhkan)
        $content = str_replace('{{institution_from_link}}', '#', $content);
        $content = str_replace('{{institution_to_link}}', '#', $content);
        
        // 3. Ganti placeholders status jika ada di template (meskipun data ini tidak selalu ada di semua log type)
        $content = str_replace('{{status_old}}', $statusOldName, $content);
        $content = str_replace('{{status_new}}', $statusNewName, $content);

        return $content;
    }

    /**
     * Mengambil DETAIL laporan dari LAPOR! (Endpoint Lama/Detail). (GET)
     * Kita gunakan ini untuk mendapatkan status utama dan data dasar.
     */
    public function getLaporDetail(string $complaintId): array
    {
        // Endpoint: /complaints/{id}/complaint
        $url = $this->getApiSetting('base_url') . "/complaints/{$complaintId}/complaint";
        
        $authHeaders = array_merge($this->getAuthHeaders(), [ 'Accept' => 'application/json' ]);

        try {
            $response = Http::timeout(30)->withHeaders($authHeaders)->get($url);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // MENGAMBIL SELURUH OBJECT 'results'
                $results = $responseData['results'] ?? null;
                
                if ($results) {
                    // Mengembalikan seluruh bagian 'results' (yang mencakup 'data' dan 'data_logs')
                    return ['success' => true, 'results' => $results]; 
                } else {
                    $message = $responseData['message'] ?? 'Data detail laporan tidak ditemukan.';
                    return ['success' => false, 'error' => $message];
                }
            } else {
                return ['success' => false, 'error' => "API error: HTTP status {$response->status()}."];
            }

        } catch (\Exception $e) {
            Log::error('LAPOR! API Connection Error: ' . $e->getMessage(), ['complaint_id' => $complaintId]);
            return ['success' => false, 'error' => 'Kesalahan koneksi saat mengambil status: ' . $e->getMessage()];
        }
    }
    
    /**
     * BARU: Mengambil riwayat tindak lanjut (follow-up logs) dari LAPOR! (GET)
     * Menggunakan endpoint: /complaints/:id/followups
     */
    public function getFollowUpLogs(string $complaintId): array
    {
        // ENDPOINT CEK TINDAK LANJUT
        $url = $this->getApiSetting('base_url') . "/complaints/{$complaintId}/followups"; 
        
        $authHeaders = array_merge($this->getAuthHeaders(), [ 'Accept' => 'application/json' ]);

        try {
            $response = Http::timeout(30)->withHeaders($authHeaders)->get($url);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Mengambil array data logs
                $logs = $responseData['results']['data'] ?? [];
                
                // Normalisasi Waktu Langsung Setelah Diterima
                $normalizedLogs = collect($logs)->map(function ($log) {
                    if (isset($log['created_at'])) {
                        $dateTimeString = $log['created_at'];
                        $format = 'd-m-Y H:i:s';
                        
                        try {
                            $carbonObject = Carbon::createFromFormat($format, $dateTimeString);
                            
                            $log['created_at'] = $carbonObject->format($format);
                            
                        } catch (\Exception $e) {
                            Log::warning("LAPOR! Log Time Parse Failed: " . $e->getMessage());
                        }
                    }
                    
                    return $log;
                })->all();
                
                // Kembalikan log yang sudah dinormalisasi
                return ['success' => true, 'logs' => $normalizedLogs]; 
            } else {
                return ['success' => false, 'error' => "API error: HTTP status {$response->status()}."];
            }

        } catch (\Exception $e) {
            Log::error('LAPOR! API Follow-up Error: ' . $e->getMessage(), ['complaint_id' => $complaintId]);
            return ['success' => false, 'error' => 'Kesalahan koneksi saat mengambil riwayat: ' . $e->getMessage()];
        }
    }

    /**
     * Mengirim tindak lanjut/balasan ke API LAPOR! (/complaints/followup/add) 
     */
    public function sendFollowUpToLapor(string $complaintId, array $data): array
    {
        $url = $this->getApiSetting('base_url') . "/complaints/followup/add";

        // Data-data yang tidak berupa file
        $defaultPayload = [
            'user_id' => 52676,
            'institution_from_id' => 151345, 
            'institution_to_id' => 151345, 
            'complaint_id' => $complaintId,
            'info_disposition' => null, 
            'template_code' => $data['template_code'] ?? 'reply_lmw',
            'rating' => $data['rating'] ?? 0,
            'is_secret' => isset($data['is_secret']) && $data['is_secret'] === '1' ? 1 : 0,
            'content' => $data['content'], // Konten balasan
        ];
        
        $authHeaders = $this->getAuthHeaders();

        // Siapkan HTTP Client
        $http = Http::withHeaders($authHeaders);
        
        // 1. data non-file ke payload
        $http->asMultipart()->withHeaders([
            'Accept' => 'application/json'
        ]); 

        // 2. File Attachment jika ada
        if (isset($data['attachment']) && is_array($data['attachment'])) {
            foreach ($data['attachment'] as $index => $file) {
                // Pastikan file valid sebelum diattach
                if ($file instanceof \Illuminate\Http\UploadedFile && $file->isValid()) {
                    // PENTING: Gunakan 'attachment[]' sebagai field name
                    $http->attach(
                        "attachment[{$index}]",
                        file_get_contents($file->getRealPath()),
                        $file->getClientOriginalName()
                    );
                }
            }
        }
        
        // data non-file ke form fields (multipart)
        foreach ($defaultPayload as $key => $value) {
            // Khusus untuk boolean/null/int, harus dikonversi ke string di payload multipart
            $http->withHeaders(['Accept' => 'application/json'])
                ->asMultipart()
                ->attach($key, (string) $value);
        }
        
        try {
            // Kirim permintaan menggunakan form-data (Multipart)
            // Catatan: Jika ada data form non-file yang harus disertakan, 
            // Anda harus menggunakan attach() untuk semua field non-file.
            // Solusi yang lebih bersih: Gunakan client() dan attach() untuk file, 
            // lalu tambahkan field form di post.
            
            // REVISI SOLUSI PENGIRIMAN MULTIPART YANG LEBIH CLEAN
            $client = Http::withHeaders($authHeaders);
            
            if (isset($data['attachment'])) {
                foreach ($data['attachment'] as $index => $file) {
                    if ($file instanceof \Illuminate\Http\UploadedFile && $file->isValid()) {
                        $client->attach(
                            "attachment[{$index}]",
                            file_get_contents($file->getRealPath()),
                            $file->getClientOriginalName()
                        );
                    }
                }
            }
            
            // GABUNGKAN data file dan non-file, lalu kirim sebagai multipart form
            $combinedPayload = array_merge($defaultPayload, ['_token' => csrf_token()]);

            $response = $client->asMultipart()->post($url, $combinedPayload);
            $responseData = $response->json();

            if ($response->successful()) {
                return ['success' => true, 'response_data' => $responseData];
            } else {
                Log::error('Gagal kirim follow-up ke LAPOR! API.', ['response' => $response->body()]);
                return ['success' => false, 'error' => $response->body()];
            }
        } catch (\Exception $e) {
            Log::error('Exception saat kirim follow-up: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Mengambil daftar template balasan dari LAPOR!. (GET)
     * Menggunakan endpoint: /masters/templates
     */
    public function getLaporTemplates(): array
    {
        $url = $this->getApiSetting('base_url') . "/masters/templates?page_size=1000";
        
        $authHeaders = array_merge($this->getAuthHeaders(), [
            'Accept' => 'application/json'
        ]);

        try {
            $response = Http::timeout(30)->withHeaders($authHeaders)->get($url);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Mengembalikan array data dari API
                $templates = $responseData['results']['data'] ?? [];
                
                return ['success' => true, 'templates' => $templates];
            } else {
                 return ['success' => false, 'error' => "API error: HTTP status {$response->status()}."];
            }

        } catch (\Exception $e) {
            Log::error('LAPOR! API Templates Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Kesalahan koneksi saat mengambil templates: ' . $e->getMessage()];
        }
    }
}