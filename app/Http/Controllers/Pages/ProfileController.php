<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\SuperadminController;
use App\Models\ApiSetting;
use App\Models\UnitKerja;
use App\Models\Deputy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

        $apiSettings = [
            'base_url' => ApiSetting::where('key', 'base_url')->first()?->value,
            'authorization' => ApiSetting::where('key', 'authorization')->first()?->value,
            'token' => ApiSetting::where('key', 'token')->first()?->value,
        ];
        
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

        return redirect()->back()->with('success', 'Profil berhasil diperbarui!');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);
        
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return redirect()->back()->with('error', 'Kata sandi lama tidak cocok.');
        }

        $user->update([
            'password' => Hash::make($request->input('new_password'))
        ]);
        
        return redirect()->back()->with('success', 'Kata sandi berhasil diperbarui!');
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
        $request->validate([
            'base_url' => 'nullable|url|max:255',
            'authorization' => 'nullable|string|max:255',
            'token' => 'nullable|string',
        ]);

        $settings = $request->only(['base_url', 'authorization', 'token']);

        foreach ($settings as $key => $value) {
            if ($value) {
                ApiSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        }
        
        return redirect()->back()->with('success', 'Pengaturan API berhasil disimpan.');
    }
}
