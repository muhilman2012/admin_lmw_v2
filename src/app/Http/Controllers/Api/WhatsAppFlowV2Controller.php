<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use phpseclib3\Crypt\RSA;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppFlowV2Controller extends Controller
{
    private const LOCK_TTL = 60;

    public function handleWebhook(Request $request)
    {
        $waLog = Log::build([
            'driver' => 'single',
            'path'   => storage_path('logs/wa_flows_debug.log'),
        ]);

        try {
            // 1. Ambil data enkripsi dari Request Meta
            $encryptedAesKey   = base64_decode($request->input('encrypted_aes_key'));
            $encryptedFlowData = base64_decode($request->input('encrypted_flow_data'));
            $initialVector     = base64_decode($request->input('initial_vector'));

            // 2. Dekripsi Kunci AES menggunakan Private Key
            $privateKeyStr = config('services.lmw.wa_private_key');
            $privateKeyStr = str_replace(['\\n', '\n'], "\n", $privateKeyStr);

            try {
                $rsa = RSA::load($privateKeyStr)
                    ->withPadding(RSA::ENCRYPTION_OAEP)
                    ->withHash('sha256')
                    ->withMGFHash('sha256');

                $aesKey = $rsa->decrypt($encryptedAesKey);
            } catch (\Exception $e) {
                $waLog->error("RSA Decryption Failed: " . $e->getMessage());
                throw new \Exception("Gagal mendekripsi AES Key");
            }

            // 3. Dekripsi Flow Data (AES-128-GCM)
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
                $waLog->error("GCM Decryption Failed", ['ssl_error' => $sslError]);
                throw new \Exception("Gagal mendekripsi Flow Data (GCM)");
            }

            $flowData = json_decode($flowDataJson, true);

            $rootAction = $flowData['action'] ?? null;
            $screen     = $flowData['screen'] ?? null;
            $formData   = $flowData['data'] ?? [];
            $flowToken  = $flowData['flow_token'] ?? null;

            $waLog->info("=== REQUEST DARI WA FLOWS ===");
            $waLog->info("Action: {$rootAction} | Screen: {$screen} | Token: {$flowToken}", $formData);

            $apiHeaders = [
                'Authorization' => 'Bearer ' . config('services.lmw.api_token'),
                'X-LMW-API-KEY' => config('services.lmw.api_key'),
                'Accept'        => 'application/json',
            ];

            $responseData = [];

            // 4. ROUTING BERDASARKAN ROOT ACTION & SCREEN
            if ($rootAction === 'ping') {
                $responseData = ['data' => ['status' => 'active']];
            } 
            elseif ($rootAction === 'INIT') {
                $responseData = [
                    'screen' => 'MENU',
                    'data'   => ['error_message' => '']
                ];
            } 
            elseif ($rootAction === 'data_exchange') {
                
                // =================================================================
                // 1. SCREEN: MENU UTAMA (SUDAH DISINKRONKAN DENGAN JSON)
                // =================================================================
                if ($screen === 'MENU') {
                    // Mendukung 'pilihan_menu' sesuai JSON Flow
                    $pilihan = $formData['pilihan_menu'] ?? $formData['menu_pilihan'] ?? '';

                    if ($pilihan === 'kirim_laporan' || $pilihan === 'buat_pengaduan') {
                        $responseData = [
                            'version' => '3.0',
                            'screen'  => 'IDENTITAS',
                            'data'    => ['error_message' => '']
                        ];
                    elseif ($pilihan === 'cek_status') {
                        $responseData = [
                            'version' => '3.0',
                            'screen'  => 'CEK_STATUS',
                            'data'    => [
                                'error_message' => ' '
                            ]
                        ];
                    } elseif ($pilihan === 'kirim_dokumen') {
                        // Sesuai routing_model JSON: "KIRIM_DOKUMEN"
                        $responseData = [
                            'version' => '3.0',
                            'screen'  => 'KIRIM_DOKUMEN',
                            'data'    => ['error_message' => '']
                        ];
                    } else {
                        $responseData = [
                            'version' => '3.0',
                            'screen'  => 'MENU',
                            'data'    => ['error_message' => 'Silakan pilih layanan yang Anda butuhkan terlebih dahulu.']
                        ];
                    }
                }

                // =================================================================
                // 2. SCREEN: CEK STATUS (VERIFIKASI 2 LANGKAH)
                // =================================================================
                elseif ($screen === 'CEK_STATUS') {
                    $ticketNumber = trim($formData['nomor_tiket'] ?? $formData['ticket_number'] ?? '');
                    $nik          = trim($formData['nik'] ?? '');
                    // Validasi lokal
                    if ($ticketNumber === '') {
                        $errorMessage = '⚠️ Nomor tiket wajib diisi.';
                    } elseif (!preg_match('/^\d{16}$/', $nik)) {
                        $errorMessage = '⚠️ NIK harus 16 digit angka.';
                    } else {
                        $errorMessage = null;
                    }
                    if ($errorMessage) {
                        $responseData = [
                            'version' => '3.0',
                            'screen'  => 'CEK_STATUS',
                            'data'    => array_merge($formData, ['error_message' => $errorMessage])
                        ];
                    } else {
                        // Langkah 1: Cek apakah laporan ada
                        $checkRes = Http::withHeaders($apiHeaders)->get(url("/api/reports/{$ticketNumber}/check"));
                        $exists   = $checkRes->json('data.exists') ?? false;
                        if (!$checkRes->successful() || !$exists) {
                            $responseData = [
                                'version' => '3.0',
                                'screen'  => 'CEK_STATUS',
                                'data'    => array_merge($formData, [
                                    'error_message' => '⚠️ Nomor tiket tidak ditemukan. Mohon periksa kembali.'
                                ])
                            ];
                        } else {
                            // Langkah 2: Verifikasi NIK
                            $verifyRes = Http::withHeaders($apiHeaders)->get(url("/api/reports/{$ticketNumber}/verify"), ['nik' => $nik]);
                            $verified  = $verifyRes->json('data.verified') ?? false;
                            if (!$verifyRes->successful() || !$verified) {
                                $responseData = [
                                    'version' => '3.0',
                                    'screen'  => 'CEK_STATUS',
                                    'data'    => array_merge($formData, [
                                        'error_message' => '⚠️ NIK tidak sesuai dengan data pelapor nomor tiket ini.'
                                    ])
                                ];
                            } else {
                                // Langkah 3: Ambil status & kirim HANYA 5 data yang diminta
                                $statusRes = Http::withHeaders($apiHeaders)->get(url("/api/reports/{$ticketNumber}/status"));
                                if ($statusRes->successful()) {
                                    $info = $statusRes->json('data') ?? [];
                                    $responseData = [
                                        'version' => '3.0',
                                        'screen'  => 'DETAIL_LAPORAN',
                                        'data'    => [
                                            'nomor_tiket'       => (string) ($info['ticket_number'] ?? $ticketNumber),
                                            'nama_pelapor'      => (string) ($info['nama_pengadu'] ?? '-'),
                                            'tanggal_aduan'     => (string) ($info['tanggal_laporan'] ?? date('d-m-Y')),
                                            'status_laporan'    => (string) ($info['status_laporan'] ?? 'Dalam Proses'),
                                            'tanggapan_laporan' => (string) ($info['tanggapan'] ?? 'Laporan pengaduan Saudara dalam proses verifikasi & penelaahan.')
                                        ]
                                    ];
                                } else {
                                    $responseData = [
                                        'version' => '3.0',
                                        'screen'  => 'CEK_STATUS',
                                        'data'    => array_merge($formData, [
                                            'error_message' => '⚠️ Gagal mengambil detail status. Silakan coba kembali.'
                                        ])
                                    ];
                                }
                            }
                        }
                    }
                }

                // =================================================================
                // 3. SCREEN: KIRIM DOKUMEN (VERIFIKASI & ELIGIBILITAS)
                // =================================================================
                elseif ($screen === 'KIRIM_DOKUMEN') {
                    $ticketNumber = trim($formData['nomor_tiket'] ?? $formData['ticket_number'] ?? '');
                    $nik          = trim($formData['nik'] ?? '');

                    if ($ticketNumber === '') {
                        $errorMessage = 'Nomor tiket wajib diisi.';
                    } elseif (!preg_match('/^\d{16}$/', $nik)) {
                        $errorMessage = 'NIK harus 16 digit angka.';
                    } else {
                        $errorMessage = null;
                    }

                    if ($errorMessage) {
                        $responseData = [
                            'version' => '3.0',
                            'screen'  => 'KIRIM_DOKUMEN',
                            'data'    => array_merge($formData, ['error_message' => $errorMessage])
                        ];
                    } else {
                        // 1. Cek Tiket
                        $checkRes = Http::withHeaders($apiHeaders)->get(url("/api/reports/{$ticketNumber}/check"));
                        if (!$checkRes->successful() || !($checkRes->json('data.exists') ?? false)) {
                            $responseData = [
                                'version' => '3.0',
                                'screen'  => 'KIRIM_DOKUMEN',
                                'data'    => array_merge($formData, ['error_message' => 'Nomor tiket tidak ditemukan.'])
                            ];
                        } else {
                            // 2. Verifikasi NIK
                            $verifyRes = Http::withHeaders($apiHeaders)->get(url("/api/reports/{$ticketNumber}/verify"), ['nik' => $nik]);
                            if (!$verifyRes->successful() || !($verifyRes->json('data.verified') ?? false)) {
                                $responseData = [
                                    'version' => '3.0',
                                    'screen'  => 'KIRIM_DOKUMEN',
                                    'data'    => array_merge($formData, ['error_message' => 'NIK tidak sesuai dengan data pemohon.'])
                                ];
                            } else {
                                // 3. Cek Eligibilitas Dokumen Tambahan
                                $eligibilityRes = Http::withHeaders($apiHeaders)->get(url("/api/reports/{$ticketNumber}/document-eligibility"));
                                $isEligible     = $eligibilityRes->json('data.eligible') ?? false;

                                if (!$isEligible) {
                                    $msg = $eligibilityRes->json('message') ?? 'Laporan ini tidak dalam status yang membutuhkan data dukung tambahan.';
                                    $responseData = [
                                        'version' => '3.0',
                                        'screen'  => 'KIRIM_DOKUMEN',
                                        'data'    => array_merge($formData, ['error_message' => $msg])
                                    ];
                                } else {
                                    // Arahkan ke screen UPLOAD_DOKUMEN_TAMBAHAN
                                    $responseData = [
                                        'version' => '3.0',
                                        'screen'  => 'UPLOAD_DOKUMEN_TAMBAHAN',
                                        'data'    => [
                                            'nomor_tiket'     => (string) $ticketNumber,
                                            'reporter_id'     => '0',
                                            'judul_pengaduan' => 'Laporan Tiket #' . $ticketNumber,
                                            'error_message'   => ''
                                        ]
                                    ];
                                }
                            }
                        }
                    }
                }

                // =================================================================
                // 4. SCREEN: UPLOAD DOKUMEN TAMBAHAN (DILINDUNGI IDEMPOTENCY)
                // =================================================================
                elseif ($screen === 'UPLOAD_DOKUMEN_TAMBAHAN') {
                    $ticketNumber = $formData['nomor_tiket'] ?? '';
                    $dokumenData  = $formData['dokumen_tambahan_base64'] ?? [];
                    $keterangan   = $formData['keterangan_dokumen'] ?? 'Dokumen Pengaduan Tambahan via WhatsApp';

                    if (empty($dokumenData)) {
                        $responseData = [
                            'version' => '3.0',
                            'screen'  => 'UPLOAD_DOKUMEN_TAMBAHAN',
                            'data'    => array_merge($formData, ['error_message' => 'Dokumen tambahan wajib dilampirkan.'])
                        ];
                    } else {
                        $idempotencyKey  = 'submit_doc_lock_' . md5($ticketNumber . ($flowToken ?? json_encode($dokumenData)));
                        $cachedResultKey = 'submit_doc_res_' . $idempotencyKey;

                        if (!Cache::add($idempotencyKey, true, self::LOCK_TTL)) {
                            $cachedResponse = Cache::get($cachedResultKey);
                            if ($cachedResponse) {
                                $responseData = $cachedResponse;
                            } else {
                                $responseData = [
                                    'version' => '3.0',
                                    'screen'  => 'UPLOAD_DOKUMEN_TAMBAHAN',
                                    'data'    => array_merge($formData, [
                                        'error_message' => '⏳ Dokumen sedang diproses. Mohon tunggu sebentar...'
                                    ])
                                ];
                            }
                        } else {
                            try {
                                $fileObj = is_array($dokumenData) && isset($dokumenData[0]) ? $dokumenData[0] : $dokumenData;

                                if (str_contains($fileObj['cdn_url'] ?? '', 'EXAMPLE_DATA')) {
                                    $base64Data = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
                                } else {
                                    $base64Data = $this->downloadAndDecryptFlowMedia($fileObj, 5 * 1024 * 1024);
                                }

                                $docResponse = Http::withHeaders($apiHeaders)
                                    ->patch(url("/api/reports/{$ticketNumber}/document-additional"), [
                                        'file_base64' => $base64Data,
                                        'description' => $keterangan,
                                    ]);

                                if ($docResponse->successful()) {
                                    $responseData = [
                                        'version' => '3.0',
                                        'screen'  => 'DOKUMEN_TERKIRIM', // Sesuai JSON routing_model
                                        'data'    => [
                                            'nomor_tiket' => (string) $ticketNumber,
                                        ]
                                    ];

                                    Cache::put($cachedResultKey, $responseData, self::LOCK_TTL);
                                } else {
                                    Cache::forget($idempotencyKey);
                                    $errBody = $docResponse->json('message') ?? 'Gagal mengunggah dokumen tambahan.';
                                    $responseData = [
                                        'version' => '3.0',
                                        'screen'  => 'UPLOAD_DOKUMEN_TAMBAHAN',
                                        'data'    => array_merge($formData, ['error_message' => $errBody])
                                    ];
                                }
                            } catch (\Exception $e) {
                                Cache::forget($idempotencyKey);
                                $msg = ($e->getMessage() === 'FILE_TOO_LARGE')
                                    ? 'Ukuran file melebihi batas 5MB.'
                                    : 'Kendala saat memproses dokumen: ' . $e->getMessage();

                                $responseData = [
                                    'version' => '3.0',
                                    'screen'  => 'UPLOAD_DOKUMEN_TAMBAHAN',
                                    'data'    => array_merge($formData, ['error_message' => $msg])
                                ];
                            }
                        }
                    }
                }

                // =================================================================
                // 5. ALUR PENGADUAN BARU
                // =================================================================
                elseif ($screen === 'IDENTITAS') {
                    $nik      = $formData['nik'] ?? '';
                    $name     = $formData['name'] ?? '';
                    $email    = $formData['email'] ?? '';
                    $address  = $formData['address'] ?? '';
                    $rawPhone = $formData['phone_number'] ?? '';

                    $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
                    if (str_starts_with($cleanPhone, '08')) {
                        $cleanPhone = '62' . substr($cleanPhone, 1);
                    } elseif (str_starts_with($cleanPhone, '8')) {
                        $cleanPhone = '62' . $cleanPhone;
                    }

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

                    if ($errorMessage !== null) {
                        $responseData = [
                            'version' => '3.0',
                            'screen'  => 'IDENTITAS',
                            'data'    => array_merge($formData, ['error_message' => $errorMessage])
                        ];
                    } else {
                        $payloadLmw = [
                            'nik'          => $nik,
                            'name'         => $name,
                            'email'        => $email,
                            'address'      => $address,
                            'phone_number' => $cleanPhone
                        ];

                        $eligibilityResponse = Http::withHeaders($apiHeaders)
                            ->post(url('/api/reporters/check-eligibility-v2'), $payloadLmw);

                        $resData = $eligibilityResponse->json();

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

                            $responseData = [
                                'version' => '3.0',
                                'screen'  => 'IDENTITAS',
                                'data'    => array_merge($formData, ['error_message' => $apiErrorMessage])
                            ];
                        } else {
                            $reporterResponse = Http::withHeaders($apiHeaders)->post(url('/api/reporters'), $payloadLmw);
                            $reporterId = $reporterResponse->json('reporter_id') ?? '0';

                            $responseData = [
                                'version' => '3.0',
                                'screen'  => 'PENGADUAN',
                                'data'    => ['reporter_id' => (string) $reporterId]
                            ];
                        }
                    }
                }

                elseif ($screen === 'UPLOAD_KTP') {
                    $ktpData  = $formData['ktp_base64'] ?? [];
                    $ktpDocId = '0';
                    $errorMessage = null;

                    if (empty($ktpData)) {
                        $errorMessage = "KTP wajib dilampirkan.";
                    } elseif (count($ktpData) > 1) {
                        $errorMessage = "KTP hanya boleh 1 file saja.";
                    } else {
                        try {
                            if (str_contains($ktpData[0]['cdn_url'], 'EXAMPLE_DATA')) {
                                $base64Data = "data:image/png;base64,...";
                            } else {
                                $base64Data = $this->downloadAndDecryptFlowMedia($ktpData[0], 2 * 1024 * 1024);
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

                elseif ($screen === 'UPLOAD_KK') {
                    $kkData   = $formData['kk_base64'] ?? [];
                    $kkDocId  = '0';
                    $errorMessage = null;

                    if (empty($kkData)) {
                        $errorMessage = "KK wajib dilampirkan.";
                    } elseif (count($kkData) > 1) {
                        $errorMessage = "KK hanya boleh 1 file saja.";
                    } else {
                        try {
                            if (str_contains($kkData[0]['cdn_url'], 'EXAMPLE_DATA')) {
                                $base64Data = "data:image/png;base64,...";
                            } else {
                                $base64Data = $this->downloadAndDecryptFlowMedia($kkData[0], 2 * 1024 * 1024);
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

                elseif ($screen === 'UPLOAD_BUKTI') {
                    $buktiData = $formData['pendukung_base64'] ?? [];
                    $pendukungDocIds = [];
                    $errorMessage = null;

                    if (empty($buktiData)) {
                        $errorMessage = "Bukti wajib dilampirkan minimal 1 file.";
                    } else {
                        try {
                            foreach ($buktiData as $index => $fileData) {
                                if (str_contains($fileData['cdn_url'], 'EXAMPLE_DATA')) {
                                    $base64Data = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
                                } else {
                                    $base64Data = $this->downloadAndDecryptFlowMedia($fileData, 5 * 1024 * 1024);
                                }

                                $docResponse = Http::withHeaders($apiHeaders)->post(url('/api/documents'), [
                                    'file_base64' => $base64Data,
                                    'description' => 'Dokumen Bukti Pengaduan (File ke-' . ($index + 1) . ')'
                                ]);

                                if (!$docResponse->successful()) {
                                    throw new \Exception("API_ERROR");
                                }

                                $newDocId = $docResponse->json('data.id');
                                if ($newDocId) {
                                    $pendukungDocIds[] = $newDocId;
                                }
                            }
                        } catch (\Exception $e) {
                            $errorMessage = ($e->getMessage() === 'FILE_TOO_LARGE')
                                ? "Terdapat file bukti yang ukurannya melebihi 5MB."
                                : "Server gagal menyimpan dokumen bukti.";
                        }
                    }

                    if ($errorMessage) {
                        $responseData = [
                            'version' => '3.0',
                            'screen'  => 'UPLOAD_BUKTI',
                            'data'    => array_merge($formData, ['error_message' => $errorMessage])
                        ];
                    } else {
                        $pendukungIdsStr = implode(',', $pendukungDocIds);
                        $responseData = [
                            'version' => '3.0',
                            'screen'  => 'PREVIEW',
                            'data'    => array_merge($formData, ['pendukung_doc_id' => $pendukungIdsStr])
                        ];
                    }
                }

                elseif ($screen === 'PREVIEW') {
                    $reporterId = $formData['reporter_id'] ?? '0';
                    $judul      = $formData['judul_pengaduan'] ?? '';

                    $lockKey = 'submit_report_lock_' . md5($reporterId . $judul . ($flowToken ?? ''));
                    $cachedSuccessKey = 'submit_report_success_' . $lockKey;

                    if (!Cache::add($lockKey, true, self::LOCK_TTL)) {
                        $cachedResponse = Cache::get($cachedSuccessKey);
                        if ($cachedResponse) {
                            $responseData = $cachedResponse;
                        } else {
                            $responseData = [
                                'version' => '3.0',
                                'screen'  => 'PREVIEW',
                                'data'    => array_merge($formData, [
                                    'error_message' => '⏳ Laporan Anda sedang diproses. Mohon tunggu sebentar...'
                                ])
                            ];
                        }
                    } else {
                        try {
                            $rawDocIds = [
                                (int) ($formData['ktp_doc_id'] ?? 0),
                                (int) ($formData['kk_doc_id'] ?? 0)
                            ];

                            $buktiIdsStr = $formData['pendukung_doc_id'] ?? '';
                            if (!empty($buktiIdsStr)) {
                                foreach (explode(',', $buktiIdsStr) as $bId) {
                                    $rawDocIds[] = (int) trim($bId);
                                }
                            }

                            $cleanDocIds  = array_values(array_filter($rawDocIds));
                            $judulBersih  = preg_replace('/\s+/', ' ', trim(preg_replace('/[^a-zA-Z0-9\s\.,\-]/', ' ', $formData['judul_pengaduan'] ?? '')));
                            $detailBersih = trim(preg_replace('/[^a-zA-Z0-9\s\.,\-\(\)\/\r\n]/', ' ', $formData['detail_pengaduan'] ?? ''));
                            $lokasiBersih = trim(preg_replace('/[^a-zA-Z0-9\s\.,\-\(\)\/]/', ' ', $formData['lokasi_kejadian'] ?? ''));

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
                                    'source'     => $formData['sumber_pengaduan'] ?? 'whatsapp'
                                ]
                            ]);

                            if ($reportResponse->successful()) {
                                $responseData = [
                                    'version' => '3.0',
                                    'screen'  => 'SELESAI',
                                    'data'    => [
                                        'ticket_number' => (string) ($reportResponse->json('data.ticket_number') ?? '-'),
                                        'category'      => (string) ($reportResponse->json('data.category') ?? '-')
                                    ]
                                ];

                                Cache::put($cachedSuccessKey, $responseData, self::LOCK_TTL);
                            } else {
                                Cache::forget($lockKey);
                                $responseData = [
                                    'version' => '3.0',
                                    'screen'  => 'PREVIEW',
                                    'data'    => array_merge($formData, [
                                        'error_message' => 'Terjadi kesalahan sistem saat menyimpan laporan. Silakan coba lagi.'
                                    ])
                                ];
                            }
                        } catch (\Exception $e) {
                            Cache::forget($lockKey);
                            $responseData = [
                                'version' => '3.0',
                                'screen'  => 'PREVIEW',
                                'data'    => array_merge($formData, [
                                    'error_message' => 'Gagal terhubung ke server. Silakan coba beberapa saat lagi.'
                                ])
                            ];
                        }
                    }
                } else {
                    $responseData = [
                        'version' => '3.0',
                        'screen'  => 'MENU',
                        'data'    => ['error_message' => 'Screen tidak dikenali.']
                    ];
                }
            }

            // 5. Enkripsi Balasan untuk Meta (AES-128-GCM)
            $flippedIv = '';
            for ($i = 0; $i < strlen($initialVector); $i++) {
                $flippedIv .= chr(~ord($initialVector[$i]) & 0xFF);
            }

            $responseAuthTag   = '';
            $encryptedResponse = openssl_encrypt(
                json_encode($responseData),
                'aes-128-gcm',
                $aesKey,
                OPENSSL_RAW_DATA,
                $flippedIv,
                $responseAuthTag
            );

            return response(base64_encode($encryptedResponse . $responseAuthTag), 200)
                ->header('Content-Type', 'text/plain');

        } catch (\Exception $e) {
            $waLog = Log::build([
                'driver' => 'single',
                'path'   => storage_path('logs/wa_flows_debug.log'),
            ]);
            $waLog->error("FATAL WA Flows Error: " . $e->getMessage());
            return response('Server Error', 500);
        }
    }

    private function downloadAndDecryptFlowMedia($mediaObject, $maxBytes = 5242880)
    {
        $cdnUrl   = $mediaObject['cdn_url'] ?? '';
        $metadata = $mediaObject['encryption_metadata'] ?? null;
        $fileName = $mediaObject['file_name'] ?? 'document.jpg';

        if (!$cdnUrl || !$metadata) {
            throw new \Exception("Data media tidak lengkap.");
        }

        $allowedExtensions = ['jpeg', 'jpg', 'png', 'heic', 'pdf', 'docx', 'doc', 'xlsx', 'xls'];
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception("INVALID_EXTENSION");
        }

        $response = Http::get($cdnUrl);
        if (!$response->successful()) {
            throw new \Exception("Gagal mendownload file dari CDN Meta.");
        }

        $downloadedData = $response->body();
        $ciphertext     = substr($downloadedData, 0, -10);
        $encKey         = base64_decode($metadata['encryption_key']);
        $iv             = base64_decode($metadata['iv']);

        $decryptedData = openssl_decrypt(
            $ciphertext,
            'aes-256-cbc',
            $encKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decryptedData === false) {
            throw new \Exception("Gagal mendekripsi file CDN Meta.");
        }

        if (strlen($decryptedData) > $maxBytes) {
            throw new \Exception("FILE_TOO_LARGE");
        }

        $mimeType = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'heic'        => 'image/heic',
            'pdf'         => 'application/pdf',
            'doc'         => 'application/msword',
            'docx'        => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'         => 'application/vnd.ms-excel',
            'xlsx'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default       => 'application/octet-stream',
        };

        return "data:{$mimeType};base64," . base64_encode($decryptedData);
    }
}