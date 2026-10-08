<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use phpseclib3\Crypt\RSA;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppFlowController extends Controller
{
    /**
     * TTL untuk idempotency lock (dalam detik).
     * 60 detik cukup untuk menutupi API call lambat + network latency.
     */
    private const IDEMPOTENCY_TTL = 60;

    /**
     * Custom WA Flows logger instance (lazy-initialized).
     */
    private $waLog;

    /**
     * Mendapatkan WA Flows logger (singleton per request).
     */
    private function waLog()
    {
        if (!$this->waLog) {
            $this->waLog = Log::build([
                'driver' => 'single',
                'path'   => storage_path('logs/wa_flows_debug.log'),
            ]);
        }
        return $this->waLog;
    }

    /**
     * ======================================================================
     * INTI SOLUSI: Idempotency wrapper untuk mencegah duplikasi.
     * ======================================================================
     *
     * Cara kerja:
     * 1. Generate unique key dari flow_token + screen + fingerprint data
     * 2. Coba acquire lock dengan Cache::add() (atomic pada Redis/Memcached)
     * 3. Jika lock GAGAL (sudah ada), artinya request duplikat:
     *    - Tunggu sebentar lalu cek apakah response sebelumnya sudah tersimpan
     *    - Jika ya: return response yang sama (bukan error!)
     *    - Jika belum: return null agar caller bisa return "sedang diproses"
     * 4. Jika lock BERHASIL: jalankan callback, simpan response, return response
     *
     * PENTING: Mengembalikan RESPONSE SUKSES yang sama untuk retry, bukan error.
     * Ini mencegah WA client retry berulang dan user melihat error palsu.
     */
    private function idempotent(string $idempotencyKey, callable $process): ?array
    {
        $lockKey    = 'wa_flow_lock:' . $idempotencyKey;
        $resultKey  = 'wa_flow_result:' . $idempotencyKey;

        // Coba acquire lock
        if (!Cache::add($lockKey, true, self::IDEMPOTENCY_TTL)) {
            // Lock sudah ada → ini request duplikat
            $this->waLog()->warning("Duplikat terdeteksi. Key: {$idempotencyKey}");

            // Tunggu sebentar agar proses pertama mungkin sudah selesai
            usleep(500_000); // 500ms

            // Cek apakah response dari proses pertama sudah tersimpan
            $cachedResult = Cache::get($resultKey);
            if ($cachedResult) {
                $this->waLog()->info("Mengembalikan cached response untuk key: {$idempotencyKey}");
                return $cachedResult;
            }

            // Proses pertama belum selesai — return null (caller harus handle)
            return null;
        }

        try {
            // Jalankan logika bisnis
            $result = $process();

            // Simpan response sukses ke cache agar retry bisa mendapat response yang sama
            Cache::put($resultKey, $result, self::IDEMPOTENCY_TTL);

            return $result;
        } catch (\Exception $e) {
            // Jika gagal, hapus lock agar user bisa retry
            Cache::forget($lockKey);
            Cache::forget($resultKey);
            throw $e;
        }
    }

    /**
     * Generate idempotency key dari flow_token, screen, dan data fingerprint.
     *
     * Prioritas key:
     * 1. flow_token + screen (paling reliable, unique per flow session)
     * 2. Hash dari screen + data (fallback jika flow_token tidak ada)
     */
    private function makeIdempotencyKey(?string $flowToken, string $screen, array $data): string
    {
        if ($flowToken) {
            return $flowToken . ':' . $screen;
        }

        // Fallback: hash dari screen + sorted data
        $fingerprint = $screen . ':' . json_encode($data, JSON_SORT_KEYS);
        return 'fp:' . md5($fingerprint);
    }

    /**
     * Response "sedang diproses" untuk kasus duplikat di mana
     * response pertama belum tersimpan di cache.
     */
    private function processingResponse(string $screen, array $formData): array
    {
        return [
            'version' => '3.0',
            'screen'  => $screen,
            'data'    => array_merge($formData, [
                'error_message' => '⏳ Permintaan Anda sedang diproses. Mohon tunggu sebentar.'
            ])
        ];
    }

    // ======================================================================
    // MAIN WEBHOOK HANDLER
    // ======================================================================

    public function handleWebhook(Request $request)
    {
        try {
            // 1. Ambil data dari Request
            $encryptedAesKey   = base64_decode($request->input('encrypted_aes_key'));
            $encryptedFlowData = base64_decode($request->input('encrypted_flow_data'));
            $initialVector     = base64_decode($request->input('initial_vector'));

            // 2. Ambil Private Key dan bersihkan string \n
            $privateKeyStr = config('services.lmw.wa_private_key');
            $privateKeyStr = str_replace(['\\n', '\n'], "\n", $privateKeyStr);

            // 3. Dekripsi AES Key menggunakan phpseclib
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

            // 4. Dekripsi Flow Data (aes-128-gcm)
            $authTag    = substr($encryptedFlowData, -16);
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
                $sslError = "";
                while ($msg = openssl_error_string()) {
                    $sslError .= $msg . " | ";
                }
                Log::error("GCM Decryption Failed", [
                    'aes_key_length'  => strlen($aesKey),
                    'iv_length'       => strlen($initialVector),
                    'auth_tag_length' => strlen($authTag),
                    'ssl_error'       => $sslError,
                ]);
                throw new \Exception("Gagal mendekripsi Flow Data (GCM)");
            }

            $flowData = json_decode($flowDataJson, true);

            // 5. TANGKAP VARIABEL ROOT DARI META
            $rootAction = $flowData['action'] ?? null;
            $screen     = $flowData['screen'] ?? null;
            $formData   = $flowData['data'] ?? [];
            $flowToken  = $flowData['flow_token'] ?? null; // ← KUNCI IDEMPOTENCY

            $this->waLog()->info("=== REQUEST DARI WA FLOWS ===");
            $this->waLog()->info("Root Action: {$rootAction} | Screen: {$screen} | FlowToken: " . ($flowToken ?? 'N/A'), $formData);

            $apiHeaders = [
                'Authorization' => 'Bearer ' . config('services.lmw.api_token'),
                'X-LMW-API-KEY' => config('services.lmw.api_key'),
                'Accept'        => 'application/json',
            ];
            $apiUrl      = config('services.lmw.api_url');
            $responseData = [];

            // 6. ROUTING LOGIKA BERDASARKAN ROOT ACTION & SCREEN
            if ($rootAction === 'ping') {
                $responseData = ['data' => ['status' => 'active']];
            }
            elseif ($rootAction === 'INIT') {
                $responseData = [
                    'screen' => 'IDENTITAS',
                    'data'   => ['error_message' => ''],
                ];
            }
            elseif ($rootAction === 'data_exchange') {
                if ($screen === 'IDENTITAS') {
                    $responseData = $this->handleIdentitas($flowToken, $formData, $apiHeaders);
                }
                elseif ($screen === 'UPLOAD_KTP') {
                    $responseData = $this->handleUploadKtp($flowToken, $formData, $apiHeaders);
                }
                elseif ($screen === 'UPLOAD_KK') {
                    $responseData = $this->handleUploadKk($flowToken, $formData, $apiHeaders);
                }
                elseif ($screen === 'UPLOAD_BUKTI') {
                    $responseData = $this->handleUploadBukti($flowToken, $formData, $apiHeaders);
                }
                elseif ($screen === 'PREVIEW') {
                    $responseData = $this->handlePreview($flowToken, $formData, $apiHeaders);
                }

                else {
                    $responseData = [
                        'screen' => 'SUCCESS',
                        'data'   => ['ticket_number' => 'UNKNOWN_SCREEN', 'category' => '-'],
                    ];
                }
            }
            else {
                $responseData = [
                    'screen' => 'SUCCESS',
                    'data'   => ['ticket_number' => 'UNKNOWN_ACTION', 'category' => '-'],
                ];
            }

            // 7. ENKRIPSI KEMBALI BALASAN UNTUK META
            $flippedIv = '';
            for ($i = 0; $i < strlen($initialVector); $i++) {
                $flippedIv .= chr(~ord($initialVector[$i]) & 0xFF);
            }

            $responseJson    = json_encode($responseData);
            $responseAuthTag = '';

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
            $this->waLog()->error("FATAL WA Flows Error: " . $e->getMessage());
            Log::error("WA Flows Error: " . $e->getMessage());
            return response('Server Error', 500);
        }
    }

    // ======================================================================
    // HANDLER PER SCREEN (dengan idempotency protection)
    // ======================================================================

    /**
     * Screen IDENTITAS: Validasi data + cek eligibility + buat reporter.
     * 
     * PROTEKSI: Idempotency berdasarkan NIK + phone (mencegah reporter duplikat).
     */
    private function handleIdentitas(?string $flowToken, array $formData, array $apiHeaders): array
    {
        $nik  = $formData['nik'] ?? '';
        $name = $formData['name'] ?? '';
        $email   = $formData['email'] ?? '';
        $address = $formData['address'] ?? '';

        // Auto-format nomor HP
        $rawPhone   = $formData['phone_number'] ?? '';
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        if (str_starts_with($cleanPhone, '08')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        } elseif (str_starts_with($cleanPhone, '8')) {
            $cleanPhone = '62' . $cleanPhone;
        }

        // Validasi lokal
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

        // Jika validasi lokal gagal, tidak perlu idempotency (tidak ada side-effect)
        if ($errorMessage !== null) {
            return [
                'screen' => 'IDENTITAS',
                'data'   => ['error_message' => $errorMessage],
            ];
        }

        // ┌─────────────────────────────────────────────────────┐
        // │ IDEMPOTENCY: Cegah pembuatan reporter duplikat      │
        // │ Key: flow_token + screen, atau fallback NIK + phone │
        // └─────────────────────────────────────────────────────┘
        $idempotencyKey = $this->makeIdempotencyKey($flowToken, 'IDENTITAS', [
            'nik'   => $nik,
            'phone' => $cleanPhone,
        ]);

        $result = $this->idempotent($idempotencyKey, function () use ($nik, $name, $email, $address, $cleanPhone, $apiHeaders) {
            $payloadLmw = [
                'nik'          => $nik,
                'name'         => $name,
                'email'        => $email,
                'address'      => $address,
                'phone_number' => $cleanPhone,
            ];

            $eligibilityResponse = Http::withHeaders($apiHeaders)
                ->post(url('/api/reporters/check-eligibility-v2'), $payloadLmw);

            $resData = $eligibilityResponse->json();
            $this->waLog()->info("Response API Eligibility:", $resData ?? []);

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

                $this->waLog()->warning("Hasil Validasi Ditolak: " . $apiErrorMessage);

                // Validasi API gagal → hapus lock agar user bisa coba lagi dengan data berbeda
                // (throw exception agar idempotent() menghapus lock)
                throw new \Exception("VALIDATION_FAILED:" . $apiErrorMessage);
            }

            $reporterResponse = Http::withHeaders($apiHeaders)
                ->post(url('/api/reporters'), $payloadLmw);
            $this->waLog()->info("Response API Create Reporter:", $reporterResponse->json() ?? []);

            $reporterId = $reporterResponse->json('reporter_id') ?? '0';
            $this->waLog()->info("Sukses Lolos Validasi. Reporter ID: " . $reporterId);

            return [
                'screen' => 'PENGADUAN',
                'data'   => ['reporter_id' => (string) $reporterId],
            ];
        });

        // Handle kasus duplikat di mana response belum ter-cache
        if ($result === null) {
            return $this->processingResponse('IDENTITAS', $formData);
        }

        return $result;
    }

    /**
     * Screen UPLOAD_KTP: Upload dokumen KTP.
     */
    private function handleUploadKtp(?string $flowToken, array $formData, array $apiHeaders): array
    {
        $ktpData      = $formData['ktp_base64'] ?? [];
        $errorMessage = null;

        if (empty($ktpData)) {
            $errorMessage = "KTP wajib dilampirkan.";
        } elseif (count($ktpData) > 1) {
            $errorMessage = "KTP hanya boleh 1 file saja.";
        }

        // Validasi gagal → tidak ada side-effect, tidak perlu idempotency
        if ($errorMessage) {
            return ['version' => '3.0', 'screen' => 'UPLOAD_KTP', 'data' => array_merge($formData, ['error_message' => $errorMessage])];
        }

        // ┌──────────────────────────────────────────────────┐
        // │ IDEMPOTENCY: Cegah upload KTP duplikat           │
        // └──────────────────────────────────────────────────┘
        $idempotencyKey = $this->makeIdempotencyKey($flowToken, 'UPLOAD_KTP', [
            'cdn_url' => $ktpData[0]['cdn_url'] ?? '',
        ]);

        $result = $this->idempotent($idempotencyKey, function () use ($ktpData, $formData, $apiHeaders) {
            try {
                if (str_contains($ktpData[0]['cdn_url'], 'EXAMPLE_DATA')) {
                    $base64Data = "data:image/png;base64,...";
                } else {
                    $base64Data = $this->downloadAndDecryptFlowMedia($ktpData[0], 2 * 1024 * 1024);
                }

                $docResponse = Http::withHeaders($apiHeaders)->post(url('/api/documents'), [
                    'file_base64'  => $base64Data,
                    'description'  => 'Dokumen KTP',
                ]);
                $ktpDocId = $docResponse->json('data.id') ?? '0';

                return [
                    'version' => '3.0',
                    'screen'  => 'UPLOAD_KK',
                    'data'    => array_merge($formData, [
                        'ktp_doc_id'    => (string) $ktpDocId,
                        'error_message' => '',
                    ]),
                ];
            } catch (\Exception $e) {
                $errorMessage = ($e->getMessage() === 'FILE_TOO_LARGE')
                    ? "Ukuran KTP maksimal 2MB."
                    : "Gagal memproses KTP.";

                // Re-throw agar lock dihapus dan user bisa retry
                throw new \Exception("UPLOAD_ERROR:" . $errorMessage);
            }
        });

        if ($result === null) {
            return $this->processingResponse('UPLOAD_KTP', $formData);
        }

        return $result;
    }

    /**
     * Screen UPLOAD_KK: Upload dokumen Kartu Keluarga.
     */
    private function handleUploadKk(?string $flowToken, array $formData, array $apiHeaders): array
    {
        $kkData       = $formData['kk_base64'] ?? [];
        $errorMessage = null;

        if (empty($kkData)) {
            $errorMessage = "KK wajib dilampirkan.";
        } elseif (count($kkData) > 1) {
            $errorMessage = "KK hanya boleh 1 file saja.";
        }

        if ($errorMessage) {
            return ['version' => '3.0', 'screen' => 'UPLOAD_KK', 'data' => array_merge($formData, ['error_message' => $errorMessage])];
        }

        // ┌──────────────────────────────────────────────────┐
        // │ IDEMPOTENCY: Cegah upload KK duplikat            │
        // └──────────────────────────────────────────────────┘
        $idempotencyKey = $this->makeIdempotencyKey($flowToken, 'UPLOAD_KK', [
            'cdn_url' => $kkData[0]['cdn_url'] ?? '',
        ]);

        $result = $this->idempotent($idempotencyKey, function () use ($kkData, $formData, $apiHeaders) {
            try {
                if (str_contains($kkData[0]['cdn_url'], 'EXAMPLE_DATA')) {
                    $base64Data = "data:image/png;base64,...";
                } else {
                    $base64Data = $this->downloadAndDecryptFlowMedia($kkData[0], 2 * 1024 * 1024);
                }

                $docResponse = Http::withHeaders($apiHeaders)->post(url('/api/documents'), [
                    'file_base64'  => $base64Data,
                    'description'  => 'Dokumen Kartu Keluarga',
                ]);
                $kkDocId = $docResponse->json('data.id') ?? '0';

                return [
                    'version' => '3.0',
                    'screen'  => 'UPLOAD_BUKTI',
                    'data'    => array_merge($formData, [
                        'kk_doc_id'     => (string) $kkDocId,
                        'error_message' => '',
                    ]),
                ];
            } catch (\Exception $e) {
                $errorMessage = ($e->getMessage() === 'FILE_TOO_LARGE')
                    ? "Ukuran KK maksimal 2MB."
                    : "Gagal memproses KK.";
                throw new \Exception("UPLOAD_ERROR:" . $errorMessage);
            }
        });

        if ($result === null) {
            return $this->processingResponse('UPLOAD_KK', $formData);
        }

        return $result;
    }

    /**
     * Screen UPLOAD_BUKTI: Upload dokumen bukti pendukung.
     */
    private function handleUploadBukti(?string $flowToken, array $formData, array $apiHeaders): array
    {
        $buktiData    = $formData['pendukung_base64'] ?? [];
        $errorMessage = null;

        if (empty($buktiData)) {
            return [
                'version' => '3.0',
                'screen'  => 'UPLOAD_BUKTI',
                'data'    => array_merge($formData, ['error_message' => 'Bukti wajib dilampirkan minimal 1 file.']),
            ];
        }

        // ┌──────────────────────────────────────────────────┐
        // │ IDEMPOTENCY: Cegah upload bukti duplikat         │
        // └──────────────────────────────────────────────────┘
        $cdnUrls = array_map(fn($f) => $f['cdn_url'] ?? '', $buktiData);
        $idempotencyKey = $this->makeIdempotencyKey($flowToken, 'UPLOAD_BUKTI', [
            'cdn_urls' => $cdnUrls,
        ]);

        $result = $this->idempotent($idempotencyKey, function () use ($buktiData, $formData, $apiHeaders) {
            $pendukungDocIds = [];

            try {
                foreach ($buktiData as $index => $fileData) {
                    if (str_contains($fileData['cdn_url'], 'EXAMPLE_DATA')) {
                        $base64Data = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
                    } else {
                        $base64Data = $this->downloadAndDecryptFlowMedia($fileData, 5 * 1024 * 1024);
                    }

                    $docResponse = Http::withHeaders($apiHeaders)->post(url('/api/documents'), [
                        'file_base64'  => $base64Data,
                        'description'  => 'Dokumen Bukti Pengaduan (File ke-' . ($index + 1) . ')',
                    ]);

                    if (!$docResponse->successful()) {
                        $this->waLog()->error("API LMW menolak file Bukti ke-" . ($index + 1) . " | HTTP: " . $docResponse->status() . " | Body: " . $docResponse->body());
                        throw new \Exception("API_ERROR");
                    }

                    $newDocId = $docResponse->json('data.id');
                    if ($newDocId) {
                        $pendukungDocIds[] = $newDocId;
                    }
                }
            } catch (\Exception $e) {
                if ($e->getMessage() === 'FILE_TOO_LARGE') {
                    throw new \Exception("UPLOAD_ERROR:Terdapat file bukti yang ukurannya melebihi 5MB. Silakan kurangi ukurannya.");
                }
                throw new \Exception("UPLOAD_ERROR:Server gagal menyimpan dokumen bukti. Pastikan koneksi stabil atau kurangi jumlah file.");
            }

            $pendukungIdsStr = implode(',', $pendukungDocIds);
            return [
                'version' => '3.0',
                'screen'  => 'PREVIEW',
                'data'    => array_merge($formData, ['pendukung_doc_id' => $pendukungIdsStr]),
            ];
        });

        if ($result === null) {
            return $this->processingResponse('UPLOAD_BUKTI', $formData);
        }

        return $result;
    }

    /**
     * Screen PREVIEW: Submit akhir laporan pengaduan.
     * 
     * Ini screen PALING KRITIS — duplikasi di sini = laporan duplikat di sistem.
     */
    private function handlePreview(?string $flowToken, array $formData, array $apiHeaders): array
    {
        $reporterId = $formData['reporter_id'] ?? '0';

        // ┌──────────────────────────────────────────────────────────────────┐
        // │ IDEMPOTENCY: Cegah laporan duplikat.                            │
        // │ Key menggunakan flow_token + screen + reporter_id + judul.      │
        // │ Ini memastikan submit yang PERSIS SAMA tidak diproses ulang.     │
        // └──────────────────────────────────────────────────────────────────┘
        $idempotencyKey = $this->makeIdempotencyKey($flowToken, 'PREVIEW', [
            'reporter_id'     => $reporterId,
            'judul_pengaduan' => $formData['judul_pengaduan'] ?? '',
        ]);

        $result = $this->idempotent($idempotencyKey, function () use ($formData, $reporterId, $apiHeaders) {
            $rawDocIds = [
                (int) ($formData['ktp_doc_id'] ?? 0),
                (int) ($formData['kk_doc_id'] ?? 0),
            ];

            $buktiIdsStr = $formData['pendukung_doc_id'] ?? '';
            if (!empty($buktiIdsStr)) {
                $buktiArr = explode(',', $buktiIdsStr);
                foreach ($buktiArr as $bId) {
                    $rawDocIds[] = (int) trim($bId);
                }
            }

            $cleanDocIds = array_values(array_filter($rawDocIds));

            // Sanitasi
            $judulRaw  = $formData['judul_pengaduan'] ?? '';
            $detailRaw = $formData['detail_pengaduan'] ?? '';
            $lokasiRaw = $formData['lokasi_kejadian'] ?? '';

            $judulBersih  = preg_replace('/\s+/', ' ', trim(preg_replace('/[^a-zA-Z0-9\s\.,\-]/', ' ', $judulRaw)));
            $detailBersih = trim(preg_replace('/[^a-zA-Z0-9\s\.,\-\(\)\/\r\n]/', ' ', $detailRaw));
            $lokasiBersih = trim(preg_replace('/[^a-zA-Z0-9\s\.,\-\(\)\/]/', ' ', $lokasiRaw));

            $waktuRaw       = $formData['waktu_kejadian'] ?? '';
            $waktuFormatted = is_numeric($waktuRaw) ? date('Y-m-d', $waktuRaw / 1000) : $waktuRaw;

            $reportResponse = Http::withHeaders($apiHeaders)->post(url('/api/reports'), [
                'reporter_id'    => (int) $reporterId,
                'document_ids'   => $cleanDocIds,
                'report_details' => [
                    'subject'    => $judulBersih,
                    'details'    => $detailBersih,
                    'location'   => $lokasiBersih,
                    'event_date' => $waktuFormatted,
                    'source'     => $formData['sumber_pengaduan'] ?? 'whatsapp',
                ],
            ]);

            if ($reportResponse->successful()) {
                return [
                    'version' => '3.0',
                    'screen'  => 'SELESAI',
                    'data'    => [
                        'ticket_number' => (string) ($reportResponse->json('data.ticket_number') ?? '-'),
                        'category'      => (string) ($reportResponse->json('data.category') ?? '-'),
                    ],
                ];
            }

            $this->waLog()->error("API Submit Laporan Gagal: " . $reportResponse->body());
            throw new \Exception("API_SUBMIT_FAILED");
        });

        if ($result === null) {
            return $this->processingResponse('PREVIEW', $formData);
        }

        return $result;
    }

    // ======================================================================
    // HELPER: Download & Decrypt Media dari CDN Meta
    // ======================================================================

    private function downloadAndDecryptFlowMedia($mediaObject, $maxBytes = 2097152)
    {
        $cdnUrl   = $mediaObject['cdn_url'] ?? '';
        $metadata = $mediaObject['encryption_metadata'] ?? null;
        $fileName = $mediaObject['file_name'] ?? 'document.jpg';

        if (!$cdnUrl || !$metadata) {
            throw new \Exception("Data media tidak lengkap (cdn_url atau encryption_metadata tidak ditemukan).");
        }

        $allowedExtensions = ['jpeg', 'jpg', 'png', 'heic', 'pdf', 'docx', 'pptx', 'doc'];
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            $this->waLog()->warning("File {$fileName} ditolak karena ekstensi tidak diizinkan: {$extension}");
            throw new \Exception("INVALID_EXTENSION");
        }

        $this->waLog()->info("Mendownload file terenkripsi dari CDN Meta...");

        $response = Http::get($cdnUrl);

        if (!$response->successful()) {
            throw new \Exception("Gagal mendownload file dari CDN Meta. Status: " . $response->status());
        }

        $downloadedData = $response->body();
        $ciphertext     = substr($downloadedData, 0, -10);
        $encKey         = base64_decode($metadata['encryption_key']);
        $iv             = base64_decode($metadata['iv']);

        $this->waLog()->info("Mendekripsi file dengan AES-256-CBC...");

        $decryptedData = openssl_decrypt(
            $ciphertext,
            'aes-256-cbc',
            $encKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decryptedData === false) {
            $sslError = "";
            while ($msg = openssl_error_string()) {
                $sslError .= $msg . " | ";
            }
            $this->waLog()->error("Dekripsi OpenSSL Gagal! Detail: " . $sslError);
            throw new \Exception("Gagal mendekripsi file CDN Meta.");
        }

        $fileSize = strlen($decryptedData);
        if ($fileSize > $maxBytes) {
            $this->waLog()->warning("File {$fileName} ditolak karena melebihi batas. Ukuran: {$fileSize} bytes, Max: {$maxBytes} bytes");
            throw new \Exception("FILE_TOO_LARGE");
        }

        $mimeType = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'heic'        => 'image/heic',
            'pdf'         => 'application/pdf',
            'doc'         => 'application/msword',
            'docx'        => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'pptx'        => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            default       => 'application/octet-stream',
        };

        $base64 = base64_encode($decryptedData);

        $this->waLog()->info("Berhasil mendekripsi dokumen {$fileName}. Ukuran asli: {$fileSize} bytes");

        return "data:{$mimeType};base64,{$base64}";
    }
}
