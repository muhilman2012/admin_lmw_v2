@extends('layouts.app')

@section('title', 'Detail Laporan')
@section('page_pretitle', 'Laporan & Pengaduan')
@section('page_title', 'Detail Laporan')

@section('content')
<div class="card">
    <div class="card-header sticky-top bg-white" style="top: 56px; z-index:10;">
        <div class="d-flex align-items-center justify-content-between w-100">
        <h2 class="page-title">Detail Data Pengaduan #{{ $report->ticket_number }}</h2>
            <div class="row">
                <div class="col">
                    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary me-1">
                        <i class="ti ti-chevron-left me-2"></i>Kembali ke Daftar Laporan
                    </a>

                    {{-- @php
                        $isAssignedToCurrentUser = true;
                        $isAnalysisSubmitted = false;
                    @endphp
                    @if ($isAssignedToCurrentUser && !$isAnalysisSubmitted)
                        <button class="btn btn-primary me-1" data-bs-toggle="modal" data-bs-target="#modal-kirim-analisis">
                            <i class="ti ti-check me-2"></i> Kirim Analisis
                        </button>
                    @endif --}}

                    <button class="btn btn-primary me-1" data-bs-toggle="modal" data-bs-target="#modal-kirim-analisis">
                        <i class="ti ti-check me-2"></i> Kirim Analisis
                    </button>

                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modal-teruskan-lapor">
                        <i class="ti ti-share me-2"></i>Teruskan
                    </button>

                    <a id="vw-edit-link" href="{{ route('reports.edit', $report->uuid) }}" class="btn btn-outline-primary me-1">
                        <i class="ti ti-edit me-2"></i>Edit Laporan
                    </a>

                    <button class="btn btn-warning me-1" data-bs-toggle="modal" data-bs-target="#modal-quick-action">
                        <i class="ti ti-pencil me-2"></i>Edit Tanggapan
                    </button>

                    <button class="btn btn-green" data-bs-toggle="modal" data-bs-target="#modal-persetujuan-analis">
                        <i class="icon ti ti-checks me-2"></i>Setujui/Perbaiki Analisis
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
        <div class="col-lg-8 pe-2">
            <div class="row">
            <div class="col-lg-6">
                <div class="card card-border mb-3">
                <div class="card-header"><strong>Data Pelapor</strong></div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="label me-4">Nama Lengkap:</div>
                        <div class="text-end" style="max-width: 70%">{{ $report->reporter->name ?? '-' }}</div>
                    </div>
                    </div>
                    <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="label me-4">NIK:</div>
                        <div class="text-end" style="max-width: 70%">{{ $report->reporter->nik ?? '-' }}</div>
                    </div>
                    </div>
                    <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="label me-4">Nomor KK:</div>
                        <div class="text-end" style="max-width: 70%">{{ $report->reporter->kk_number ?? '-' }}</div>
                    </div>
                    </div>
                    <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="label me-4">Nomor HP:</div>
                        <div class="text-end" style="max-width: 70%">{{ $report->reporter->phone_number ?? '-' }}</div>
                    </div>
                    </div>
                    <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="label me-4">Email:</div>
                        <div class="text-end" style="max-width: 70%">{{ $report->reporter->email ?? '-' }}</div>
                    </div>
                    </div>
                    <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="label me-4">Alamat Lengkap:</div>
                        <div class="text-end" style="max-width: 70%">{{ $report->reporter->address ?? '-' }}</div>
                    </div>
                    </div>
                </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card card-border mb-3">
                <div class="card-header"><strong>Detail Analis dan Disposisi</strong></div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item">
                        <div class="label me-4 mb-1">Distribusi:</div>
                        <div class="text-end" style="max-width: 100%; word-wrap: break-word;">
                            {{-- PENTING: Jika nama Deputi sangat panjang, ini akan wrap --}}
                            @if ($report->deputy)
                                <div class="badge bg-primary-lt w-100 mb-1 d-block text-wrap text-start">
                                    <strong class="text-uppercase small">Deputi:</strong> {{ $report->deputy->name }}
                                </div>
                            @endif
                            
                            {{-- Unit Kerja (Sub-level) --}}
                            @if ($report->unitKerja)
                                <div class="badge bg-secondary-lt w-100 d-block text-wrap text-start">
                                    <strong class="text-uppercase small">Unit:</strong> {{ $report->unitKerja->name }}
                                </div>
                            @endif

                            @if (!$report->deputy && !$report->unitKerja)
                                <span>-</span>
                            @endif
                        </div>
                    </div>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="label me-4">Disposisi dari:</div>
                            <div class="text-end" style="max-width: 70%">
                                {{ $currentAssignment->assignedBy->name ?? '-' }}
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="label me-4">Petugas Analis:</div>
                            <div class="text-end" style="max-width: 70%">
                                {{ $currentAssignment->assignedTo->name ?? 'Belum Ditugaskan' }}
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="label me-4">Status Analisis:</div>
                            <div class="text-end" style="max-width: 70%">
                                {{ $currentAssignment->status ?? '-' }}
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="label me-4">Catatan Disposisi:</div>
                            <div class="text-end" style="max-width: 70%">
                                {{ $currentAssignment->notes ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex flex-wrap gap-2 justify-content-between">
                    <a href="{{ route('reports.download.user', ['uuid' => $report->uuid]) }}" class="btn btn-primary" target="_blank">
                        <i class="ti ti-download me-2"></i>Tanda Terima Pengadu
                    </a>
                    <a href="{{ route('reports.download.government', ['uuid' => $report->uuid]) }}" class="btn btn-secondary" target="_blank">
                        <i class="ti ti-download me-2"></i>Tanda Terima K/L/D
                    </a>
                </div>
                </div>
            </div>
            </div>
            <div class="card card-border mb-3">
            <div class="card-header"><strong>Detail Laporan Lengkap </strong></div>
            <div class="p-3 text-start">{{ $report->details ?? '-' }}</div>
            </div>
            <div class="card card-border mb-3">
            <div class="card-header"><strong>Detail Aduan</strong></div>
            <div class="list-group list-group-flush">
                <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="label me-4">Status Laporan:</div>
                    <div class="text-end" style="max-width: 70%">{{ $report->status ?? '-' }}</div>
                </div>
                </div>
                <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="label me-4">Tanggapan:</div>
                    <div class="text-end" style="max-width: 70%">{{ $report->response ?? '-' }}</div>
                </div>
                </div>
                <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="label me-4">Analisis dari JF:</div>
                    <div class="text-end" style="max-width: 70%">{{ $report->jf_analysis ?? '-' }}</div>
                </div>
                </div>
            </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-border mb-3">
            <div class="card-header"><strong>Info Laporan</strong></div>
            <div class="list-group list-group-flush">
                <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="label me-4">Nomor Tiket:</div>
                    <div class="text-end fw-bolder" style="max-width: 70%">{{ $report->ticket_number }}</div>
                </div>
                </div>
                <div class="list-group-item">
                    <div class="label me-4 mb-2">Kategori Laporan:</div>
                    <div class="d-flex flex-column gap-1 mt-1"> 
                        {{-- 1. Kategori Utama --}}
                        @if ($report->parent_category_name)
                            {{-- Jika ada parent, tampilkan parent sebagai utama --}}
                            <span class="badge bg-purple-lt justify-content-start text-start d-block text-wrap w-100">
                                Utama: <strong class="ms-1">{{ $report->parent_category_name }}</strong>
                            </span>
                        @else
                            {{-- Jika tidak ada parent, kategori yang dipilih adalah kategori utama --}}
                            <span class="badge bg-purple-lt justify-content-start text-start d-block text-wrap w-100">
                                Utama: <strong class="ms-1">{{ $report->category->name ?? '-' }}</strong>
                            </span>
                        @endif
                        {{-- 2. Sub-Kategori (Hanya muncul jika ada parent) --}}
                        @if ($report->parent_category_name)
                            <span class="badge bg-blue-lt justify-content-start text-start d-block text-wrap w-100">
                                Sub: <strong class="ms-1">{{ $report->category->name ?? '-' }}</strong>
                            </span>
                        @endif
                    </div>
                </div>
                <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="label me-4">Judul Laporan:</div>
                    <div class="text-end" style="max-width: 70%">{{ $report->subject }}</div>
                </div>
                </div>
                <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="label me-4">Tanggal Kejadian:</div>
                    <div class="text-end" style="max-width: 70%">{{ $report->event_date?->format('d/m/Y') ?? '-' }}</div>
                </div>
                </div>
                <div class="list-group-item">
                <div class="card card-border mb-3">
                    <div class="card-header"><strong>Foto KTP</strong></div>
                    <div class="card-body text-center">
                        @if ($report->reporter->ktpDocument)
                            @php
                                $filePath = $report->reporter->ktpDocument->file_path;
                                $key = ltrim($filePath, '/');
                                $ktpUrl = signMinioUrlSmart(env('AWS_COMPLAINT_BUCKET'), $key, 10);
                            @endphp
                            <a href="{{ $ktpUrl }}" target="_blank">
                                <img src="{{ $ktpUrl }}" alt="KTP Pelapor" class="img-fluid" style="max-height: 400px;">
                            </a>
                            <div class="mt-2 text-muted">
                                Klik gambar untuk melihat dalam ukuran penuh.
                            </div>
                        @else
                            <div class="alert alert-info m-0">Tidak ada foto KTP yang dilampirkan.</div>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-column justify-content-between align-items-start">
                    <div class="label me-4 mb-3">Dokumen Pendukung:</div>
                    <div class="text-end" style="max-width: 70%">
                        @forelse($report->documents as $document)
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                <span>
                                    <i class="ti ti-file me-2"></i> {{ $document->file_name }}
                                    @if ($document->description)
                                        <span class="text-secondary small ms-2">{{ $document->description }}</span>
                                    @endif
                                </span>
                                @php
                                    $key = ltrim($document->file_path, '/');
                                    $url = signMinioUrlSmart(env('AWS_COMPLAINT_BUCKET'), $key, 10);
                                @endphp
                                <a class="btn btn-sm btn-outline-primary" href="{{ $url }}" target="_blank">
                                    <i class="ti ti-eye"></i> Lihat
                                </a>
                            </div>
                        @empty
                            <div>Tidak ada dokumen.</div>
                        @endforelse
                    </div>
                </div>
                </div>
            </div>
            </div>
            <div class="card border">
            <div class="card-header"><strong>Log Aduan</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 78vh; overflow-y: auto">
                <table class="table table-vcenter card-table table-sm mb-0">
                    <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Aktivitas</th>
                        <th>Oleh</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($report->activityLogs as $log)
                        <tr>
                        <td><span class="fs-5">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}</span></td>
                        <td><span class="fs-5">{{ $log->description }}</span></td>
                        <td><span class="fs-5">{{ $log->user?->name ?? '-' }}</span></td>
                        </tr>
                    @empty
                        <tr>
                        <td colspan="3" class="text-center text-muted">Tidak ada log.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
                </div>
            </div>
            </div>
        </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-quick-action" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('reports.update-response', $report->uuid) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Tanggapan Pengaduan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status Pengaduan</label>
                        <select name="status" class="form-select" required>
                            <option value="Proses verifikasi dan telaah" {{ $report->status == 'Proses verifikasi dan telaah' ? 'selected' : '' }}>Proses verifikasi dan telaah</option>
                            <option value="Menunggu kelengkapan data dukung dari Pelapor" {{ $report->status == 'Menunggu kelengkapan data dukung dari Pelapor' ? 'selected' : '' }}>Menunggu kelengkapan data dukung dari Pelapor</option>
                            <option value="Diteruskan kepada instansi yang berwenang untuk penanganan lebih lanjut" {{ $report->status == 'Diteruskan kepada instansi yang berwenang untuk penanganan lebih lanjut' ? 'selected' : '' }}>Diteruskan kepada instansi yang berwenang untuk penanganan lebih lanjut</option>
                            <option value="Penanganan Selesai" {{ $report->status == 'Penanganan Selesai' ? 'selected' : '' }}>Penanganan Selesai</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Klasifikasi Aduan</label>
                        <select name="classification" class="form-select" required>
                            <option value="" disabled {{ is_null($report->classification) ? 'selected' : '' }}>-- Belum diklasifikasikan --</option>
                            <option value="Pengaduan berkadar pengawasan" {{ $report->classification == 'Pengaduan berkadar pengawasan' ? 'selected' : '' }}>Pengaduan berkadar pengawasan</option>
                            <option value="Pengaduan tidak berkadar pengawasan" {{ $report->classification == 'Pengaduan tidak berkadar pengawasan' ? 'selected' : '' }}>Pengaduan tidak berkadar pengawasan</option>
                            <option value="Aspirasi" {{ $report->classification == 'Aspirasi' ? 'selected' : '' }}>Aspirasi</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggapan</label>
                        <textarea name="response" class="form-control" rows="7" required>{{ $report->response ?? '' }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="modal-kirim-analisis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form action="{{ route('reports.submit-analysis', $report->uuid) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kirim Hasil Analisis Laporan #{{ $report->ticket_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        {{-- KOLOM KIRI: PRATINJAU LAPORAN --}}
                        <div class="col-lg-5 border-end">
                            <h4 class="mb-3 text-primary">Detail Laporan</h4>
                            <div class="mb-3">
                                <strong>Judul:</strong> {{ $report->subject ?? '-' }}
                            </div>
                            <div class="card card-body bg-light">
                                <strong>Detail:</strong>
                                <p class="text-secondary mt-1">{{ $report->details ?? 'Tidak ada detail laporan.' }}</p>
                            </div>
                        </div>

                        {{-- KOLOM KANAN: FORM ANALISIS --}}
                        <div class="col-lg-7">
                            <h4 class="mb-3 text-success">Form Analisis</h4>

                            {{-- 1. Klasifikasi Aduan (Sama seperti Edit Tanggapan) --}}
                            <div class="mb-3">
                                <label class="form-label">Klasifikasi Aduan<span class="text-danger">*</span></label>
                                <select name="classification" class="form-select" required>
                                    <option value="" disabled selected>-- Pilih Klasifikasi --</option>
                                    <option value="Pengaduan berkadar pengawasan" {{ $report->classification == 'Pengaduan berkadar pengawasan' ? 'selected' : '' }}>Pengaduan berkadar pengawasan</option>
                                    <option value="Pengaduan tidak berkadar pengawasan" {{ $report->classification == 'Pengaduan tidak berkadar pengawasan' ? 'selected' : '' }}>Pengaduan tidak berkadar pengawasan</option>
                                    <option value="Aspirasi" {{ $report->classification == 'Aspirasi' ? 'selected' : '' }}>Aspirasi</option>
                                </select>
                            </div>

                            {{-- 2. Hasil Analisis (Worksheet) --}}
                            <div class="mb-3">
                                <label class="form-label">Hasil Analisis (Lembar Kerja Analis)<span class="text-danger">*</span></label>
                                {{-- Ambil nilai dari assignment jika sudah ada --}}
                                <textarea name="analyst_worksheet" class="form-control" rows="10" required>{{ $currentAssignment->analyst_worksheet ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Kirim Analisis</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="modal-persetujuan-analis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Persetujuan Analisis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="5" placeholder="Tambahkan catatan persetujuan atau perbaikan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="{{ route('reports.approve', $report->uuid) }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-green">Setujui</button>
                </form>
                <form action="{{ route('reports.approve', $report->uuid) }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="action" value="revise">
                    <button type="submit" class="btn btn-red">Perbaiki</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-teruskan-lapor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <form id="forward-form" action="{{ route('reports.forward', $report->uuid) }}" method="POST">
             @csrf
             <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Teruskan Laporan ke LAPOR!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Tujuan Instansi</label>
                    <select name="institution_id" id="select-institution" class="form-select" required>
                        <option value="" disabled selected>Pilih Instansi...</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}">{{ $institution->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan Tambahan (Opsional)</label>
                    <textarea name="additional_notes" class="form-control" rows="3" placeholder="Tambahkan catatan untuk instansi tujuan..."></textarea>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="exclude_reporter_data" id="exclude-reporter-data">
                    <label class="form-check-label" for="exclude-reporter-data">
                        Jangan sertakan data pelapor
                    </label>
                </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="forward-submit-btn" class="btn btn-info">
                        Teruskan Laporan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('script')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const forwardModal = document.getElementById('modal-teruskan-lapor');
        const forwardForm = document.getElementById('forward-form');
        
        // --- Fungsi Pure JS untuk Menyembunyikan Modal & Menghapus Backdrop ---
        const hideForwardModal = () => {
            if (!forwardModal) return;

            // 1. Sembunyikan modal secara visual (menghapus kelas Bootstrap)
            forwardModal.classList.remove('show');
            forwardModal.style.display = 'none';
            forwardModal.setAttribute('aria-hidden', 'true');
            
            // 2. Bersihkan body class dan overflow
            document.body.classList.remove('modal-open');
            document.body.style.overflow = ''; 

            // 3. Hapus elemen backdrop
            const modalBackdrop = document.querySelector('.modal-backdrop');
            if (modalBackdrop) {
                modalBackdrop.remove();
            }
        };

        // --- Fungsi untuk Menginisialisasi TomSelect ---
        const initializeTomSelect = () => {
            const selectElement = document.getElementById('select-institution');

            if (window.TomSelect && selectElement) {
                if (selectElement.tomselect) {
                    selectElement.tomselect.destroy();
                }

                new TomSelect("#select-institution", {
                    plugins: { dropdown_input: {} },
                    create: false,
                    allowEmptyOption: false,
                    sortField: { field: "text", direction: "asc" },
                });
            }
        };

        // --- Logika Loader dan Form Submit Utama ---
        if (forwardModal && forwardForm) {
            // 1. Inisialisasi TomSelect saat modal dibuka
            forwardModal.addEventListener('shown.bs.modal', function () {
                initializeTomSelect();
            });

            // 2. Handle Submit Form
            forwardForm.addEventListener('submit', function (e) {
                // a. PENTING: Mencegah aksi default form (redirect langsung)
                e.preventDefault();

                // b. Sembunyikan modal secara manual
                hideForwardModal();

                // c. Tampilkan Loader
                // Pastikan window.appLoader sudah dimuat dari loader-util.js
                window.appLoader.show('Meneruskan laporan ke Instansi tujuan melalui LAPOR!. Mohon tunggu...');
                
                // d. Kirim form secara paksa setelah jeda singkat (50ms)
                // Jeda ini memberi waktu browser untuk menampilkan overlay loading sebelum redirect.
                setTimeout(() => {
                    forwardForm.submit(); 
                }, 50); 
            });
        }
    });
</script>