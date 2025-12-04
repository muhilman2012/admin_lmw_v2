<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->with('error', 'Akun Anda tidak aktif. Mohon hubungi administrator.')->onlyInput('email');
            }

            $request->session()->regenerate();
            
            LoginLog::create([
                'user_id' => Auth::id(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->intended(route('dashboard'));
        }

        return back()->with('error', 'Email atau password yang Anda masukkan salah.')->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Anda telah logout.');
    }

    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password'); 
    }

    public function handleForgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'nip' => 'required|string', // Menggunakan NIP sebagai layer keamanan kedua
        ], [
            'nip.required' => 'NIP wajib diisi untuk verifikasi.',
            'email.exists' => 'Email tidak terdaftar.'
        ]);

        $user = User::where('email', $request->email)->first();

        // 1. Verifikasi NIP
        if ($user->nip !== $request->nip) {
            throw ValidationException::withMessages([
                'nip' => ['NIP atau Email tidak cocok.']
            ]);
        }

        // 2. Generate Password Sementara
        $temporaryPassword = Str::random(10); 
        $hashedPassword = Hash::make($temporaryPassword);

        // 3. Update User dan Flag
        $user->update([
            'password' => $hashedPassword,
            'temporary_password' => $temporaryPassword, // Simpan untuk ditampilkan sekali
            'needs_password_reset' => true, // Paksa reset setelah login
        ]);

        // 4. Redirect ke halaman login dengan pesan sukses
        return redirect()->route('login')->with('status', [
            'type' => 'success',
            'message' => "Kata sandi baru sementara Anda telah dibuat: <strong class='text-primary'>{$temporaryPassword}</strong> <br>Silakan login menggunakan kata sandi ini dan segera ubah di halaman profil."
        ]);
    }
}
