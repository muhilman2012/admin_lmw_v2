<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SuperadminController extends Controller
{
    /**
     * Regenerate the LMW API token.
     */
    public function regenerateToken(Request $request)
    {
        // Periksa peran pengguna yang meminta.
        if (!$request->user()->hasRole('superadmin')) {
            return response()->json(['message' => 'Unauthorized. Only superadmin can perform this action.'], 403);
        }

        // Hasilkan token baru yang lebih unik
        $newToken = $this->generateNewApiToken();

        // Simpan token baru ke file .env
        $this->updateEnvFile($newToken);

        return response()->json([
            'message' => 'LMW API token berhasil diperbarui.',
            'new_token' => $newToken,
        ]);
    }

    /**
     * Generate a new, random, and human-readable API token.
     */
    protected function generateNewApiToken(): string
    {
        return Str::random(8) . '-' . Str::random(8) . '-' . Str::random(8) . '-' . Str::random(8);
    }

    /**
     * Update the LMW_API_TOKEN in the .env file.
     */
    protected function updateEnvFile(string $newToken): void
    {
        $path = base_path('.env');

        if (File::exists($path)) {
            $contents = file_get_contents($path);
            $contents = preg_replace('/^LMW_API_TOKEN=.*/m', 'LMW_API_TOKEN=' . $newToken, $contents);
            File::put($path, $contents);
            Artisan::call('config:clear');
        }
    }
}