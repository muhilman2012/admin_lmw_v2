<?php

namespace App\Imports;

use App\Models\User;
use App\Models\UnitKerja;
use App\Models\Deputy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException; 
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Validation\ValidationException;

class UsersImport implements ToCollection, WithHeadingRow, WithStartRow
{
    private $importedCount = 0;

    private const TEMPORARY_PASSWORD = 'password';

    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $email = trim(strtolower($row['email']));
                $name = trim($row['name']);
                
                if (empty($email) || empty($name)) { continue; }

                $unitKerja = UnitKerja::where('name', $row['unit_kerja_name'])->first();
                $deputy = Deputy::where('name', $row['deputy_name'])->first(); 

                $userData = [
                    'name' => $name,
                    'email' => $email,
                    'role' => strtolower($row['role']),
                    'password' => Hash::make(self::TEMPORARY_PASSWORD),
                    'temporary_password' => self::TEMPORARY_PASSWORD,
                    'needs_password_reset' => true,
                    'is_active' => true,
                    'unit_kerja_id' => $unitKerja->id ?? null,
                    'deputy_id' => $deputy->id ?? null,
                    'jabatan' => $row['jabatan'] ?? null,
                    'nip' => $row['nip'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'email_verified_at' => now(),
                ];

                try {
                    if (strtolower($row['role']) === 'deputy') {
                         $userData['unit_kerja_id'] = null;
                    } else {
                         $userData['deputy_id'] = null;
                    }
                    
                    $user = User::updateOrCreate(['email' => $email], $userData);
                    
                    // Assign Role
                    if ($user->role) {
                         $user->syncRoles($user->role);
                    }
                    
                    $this->importedCount++; 
                    
                } catch (QueryException $e) {
                    if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                         throw new \Exception("Duplikasi Email terdeteksi: Email '{$email}' sudah terdaftar.");
                    }
                    throw $e;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e; 
        }
    }
    
    public function getImportedCount(): int
    {
        return $this->importedCount;
    }
}
