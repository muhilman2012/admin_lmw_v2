<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionWaService implements WhatsAppServiceInterface
{
    protected $apiUrl;
    protected $apiKey;
    protected $instanceName;

    public function __construct()
    {
        $this->apiUrl = env('EVOLUTION_API_URL', 'http://lmw-wa-engine:8080');
        $this->apiKey = env('EVOLUTION_API_TOKEN', 'yAak6w5pzF3XzcHUTEbc');
        $this->instanceName = env('EVOLUTION_INSTANCE_NAME', 'lmw_pusat_v2');
    }

    public function sendMessage(string $phone, string $message): bool
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey
            ])->post("{$this->apiUrl}/message/sendText/{$this->instanceName}", [
                'number' => $phone,
                'options' => [
                    'delay' => 1200,
                ],
                'textMessage' => [
                    'text' => $message
                ]
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WA Send Error: ' . $e->getMessage());
            return false;
        }
    }

    public function getConnectionStatus(): array
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
            ])->get("{$this->apiUrl}/instance/connectionState/{$this->instanceName}");

            if (!$response->successful()) {
                return ['status' => 'disconnected', 'qr_base64' => null];
            }

            $data = $response->json();
            $state = strtolower($data['instance']['state'] ?? $data['state'] ?? 'close');

            if ($state === 'open') {
                return ['status' => 'connected', 'qr_base64' => null];
            }

            // Ambil QR Code jika belum terhubung
            $qrResponse = Http::withHeaders([
                'apikey' => $this->apiKey,
            ])->get("{$this->apiUrl}/instance/connect/{$this->instanceName}");

            if ($qrResponse->successful()) {
                $qrData = $qrResponse->json();
                
                // Cek semua kemungkinan struktur data base64 di Evolution v2
                $base64 = $qrData['base64'] 
                    ?? $qrData['qrcode']['base64'] 
                    ?? $qrData['instance']['base64'] 
                    ?? null;

                if ($base64) {
                    return ['status' => 'scan_qr', 'qr_base64' => $base64];
                }
            }

            return ['status' => 'scan_qr', 'qr_base64' => null];

        } catch (\Exception $e) {
            Log::error('WA Connection Status Error: ' . $e->getMessage());
            return ['status' => 'disconnected', 'qr_base64' => null];
        }
    }

    public function getInstanceState()
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
            ])->get("{$this->apiUrl}/instance/connectionState/{$this->instanceName}");

            return $response->json();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function fetchQrCode()
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
            ])->get("{$this->apiUrl}/instance/connect/{$this->instanceName}");

            return $response->json();
        } catch (\Exception $e) {
            return null;
        }
    }
}