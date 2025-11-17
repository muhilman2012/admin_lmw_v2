<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\SuperadminController;
use App\Models\ApiSetting;
use App\Models\UnitKerja;
use App\Models\Deputy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $user->load(['loginLogs' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }]);

        $apiSettings = ApiSetting::all()->groupBy('name')->map(function ($group) {
            return $group->pluck('value', 'key')->all();
        })->all();
        
        $units = UnitKerja::with('deputy')->get();
        $deputies = Deputy::all();
        
        return view('pages.profile.index', compact('user', 'apiSettings', 'units', 'deputies'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['string', 'max:255', 'nullable'],
            'email' => ['string', 'email', 'max:255', Rule::unique('users')->ignore($user->id), 'nullable'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'jabatan' => ['string', 'max:255', 'nullable'],
            'nip' => ['string', 'max:255', Rule::unique('users')->ignore($user->id), 'nullable'],
            'unit_kerja_id' => ['nullable', 'exists:unit_kerjas,id'],
        ]);

        $dataToUpdate = $request->only(['name', 'email', 'jabatan', 'unit_kerja_id']);

        $dataToUpdate = array_filter($dataToUpdate, function($value) {
            return !is_null($value);
        });

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('uploads')->delete($user->avatar);
            }
            
            $avatarPath = $request->file('avatar')->store('avatars', 'uploads');
            $dataToUpdate['avatar'] = $avatarPath;
        }

        if (!empty($dataToUpdate)) {
            $user->update($dataToUpdate);
        }

        return redirect()->route('users.profile.index', ['#pane-account'])->with('success', 'Profil berhasil diperbarui!');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        try{
            $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:8|confirmed',
            ]);
            
            $currentPasswordInput = $request->input('current_password');

            $isTempPassValid = false;
            if ($user->needs_password_reset && $user->temporary_password) {
                if ($currentPasswordInput === $user->temporary_password) {
                    $isTempPassValid = true;
                }
            }

            $isOldPassValid = Hash::check($currentPasswordInput, $user->password);
            if (!$isTempPassValid && !$isOldPassValid) {
                Log::warning("Password mismatch for user {$user->id}. Temp: {$user->needs_password_reset}. Input: {$currentPasswordInput}.");
                return redirect()->back()->with('error', 'Kata sandi lama tidak cocok.')->withErrors(['current_password' => 'Kata sandi lama tidak valid.']);
            }
            
            $user->update([
                'password' => Hash::make($request->input('new_password')),
                'needs_password_reset' => false, 
                'temporary_password' => null, 
            ]);
            
            return redirect()->to(route('users.profile.index') . '#pane-reset-password')
                ->with('success', 'Kata sandi berhasil diperbarui!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Tangani kegagalan validasi (misalnya: new_password tidak sama dengan konfirmasi)
            Log::warning("Validation failed for user {$user->id}: " . json_encode($e->errors()));
            return redirect()->back()->withErrors($e->errors())->withInput();
        
        } catch (\Exception $e) {
            // Tangani kegagalan fatal (misalnya: Eloquent/DB error)
            Log::critical("FATAL ERROR during password update for user {$user->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyimpan kata sandi karena kesalahan sistem.')->withInput();
        }
    }

    public function regenerateApiToken(Request $request)
    {
        if (!$request->user()->hasRole('superadmin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $superadminController = new SuperadminController();
        $newToken = $superadminController->regenerateToken($request);
        
        return $newToken;
    }

    public function updateApiSettings(Request $request)
    {
        $apiName = $request->input('api_name');
        
        // 1. Validasi
        $rules = [
            'api_name' => 'required|string|in:lmw_api,lapor_api,dukcapil_api,gemini_api,v1_migration_api',
        ];

        $settingsToUpdate = [];
        
        switch ($apiName) {
            case 'v1_migration_api':
                $rules['base_url'] = 'required|url|max:255'; 
                $rules['authorization'] = 'required|string';
                $settingsToUpdate = $request->only(['base_url', 'authorization']);
                break;
            case 'lapor_api':
                $rules['base_url'] = 'nullable|url|max:255';
                $rules['auth_key'] = 'nullable|string|in:Authorization,auth';
                $rules['auth_value'] = 'nullable|string';
                $rules['token'] = 'nullable|string';
                $settingsToUpdate = $request->only(['base_url', 'auth_key', 'auth_value', 'token']);
                break;
            case 'dukcapil_api':
            case 'lmw_api':
                $rules['base_url'] = 'nullable|url|max:255';
                $rules['authorization'] = 'nullable|string';
                $rules['token'] = 'nullable|string';
                $settingsToUpdate = $request->only(['base_url', 'authorization', 'token']);
                break;
            case 'gemini_api':
                $rules['endpoint'] = 'nullable|url|max:255';
                $rules['model'] = 'nullable|string|max:100';
                $rules['api_key_primary'] = 'nullable|string';
                $rules['api_key_fallback'] = 'nullable|string';
                $settingsToUpdate = $request->only(['endpoint', 'model', 'api_key_primary', 'api_key_fallback']);
                break;
            default:
                throw ValidationException::withMessages(['api_name' => 'Nama API tidak valid.']);
        }

        $request->validate($rules);

        // 2. Simpan ke Database
        foreach ($settingsToUpdate as $key => $value) {
            if (!is_null($value) || $request->has($key)) {

                $dbKey = $key;

                // Khusus untuk LMW, pastikan kita hanya mengambil nilai
                if ($apiName === 'lmw_api' && $key === 'api_token') {
                     $dbKey = 'api_token';
                }

                // Gunakan kombinasi 'name' dan 'key' untuk mencari
                ApiSetting::updateOrCreate(
                    ['name' => $apiName, 'key' => $dbKey],
                    ['value' => $value ?? ''] 
                );
            }
        }
        
        // 3. Redirect
        $redirectHash = '#pane-api-' . Str::of($apiName)->replace('_api', '');
        return redirect()->to(route('users.profile.index') . $redirectHash)->with('success', 'Pengaturan API ' . Str::upper($apiName) . ' berhasil disimpan.');
    }
}
