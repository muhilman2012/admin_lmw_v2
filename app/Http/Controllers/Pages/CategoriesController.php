<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\UnitKerja;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriesController extends Controller
{
    public function index()
    {
        // Muat hanya kategori utama, eager load sub-kategori, dan relasi unitKerjas
        $categories = Category::mainCategories()
            ->with(['children', 'unitKerjas']) // <-- Tambahkan 'unitKerjas'
            ->orderBy('name')
            ->get();

        // Ambil semua Unit Kerja
        $units = UnitKerja::orderBy('name')->get(); // <-- Tambahkan ini

        // Ambil semua kategori untuk digunakan di dropdown parent saat membuat/mengedit
        $allCategories = Category::all(['id', 'name']);

        // Kirimkan Unit Kerja ke view
        return view('pages.categories.index', compact('categories', 'allCategories', 'units')); // <-- Tambahkan 'units'
    }

    /**
     * Tampilkan form untuk membuat kategori baru.
     */
    public function create()
    {
        $mainCategories = Category::mainCategories()->orderBy('name')->get();
        return view('pages.categories.create', compact('mainCategories'));
    }

    /**
     * Simpan kategori baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        Category::create($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Kategori/Sub-Kategori berhasil ditambahkan.');
    }

    /**
     * Hapus kategori dari database.
     */
    public function destroy(Category $category)
    {
        // Perhatian: Sebelum menghapus kategori, Anda harus memindahkan
        // laporan yang terkait ke kategori lain atau menghapusnya (sesuai logika bisnis Anda).
        // Untuk contoh ini, kita hanya akan membatasi penghapusan jika ada sub-kategori terkait.

        if ($category->children()->exists()) {
            return redirect()->route('categories.index')
                ->with('error', 'Gagal menghapus. Kategori ini memiliki Sub-Kategori.');
        }

        // Opsional: Cek apakah kategori ini digunakan di model Report sebelum menghapus

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    public function assignUnits(Request $request)
    {
        $validated = $request->validate([
            'unit_assignments' => 'nullable|array',
            'unit_assignments.*' => 'nullable|integer|exists:unit_kerjas,id',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        $category = Category::find($validated['category_id']);
        
        // Ambil semua Unit ID yang dikirim (Unit yang dicentang)
        $assignedUnitIds = array_keys($validated['unit_assignments'] ?? []);
        
        // SINKRONISASI: Hanya Unit ID yang ada di $assignedUnitIds yang akan tetap terpasang.
        // Semua Unit ID lainnya yang sebelumnya terpasang akan dilepas (detached).
        $category->unitKerjas()->sync($assignedUnitIds);

        return redirect()->route('categories.index')
            ->with('success', "Penugasan unit kerja untuk kategori '{$category->name}' berhasil diperbarui.");
    }
}
