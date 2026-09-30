<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use phpseclib3\Crypt\RSA;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppFlowController extends Controller
{
    public function handleWebhook(Request $request)
    {
        try {
            // 1. Ambil data dari Request
            $encryptedAesKey = base64_decode($request->input('encrypted_aes_key'));
            $encryptedFlowData = base64_decode($request->input('encrypted_flow_data'));
            $initialVector = base64_decode($request->input('initial_vector'));

            // 2. Ambil Private Key dan bersihkan string \n (agar aman dari salah baca)
            $privateKeyStr = config('services.lmw.wa_private_key');
            $privateKeyStr = str_replace(['\\n', '\n'], "\n", $privateKeyStr);

            // 3. Dekripsi AES Key menggunakan phpseclib (Mendukung SHA-256 OAEP Meta)
            try {
                $rsa = RSA::load($privateKeyStr)
                        ->withPadding(RSA::ENCRYPTION_OAEP)
                        ->withHash('sha256')
                        ->withMGFHash('sha256');

                $aesKey = $rsa->decrypt($encryptedAesKey);
            } catch (\Exception $e) {
                Log::error("RSA Decryption Failed: " . $e->getMessage());
                throw new \Exception("Gagal mendekripsi AES Key");
            }

            // 4. Dekripsi Flow Data (Gunakan aes-128-gcm sesuai panjang kunci 16 byte dari Meta)
            $authTag = substr($encryptedFlowData, -16);
            $ciphertext = substr($encryptedFlowData, 0, -16);

            $flowDataJson = openssl_decrypt(
                $ciphertext, 
                'aes-128-gcm',
                $aesKey, 
                OPENSSL_RAW_DATA, 
                $initialVector, 
                $authTag
            );

            if (!$flowDataJson) {
                // Kumpulkan semua pesan error dari OpenSSL
                $sslError = "";
                while ($msg = openssl_error_string()) {
                    $sslError .= $msg . " | ";
                }
                
                // Log rincian panjang data untuk mengecek anomali
                Log::error("GCM Decryption Failed", [
                    'aes_key_length' => strlen($aesKey),
                    'iv_length' => strlen($initialVector),
                    'auth_tag_length' => strlen($authTag),
                    'ssl_error' => $sslError
                ]);
                
                throw new \Exception("Gagal mendekripsi Flow Data (GCM)");
            }

            $flowData = json_decode($flowDataJson, true);

            // BIKIN LOG KHUSUS WA FLOWS
            $waLog = \Illuminate\Support\Facades\Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/wa_flows_debug.log'),
            ]);

            // 5. TANGKAP VARIABEL ROOT DARI META
            $rootAction = $flowData['action'] ?? null; // Selalu berisi: ping, INIT, atau data_exchange
            $screen = $flowData['screen'] ?? null;     // Berisi screen yang baru saja disubmit
            $formData = $flowData['data'] ?? [];       // Ini yang berisi inputan Form (NIK, Nama, KTP, dll)

            // Catat data yang masuk dari WhatsApp
            $waLog->info("=== REQUEST DARI WA FLOWS ===");
            $waLog->info("Root Action: {$rootAction} | Screen: {$screen}", $formData);

            $apiHeaders = [
                'Authorization' => 'Bearer ' . config('services.lmw.api_token'),
                'X-LMW-API-KEY' => config('services.lmw.api_key'),
                'Accept' => 'application/json'
            ];
            $apiUrl = config('services.lmw.api_url');
            $responseData = [];

            // 6. ROUTING LOGIKA BERDASARKAN ROOT ACTION & SCREEN
            if ($rootAction === 'ping') {
                $responseData = ['data' => ['status' => 'active']];
            } 
            elseif ($rootAction === 'INIT') {
                $responseData = [
                    'screen' => 'IDENTITAS',
                    'data' => ['error_message' => '']
                ];
            }
            elseif ($rootAction === 'data_exchange') {
                
                // --- A. JIKA FORM IDENTITAS YANG DISUBMIT ---
                if ($screen === 'IDENTITAS') {
                    $nik = $formData['nik'] ?? '';
                    $name = $formData['name'] ?? '';
                    $email = $formData['email'] ?? '';
                    $address = $formData['address'] ?? '';
                    
                    // 1. LAPISAN VALIDASI LOKAL (Sesuai Standar Qontak)
                    $errorMessage = null;
                    if (!preg_match('/^\d{16}$/', $nik)) {
                        $errorMessage = 'NIK harus 16 digit angka.';
                    } elseif (trim($name) === '') {
                        $errorMessage = 'Nama wajib diisi.';
                    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { 
                        $errorMessage = 'Format email tidak valid.';
                    } elseif (trim($address) === '') {
                        $errorMessage = 'Alamat wajib diisi.';
                    }

                    // Jika validasi lokal gagal, langsung kembalikan error ke WA
                    if ($errorMessage !== null) {
                        $responseData = [
                            'screen' => 'IDENTITAS',
                            'data' => [
                                'error_message' => $errorMessage // Tanpa embel-embel emoji
                            ]
                        ];
                    } 
                    // 2. JIKA VALIDASI LOKAL LOLOS, LANJUT CEK KE API INTERNAL LMW
                    else {
                        $payloadLmw = [
                            'nik' => $nik,
                            'name' => $name,
                            'email' => $email,
                            'address' => $address,
                            'phone_number' => '081100000000' // Dummy untuk testing, WA Flows tidak kirim nomor otomatis
                        ];

                        $eligibilityResponse = Http::withHeaders($apiHeaders)
                            ->post($apiUrl . '/api/reporters/check-eligibility-v2', $payloadLmw);

                        $resData = $eligibilityResponse->json();
                        $waLog->info("Response API Eligibility:", $resData ?? []);

                        if (!$eligibilityResponse->successful() || (isset($resData['eligible']) && $resData['eligible'] === false)) {
                            $apiErrorMessage = 'Verifikasi gagal. Silakan periksa kembali data Anda.'; 
                            
                            if (isset($resData['validations']) && is_array($resData['validations'])) {
                                foreach ($resData['validations'] as $key => $validation) {
                                    if (isset($validation['status']) && $validation['status'] === false) {
                                        $apiErrorMessage = $validation['message'] ?? "Kesalahan pada {$key}.";
                                        break;
                                    }
                                }
                            }

                            $waLog->warning("Hasil Validasi Ditolak: " . $apiErrorMessage);

                            $responseData = [
                                'screen' => 'IDENTITAS',
                                'data' => ['error_message' => $apiErrorMessage] // Error dari API LMW
                            ];
                        } else {
                            $reporterResponse = Http::withHeaders($apiHeaders)->post($apiUrl . '/api/reporters', $payloadLmw);
                            $reporterId = $reporterResponse->json('reporter_id') ?? '0';
                            
                            $waLog->info("Sukses Lolos Validasi. Reporter ID: " . $reporterId);

                            $responseData = [
                                'screen' => 'PENGADUAN',
                                'data' => ['reporter_id' => (string) $reporterId]
                            ];
                        }
                    }
                }
                
                // --- B. JIKA FORM PENGADUAN (LANJUT UPLOAD KTP) DISUBMIT ---
                // Di WA Flows Anda, form PENGADUAN action-nya adalah 'navigate' ke UPLOAD_KTP.
                // Jadi kita tidak perlu menangkap 'PENGADUAN' di sini, melainkan langsung menangkap UPLOAD_KTP saat disubmit.
                
                // --- C. JIKA FORM UPLOAD KTP DISUBMIT ---
                elseif ($screen === 'UPLOAD_KTP') {
                    $mediaId = $formData['ktp_base64'] ?? ''; 
                    $base64Data = $this->downloadMetaMediaAsBase64($mediaId);

                    $docResponse = Http::withHeaders($apiHeaders)->post($apiUrl . '/api/documents', [
                        'file_base64' => $base64Data,
                        'description' => 'KTP Pengadu (WA Flows)'
                    ]);
                    $ktpDocId = $docResponse->json('data.id') ?? '0';

                    $responseData = [
                        'screen' => 'UPLOAD_KK',
                        'data' => [
                            'reporter_id' => $formData['reporter_id'] ?? '',
                            'ktp_doc_id' => (string) $ktpDocId,
                            'judul_pengaduan' => $formData['judul_pengaduan'] ?? '',
                            'detail_pengaduan' => $formData['detail_pengaduan'] ?? '',
                            'lokasi_kejadian' => $formData['lokasi_kejadian'] ?? '',
                            'waktu_kejadian' => $formData['waktu_kejadian'] ?? ''
                        ]
                    ];
                }
                
                // --- D. JIKA FORM UPLOAD KK DISUBMIT ---
                elseif ($screen === 'UPLOAD_KK') {
                    $mediaId = $formData['kk_base64'] ?? '';
                    $base64Data = $this->downloadMetaMediaAsBase64($mediaId);

                    $docResponse = Http::withHeaders($apiHeaders)->post($apiUrl . '/api/documents', [
                        'file_base64' => $base64Data,
                        'description' => 'Kartu Keluarga (WA Flows)'
                    ]);
                    $kkDocId = $docResponse->json('data.id') ?? '0';

                    $responseData = [
                        'screen' => 'UPLOAD_BUKTI',
                        'data' => [
                            'reporter_id' => $formData['reporter_id'] ?? '',
                            'ktp_doc_id' => $formData['ktp_doc_id'] ?? '',
                            'kk_doc_id' => (string) $kkDocId,
                            'judul_pengaduan' => $formData['judul_pengaduan'] ?? '',
                            'detail_pengaduan' => $formData['detail_pengaduan'] ?? '',
                            'lokasi_kejadian' => $formData['lokasi_kejadian'] ?? '',
                            'waktu_kejadian' => $formData['waktu_kejadian'] ?? ''
                        ]
                    ];
                }
                
                // --- E. JIKA FORM UPLOAD BUKTI DISUBMIT ---
                elseif ($screen === 'UPLOAD_BUKTI') {
                    $mediaId = $formData['pendukung_base64'] ?? '';
                    $base64Data = $this->downloadMetaMediaAsBase64($mediaId);

                    $docResponse = Http::withHeaders($apiHeaders)->post($apiUrl . '/api/documents', [
                        'file_base64' => $base64Data,
                        'description' => 'Dokumen Pendukung Laporan (WA Flows)'
                    ]);
                    $pendukungDocId = $docResponse->json('data.id') ?? '0';

                    $responseData = [
                        'screen' => 'PREVIEW',
                        'data' => [
                            'reporter_id' => $formData['reporter_id'] ?? '',
                            'ktp_doc_id' => $formData['ktp_doc_id'] ?? '',
                            'kk_doc_id' => $formData['kk_doc_id'] ?? '',
                            'pendukung_doc_id' => (string) $pendukungDocId,
                            'judul_pengaduan' => $formData['judul_pengaduan'] ?? '',
                            'detail_pengaduan' => $formData['detail_pengaduan'] ?? '',
                            'lokasi_kejadian' => $formData['lokasi_kejadian'] ?? '',
                            'waktu_kejadian' => $formData['waktu_kejadian'] ?? ''
                        ]
                    ];
                }
                
                // --- F. JIKA PREVIEW DIKONFIRMASI (SUBMIT AKHIR) ---
                elseif ($screen === 'PREVIEW') {
                    $reportResponse = Http::withHeaders($apiHeaders)->post($apiUrl . '/api/reports', [
                        'reporter_id' => (int) ($formData['reporter_id'] ?? 0),
                        'document_ids' => [
                            (int) ($formData['ktp_doc_id'] ?? 0),
                            (int) ($formData['kk_doc_id'] ?? 0),
                            (int) ($formData['pendukung_doc_id'] ?? 0)
                        ],
                        'report_details' => [
                            'subject' => $formData['judul_pengaduan'] ?? '',
                            'details' => $formData['detail_pengaduan'] ?? '',
                            'location' => $formData['lokasi_kejadian'] ?? '',
                            'event_date' => $formData['waktu_kejadian'] ?? '',
                            'source' => $formData['sumber_pengaduan'] ?? 'whatsapp'
                        ]
                    ]);

                    $responseData = [
                        'screen' => 'SUCCESS',
                        'data' => [
                            'ticket_number' => (string) ($reportResponse->json('data.ticket_number') ?? '-'),
                            'category' => (string) ($reportResponse->json('data.category') ?? '-')
                        ]
                    ];
                }
                
                else {
                    $responseData = [
                        'screen' => 'SUCCESS',
                        'data' => ['ticket_number' => 'UNKNOWN_SCREEN', 'category' => '-']
                    ];
                }
            }
            else {
                $responseData = [
                    'screen' => 'SUCCESS',
                    'data' => ['ticket_number' => 'UNKNOWN_ACTION', 'category' => '-']
                ];
            }

            // 7. ENKRIPSI KEMBALI BALASAN UNTUK META
            $flippedIv = '';
            for ($i = 0; $i < strlen($initialVector); $i++) {
                $flippedIv .= chr(~ord($initialVector[$i]) & 0xFF);
            }

            $responseJson = json_encode($responseData);
            $responseAuthTag = '';
            
            // PERBAIKAN: Gunakan aes-128-gcm di sini juga!
            $encryptedResponse = openssl_encrypt(
                $responseJson, 
                'aes-128-gcm', 
                $aesKey, 
                OPENSSL_RAW_DATA, 
                $flippedIv, 
                $responseAuthTag
            );
            
            $finalResponse = base64_encode($encryptedResponse . $responseAuthTag);

            return response($finalResponse, 200)->header('Content-Type', 'text/plain');

        } catch (\Exception $e) {
            Log::error("WA Flows Error: " . $e->getMessage());
            return response('Server Error', 500);
        }
    }

    /**
     * Fungsi helper untuk mengambil file dari Server Meta dan mengubahnya ke Base64
     */
    private function downloadMetaMediaAsBase64($mediaId)
    {
        // 1. Ambil token dari config services.php
        $metaToken = config('services.lmw.wa_meta_token'); 

        // 2. Dapatkan URL unduhan Media dari Graph API Meta
        $response = Http::withToken($metaToken)
            ->get("https://graph.facebook.com/v19.0/{$mediaId}");
        
        if (!$response->successful()) {
            throw new \Exception('Gagal mendapatkan URL dari Meta Media ID: ' . $mediaId);
        }
        
        $mediaUrl = $response->json('url');

        // 3. Download File Biner aslinya (WAJIB pakai token Meta lagi)
        $fileResponse = Http::withToken($metaToken)->get($mediaUrl);
        
        if (!$fileResponse->successful()) {
            throw new \Exception('Gagal mengunduh file biner dokumen dari Meta');
        }

        // 4. Konversi biner ke format Base64 yang siap dikirim ke API LMW
        $mimeType = $fileResponse->header('Content-Type') ?? 'application/pdf';
        $base64 = base64_encode($fileResponse->body());

        return "data:{$mimeType};base64,{$base64}";
    }
}