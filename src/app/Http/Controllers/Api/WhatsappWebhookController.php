<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WhatsappWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $rawEvent = $request->input('event', '');
        $event = strtolower(str_replace('_', '.', $rawEvent));
        
        $instance = $request->input('instance');
        $data = $request->input('data');

        Log::info("WEBHOOK DITERIMA [Event: {$event}]");

        if ($event === 'messages.upsert') {
            $messages = isset($data['messages']) ? $data['messages'] : [$data];

            foreach ($messages as $msgData) {
                $key = $msgData['key'] ?? null;
                $messageData = $msgData['message'] ?? null;

                if (!$key) continue;

                $messageType = $msgData['messageType'] ?? 'unknown';
                $content = '';

                // Tangkap Teks/Caption
                if (isset($messageData['conversation'])) {
                    $content = $messageData['conversation'];
                } elseif (isset($messageData['extendedTextMessage']['text'])) {
                    $content = $messageData['extendedTextMessage']['text'];
                } elseif (isset($messageData['imageMessage']['caption'])) {
                    $content = $messageData['imageMessage']['caption'];
                } elseif (isset($messageData['videoMessage']['caption'])) {
                    $content = $messageData['videoMessage']['caption'];
                } elseif (isset($messageData['documentMessage']['title']) || isset($messageData['documentMessage']['fileName'])) {
                    $content = $messageData['documentMessage']['title'] ?? $messageData['documentMessage']['fileName'];
                }

                // ==========================================
                // PENANGANAN MEDIA (GAMBAR, VIDEO, DOKUMEN)
                // ==========================================
                $mediaTypes = ['imageMessage', 'videoMessage', 'documentMessage', 'audioMessage'];
                
                if (in_array($messageType, $mediaTypes)) {
                    
                    try {
                        // Tembak endpoint untuk memecah media menjadi Base64
                        $apiUrl = 'http://host.docker.internal:8082'; 
                        $apiKey = 'yAak6w5pzF3XzcHUTEbc'; 

                        $response = Http::timeout(30)
                            ->withHeaders([
                                'apikey' => $apiKey,
                                'Content-Type' => 'application/json'
                            ])
                            ->post("{$apiUrl}/chat/getBase64FromMediaMessage/{$instance}", [
                                'message' => $msgData
                            ]);

                        if ($response->successful()) {
                            $mediaData = $response->json();
                            $base64String = $mediaData['base64'] ?? null;
                            $mimetype = $mediaData['mimetype'] ?? 'application/octet-stream';
                            
                            // ==================================================
                            // PERBAIKAN: Deteksi ekstensi file langsung dari mimetype
                            // ==================================================
                            $ext = 'bin'; // default fallback
                            if (str_contains($mimetype, 'jpeg') || str_contains($mimetype, 'jpg')) $ext = 'jpg';
                            elseif (str_contains($mimetype, 'png')) $ext = 'png';
                            elseif (str_contains($mimetype, 'webp')) $ext = 'webp';
                            elseif (str_contains($mimetype, 'mp4')) $ext = 'mp4';
                            elseif (str_contains($mimetype, 'ogg') || str_contains($mimetype, 'audio')) $ext = 'ogg';
                            else {
                                $parts = explode('/', $mimetype);
                                $ext = isset($parts[1]) ? explode(';', $parts[1])[0] : 'bin';
                            }

                            if ($base64String) {
                                // Bersihkan prefix mimetype dari base64 jika terbawa
                                if (strpos($base64String, ',') !== false) {
                                    $base64String = explode(',', $base64String)[1];
                                }
                                
                                $fileData = base64_decode($base64String);
                                
                                // NAMA FILE DIJAMIN MEMILIKI EKSTENSI (misal: 169000_abc123.jpg)
                                $fileName = time() . '_' . uniqid() . '.' . $ext;
                                $savedPath = 'wa_media/' . $fileName;
                                
                                Storage::disk('public')->put($savedPath, $fileData);
                                
                                // Sisipkan tag [MEDIA]
                                $content = '[MEDIA:' . $savedPath . '] ' . $content;
                            }
                        } else {
                            Log::warning("Gagal mengunduh media dari Evolution API: " . $response->body());
                            $content = '[Tipe Pesan: ' . $messageType . '] ' . $content;
                        }
                    } catch (\Exception $e) {
                        Log::error("Error saat mengunduh media: " . $e->getMessage());
                        $content = '[Tipe Pesan: ' . $messageType . '] ' . $content;
                    }
                }

                // Jika pesan (termasuk caption dan tag [MEDIA]) benar-benar kosong
                if (empty(trim($content)) && $messageType !== 'protocolMessage') {
                    $content = '[Tipe Pesan: ' . $messageType . ']';
                }

                // Simpan ke DB
                WhatsappMessage::updateOrCreate(
                    ['message_id' => $key['id']], 
                    [
                        'instance_name' => $instance,
                        'remote_jid' => $key['remoteJid'],
                        'from_me' => $key['fromMe'] ?? false,
                        'push_name' => $msgData['pushName'] ?? 'Tidak Ada Nama',
                        'message_type' => $messageType,
                        'content' => $content,
                        'status' => ($key['fromMe'] ?? false) ? 'SENT' : 'RECEIVED',
                        'message_timestamp' => isset($msgData['messageTimestamp']) ? date('Y-m-d H:i:s', (int)$msgData['messageTimestamp']) : now(),
                    ]
                );
            }
        }
        elseif ($event === 'messages.update') {
            $updates = isset($data['keyId']) || isset($data['key']) ? [$data] : $data;

            foreach ($updates as $msg) {
                $messageId = $msg['keyId'] ?? ($msg['key']['id'] ?? null);
                $rawStatus = $msg['status'] ?? ($msg['update']['status'] ?? null);

                if ($messageId && $rawStatus) {
                    $statusText = 'PENDING';
                    
                    if (is_string($rawStatus)) {
                        $statusText = match (strtoupper($rawStatus)) {
                            'ERROR' => 'ERROR',
                            'SERVER_ACK' => 'SENT',
                            'DELIVERY_ACK' => 'DELIVERED',
                            'READ' => 'READ',
                            'PLAYED' => 'READ',
                            default => 'PENDING',
                        };
                    } else {
                        $statusText = match ((int)$rawStatus) {
                            2 => 'ERROR', 3 => 'SENT', 4 => 'DELIVERED', 5 => 'READ', default => 'PENDING',
                        };
                    }

                    WhatsappMessage::where('message_id', $messageId)->update(['status' => $statusText]);
                }
            }
        }

        return response()->json(['success' => true]);
    }
}