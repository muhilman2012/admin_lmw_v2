<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- 1. Buat Izin (Permissions) ---
        $permissions = [
            // Izin untuk Pengelolaan Laporan
            'view all reports',
            'create reports',
            'edit reports',
            'delete reports',
            'assign reports',
            'view reports under unit',
            
            // Izin untuk Pengelolaan Pengguna
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Izin untuk Analis
            'view assigned reports',
            'process reports',
            'fill analyst worksheet',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // --- 2. Buat Peran (Roles) dan Berikan Izin ---

        // Peran Superadmin
        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $superadmin->givePermissionTo(Permission::all());

        // Peran Admin
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'view all reports',
            'create reports',
            'edit reports',
            'view users',
            'create users',
            'edit users',
        ]);

        // Peran Deputi
        $deputy = Role::firstOrCreate(['name' => 'deputy']);
        $deputy->givePermissionTo(['view all reports']);

        // Peran Asdep/Karo
        $asdep_karo = Role::firstOrCreate(['name' => 'asdep_karo']);
        $asdep_karo->givePermissionTo(['view reports under unit', 'assign reports', 'process reports']);

        // Peran Analis
        $analyst = Role::firstOrCreate(['name' => 'analyst']);
        $analyst->givePermissionTo(['view assigned reports', 'fill analyst worksheet']);
    }
}
