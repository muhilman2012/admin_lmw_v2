<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppFlowController extends Controller
{
    public function handleWebhook(Request $request)
    {
        try {
            // 1. Dekripsi Request Meta
            $encryptedAesKey = base64_decode($request->input('encrypted_aes_key'));
            $encryptedFlowData = base64_decode($request->input('encrypted_flow_data'));
            $initialVector = base64_decode($request->input('initial_vector'));

            $privateKey = config('services.lmw.wa_private_key');
            $privateKey = str_replace('\n', "\n", $privateKey);

            $aesKey = '';
            if (!openssl_private_decrypt($encryptedAesKey, $aesKey, $privateKey, OPENSSL_PKCS1_OAEP_PADDING)) {
                throw new \Exception("Gagal mendekripsi AES Key");
            }

            $authTag = substr($encryptedFlowData, -16);
            $ciphertext = substr($encryptedFlowData, 0, -16);

            $flowDataJson = openssl_decrypt($ciphertext, 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $initialVector, $authTag);
            $flowData = json_decode($flowDataJson, true);

            // 2. PROSES LOGIKA ACTION
            $action = $flowData['action'] ?? null;
            $responseData = [];

            // Header standar API Internal LMW
            $apiHeaders = [
                'Authorization' => 'Bearer ' . config('services.lmw.api_token'),
                'X-LMW-API-KEY' => config('services.lmw.api_key'),
                'Accept' => 'application/json'
            ];
            $apiUrl = config('services.lmw.api_url');

            if ($action === 'ping') {
                $responseData = ['data' => ['status' => 'active']];
            } 
            elseif ($action === 'proses_identitas') {
                $phoneNumber = $flowData['flow_token'] ?? ''; 
                $payloadLmw = [
                    'nik' => $flowData['nik'],
                    'name' => $flowData['name'],
                    'email' => $flowData['email'] ?? '',
                    'address' => $flowData['address'],
                    'phone_number' => $phoneNumber 
                ];

                $eligibilityResponse = Http::withHeaders($apiHeaders)
                    ->post($apiUrl . '/api/reporters/check-eligibility-v2', $payloadLmw);

                if (!$eligibilityResponse->successful() || $eligibilityResponse->json('status') === 'error' || $eligibilityResponse->json('status') === 'failed') {
                    $errorMessage = $eligibilityResponse->json('message') ?? 'NIK tidak valid atau sedang dalam masa tunggu.';
                    $responseData = [
                        'screen' => 'IDENTITAS',
                        'data' => ['error_message' => "⚠️ " . $errorMessage]
                    ];
                } else {
                    $reporterResponse = Http::withHeaders($apiHeaders)->post($apiUrl . '/api/reporters', $payloadLmw);
                    $reporterId = $reporterResponse->json('reporter_id') ?? '0';
                    $responseData = [
                        'screen' => 'PENGADUAN',
                        'data' => ['reporter_id' => (string) $reporterId]
                    ];
                }
            }
            elseif ($action === 'proses_upload_ktp') {
                // Konversi Media ID dari Meta menjadi Base64
                $mediaId = $flowData['ktp_base64']; 
                $base64Data = $this->downloadMetaMediaAsBase64($mediaId);

                $docResponse = Http::withHeaders($apiHeaders)->post($apiUrl . '/api/documents', [
                    'file_base64' => $base64Data,
                    'description' => 'KTP Pengadu (WA Flows)'
                ]);
                $ktpDocId = $docResponse->json('data.id') ?? '0';

                $responseData = [
                    'screen' => 'UPLOAD_KK',
                    'data' => [
                        'reporter_id' => $flowData['reporter_id'],
                        'ktp_doc_id' => (string) $ktpDocId,
                        'judul_pengaduan' => $flowData['judul_pengaduan'],
                        'detail_pengaduan' => $flowData['detail_pengaduan'],
                        'lokasi_kejadian' => $flowData['lokasi_kejadian'],
                        'waktu_kejadian' => $flowData['waktu_kejadian']
                    ]
                ];
            }
            elseif ($action === 'proses_upload_kk') {
                $mediaId = $flowData['kk_base64'];
                $base64Data = $this->downloadMetaMediaAsBase64($mediaId);

                $docResponse = Http::withHeaders($apiHeaders)->post($apiUrl . '/api/documents', [
                    'file_base64' => $base64Data,
                    'description' => 'Kartu Keluarga (WA Flows)'
                ]);
                $kkDocId = $docResponse->json('data.id') ?? '0';

                $responseData = [
                    'screen' => 'UPLOAD_BUKTI',
                    'data' => [
                        'reporter_id' => $flowData['reporter_id'],
                        'ktp_doc_id' => $flowData['ktp_doc_id'],
                        'kk_doc_id' => (string) $kkDocId,
                        'judul_pengaduan' => $flowData['judul_pengaduan'],
                        'detail_pengaduan' => $flowData['detail_pengaduan'],
                        'lokasi_kejadian' => $flowData['lokasi_kejadian'],
                        'waktu_kejadian' => $flowData['waktu_kejadian']
                    ]
                ];
            }
            elseif ($action === 'proses_upload_bukti') {
                $mediaId = $flowData['pendukung_base64'];
                $base64Data = $this->downloadMetaMediaAsBase64($mediaId);

                $docResponse = Http::withHeaders($apiHeaders)->post($apiUrl . '/api/documents', [
                    'file_base64' => $base64Data,
                    'description' => 'Dokumen Pendukung Laporan (WA Flows)'
                ]);
                $pendukungDocId = $docResponse->json('data.id') ?? '0';

                $responseData = [
                    'screen' => 'PREVIEW',
                    'data' => [
                        'reporter_id' => $flowData['reporter_id'],
                        'ktp_doc_id' => $flowData['ktp_doc_id'],
                        'kk_doc_id' => $flowData['kk_doc_id'],
                        'pendukung_doc_id' => (string) $pendukungDocId,
                        'judul_pengaduan' => $flowData['judul_pengaduan'],
                        'detail_pengaduan' => $flowData['detail_pengaduan'],
                        'lokasi_kejadian' => $flowData['lokasi_kejadian'],
                        'waktu_kejadian' => $flowData['waktu_kejadian']
                    ]
                ];
            }
            elseif ($action === 'submit_laporan_akhir') {
                $reportResponse = Http::withHeaders($apiHeaders)->post($apiUrl . '/api/reports', [
                    'reporter_id' => (int) $flowData['reporter_id'],
                    'document_ids' => [
                        (int) $flowData['ktp_doc_id'],
                        (int) $flowData['kk_doc_id'],
                        (int) $flowData['pendukung_doc_id']
                    ],
                    'report_details' => [
                        'subject' => $flowData['judul_pengaduan'],
                        'details' => $flowData['detail_pengaduan'],
                        'location' => $flowData['lokasi_kejadian'],
                        'event_date' => $flowData['waktu_kejadian'],
                        'source' => $flowData['sumber_pengaduan'] ?? 'whatsapp'
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
                    'data' => ['ticket_number' => 'UNKNOWN_ACTION', 'category' => '-']
                ];
            }

            // 3. ENKRIPSI KEMBALI BALASAN UNTUK META
            $flippedIv = '';
            for ($i = 0; $i < strlen($initialVector); $i++) {
                $flippedIv .= chr(~ord($initialVector[$i]) & 0xFF);
            }

            $responseJson = json_encode($responseData);
            $responseAuthTag = '';
            $encryptedResponse = openssl_encrypt($responseJson, 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $flippedIv, $responseAuthTag);
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