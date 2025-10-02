@extends('layouts.app')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    Manajemen Kategori Laporan
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-create-category">
                        <i class="ti ti-plus me-2"></i> Tambah Kategori
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        {{-- Pesan Sesi --}}
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        
        {{-- BAGIAN 1: LIST KATEGORI UTAMA/SUB --}}
        <div class="card mb-4">
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th>Nama Kategori</th>
                            <th>Tipe</th>
                            <th class="w-1">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $index => $category)
                            <tr class="table-primary fw-bold">
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <i class="ti ti-folder me-2 text-primary"></i> 
                                    {{ $category->name }}
                                    @if ($category->children->count() > 0)
                                        <a class="badge bg-primary-lt ms-2" data-bs-toggle="collapse" href="#subcat-{{ $category->id }}" role="button" aria-expanded="false" aria-controls="subcat-{{ $category->id }}">
                                            {{ $category->children->count() }} Sub-Kategori
                                        </a>
                                    @endif
                                </td>
                                <td>Kategori Utama</td>
                                <td>
                                    {{-- Tombol Edit/Delete Kategori Utama --}}
                                    <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus Kategori Utama ini? Semua sub-kategori harus dihapus dulu!');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-icon btn-danger" title="Hapus Kategori">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                        {{-- Anda bisa menambahkan tombol edit di sini --}}
                                    </form>
                                </td>
                            </tr>
                            
                            {{-- Baris Sub-Kategori --}}
                            @if ($category->children->count() > 0)
                                <tr>
                                    <td colspan="4" class="p-0 border-0">
                                        <div class="collapse multi-collapse" id="subcat-{{ $category->id }}">
                                            <table class="table table-sm table-striped mb-0">
                                                <tbody>
                                                    @foreach ($category->children as $child)
                                                        <tr>
                                                            <td style="width: 5%;"></td>
                                                            <td style="padding-left: 3rem;">
                                                                <i class="ti ti-arrow-merge-right me-2 text-secondary"></i> 
                                                                {{ $child->name }}
                                                            </td>
                                                            <td>Sub-Kategori</td>
                                                            <td class="w-1">
                                                                {{-- Tombol Delete Sub-Kategori --}}
                                                                <form action="{{ route('categories.destroy', $child) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus Sub-Kategori ini?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Hapus Sub-Kategori">
                                                                        <i class="ti ti-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada kategori yang dibuat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- BAGIAN 2: TABEL PENUGASAN UNIT KERJA KE KATEGORI --}}
        {{-- Menggunakan $units yang dikirim dari controller --}}
        @if ($units->count() > 0 && $categories->where('parent_id', null)->count() > 0)
            <h3 class="mt-5 mb-3">Manajemen Penugasan Unit Kerja (Disposisi)</h3>
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="w-25">Kategori Utama</th>
                                @foreach ($units as $unit)
                                    <th class="text-center" title="{{ $unit->name }}">{{ Str::limit($unit->name, 20) }}</th>
                                @endforeach
                                <th class="w-1">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories->where('parent_id', null) as $category)
                                {{-- Setiap baris adalah FORM terpisah untuk menyimpan penugasan kategori ini --}}
                                <form action="{{ route('categories.assign.units') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="category_id" value="{{ $category->id }}">
                                    <tr>
                                        <td>
                                            <i class="ti ti-folder-check me-2 text-success"></i>
                                            <strong>{{ $category->name }}</strong>
                                        </td>
                                        @foreach ($units as $unit)
                                            <td class="text-center">
                                                @php
                                                    // Cek apakah Unit ini sudah ditugaskan ke Kategori saat ini
                                                    $isAssigned = $category->unitKerjas->contains($unit->id);
                                                @endphp
                                                <label class="form-check form-check-single form-switch">
                                                    {{-- Gunakan array naming untuk mengirim hanya yang dicentang --}}
                                                    <input 
                                                        class="form-check-input" 
                                                        type="checkbox" 
                                                        name="unit_assignments[{{ $unit->id }}]" 
                                                        value="{{ $unit->id }}"
                                                        {{ $isAssigned ? 'checked' : '' }}
                                                    >
                                                </label>
                                            </td>
                                        @endforeach
                                        <td class="w-1">
                                            <button type="submit" class="btn btn-sm btn-primary" title="Simpan Penugasan">
                                                Simpan
                                            </button>
                                        </td>
                                    </tr>
                                </form>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
             <div class="alert alert-info mt-4">Silakan tambahkan Kategori Utama dan Unit Kerja untuk mengelola penugasan.</div>
        @endif
    </div>
</div>

{{-- Modal Tambah Kategori (Tidak Berubah) --}}
<div class="modal modal-blur fade" id="modal-create-category" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kategori atau Sub-Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('categories.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori / Sub-Kategori</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Contoh: Biro Umum atau Administrasi" required value="{{ old('name') }}">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Induk Kategori (Optional)</label>
                        <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                            <option value="">-- Pilih sebagai Kategori Utama --</option>
                            @foreach ($allCategories as $cat)
                                <option value="{{ $cat->id }}" {{ old('parent_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <small class="form-hint">Pilih kategori induk jika Anda membuat Sub-Kategori.</small>
                        @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection