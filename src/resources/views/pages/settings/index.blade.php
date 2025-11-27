@extends('layouts.app')

@section('title', 'Pengaturan Aplikasi')
@section('page_pretitle', 'Pengaturan')
@section('page_title', 'Pengaturan Aplikasi')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    Pengaturan Sistem
                </h2>
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
        
        <div class="card">
            <div class="card-header sticky-top bg-white" style="top: 56px; z-index:10;">
                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a href="#tab-categories" class="nav-link active" data-bs-toggle="tab" aria-selected="true" role="tab">
                            <i class="ti ti-list me-2"></i> Daftar Kategori
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-assignments" class="nav-link" data-bs-toggle="tab" aria-selected="false" tabindex="-1" role="tab">
                            <i class="ti ti-link me-2"></i> Penugasan Unit Kerja
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-matrix" class="nav-link" data-bs-toggle="tab" aria-selected="false" tabindex="-1" role="tab">
                            <i class="ti ti-table me-2"></i> Matrix Organisasi
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-import" class="nav-link" data-bs-toggle="tab" aria-selected="false" tabindex="-1" role="tab">
                            <i class="ti ti-cloud-upload me-2"></i> Import Data
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-maintenance" class="nav-link" data-bs-toggle="tab" aria-selected="false" tabindex="-1" role="tab">
                            <i class="ti ti-tool me-2"></i> Maintenance
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-gemini-monitor" class="nav-link" data-bs-toggle="tab" aria-selected="false" tabindex="-1" role="tab">
                            <i class="ti ti-activity-heartbeat me-2"></i> Monitoring Gemini API
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-templates" class="nav-link" data-bs-toggle="tab" aria-selected="false" tabindex="-1" role="tab">
                            <i class="ti ti-template me-2"></i> Status & Dokumen Templates
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content">
                    {{-- TAB 1: DAFTAR KATEGORI UTAMA/SUB --}}
                    <div class="tab-pane active show" id="tab-categories" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                             <h4 class="mb-0">Daftar Kategori Utama & Sub-Kategori</h4>
                             <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-create-category">
                                <i class="ti ti-plus me-2"></i> Tambah Kategori
                             </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-striped">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th>Nama Kategori</th>
                                        <th>Tipe</th>
                                        <th>Status</th>
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
                                                <label class="form-check form-switch form-switch-lg">
                                                    <input class="form-check-input toggle-category-active" 
                                                        type="checkbox" 
                                                        data-category-id="{{ $category->id }}"
                                                        {{ $category->is_active ? 'checked' : '' }}
                                                    />
                                                </label>
                                            </td>
                                            <td>
                                                {{-- Tombol Edit/Delete Kategori Utama --}}
                                                <form id="delete-category-{{ $category->id }}" action="{{ route('settings.destroy', $category) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" 
                                                            class="btn btn-sm btn-icon btn-danger delete-category-btn" 
                                                            data-category-id="{{ $category->id }}"
                                                            data-category-name="{{ $category->name }}" 
                                                            title="Hapus Kategori">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                    {{-- Anda bisa menambahkan tombol edit di sini --}}
                                                </form>
                                            </td>
                                        </tr>
                                        
                                        {{-- Baris Sub-Kategori --}}
                                        @if ($category->children->count() > 0)
                                            @foreach ($category->children as $child)
                                                <tr class="collapse multi-collapse" id="subcat-{{ $category->id }}">
                                                    <td style="width: 5%;"></td> {{-- Kolom No. Kosong --}}
                                                    
                                                    {{-- KOLOM NAMA (DENGAN INDENTASI) --}}
                                                    <td style="padding-left: 3rem;"> 
                                                        <i class="ti ti-arrow-merge-right me-2 text-secondary"></i> 
                                                        {{ $child->name }}
                                                    </td>
                                                    
                                                    <td>Sub-Kategori</td>
                                                    
                                                    {{-- KOLOM STATUS (SEJAJAR DENGAN TOGGLE UTAMA) --}}
                                                    <td>
                                                        <label class="form-check form-switch form-switch-lg"> {{-- Gunakan form-switch-lg agar sejajar --}}
                                                            <input class="form-check-input toggle-category-active" 
                                                                type="checkbox" 
                                                                data-category-id="{{ $child->id }}"
                                                                {{ $child->is_active ? 'checked' : '' }}
                                                            />
                                                        </label>
                                                    </td>
                                                    
                                                    {{-- KOLOM AKSI --}}
                                                    <td class="w-1">
                                                        {{-- Tombol Delete Sub-Kategori --}}
                                                        <form id="delete-subcategory-{{ $child->id }}" action="{{ route('settings.destroy', $child) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-icon btn-outline-danger delete-subcategory-btn" 
                                                                    data-category-id="{{ $child->id }}"
                                                                    data-category-name="{{ $child->name }}"
                                                                    title="Hapus Sub-Kategori">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
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

                    {{-- TAB 2: MATRIKS PENUGASAN UNIT KERJA (AJAX Filtered) --}}
                    <div class="tab-pane" id="tab-assignments" role="tabpanel">
                        <h4 class="mb-3">Filter Penugasan Berdasarkan Kedeputian</h4>

                        {{-- FILTER DROPDOWN --}}
                        <div class="mb-4 d-flex align-items-center">
                            <label for="deputy-filter" class="form-label me-3 mb-0">Pilih Kedeputian:</label>
                            <select id="deputy-filter" class="form-select w-auto" style="min-width: 200px;">
                                <option value="">-- Pilih Deputi --</option>
                                @foreach ($deputies as $deputy)
                                    <option value="{{ $deputy->id }}" {{ $deputy->id == $lastActiveDeputyId ? 'selected' : '' }}>
                                        {{ $deputy->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- CONTAINER HASIL MATRIKS --}}
                        <div class="card">
                            <div class="card-body">
                                <div id="assignment-matrix-container">
                                    <p class="text-center text-muted m-0 p-4">Silakan pilih Kedeputian di atas untuk menampilkan matriks penugasan.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TAB 3: MATRIX DISTRIBUSI (MANAJEMEN DEPUTI & UNIT KERJA) --}}
                    <div class="tab-pane" id="tab-matrix" role="tabpanel">
                        <h4 class="mb-3">Manajemen Hierarki Organisasi (Deputi & Unit Kerja)</h4>
                        <p class="text-muted">Gunakan tab ini untuk menambah, mengedit, atau memindahkan Unit Kerja antar Kedeputian.</p>
                        {{-- Tombol Tambah Unit Kerja Baru (GLOBAL) --}}
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modal-manage-unit"
                                    data-mode="create"
                                    data-unit-id=""
                            >
                                <i class="ti ti-plus me-2"></i> Tambah Unit Kerja
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-vcenter card-table">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th style="width: 50%;">Kedeputian</th>
                                        <th style="width: 40%;">Unit Kerja Dibawahnya</th>
                                        <th class="w-1">Aksi Deputi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($groupedUnits as $deputyName => $data)
                                        <tr class="table-info fw-bold">
                                            <td>{{ $deputyName }}</td>
                                            <td>
                                                <span class="badge bg-info-lt">{{ count($data['units']) }} Unit</span>
                                            </td>
                                            <td>
                                                {{-- Tombol Edit/Delete Deputi --}}
                                                <button class="btn btn-sm btn-icon btn-outline-secondary edit-deputy-trigger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modal-manage-deputy"
                                                        data-deputy-id="{{ $data['id'] }}" 
                                                        data-deputy-name="{{ $deputyName }}"
                                                        title="Edit Deputi">
                                                    <i class="ti ti-pencil"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        
                                        {{-- Loop untuk menampilkan Unit Kerja di bawah Deputi ini --}}
                                        @forelse ($data['units'] as $unit)
                                            <tr data-deputy-id="{{ $data['id'] }}">
                                                <td></td> {{-- Kolom Deputi kosong --}}
                                                <td style="padding-left: 2rem;">
                                                    <i class="ti ti-arrow-merge-right me-2 text-secondary"></i> {{ $unit->name }}
                                                </td>
                                                <td>
                                                    <button type="button" 
                                                        class="btn btn-sm btn-icon btn-outline-secondary edit-unit-trigger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modal-manage-unit" {{-- Tambahkan target modal --}}
                                                        data-mode="edit"
                                                        data-unit-id="{{ $unit->id }}"
                                                        data-unit-name="{{ $unit->name }}"
                                                        data-current-deputy-id="{{ $data['id'] }}"
                                                        title="Ubah Unit/Pindahkan Deputi">
                                                        <i class="ti ti-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-unit-btn" data-unit-id="{{ $unit->id }}" title="Hapus Unit">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td></td>
                                                <td class="text-muted fst-italic">Belum ada Unit Kerja yang ditugaskan ke Deputi ini.</td>
                                                <td></td>
                                            </tr>
                                        @endforelse
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Tidak ada data Kedeputian yang terdaftar.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Di Tab 4: Import Data --}}
                    <div class="tab-pane" id="tab-import" role="tabpanel">
                        <h4 class="mb-3">Import Data Massal</h4>
                        {{-- Navigasi Sub-Tab untuk Import --}}
                        <ul class="nav nav-pills card-header-pills mb-3" role="tablist">
                            <li class="nav-item">
                                <a href="#import-reports" class="nav-link active" data-bs-toggle="tab" role="tab">Data Laporan</a>
                            </li>
                            <li class="nav-item">
                                <a href="#import-users" class="nav-link" data-bs-toggle="tab" role="tab">Data Pengguna</a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            {{-- Sub-Tab 1: Import Laporan (Kode Anda yang sudah ada, dibungkus div) --}}
                            <div class="tab-pane fade show active" id="import-reports" role="tabpanel">
                                <h4 class="mb-3">Import Data Laporan (Massal)</h4>
                                <div class="alert alert-warning">
                                    Gunakan fitur ini hanya untuk mengimpor data lama atau massal. Pastikan file Excel/CSV sesuai dengan format yang ditentukan.
                                </div>
                                
                                <div class="mb-3">
                                    <a href="{{ route('export.template') }}" class="btn btn-outline-primary btn-sm">
                                        <i class="ti ti-download me-2"></i> Download Template (.xlsx)
                                    </a>
                                </div>
                                @if (session()->has('import_errors'))
                                    <div class="card mt-4 border-danger-subtle">
                                        <div class="card-header bg-danger-lt">
                                            <h3 class="card-title text-danger">Detail Kegagalan Import</h3>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-vcenter table-striped mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 20%;">Nomor Tiket</th>
                                                            <th>Alasan Kegagalan</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach (session('import_errors') as $error)
                                                            <tr>
                                                                <td>{{ $error['ticket'] ?? 'N/A' }}</td>
                                                                <td class="text-danger">{{ $error['error'] }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <form id="import-form" action="{{ route('import.reports') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label">Pilih File (.xlsx, .csv)</label>
                                        <input type="file" name="import_file" id="import-file-input" class="form-control" required>
                                        <small class="form-hint mt-2">Format yang didukung: Excel (.xlsx) dan CSV. Maks 5MB.</small>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="skipValidationCheckbox" name="skip_validation" value="1">
                                            <label class="form-check-label" for="skipValidationCheckbox">Lewati validasi duplikasi NIK/Tiket</label>
                                        </div>
                                    </div>
                                    <div id="import-preview-container" class="mb-4" style="display: none;">
                                        </div>

                                    <button type="submit" class="btn btn-success" id="btn-proses-import" disabled>
                                        <i class="ti ti-file-import me-2"></i> Proses Import Data
                                    </button>
                                </form>
                            </div>
                            
                            {{-- Sub-Tab 2: Import Pengguna --}}
                            <div class="tab-pane fade" id="import-users" role="tabpanel">
                                <h5 class="mb-3">Import Data Pengguna (Staf & Analis)</h5>
                                <div class="alert alert-info">
                                    Gunakan fitur ini untuk migrasi data pengguna lama. **Pastikan kolom 'password' diisi dengan hash password dari database Laravel versi 1.**
                                </div>
                                
                                <div class="mb-3">
                                    <a href="{{ route('import.users.template') }}" class="btn btn-outline-primary btn-sm">
                                        <i class="ti ti-download me-2"></i> Download Template Pengguna
                                    </a>
                                </div>

                                <form id="import-users-form" action="{{ route('import.users.store') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label">Pilih File (.xlsx, .csv)</label>
                                        <input type="file" name="import_file" class="form-control" required>
                                        <small class="form-hint mt-2">Kolom 'role' harus diisi dengan role yang valid (cth: analyst, superadmin).</small>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="ti ti-cloud-upload me-2"></i> Proses Import Pengguna
                                    </button>
                                </form>
                            </div>
                            
                        </div>
                    </div>
                    {{-- TAB 5: MAINTENANCE (COMMANDS ARTISAN) --}}
                    <div class="tab-pane" id="tab-maintenance" role="tabpanel">
                        <h4 class="mb-4">Tools Maintenance Sistem</h4>
                        <p class="text-muted">Gunakan alat ini dengan hati-hati. Menjalankan perintah ini secara berlebihan dapat memengaruhi kinerja sistem.</p>

                        <div class="card mb-4 border-info-subtle">
                            <div class="card-header bg-info-lt">
                                <h5 class="card-title mb-0">Cek Status Lapor! (Manual)</h5>
                            </div>
                            <div class="card-body">
                                <p>Menjalankan <code>php artisan lapor:check-status</code> untuk segera memeriksa dan memperbarui status semua laporan yang diteruskan melalui LAPOR!.</p>
                                <p class="text-secondary small">Biasanya berjalan otomatis oleh scheduler server, tombol ini memaksa pengecekan saat itu juga.</p>
                                <button type="button" 
                                        class="btn btn-primary run-maintenance-command" 
                                        data-command="lapor:check-status" 
                                        data-title="Cek Status LAPOR!">
                                    <i class="ti ti-checkup-list me-1"></i> Jalankan Cek Status
                                </button>
                            </div>
                        </div>
                        
                        <div class="card mb-4 border-info-subtle">
                            <div class="card-header bg-info-lt">
                                <h5 class="card-title mb-0">Pengiriman Ulang Laporan Gagal</h5>
                            </div>
                            <div class="card-body">
                                <p>Menjalankan <code>php artisan lapor:retry-forwarding</code> untuk mencoba mengirim ulang semua laporan yang statusnya <strong>"Gagal Forward"</strong>.</p>
                                <p class="text-secondary small">Perintah ini juga berjalan otomatis oleh scheduler server.</p>
                                <button type="button" 
                                        class="btn btn-info run-maintenance-command" 
                                        data-command="lapor:retry-forwarding" 
                                        data-title="Kirim Ulang LAPOR!">
                                    <i class="ti ti-reload me-1"></i> Jalankan Retry Forwarding
                                </button>
                            </div>
                        </div>
                        <div class="card mb-4 border-success-subtle">
                            <div class="card-header bg-success-lt">
                                <h5 class="card-title mb-0">Migrasi Data Laporan V1</h5>
                            </div>
                            <div class="card-body">
                                <p>Jalankan migrasi per halaman (page) secara manual. Jumlah data per halaman ditentukan saat memanggil.</p>
                                <p class="text-danger small"><strong>PERINGATAN:</strong> Pastikan Base URL dan Token API V1 sudah diatur di tab API MIGRASI V1.</p>
                                <button type="button" 
                                        class="btn btn-success" 
                                        onclick="promptMigrationSettings('migrate:v1', 'Migrasi Data V1')">
                                    <i class="ti ti-database-import me-1"></i> Mulai Migrasi
                                </button>
                            </div>
                        </div>
                        <div class="card mb-4 border-warning-subtle">
                            <div class="card-header bg-warning-lt">
                                <h5 class="card-title mb-0">Migrasi Log Aktivitas V1</h5>
                            </div>
                            <div class="card-body">
                                <p>Menjalankan <code>php artisan migrate:logs</code> untuk memigrasi riwayat aktivitas pengguna dari sistem V1. Ini adalah <strong>Tahap 3</strong>  migrasi.</p>
                                <p class="text-muted small">Memproses data dalam batch. Gunakan parameter 'page' untuk melanjutkan jika terjadi timeout.</p>
                                <button type="button" 
                                        class="btn btn-warning" 
                                        onclick="promptMigrationSettings('migrate:logs', 'Migrasi Log Aktivitas')">
                                    <i class="ti ti-history me-1"></i> Mulai Migrasi Logs
                                </button>
                            </div>
                        </div>
                        <div class="card mb-4 border-danger-subtle">
                            <div class="card-header bg-danger-lt">
                                <h5 class="card-title mb-0">Migrasi Dokumen Khusus</h5>
                            </div>
                            <div class="card-body">
                                <p>Menjalankan <code>php artisan migrate:documents</code> untuk Sinkronisasi otomatis dan upload ke MinIO. Pastikan semua file migrasi sudah ada dalam folder storage dengan nama <strong>dokumen_v1</strong></p>
                                <button type="button" class="btn btn-danger run-maintenance-command" data-command="migrate:documents" data-title="Migrasi Dokumen">
                                    <i class="ti ti-database-export me-1"></i> Jalankan Migrate:Documents
                                </button>
                            </div>
                        </div>
                        <div class="card mb-4 border-warning-subtle">
                            <div class="card-header bg-warning-lt">
                                <h5 class="card-title mb-0">Sinkronisasi Institusi</h5>
                            </div>
                            <div class="card-body">
                                <p>Menjalankan <code>php artisan sync:institutions</code> untuk menyinkronkan data institusi/unit kerja LAPOR! terbaru.</p>
                                <button type="button" class="btn btn-warning run-maintenance-command" data-command="sync:institutions" data-title="Sinkronisasi Institusi">
                                    <i class="ti ti-rotate me-1"></i> Jalankan sync:institutions
                                </button>
                            </div>
                        </div>
                        {{-- Form tersembunyi untuk submit command --}}
                        <form id="maintenance-command-form" action="{{ route('settings.run-maintenance') }}" method="POST" style="display: none;">
                            @csrf
                            <input type="hidden" name="command" id="maintenance-command-input">
                            <input type="hidden" name="start_page" id="migration-page-input">
                            <input type="hidden" name="limit" id="migration-limit-input">
                            <input type="hidden" name="active_tab_hash" value="#tab-maintenance">
                        </form>
                    </div>
                    {{-- TAB 6 : MONITORING GEMINI API --}}
                    <div class="tab-pane" id="tab-gemini-monitor" role="tabpanel">
                        <h4 class="mb-3">Status dan Health Check Gemini API</h4>
                        <p class="text-muted">Fitur ini menampilkan status koneksi dan hasil uji coba sederhana ke Gemini API. Status di-cache selama 5 menit.</p>
                        <div class="row row-cards" id="gemini-status-results">
                            {{-- Loader Awal --}}
                            <div class="col-12 text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted">Memeriksa status API...</p>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="checkGeminiStatus(true)">
                                <i class="ti ti-refresh me-1"></i> Refresh Manual
                            </button>
                        </div>
                    </div>
                    {{-- TAB 7 : TEMPLATES STATUS & DOKUMEN --}}
                    <div class="tab-pane" id="tab-templates" role="tabpanel">
                        <h4 class="mb-4">Manajemen Template Status Laporan dan Data Dukung</h4>

                        <ul class="nav nav-tabs nav-tabs-alt" data-bs-toggle="tabs" role="tablist">
                            <li class="nav-item"><a href="#sub-tab-status" class="nav-link active" data-bs-toggle="tab" role="tab">Status Templates ({{ $statusTemplates->count() }})</a></li>
                            <li class="nav-item"><a href="#sub-tab-document" class="nav-link" data-bs-toggle="tab" role="tab">Document Templates ({{ $documentTemplates->count() }})</a></li>
                        </ul>

                        <div class="tab-content border-0 card-body">
                            
                            {{-- SUB-TAB 1: STATUS TEMPLATES --}}
                            <div class="tab-pane active show" id="sub-tab-status" role="tabpanel">
                                @include('pages.settings.partials.status_templates') {{--  View CRUD Status --}}
                            </div>
                            
                            {{-- SUB-TAB 2: DOCUMENT TEMPLATES --}}
                            <div class="tab-pane fade" id="sub-tab-document" role="tabpanel">
                                @include('pages.settings.partials.document_templates') {{-- 🔥 View CRUD Dokumen --}}
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Tambah Kategori --}}
<div class="modal modal-blur fade" id="modal-create-category" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kategori atau Sub-Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('settings.store') }}" method="POST">
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

