<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // DAFTAR EMAIL PENGECUALIAN (WHITELIST)
        $whitelistedEmails = [
            'sespri@set.wapresri.go.id'
        ];

        // 1. Cek User Global (untuk Superadmin)
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('superadmin')) {
                return true;
            }
        });

        // 2. DEFINISI GATE 'create reports'
        Gate::define('create reports', function (User $user) use ($whitelistedEmails) {
            
            // Pengecekan 1: Role yang diizinkan (Selain role 'admin' yang bermasalah)
            // Asumsi role yang diizinkan adalah deputy, asdep_karo, atau role lain yang bisa membuat laporan
            $isAllowedRole = $user->hasAnyRole(['deputy', 'asdep_karo']);
            
            // Pengecekan 2: User termasuk dalam daftar pengecualian (Whitelist)
            $isWhitelisted = in_array($user->email, $whitelistedEmails);
            
            // Izinkan jika user memiliki role yang diizinkan ATAU dia masuk dalam whitelist
            if ($isAllowedRole || $isWhitelisted) {
                return true;
            }
            
            // Tolak akses jika tidak memenuhi kriteria di atas
            return false;
        });
    }
}
