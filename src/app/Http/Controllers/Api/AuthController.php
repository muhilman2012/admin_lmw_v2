<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if (!$user->is_active) {
                return response()->json([
                    'message' => 'Akun Anda tidak aktif. Mohon hubungi administrator.'
                ], 401);
            }
            
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil!',
                'token' => $token,
            ]);
        }

        return response()->json([
            'message' => 'Kredensial tidak valid.'
        ], 401);
    }
}