{{-- MODAL TAMBAH UNIT KERJA (Digunakan di Tab Matrix Distribusi) --}}
<div class="modal modal-blur fade" id="modal-manage-unit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-manage-unit-title">Tambah Unit Kerja Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            {{-- Form ini di-handle oleh JS untuk mengubah method (POST/PUT) dan action --}}
            <form id="unit-management-form" action="{{ route('settings.unitkerjas.store') }}" method="POST">
                @csrf
                {{-- Input tersembunyi untuk menampung method PUT/PATCH --}}
                <input type="hidden" name="_method" value="POST" id="unit-management-method"> 
                <input type="hidden" name="unit_id" id="unit-management-id"> {{-- ID Unit untuk Edit --}}
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Unit Kerja</label>
                        <input type="text" name="name" id="unit-name-input" class="form-control" placeholder="Contoh: Biro Perencanaan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tugaskan ke Kedeputian</label>
                        <select name="deputy_id" id="unit-deputy-select" class="form-select" required>
                            <option value="">-- Pilih Deputi --</option>
                            {{-- Data Deputi di-loop di sini --}}
                            @foreach ($deputies as $deputy)
                                <option value="{{ $deputy->id }}">{{ $deputy->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="unit-management-submit-btn">Simpan Unit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT DEPUTI --}}
