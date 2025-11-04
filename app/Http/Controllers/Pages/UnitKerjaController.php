<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class UnitKerjaController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:unit_kerjas,name',
            'deputy_id' => 'required|exists:deputies,id',
        ], [
            'name.unique' => 'Nama Unit Kerja sudah terdaftar.'
        ]);
        
        UnitKerja::create($validated);
        return redirect()->route('settings.index', ['#tab-matrix'])->with('success', 'Unit Kerja berhasil ditambahkan.');
    }

    public function update(Request $request, UnitKerja $unitKerja)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('unit_kerjas')->ignore($unitKerja->id)],
            'deputy_id' => 'required|exists:deputies,id',
        ], [
             'name.unique' => 'Nama Unit Kerja sudah terdaftar.'
        ]);

        $unitKerja->update($validated);
        return redirect()->route('settings.index', ['#tab-matrix'])->with('success', 'Unit Kerja berhasil diperbarui.');
    }

    public function destroy(UnitKerja $unitKerja)
    {
        DB::beginTransaction();
        try {
            // --- 1. Hapus Relasi Many-to-Many (Kategori) ---
            // Melepaskan semua kategori yang ditugaskan ke unit ini.
            $unitKerja->categories()->detach();
            Log::info("Unit Kerja {$unitKerja->name}: Relasi kategori berhasil diputus.");

            // --- 2. Nullify Relasi One-to-Many (Pengguna) ---
            // Set kolom unit_kerja_id pada semua pengguna yang terkait menjadi NULL.
            User::where('unit_kerja_id', $unitKerja->id)
                ->update(['unit_kerja_id' => null]);
            Log::info("Unit Kerja {$unitKerja->name}: unit_kerja_id pada pengguna berhasil di-NULL-kan.");
            
            // --- 3. Hapus Unit Kerja ---
            $unitKerja->delete();
            
            DB::commit();
            return redirect()->route('settings.index', ['#tab-matrix'])
                ->with('success', 'Unit Kerja dan semua penugasannya berhasil dihapus.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menghapus Unit Kerja: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal menghapus Unit Kerja karena kesalahan sistem.');
        }
    }
}
