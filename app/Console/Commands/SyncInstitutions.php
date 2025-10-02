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
        $this->info('Starting to sync institutions from API...');

        // 1. Ambil kredensial API dari database berdasarkan key
        $baseUrl = ApiSetting::where('key', 'base_url')->value('value');
        $auth = ApiSetting::where('key', 'authorization')->value('value');
        $token = ApiSetting::where('key', 'token')->value('value');

        if (!$baseUrl || !$auth || !$token) {
            $this->error('One or more API settings (base_url, authorization, token) not found in the database. Sync aborted.');
            Log::error('API settings not complete.');
            return 1; // Exit with error code
        }

        try {
            // 2. Buat permintaan HTTP dengan kredensial yang diambil dari database
            $response = Http::withHeaders([
                'auth' => $auth,
                'token' => $token,
                'Content-Type' => 'application/json'
            ])->get("{$baseUrl}/masters/institutions/external?page_size=1000");
            
            // Cek jika permintaan gagal
            $response->throw();

            $institutions = $response->json()['results']['data'] ?? [];

            if (empty($institutions)) {
                $this->warn('API returned no institutions. Local database will not be updated.');
                Log::warning('API returned no institutions.');
                return 0; // Exit successfully, but with a warning
            }

            // 3. Update atau buat institusi di database lokal
            foreach ($institutions as $institution) {
                Institution::updateOrCreate(
                    ['id' => $institution['id']],
                    ['name' => $institution['name']]
                );
            }

            $this->info('Sync completed successfully!');
            
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $this->error('API request failed: ' . $e->getMessage());
            Log::error('API request failed: ' . $e->getMessage());
            return 1; // Exit with error code
        } catch (\Exception $e) {
            $this->error('An unexpected error occurred: ' . $e->getMessage());
            Log::error('An unexpected error occurred: ' . $e->getMessage());
            return 1; // Exit with error code
        }
    }
}
