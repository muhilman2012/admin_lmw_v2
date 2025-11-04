<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPasswordReset
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (Auth::check() && $user->needs_password_reset) {
            if ($request->routeIs('logout') || 
                $request->routeIs('users.profile.index') ||
                $request->routeIs('users.profile.update-password'))
            { 
                return $next($request);
            }
            
            session()->flash('swal:warning', 'Anda menggunakan kata sandi sementara. Harap segera ubah kata sandi Anda!');
        
            return redirect()->to(route('users.profile.index') . '#pane-reset-password');
        }

        return $next($request);
    }
}
