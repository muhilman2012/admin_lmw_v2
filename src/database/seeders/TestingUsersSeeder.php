<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\UnitKerja;
use App\Models\Deputy;
use Illuminate\Support\Str;

class TestingUsersSeeder extends Seeder
{
    public function run(): void
    {
        // --- Password default untuk semua user testing ---
        $defaultPassword = Hash::make('password');

        // --- 1. Ambil Roles yang Sudah Ada ---
        $roles = [
            'deputy'     => Role::where('name', 'deputy')->first(),
            'asdep_karo' => Role::where('name', 'asdep_karo')->first(),
            'analyst'    => Role::where('name', 'analyst')->first(),
        ];
        
        // Cek jika roles belum ada (misal RolesAndPermissionsSeeder belum jalan)
        if (!$roles['deputy'] || !$roles['asdep_karo'] || !$roles['analyst']) {
            $this->command->warn('Roles [deputy, asdep_karo, analyst] not found. Please run RolesAndPermissionsSeeder first.');
            return;
        }


        // --- 2. Ambil Struktur Organisasi Riil (Wajib ada di DB) ---
        
        // Ambil Deputi 1 untuk Deputi test
        $deputy1 = Deputy::where('name', 'Deputi Bidang Dukungan Kebijakan Perekonomian, Pariwisata, dan Transformasi Digital')->first();
        
        // Ambil Unit Asdep/Karo (Ambil Asisten Deputi pertama dari Deputi 1)
        $unitAsdep = $deputy1 ? UnitKerja::where('deputy_id', $deputy1->id)
                                        ->where('name', 'Asisten Deputi Industri, Perdagangan, Pariwisata, dan Ekonomi Kreatif')
                                        ->first() : null;

        // Ambil Unit Analis (Ambil Unit Biro Umum dari Deputi Administrasi)
        $deputyAdm = Deputy::where('name', 'Deputi Bidang Administrasi')->first();
        $unitAnalis = $deputyAdm ? UnitKerja::where('deputy_id', $deputyAdm->id)
                                           ->where('name', 'Biro Umum')
                                           ->first() : null;

        // --- PENTING: Jika struktur belum ada, seeder akan berhenti ---
        if (!$deputy1 || !$unitAsdep || !$unitAnalis) {
            $this->command->error('Struktur Deputi/Unit Kerja tidak ditemukan di database. Pastikan UnitKerjaSeeder sudah dijalankan!');
            return;
        }

        $this->command->info('Creating Test Users...');
        
        // --- 3. Buat Akun DEPUTI (4 Akun) ---
        $deputyNames = [
            'Deputi Bidang Dukungan Kebijakan Perekonomian, Pariwisata, dan Transformasi Digital',
            'Deputi Bidang Dukungan Kebijakan Peningkatan Kesejahteraan dan Pembangunan Sumber Daya Manusia',
            'Deputi Bidang Dukungan Kebijakan Pemerintahan dan Pemerataan Pembangunan',
            'Deputi Bidang Administrasi',
        ];
        
        foreach ($deputyNames as $deputyName) {
            $deputy = Deputy::where('name', $deputyName)->first();
            if ($deputy) {
                $emailSuffix = str_replace([' ', ','], '', strtolower(Str::limit($deputy->name, 15, '')));
                $email = "{$emailSuffix}@deputi.id";
                
                $deputyUser = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $deputyName,
                        'password' => $defaultPassword,
                        'role' => 'deputy',
                        'is_active' => true,
                        // Unit Kerja ID dibiarkan NULL karena Deputi adalah kepala Unit/Biro
                    ]
                );
                $deputyUser->assignRole($roles['deputy']);
            }
        }


        // --- 4. Buat Akun ASDEP/KARO (1 Akun Contoh) ---
        $asdepUser = User::firstOrCreate(
            ['email' => 'asdep.indag@test.com'],
            [
                'name' => 'Asdep Indag',
                'password' => $defaultPassword,
                'role' => 'asdep_karo',
                'is_active' => true,
                'unit_kerja_id' => $unitAsdep->id, // Assign ke Unit Asdep
                'jabatan' => 'Asisten Deputi Senior',
            ]
        );
        $asdepUser->assignRole($roles['asdep_karo']);


        // --- 5. Buat Akun ANALIS (1 Akun Contoh) ---
        $analisUser = User::firstOrCreate(
            ['email' => 'analis.umum@test.com'],
            [
                'name' => 'Analis Umum',
                'password' => $defaultPassword,
                'role' => 'analyst',
                'is_active' => true,
                'unit_kerja_id' => $unitAnalis->id, // Assign ke Unit Biro Umum
                'jabatan' => 'Analis Madya',
            ]
        );
        $analisUser->assignRole($roles['analyst']);
    }
}
