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
                    
                    // --- MEKANISME AUTO-FORMAT NOMOR HP ---
                    $rawPhone = $formData['phone_number'] ?? '';
                    // 1. Hapus semua karakter selain angka (misal: spasi, strip, tanda +)
                    $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone); 
                    
                    // 2. Ubah awalan 08 atau 8 menjadi awalan 62
                    if (str_starts_with($cleanPhone, '08')) {
                        $cleanPhone = '62' . substr($cleanPhone, 1);
                    } elseif (str_starts_with($cleanPhone, '8')) {
                        $cleanPhone = '62' . $cleanPhone;
                    }

                    // 1. LAPISAN VALIDASI LOKAL
                    $errorMessage = null;
                    if (!preg_match('/^\d{16}$/', $nik)) {
                        $errorMessage = 'NIK harus 16 digit angka.';
                    } elseif (trim($name) === '') {
                        $errorMessage = 'Nama wajib diisi.';
                    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { 
                        $errorMessage = 'Format email tidak valid.';
                    } elseif (trim($address) === '') {
                        $errorMessage = 'Alamat wajib diisi.';
                    } elseif (!preg_match('/^628\d{7,13}$/', $cleanPhone)) {
                        $errorMessage = 'Nomor HP tidak valid.';
                    }

                    // Jika validasi lokal gagal, langsung kembalikan error ke WA
                    if ($errorMessage !== null) {
                        $responseData = [
                            'screen' => 'IDENTITAS',
                            'data' => [
                                'error_message' => $errorMessage
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
                            'phone_number' => $cleanPhone
                        ];

                        $eligibilityResponse = Http::withHeaders($apiHeaders)
                            ->post(url('/api/reporters/check-eligibility-v2'), $payloadLmw);

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
                                'data' => ['error_message' => $apiErrorMessage]
                            ];
                        } else {
                            $reporterResponse = Http::withHeaders($apiHeaders)
                                ->post(url('/api/reporters'), $payloadLmw);
                            $waLog->info("Response API Create Reporter:", $reporterResponse->json() ?? []);
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
                    $ktpData = $formData['ktp_base64'] ?? [];
                    $ktpDocId = '0';
                    $errorMessage = null;

                    if (empty($ktpData)) {
                        $errorMessage = "KTP wajib dilampirkan.";
                    } elseif (count($ktpData) > 1) {
                        $errorMessage = "KTP hanya boleh 1 file saja.";
                    } else {
                        try {
                            if (str_contains($ktpData[0]['cdn_url'], 'EXAMPLE_DATA')) {
                                $base64Data = "data:image/png;base64,..."; // Dummy
                            } else {
                                $base64Data = $this->downloadAndDecryptFlowMedia($ktpData[0], 2 * 1024 * 1024); // Limit 2MB
                            }

                            $docResponse = Http::withHeaders($apiHeaders)->post(url('/api/documents'), [
                                'file_base64' => $base64Data,
                                'description' => 'Dokumen KTP'
                            ]);
                            $ktpDocId = $docResponse->json('data.id') ?? '0';
                        } catch (\Exception $e) {
                            $errorMessage = ($e->getMessage() === 'FILE_TOO_LARGE') ? "Ukuran KTP maksimal 2MB." : "Gagal memproses KTP.";
                        }
                    }

                    if ($errorMessage) {
                        $responseData = ['version' => '3.0', 'screen' => 'UPLOAD_KTP', 'data' => array_merge($formData, ['error_message' => $errorMessage])];
                    } else {
                        $responseData = ['version' => '3.0', 'screen' => 'UPLOAD_KK', 'data' => array_merge($formData, ['ktp_doc_id' => (string) $ktpDocId, 'error_message' => ''])];
                    }
                }
                
                // --- D. JIKA FORM UPLOAD KK DISUBMIT ---
                elseif ($screen === 'UPLOAD_KK') {
                    $kkData = $formData['kk_base64'] ?? [];
                    $kkDocId = '0'; 
                    $errorMessage = null;

                    if (empty($kkData)) {
                        $errorMessage = "KK wajib dilampirkan.";
                    } elseif (count($kkData) > 1) {
                        $errorMessage = "KK hanya boleh 1 file saja.";
                    } else {
                        try {
                            if (str_contains($kkData[0]['cdn_url'], 'EXAMPLE_DATA')) {
                                $base64Data = "data:image/png;base64,..."; // Dummy
                            } else {
                                $base64Data = $this->downloadAndDecryptFlowMedia($kkData[0], 2 * 1024 * 1024); // Limit 2MB
                            }

                            $docResponse = Http::withHeaders($apiHeaders)->post(url('/api/documents'), [
                                'file_base64' => $base64Data,
                                'description' => 'Dokumen Kartu Keluarga'
                            ]);
                            $kkDocId = $docResponse->json('data.id') ?? '0';
                        } catch (\Exception $e) {
                            $errorMessage = ($e->getMessage() === 'FILE_TOO_LARGE') ? "Ukuran KK maksimal 2MB." : "Gagal memproses KK.";
                        }
                    }

                    if ($errorMessage) {
                        $responseData = ['version' => '3.0', 'screen' => 'UPLOAD_KK', 'data' => array_merge($formData, ['error_message' => $errorMessage])];
                    } else {
                        $responseData = ['version' => '3.0', 'screen' => 'UPLOAD_BUKTI', 'data' => array_merge($formData, ['kk_doc_id' => (string) $kkDocId, 'error_message' => ''])];
                    }
                }
                
                // --- E. JIKA FORM UPLOAD BUKTI DISUBMIT ---
                elseif ($screen === 'UPLOAD_BUKTI') {
                    $buktiData = $formData['pendukung_base64'] ?? [];
                    $pendukungDocIds = []; // Bisa banyak ID karena boleh multi-file
                    $errorMessage = null;

                    if (empty($buktiData)) {
                        $errorMessage = "Bukti wajib dilampirkan minimal 1 file.";
                    } else {
                        try {
                            // Looping semua file bukti yang dipilih user
                            foreach ($buktiData as $fileData) {
                                if (str_contains($fileData['cdn_url'], 'EXAMPLE_DATA')) {
                                    $base64Data = "data:image/png;base64,..."; // Dummy
                                } else {
                                    $base64Data = $this->downloadAndDecryptFlowMedia($fileData, 5 * 1024 * 1024); // Limit 5MB per file
                                }

                                $docResponse = Http::withHeaders($apiHeaders)->post(url('/api/documents'), [
                                    'file_base64' => $base64Data,
                                    'description' => 'Dokumen Bukti Pengaduan'
                                ]);
                                
                                $newDocId = $docResponse->json('data.id');
                                if ($newDocId) {
                                    $pendukungDocIds[] = $newDocId;
                                }
                            }
                        } catch (\Exception $e) {
                            $errorMessage = ($e->getMessage() === 'FILE_TOO_LARGE') ? "Terdapat file bukti yang ukurannya melebihi 5MB." : "Gagal memproses Bukti.";
                        }
                    }

                    if ($errorMessage) {
                        $responseData = ['version' => '3.0', 'screen' => 'UPLOAD_BUKTI', 'data' => array_merge($formData, ['error_message' => $errorMessage])];
                    } else {
                        // Gabungkan seluruh ID jadi string dipisah koma (Misal: "15,16,17") jika user upload > 1 bukti
                        $pendukungIdsStr = implode(',', $pendukungDocIds);
                        $responseData = ['version' => '3.0', 'screen' => 'PREVIEW', 'data' => array_merge($formData, ['pendukung_doc_id' => $pendukungIdsStr])];
                    }
                }
                
                // --- F. JIKA PREVIEW DIKONFIRMASI (SUBMIT AKHIR) ---
                elseif ($screen === 'PREVIEW') {
                    // 1. Bersihkan ID Dokumen (Hapus angka 0 dari dokumen yang di-skip)
                    $rawDocIds = [
                        (int) ($formData['ktp_doc_id'] ?? 0),
                        (int) ($formData['kk_doc_id'] ?? 0),
                        (int) ($formData['pendukung_doc_id'] ?? 0)
                    ];
                    // array_filter akan otomatis membuang elemen bernilai 0 atau false
                    $cleanDocIds = array_values(array_filter($rawDocIds));

                    // 2. Format Tanggal dari DatePicker WA (Milidetik) menjadi Y-m-d
                    $waktuRaw = $formData['waktu_kejadian'] ?? '';
                    $waktuFormatted = $waktuRaw;
                    if (is_numeric($waktuRaw)) {
                        // DatePicker WA mengirim timestamp Unix dalam milidetik
                        $waktuFormatted = date('Y-m-d', $waktuRaw / 1000);
                    }

                    $waLog->info("Payload Submit Laporan LMW:", [
                        'doc_ids' => $cleanDocIds,
                        'waktu' => $waktuFormatted
                    ]);

                    $reportResponse = Http::withHeaders($apiHeaders)->post(url('/api/reports'), [
                        'reporter_id' => (int) ($formData['reporter_id'] ?? 0),
                        'document_ids' => $cleanDocIds,
                        'report_details' => [
                            'subject' => $formData['judul_pengaduan'] ?? '',
                            'details' => $formData['detail_pengaduan'] ?? '',
                            'location' => $formData['lokasi_kejadian'] ?? '',
                            'event_date' => $waktuFormatted,
                            'source' => $formData['sumber_pengaduan'] ?? 'whatsapp'
                        ]
                    ]);

                    $waLog->info("Response API Create Report:", $reportResponse->json() ?? []);

                    if ($reportResponse->successful()) {
                        $responseData = [
                            'version' => '3.0',
                            'screen' => 'SELESAI',
                            'data' => [
                                'ticket_number' => (string) ($reportResponse->json('data.ticket_number') ?? '-'),
                                'category' => (string) ($reportResponse->json('data.category') ?? '-')
                            ]
                        ];
                    } else {
                        $waLog->error("API Tolak Submit Laporan!", $reportResponse->json() ?? []);
                        $responseData = [
                            'version' => '3.0',
                            'screen' => 'SELESAI',
                            'data' => [
                                'ticket_number' => 'GAGAL_SISTEM',
                                'category' => 'Mohon ulangi beberapa saat lagi'
                            ]
                        ];
                    }
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
            $waLog = \Illuminate\Support\Facades\Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/wa_flows_debug.log'),
            ]);
            $waLog->error("FATAL WA Flows Error: " . $e->getMessage());
            
            \Illuminate\Support\Facades\Log::error("WA Flows Error: " . $e->getMessage());
            return response('Server Error', 500);
        }
    }

    /**
     * Fungsi helper untuk mengambil file dari Server Meta dan mengubahnya ke Base64
     */
    private function downloadAndDecryptFlowMedia($mediaObject, $maxBytes = 2097152)
    {
        $waLog = \Illuminate\Support\Facades\Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/wa_flows_debug.log'),
        ]);
        
        $cdnUrl = $mediaObject['cdn_url'] ?? '';
        $metadata = $mediaObject['encryption_metadata'] ?? null;
        $fileName = $mediaObject['file_name'] ?? 'document.jpg';

        if (!$cdnUrl || !$metadata) {
            throw new \Exception("Data media tidak lengkap (cdn_url atau encryption_metadata tidak ditemukan).");
        }

        $waLog->info("Mendownload file terenkripsi dari CDN Meta...");

        $response = Http::get($cdnUrl);
        
        if (!$response->successful()) {
            throw new \Exception("Gagal mendownload file dari CDN Meta. Status: " . $response->status());
        }
        
        $downloadedData = $response->body();

        // KUNCI PERBAIKAN: Pisahkan Ciphertext murni dari 10-byte HMAC Mac di ujung file
        $ciphertext = substr($downloadedData, 0, -10);

        // Siapkan Kunci Dekripsi (Konversi dari Base64)
        $encKey = base64_decode($metadata['encryption_key']);
        $iv = base64_decode($metadata['iv']);

        $waLog->info("Mendekripsi file dengan AES-256-CBC...");

        // Dekripsi hanya bagian ciphertext-nya saja
        $decryptedData = openssl_decrypt(
            $ciphertext,
            'aes-256-cbc',
            $encKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decryptedData === false) {
            // Tangkap pesan error OpenSSL spesifik jika masih gagal
            $sslError = "";
            while ($msg = openssl_error_string()) {
                $sslError .= $msg . " | ";
            }
            $waLog->error("Dekripsi OpenSSL Gagal! Detail: " . $sslError);
            throw new \Exception("Gagal mendekripsi file CDN Meta.");
        }

        $fileSize = strlen($decryptedData);
        if ($fileSize > $maxBytes) {
            $waLog->warning("File {$fileName} ditolak karena melebihi batas. Ukuran: {$fileSize} bytes, Max: {$maxBytes} bytes");
            throw new \Exception("FILE_TOO_LARGE");
        }

        // Deteksi Tipe MIME berdasarkan ekstensi file
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeType = match($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };

        // Ubah ke format Base64 untuk dikirim ke API LMW
        $base64 = base64_encode($decryptedData);
        
        $waLog->info("Berhasil mendekripsi dokumen {$fileName}. Ukuran asli: {$fileSize} bytes");

        return "data:{$mimeType};base64,{$base64}";
    }
}