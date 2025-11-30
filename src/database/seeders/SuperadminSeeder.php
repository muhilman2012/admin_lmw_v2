<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\User;

class SuperadminSeeder extends Seeder
{
    public function run()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        
        $superadminUser = User::firstOrCreate(
            ['email' => 'superadmin@set.wapresri.go.id'],
            [
                'name' => 'System LMW',
                'password' => Hash::make('S3tW2PRE$@2025!@#'),
                'role' => 'superadmin',
                'is_active' => true,
                'phone' => '1234567890',
            ]
        );

        if ($superadminRole) {
            $superadminUser->assignRole($superadminRole);
        }
    }
}