<div class="modal modal-blur fade" id="modal-manage-deputy" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="edit-deputy-title">Edit Kedeputian</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            {{-- Form ini di-handle oleh JS untuk mengubah method (POST/PUT) dan action --}}
            <form id="deputy-management-form" action="{{ route('deputies.store') }}" method="POST">
                @csrf
                {{-- Input tersembunyi untuk menampung method PUT/PATCH --}}
                <input type="hidden" name="_method" value="POST" id="deputy-management-method"> 
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kedeputian</label>
                        <input type="text" name="name" id="deputy-name-input" class="form-control" placeholder="Contoh: Deputi Bidang A" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="deputy-management-submit-btn">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // =======================================================
    // A. DEFINISI FUNGSI UTAMA (GLOBAL HELPER FUNCTIONS)
    // =======================================================

    function initializeTomSelect(selector, config = {}) {
        const selectElement = document.getElementById(selector);

        if (window.TomSelect && selectElement) {
            if (selectElement.tomselect) { selectElement.tomselect.destroy(); }

            const defaultConfig = {
                plugins: { dropdown_input: {} },
                create: false,
                allowEmptyOption: true,
                sortField: { field: "text", direction: "asc" },
            };
            const finalConfig = { ...defaultConfig, ...config };
            new TomSelect(`#${selector}`, finalConfig);
        }
    }

    // Fungsi Global untuk menampilkan modal (Pure JS)
    window.showModalPureJS = function(modalElement) {
        if (!modalElement) return;

        document.querySelectorAll('.modal.show').forEach(m => {
            if (m !== modalElement) window.hideModalPureJS(m);
        });

        modalElement.style.display = 'block';
        modalElement.classList.add('show');
        modalElement.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');

        if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
    }

    // Fungsi Global untuk menyembunyikan modal (Pure JS)
    window.hideModalPureJS = function(modalElement) {
        if (!modalElement) return;

        modalElement.classList.remove('show');
        const backdrop = document.querySelector('.modal-backdrop');
        
        if (backdrop) { backdrop.remove(); }
        
        setTimeout(() => {
            modalElement.style.display = 'none';
            modalElement.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        }, 150);
    }
    
    // 🔥 FUNGSI AJAX UNTUK TAB PENUGASAN UNIT KERJA (Global)
    window.loadAssignmentMatrix = function(deputyId) {
        const matrixContainer = document.getElementById('assignment-matrix-container');

        if (!deputyId) {
            matrixContainer.innerHTML = '<p class="text-center text-muted m-0 p-4">Silakan pilih Kedeputian di atas untuk menampilkan matriks penugasan.</p>';
            return;
        }

        matrixContainer.innerHTML = '<p class="text-center text-muted m-0 p-4"><div class="spinner-border text-primary" role="status"></div>Memuat unit kerja...</p>';

        fetch('{{ route('settings.assignments.by.deputy') }}?deputy_id=' + deputyId)
            .then(response => {
                if (!response.ok) { throw new Error('Gagal memuat data matriks.'); }
                return response.json();
            })
            .then(data => {
                matrixContainer.innerHTML = data.html;
                // KRITIS: Panggil ulang listener tombol CRUD unit setelah AJAX sukses
                setupUnitEditListeners(); 
                setupUnitDeleteListeners(); 
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                matrixContainer.innerHTML = `<div class="alert alert-danger m-0">Gagal memuat matriks penugasan: ${error.message}</div>`;
            });
    }

    // --- LOGIKA GEMINI STATUS CHECK (Global) ---
    window.checkGeminiStatus = function(forceRefresh = false) {
        const resultsContainer = document.getElementById('gemini-status-results');
        const url = new URL('{{ route('settings.gemini.status') }}', window.location.origin);
        
        if (resultsContainer) {
            resultsContainer.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Memeriksa status API...</p>
                </div>
            `;
        }
        
        if (forceRefresh) {
            url.searchParams.set('refresh', 'true');
        }

        fetch(url, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            const statusClass = data.status === 'UP' ? 'bg-success' : 
                                 data.status === 'DEGRADED' ? 'bg-warning' : 'bg-danger';
            
            let detailsHtml = '';
            if (data.details) {
                detailsHtml = `<p class="mt-2 text-monospace text-muted small"><strong>Detail (${data.code}):</strong> ${data.details}</p>`;
            }

            if (resultsContainer) {
                resultsContainer.innerHTML = `
                    <div class="col-md-12">
                        <div class="card card-body">
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-md ${statusClass} me-3">
                                    <i class="ti ti-plug-connected text-white"></i>
                                </span>
                                <div>
                                    <h4 class="m-0">${data.status} - ${data.message}</h4>
                                    <p class="text-muted mb-0">Kode Status HTTP: ${data.code} | Terakhir di-check: ${data.timestamp} (Di-cache)</p>
                                </div>
                            </div>
                            ${detailsHtml}
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            if (resultsContainer) {
                resultsContainer.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger">Gagal koneksi ke endpoint status Gemini. Cek koneksi server Anda.</div>
                    </div>
                `;
            }
        });
    }

    // FUNGSI PROMPT MIGRATION (GLOBAL SCOPE)
    window.promptMigrationSettings = function(command, title) {
        const maintenanceForm = document.getElementById('maintenance-command-form');
        const commandInput = document.getElementById('maintenance-command-input');
        const pageInput = document.getElementById('migration-page-input');
        const limitInput = document.getElementById('migration-limit-input');

        const lastPage = parseInt(pageInput.value) || 1;
        const defaultLimit = parseInt(limitInput.value) || 500;
        
        Swal.fire({
            title: `Konfigurasi ${title}`,
            html: `
                <div class="mb-3 text-start">
                    <label for="swal-page" class="form-label">Halaman Mulai (Start Page):</label>
                    <input id="swal-page" type="number" class="form-control" value="${lastPage}" min="1" required>
                </div>
                <div class="mb-3 text-start">
                    <label for="swal-limit" class="form-label">Data per Halaman (Limit):</label>
                    <input id="swal-limit" type="number" class="form-control" value="${defaultLimit}" min="1" required>
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Jalankan Migrasi!',
            cancelButtonText: 'Batal',
            preConfirm: () => {
                const page = document.getElementById('swal-page').value;
                const limit = document.getElementById('swal-limit').value;
                if (!page || !limit || parseInt(page) < 1 || parseInt(limit) < 1) {
                    Swal.showValidationMessage(`Harap isi halaman dan limit dengan angka positif.`);
                    return false;
                }
                return { page: parseInt(page), limit: parseInt(limit) };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const { page, limit } = result.value;
                
                Swal.fire({
                    title: `Konfirmasi Migrasi?`,
                    html: `Memulai <strong>php artisan ${command}</strong> dari Halaman <strong>${page}</strong> dengan <strong>${limit}</strong> data per halaman.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Lanjutkan!',
                }).then((finalResult) => {
                    if (finalResult.isConfirmed) {
                        if (window.appLoader) {
                            window.appLoader.show(`Menjalankan ${command} (Halaman ${page}, Limit ${limit})... Mohon jangan tutup halaman.`);
                        }
                        
                        commandInput.value = command;
                        pageInput.value = page; 
                        limitInput.value = limit; 
                        
                        maintenanceForm.submit();
                    }
                });
            }
        });
    }


    // =======================================================
    // C. LOGIKA CRUD & EVENT BINDING (UTAMA)
    // =======================================================

    function setupDeleteConfirmation(buttonSelector, isSubCategory) {
        document.querySelectorAll(buttonSelector).forEach(button => {
            button.removeEventListener('click', null); 
            button.addEventListener('click', function(event) {
                event.preventDefault(); 
                const categoryId = this.getAttribute('data-category-id');
                const categoryName = this.getAttribute('data-category-name');
                const formId = isSubCategory ? `delete-subcategory-${categoryId}` : `delete-category-${categoryId}`;
                const formElement = document.getElementById(formId);
                
                Swal.fire({
                    title: isSubCategory ? 'Hapus Sub-Kategori?' : 'Hapus Kategori Utama?',
                    html: `Anda yakin ingin menghapus kategori: <strong>${categoryName}</strong>?` + (!isSubCategory ? '<br><small class="text-danger">Semua sub-kategori di dalamnya harus dihapus terlebih dahulu!</small>' : ''), 
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (formElement) { formElement.submit(); } else { console.error('Form tidak ditemukan:', formId); }
                    }
                });
            });
        });
    }

    function setupCategoryToggle() {
        document.querySelectorAll('.toggle-category-active').forEach(toggle => {
            toggle.removeEventListener('change', null);
            toggle.addEventListener('change', function() {
                const categoryId = this.getAttribute('data-category-id');
                const isActive = this.checked;
                const url = '{{ route('settings.toggle-active', ['category' => 'PLACEHOLDER_ID']) }}'.replace('PLACEHOLDER_ID', categoryId);
                
                fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: data.message,
                            showConfirmButton: false,
                            timer: 3000
                        });
                    } else {
                        this.checked = !isActive; 
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: data.message || 'Gagal mengubah status kategori.',
                        });
                    }
                })
                .catch(error => {
                    this.checked = !isActive; 
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Jaringan',
                        text: 'Koneksi gagal atau server bermasalah.',
                    });
                });
            });
        });
    }


    // --- LOGIKA TEMPLATE CRUD ---

    function setupTemplateCrudListeners() {
        
        const statusModal = document.getElementById('modal-manage-status-template');
        const documentModal = document.getElementById('modal-manage-document-template');
        
        const setupModalListeners = (selector, modalElement, type) => {
            document.querySelectorAll(selector).forEach(button => {
                
                button.removeEventListener('click', null); 
                button.removeAttribute('data-bs-toggle');
                button.removeAttribute('data-bs-target');
                
                button.addEventListener('click', function(event) {
                    event.preventDefault(); 
                    
                    const mode = this.getAttribute('data-mode');
                    const form = document.getElementById(`${type}-template-form`);
                    
                    form.reset();
                    document.getElementById(`${type}-template-id`).value = '';
                    
                    document.getElementById(`${type}-template-submit-btn`).textContent = `Simpan ${type === 'status' ? 'Template' : 'Dokumen'}`;
                    document.getElementById(`${type}-template-modal-title`).textContent = `Tambah ${type === 'status' ? 'Status' : 'Dokumen'} Template Baru`;

                    if (mode === 'edit') {
                        document.getElementById(`${type}-template-id`).value = this.getAttribute('data-id');
                        document.getElementById(`${type}-template-name`).value = this.getAttribute('data-name');
                        document.getElementById(`${type}-template-submit-btn`).textContent = `Perbarui ${type === 'status' ? 'Template' : 'Dokumen'}`;
                        document.getElementById(`${type}-template-modal-title`).textContent = `Edit ${type === 'status' ? 'Status' : 'Dokumen'} Template`;

                        if (type === 'status') {
                            document.getElementById('status-template-code').value = this.getAttribute('data-code');
                            document.getElementById('status-template-response').value = this.getAttribute('data-template');
                        }
                    }
                    window.showModalPureJS(modalElement);
                });
            });
            
            // FIX: Tambahkan listener close manual untuk tombol Batal/Close di dalam modal
            if (modalElement) {
                modalElement.querySelectorAll('[data-bs-dismiss="modal"]').forEach(closeBtn => {
                    closeBtn.removeAttribute('data-bs-dismiss'); 
                    closeBtn.addEventListener('click', function(e) { 
                        e.preventDefault(); 
                        window.hideModalPureJS(modalElement); 
                    });
                });
            }
        };

        // 1. Setup Status Template Modal
        if (statusModal) {
            setupModalListeners('.create-status-template-btn, .edit-status-template-btn', statusModal, 'status');
        }
        
        // 2. Setup Document Template Modal
        if (documentModal) {
            setupModalListeners('.create-document-template-btn, .edit-document-template-btn', documentModal, 'document');
        }
        
        // 3. SETUP DELETE CONFIRMATION (Global)
        document.querySelectorAll('.delete-template-btn').forEach(button => {
            // Hapus listener lama jika ada
            button.removeEventListener('click', null); 
            
            button.addEventListener('click', function(event) {
                event.preventDefault();
                
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const type = this.getAttribute('data-type'); // 'status' atau 'document'
                
                Swal.fire({
                    title: `Hapus ${type === 'status' ? 'Status' : 'Dokumen'} Template?`,
                    html: `Anda yakin ingin menghapus template <strong>${name}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonText: 'Batal',
                    confirmButtonText: 'Ya, Hapus!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Membuat form DELETE secara dinamis (Pure JS)
                        const form = document.createElement('form');
                        
                        // Route: /admin/settings/templates/destroy/{id}
                        form.action = '{{ route('settings.templates.destroy', ['id' => 'TEMP_ID']) }}'.replace('TEMP_ID', id);
                        form.method = 'POST'; // Harus POST untuk method override
                        form.style.display = 'none';

                        // CSRF Token
                        const csrf = document.createElement('input');
                        csrf.name = '_token';
                        csrf.value = '{{ csrf_token() }}';
                        
                        // DELETE method override
                        const method = document.createElement('input');
                        method.name = '_method';
                        method.value = 'DELETE';
                        
                        // Input Type (Status/Document)
                        const typeInput = document.createElement('input');
                        typeInput.name = 'type';
                        typeInput.value = type;
                        
                        // Input Active Tab Hash (Untuk persistensi setelah redirect)
                        const hashInput = document.createElement('input');
                        hashInput.name = 'active_tab_hash';
                        hashInput.value = '#tab-templates'; // Arahkan kembali ke tab Templates

                        form.appendChild(csrf);
                        form.appendChild(method);
                        form.appendChild(typeInput);
                        form.appendChild(hashInput);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    }

    // --- LOGIKA EDIT/DELETE UNIT KERJA (MATRIKS) ---

    function setupUnitEditListeners() {
        const unitModal = document.getElementById('modal-manage-unit');
        
        document.querySelectorAll('.edit-unit-trigger').forEach(button => {
            button.removeEventListener('click', null); 
            button.removeAttribute('data-bs-toggle');
            button.removeAttribute('data-bs-target');
            
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const unitId = this.getAttribute('data-unit-id');
                const unitName = this.getAttribute('data-unit-name');
                const currentDeputyId = this.getAttribute('data-current-deputy-id');
                
                const unitForm = document.getElementById('unit-management-form');
                const unitModalTitle = document.getElementById('modal-manage-unit-title');
                const unitNameInput = document.getElementById('unit-name-input');
                const unitDeputySelect = document.getElementById('unit-deputy-select');
                const unitMethod = document.getElementById('unit-management-method');
                const unitSubmitBtn = document.getElementById('unit-management-submit-btn');

                // 1. Set values
                unitModalTitle.textContent = 'Edit Unit Kerja: ' + unitName;
                unitMethod.value = 'PUT';
                unitNameInput.value = unitName;
                unitSubmitBtn.textContent = 'Simpan Perubahan';
                unitForm.action = `/admin/settings/unit-kerjas/${unitId}`;

                // 2. Set dropdown value (TomSelect)
                initializeTomSelect('unit-deputy-select'); 
                if (unitDeputySelect.tomselect) {
                    unitDeputySelect.tomselect.setValue(currentDeputyId);
                } else {
                    unitDeputySelect.value = currentDeputyId;
                }
                
                window.showModalPureJS(unitModal);
            });
        });

        if (unitModal) {
            unitModal.querySelectorAll('[data-bs-dismiss="modal"]').forEach(closeBtn => {
                closeBtn.removeAttribute('data-bs-dismiss'); 
                closeBtn.addEventListener('click', function(e) { e.preventDefault(); window.hideModalPureJS(unitModal); });
            });
        }
    }
    
    function setupUnitDeleteListeners() {
        const deleteButtons = document.querySelectorAll('.delete-unit-btn');

        deleteButtons.forEach(button => {
            button.removeEventListener('click', null); 
            
            button.addEventListener('click', function(event) {
                event.preventDefault();
                
                const unitId = this.getAttribute('data-unit-id'); 
                const row = this.closest('tr');
                
                // Logic untuk mendapatkan nama unit (dipertahankan)
                const unitNameElement = row.querySelector('td:nth-child(2)');
                const unitName = unitNameElement ? unitNameElement.textContent.trim().replace(/^\s*[\u200B\s\uFEFF]*\u2192?\s*|\s*[\u200B\s\uFEFF]*\u2192?\s*$/g, '').trim() : 'Unit Tidak Dikenal';
                
                Swal.fire({
                    title: 'Hapus Unit Kerja?',
                    text: `Anda yakin ingin menghapus Unit Kerja: ${unitName}? Tindakan ini tidak dapat dibatalkan.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        
                        // FIX ROUTE: Tambahkan /admin prefix
                        form.action = `/admin/settings/unit-kerjas/${unitId}`; 
                        form.method = 'POST'; // Harus POST untuk method override
                        form.style.display = 'none';
                        
                        const methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.value = 'DELETE';
                        
                        const tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = '_token';
                        // Ambil CSRF token dari meta tag global
                        tokenInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                        // Input untuk persistensi tab (opsional)
                        const tabHashInput = document.createElement('input');
                        tabHashInput.type = 'hidden';
                        tabHashInput.name = 'active_tab_hash';
                        tabHashInput.value = '#tab-matrix'; 
                        
                        form.appendChild(methodInput);
                        form.appendChild(tokenInput);
                        form.appendChild(tabHashInput);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    }
    
    // --- BINDING TOMBOL LAIN UNTUK PURE JS MODAL ---
    // =======================================================
    // LOGIKA KHUSUS MODAL TAMBAH KATEGORI
    // =======================================================
    
    const createCategoryButton = document.querySelector('[data-bs-target="#modal-create-category"]');
    const categoryModal = document.getElementById('modal-create-category');

    if (createCategoryButton && categoryModal) {
         
         // 1. Hapus atribut Bootstrap dari tombol trigger (agar tidak konflik)
         createCategoryButton.removeAttribute('data-bs-toggle');
         
         // 2. Ikat listener Buka Modal ke tombol trigger
         createCategoryButton.addEventListener('click', function(e) {
             e.preventDefault();
             
             // Reset form dan TomSelect saat modal dibuka untuk Tambah Baru
             const form = categoryModal.querySelector('form');
             if (form) form.reset();
             const parentSelect = document.getElementById('parent_id');
             if (parentSelect && parentSelect.tomselect) {
                 parentSelect.tomselect.clear();
             }
             
             window.showModalPureJS(categoryModal);
         });
         
         // 3. Ikat listener Tutup Modal ke tombol-tombol yang memiliki data-bs-dismiss="modal"
         categoryModal.querySelectorAll('[data-bs-dismiss="modal"]').forEach(closeBtn => {
             // 🔥 KRITIS: Hapus event listener lama dan atribut Bootstrap
             closeBtn.removeAttribute('data-bs-dismiss'); 
             closeBtn.addEventListener('click', function(e) { 
                 e.preventDefault(); 
                 window.hideModalPureJS(categoryModal); 
             });
         });
    }
    
    function setupModalTriggers() {        
        // Tombol Tambah Unit Kerja Baru (GLOBAL)
        const createUnitButton = document.querySelector('[data-mode="create"][data-bs-target="#modal-manage-unit"]');
        const unitModal = document.getElementById('modal-manage-unit');
        if (createUnitButton) {
             createUnitButton.removeAttribute('data-bs-toggle');
             createUnitButton.addEventListener('click', function(e) {
                 e.preventDefault();
                 // Logic mengisi form create
                 const unitForm = document.getElementById('unit-management-form');
                 unitForm.reset();
                 unitForm.action = '/admin/settings/unit-kerjas';
                 window.showModalPureJS(unitModal);
             });
        }
        
        // Tombol Edit Deputi
        const editDeputyTriggers = document.querySelectorAll('.edit-deputy-trigger');
        const deputyModal = document.getElementById('modal-manage-deputy');
        
        editDeputyTriggers.forEach(button => {
            button.removeAttribute('data-bs-toggle');
            button.removeAttribute('data-bs-target');
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const deputyId = this.getAttribute('data-deputy-id');
                const deputyName = this.getAttribute('data-deputy-name');
                
                document.getElementById('edit-deputy-title').textContent = 'Edit Kedeputian: ' + deputyName;
                document.getElementById('deputy-name-input').value = deputyName;
                document.getElementById('deputy-management-method').value = 'PUT';
                document.getElementById('deputy-management-form').action = `/admin/deputies/${deputyId}`;

                window.showModalPureJS(deputyModal);
            });
        });

        if (deputyModal) {
             deputyModal.querySelectorAll('[data-bs-dismiss="modal"]').forEach(closeBtn => {
                closeBtn.removeAttribute('data-bs-dismiss'); 
                closeBtn.addEventListener('click', function(e) { e.preventDefault(); window.hideModalPureJS(deputyModal); });
            });
        }

    }


    // =======================================================
    // D. BLOK DOMContentLoaded UTAMA (MAIN LOGIC)
    // =======================================================

    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. LOGIKA INITIALISASI PLUGIN 
        initializeTomSelect('parent_id'); 
        
        // 2. LOGIKA AJAX (ASSIGNMENT MATRIX)
        const deputyFilter = document.getElementById('deputy-filter');
        if (deputyFilter) {
            initializeTomSelect('deputy-filter'); // Inisialisasi TomSelect untuk filter Deputi
            
            deputyFilter.addEventListener('change', function() {
                window.loadAssignmentMatrix(this.value);
            });
            if (deputyFilter.value) {
                window.loadAssignmentMatrix(deputyFilter.value);
            }
        }
        
        // 3. LOGIKA BINDING UNTUK SEMUA TAB
        setupAllCrudListeners();
        setupModalTriggers(); 

        // 4. LOGIKA PERSISTENSI TAB SAAT REDIRECT & INIT GEMINI TAB
        function setupTabPersistence() {
             const forms = document.querySelectorAll('form');
             forms.forEach(form => {
                 let tabInput = form.querySelector('input[name="active_tab_hash"]');
                 if (!tabInput) {
                     tabInput = document.createElement('input');
                     tabInput.type = 'hidden';
                     tabInput.name = 'active_tab_hash';
                     form.appendChild(tabInput);
                 }

                 function updateHashValue() {
                     const activeLink = document.querySelector('.nav-tabs .nav-link.active');
                     if (activeLink) { tabInput.value = activeLink.getAttribute('href'); }
                 }

                 updateHashValue();
                 
                 const navLinks = document.querySelectorAll('.nav-tabs .nav-link');
                 navLinks.forEach(link => {
                     link.addEventListener('shown.bs.tab', updateHashValue); 
                 });
             });
         }
         setupTabPersistence();
         
         const geminiMonitorTab = document.querySelector('a[href="#tab-gemini-monitor"]');
         if (geminiMonitorTab) {
             geminiMonitorTab.addEventListener('shown.bs.tab', function (event) {
                 window.checkGeminiStatus(); 
             });
             const activeGeminiTab = document.querySelector('div#tab-gemini-monitor.active');
             if (activeGeminiTab) {
                 window.checkGeminiStatus();
             }
         }
         
         // 5. Finalisasi Event Binding CRUD (Panggil semua setup)
         function setupAllCrudListeners() {
             setupTemplateCrudListeners();
             setupUnitEditListeners();
             setupUnitDeleteListeners();
             setupCategoryToggle();
             setupDeleteConfirmation('.delete-category-btn', false);
             setupDeleteConfirmation('.delete-subcategory-btn', true);
             
             // Tambahkan setup untuk tombol Maintenance non-migrasi
             document.querySelectorAll('.run-maintenance-command').forEach(button => {
                 if (!button.hasAttribute('onclick')) {
                     button.removeEventListener('click', null); 
                     button.addEventListener('click', function() {
                         const command = this.getAttribute('data-command');
                         const title = this.getAttribute('data-title');
                         
                         Swal.fire({
                             title: `Jalankan ${title}?`,
                             html: `Anda akan menjalankan <strong>php artisan ${command}</strong>. Proses ini akan memblokir server sementara.`,
                             icon: 'warning',
                             showCancelButton: true,
                             confirmButtonColor: '#3085d6',
                             confirmButtonText: `Ya, Lanjutkan!`,
                             cancelButtonText: 'Batal',
                         }).then((result) => {
                             if (result.isConfirmed) {
                                 // Submit form Maintenance
                                 const maintenanceForm = document.getElementById('maintenance-command-form');
                                 document.getElementById('maintenance-command-input').value = command;
                                 if (window.appLoader) {
                                      window.appLoader.show(`Menjalankan ${command}... Mohon jangan tutup halaman.`);
                                 }
                                 maintenanceForm.submit();
                             }
                         });
                     });
                 }
             });
         }
    });
</script>
<script>
    /**
     * Fungsi Global untuk mengumpulkan data dari baris yang diklik (TUGAS)
     * TERMASUK LOGIKA ACTIVE DEPUTY (FIXED)
     */
    window.submitAssignment = function(categoryId) {
        const form = document.getElementById('global-assignment-form');
        const row = document.querySelector(`tr[data-category-id="${categoryId}"]`);
        const hiddenContainer = document.getElementById('hidden-unit-inputs-container');
        
        const deputyFilter = document.getElementById('deputy-filter');
        const activeDeputyIdValue = deputyFilter ? deputyFilter.value : ''; // FIX: Ambil nilai Deputi yang aktif

        if (!row || !form) { console.error("Elemen form atau baris kategori tidak ditemukan."); return; }

        hiddenContainer.innerHTML = '';
        document.getElementById('hidden-category-id').value = categoryId;

        // PENAMBAHAN: Input Hidden untuk menyimpan ID Deputi yang aktif
        const deputyInput = document.createElement('input');
        deputyInput.type = 'hidden';
        deputyInput.name = 'active_deputy_id';
        deputyInput.value = activeDeputyIdValue;
        hiddenContainer.appendChild(deputyInput);
        
        // ... (sisa kode ambil unit assignments) ...
        const togglesInRow = row.querySelectorAll('.visual-toggle');

        togglesInRow.forEach(toggle => {
            if (toggle.checked) {
                const unitId = toggle.getAttribute('data-unit-id');
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = `unit_assignments[${unitId}]`; 
                hiddenInput.value = unitId;
                hiddenContainer.appendChild(hiddenInput);
            }
        });

        form.submit();
    };
</script>
@endpush

@endsection