<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache izin
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- 1. Definisikan Izin (Permissions) ---
        $permissions = [
            // KATEGORI: REPORT CORE (CRUD)
            'view all reports',
            'create reports',
            'edit reports',
            'delete reports',
            
            // KATEGORI: USER & STRUKTUR
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage structure', // Untuk Deputi/Unit Kerja/Kategori
            
            // KATEGORI: FORWARDING & EKSPOR
            'view forwarded reports',
            'forward reports to lapor', // Memicu proses API 3 langkah
            'export data',
            'import data', // Khusus untuk migrasi data lama
            'regenerate api key', // Untuk token internal LMW
            'manage external api settings', // Untuk setting API LAPOR/Dukcapil

            // KATEGORI: ASSIGNMENT & ANALISIS
            'assign reports', // Menugaskan ke analis
            'view assigned reports', // Melihat yang ditugaskan kepada diri sendiri
            'fill analysis worksheet', // Mengisi hasil analisis (analyst_worksheet)
            'approve analysis', // Menyetujui/merevisi hasil analisis (Asdep/Deputi)
            'update report response', // Edit status & tanggapan pengaduan (Edit Tanggapan Pengaduan)
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // --- 2. Buat Peran (Roles) dan Berikan Izin ---

        // Peran 1: Superadmin (Akses Penuh)
        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $superadmin->givePermissionTo(Permission::all());

        // Peran 2: Admin (Entry Data & Manajemen User Dasar)
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'view all reports',
            'create reports',
            'edit reports',
            'view users',
            'create users',
            'edit users',
            'view forwarded reports',
        ]);

        // Peran 3: Analis (Fokus pada Tugas Sendiri)
        $analyst = Role::firstOrCreate(['name' => 'analyst']);
        $analyst->givePermissionTo([
            'view assigned reports',
            'fill analysis worksheet', 
            'update report response', // Agar bisa edit status/tanggapan cepat
            'forward reports to lapor',
        ]);

        // Peran 4: Asdep/Karo (Kontrol Unit & Persetujuan)
        $asdep_karo = Role::firstOrCreate(['name' => 'asdep_karo']);
        $asdep_karo->givePermissionTo([
            'view all reports', // Atau ganti dengan 'view reports under unit' jika hanya bisa melihat unitnya
            'assign reports',
            'approve analysis', 
            'update report response',
            'export data',
            'forward reports to lapor',
        ]);

        // Peran 5: Deputi (Pengawasan dan Akses Data Penuh)
        $deputy = Role::firstOrCreate(['name' => 'deputy']);
        $deputy->givePermissionTo([
            'view all reports',
            'approve analysis',
            'export data',
            'view forwarded reports',
        ]);
    }
}
