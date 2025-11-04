<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class UserImportController extends Controller
{
    /**
     * Menangani proses import data pengguna.
     */
    public function store(Request $request)
    {
        set_time_limit(0); 
        
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);
        
        $redirectTarget = '#import-users';

        try {
            $userImport = new UsersImport; 

            Excel::import($userImport, $request->file('import_file'));
            
            $count = $userImport->getImportedCount(); 
            
            return redirect()->to(route('settings.index') . $redirectTarget)
                ->with('success', "Import data pengguna berhasil diproses! Total {$count} pengguna diperbarui/ditambahkan.");

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error('Import Users Gagal: ' . $errorMessage);
            
            if (str_contains($errorMessage, 'Duplikasi Email terdeteksi')) {
                $displayMessage = $errorMessage;
            } else {
                $displayMessage = "Import Gagal: Terjadi kesalahan database/data. " . $errorMessage;
            }
            
            return redirect()->to(route('settings.index') . $redirectTarget)
                ->with('error', $displayMessage);
        }
    }

    /**
     * Menyediakan template Excel/CSV untuk diunduh.
     */
    public function downloadTemplate()
    {
        $headers = [
            'name', 
            'email', 
            'role', 
            'password_hash',
            'deputy_name',
            'unit_kerja_name', 
            'jabatan', 
            'nip', 
            'phone'
        ];

        $exampleData = [
            // Contoh 1: Role Analyst (memiliki Unit Kerja, tidak memiliki Deputi ID)
            [
                'Analyst Budi', 
                'budi@example.com', 
                'analyst', 
                '$2y$10$w4rB5eXyA6zC7vI8...', 
                '', // Kolom deputy_name dikosongkan
                'Biro Perencanaan', // Unit Kerja diisi
                'Kepala Bagian', 
                '1980xxxx', 
                '081xxxx'
            ],
            // Contoh 2: Role Deputy (memiliki Deputi ID, tidak memiliki Unit Kerja ID)
            [
                'Deputi Agung', 
                'agung@example.com', 
                'deputy', 
                '$2y$10$w4rB5eXyA6zC7vI8...', 
                'Deputi Bidang A', // Kolom deputy_name diisi
                '', // unit_kerja_name dikosongkan
                'Kepala Bagian', 
                '1980xxxx', 
                '081xxxx'
            ],
        ];

        $data = Collection::make([$headers, ...$exampleData]);

        return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromArray {
            protected $data;
            public function __construct($data) { $this->data = $data; }
            public function array(): array { return $this->data->toArray(); }
        }, 'template_users_import.xlsx');
    }
}
