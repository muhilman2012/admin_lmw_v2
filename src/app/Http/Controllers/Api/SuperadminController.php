<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\ApiSetting;

class SuperadminController extends Controller
{
    /**
     * Regenerate the LMW API token.
     */
    public function regenerateToken(Request $request)
    {
        if (!$request->user()->hasRole('superadmin')) {
            return response()->json(['message' => 'Unauthorized. Only superadmin can perform this action.'], 403);
        }

        $newToken = $this->generateNewApiToken();

        ApiSetting::updateOrCreate(
            ['name' => 'lmw_api', 'key' => 'api_token'],
            ['value' => $newToken]
        );

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
}