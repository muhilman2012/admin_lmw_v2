<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Institution;
use App\Models\ApiSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncInstitutions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:institutions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync institutions from external API to local database using credentials from the database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to sync institutions from LAPOR! API...');
        $apiName = 'lapor_api';

        // 1. Ambil kredensial API dari database berdasarkan name dan key
        $settings = \App\Models\ApiSetting::where('name', $apiName)
                                        ->pluck('value', 'key');

        $baseUrl = $settings->get('base_url');
        $authKey = $settings->get('auth_key', 'Authorization');
        $authValue = $settings->get('auth_value');
        $token = $settings->get('token');

        if (!$baseUrl || !$authValue || !$token) {
            $this->error("One or more required API settings for '{$apiName}' not found (Base URL, Auth Value, or Token). Sync aborted.");
            \Illuminate\Support\Facades\Log::error("API settings for '{$apiName}' not complete.");
            return 1;
        }

        try {
            // 2. Buat permintaan HTTP dengan kredensial yang diambil dari database
            $response = Http::withHeaders([
                $authKey => "Bearer {$authValue}",
                'token' => $token, 
                'Content-Type' => 'application/json'
            ])->get("{$baseUrl}/masters/institutions/external?page_size=1000");
            
            // Cek jika permintaan gagal
            $response->throw();

            // 3. Proses Response (Asumsi struktur response tetap sama)
            $institutions = $response->json()['results']['data'] ?? [];

            if (empty($institutions)) {
                $this->warn('API returned no institutions. Local database will not be updated.');
                return 0; 
            }

            $this->info("Clearing old institution data before synchronization...");
            try {
                \App\Models\Institution::truncate(); 
            } catch (\Exception $e) {
                $this->error("Failed to truncate Institution table. Attempting to delete all rows.");
                \App\Models\Institution::query()->delete();
            }

            // 4. Update atau buat institusi di database lokal
            $updatedCount = 0;
            foreach ($institutions as $institution) {
                \App\Models\Institution::updateOrCreate(
                    ['id' => $institution['id']],
                    ['name' => $institution['name']]
                );
                $updatedCount++;
            }

            $this->info("Sync completed successfully! Updated/Created {$updatedCount} institutions.");
            
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $this->error('API request failed: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('API request failed: ' . $e->getMessage());
            return 1; // Exit with error code
        } catch (\Exception $e) {
            $this->error('An unexpected error occurred: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('An unexpected error occurred: ' . $e->getMessage());
            return 1; // Exit with error code
        }

        return 0;
    }
}
