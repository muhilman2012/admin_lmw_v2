@extends('layouts.app')

@section('title', 'Detail Laporan')

@section('content')
<div class="card">
    <div class="card-header sticky-top bg-white" style="top: 56px; z-index:10;">
        <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between w-100">
            <h2 class="page-title me-lg-auto mb-2 mb-lg-0">Detail Data Pengaduan #{{ $report->ticket_number }}</h2>
            <div class="d-flex flex-wrap flex-shrink-0 btn-list ms-lg-auto">
                <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-chevron-left me-2"></i>Kembali ke Daftar Laporan
                </a>

                @php
                    // Ambil user saat ini
                    $user = auth()->user();
                    $isSuperAdmin = $user->hasRole('superadmin');
                    
                    // Asumsi: $report memiliki accessor analysis_status dan assigned_to_user_id
                    $analysisStatus = $report->analysis_status; 
                    $assignedAnalystId = $report->assigned_to_user_id; 
                    
                    // Variabel Workflow State
                    $isAnalysisApproved = ($analysisStatus === 'approved');
                    $isAnalysisSubmitted = ($analysisStatus === 'submitted');
                    $isNotAnalyst = !$user->hasRole('analyst'); 
                @endphp

                {{-- 1. Tombol Kirim Analisis --}}
                @can('fill analysis worksheet')
                    @if ($isSuperAdmin || ($assignedAnalystId === $user->id && !$isAnalysisApproved))
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-kirim-analisis">
                            <i class="ti ti-check me-2"></i> Kirim Analisis
                        </button>
                    @endif
                @endcan

                {{-- 2. Tombol Setujui/Perbaiki Analisis --}}
                @can('approve analysis')
                    @if ($isSuperAdmin || $isAnalysisSubmitted)
                        <button class="btn btn-green" data-bs-toggle="modal" data-bs-target="#modal-persetujuan-analis">
                            <i class="icon ti ti-checks me-2"></i>Setujui/Perbaiki Analisis
                        </button>
                    @endif
                @endcan

                {{-- 3. Tombol Teruskan --}}
                @can('forward reports to lapor')
                    @if ($canForward) 
                        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modal-teruskan-lapor">
                            <i class="ti ti-share me-2"></i>Teruskan
                        </button>
                    @endif
                @endcan

                {{-- 4. Tombol Edit Laporan --}}
                @can('edit reports')
                    @if ($isSuperAdmin || $isNotAnalyst)
                        <a id="vw-edit-link" href="{{ route('reports.edit', $report->uuid) }}" class="btn btn-outline-primary">
                            <i class="ti ti-edit me-2"></i>Edit Laporan
                        </a>
                    @endif
                @endcan

                {{-- 5. Tombol Edit Tanggapan --}}
                @can('update report response')
                    @if ($isSuperAdmin || $isAnalysisApproved)
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modal-quick-action">
                            <i class="ti ti-pencil me-2"></i>Edit Tanggapan
                        </button>
                    @endif
                @endcan

                {{-- 6. Tombol Disposisikan ke Analis --}}
                @php
                    $hasAssignment = !is_null($currentAssignment);
                @endphp
                
                @can('assign reports')
                    @if ($user->hasAnyRole(['deputy', 'asdep_karo']) && !$hasAssignment)
                        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modal-disposisi-cepat">
                            <i class="ti ti-user-plus me-2"></i>Disposisikan ke Analis
                        </button>
                    @endif
                @endcan
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
                            <div class="text-end" style="max-width: 70%">
                                {{-- Tombol/link yang memicu fungsi Pure JS --}}
                                <a href="#" 
                                onclick="event.preventDefault(); showKKHistoryModal('{{ $report->reporter->kk_number ?? '' }}')" 
                                class="text-blue fw-bold"
                                title="Klik untuk melihat riwayat laporan KK ini"
                                >
                                    {{ $report->reporter->kk_number ?? '-' }}
                                </a>
                            </div>
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
                            
                            {{-- Pastikan currentAssignment dimuat dan data diekstrak di sini --}}
                            @php
                                $assignedBy = $currentAssignment->assignedBy ?? null;
                                $assignedTo = $currentAssignment->assignedTo ?? null;
                                $status = $currentAssignment->status ?? '-';
                                $notes = $currentAssignment->notes ?? '-';
                                $worksheet = $currentAssignment->analyst_worksheet ?? 'Belum ada tanggapan dari Analis';
                            @endphp
                            
                            {{-- Distribusi (Tetap Sama) --}}
                            <div class="list-group-item">
                                <div class="label me-4 mb-1">Distribusi:</div>
                                <div class="text-end" style="max-width: 100%; word-wrap: break-word;">
                                    @if ($report->deputy)
                                        <div class="badge bg-primary-lt w-100 mb-1 d-block text-wrap text-start">
                                            <strong class="text-uppercase small">Deputi:</strong> {{ $report->deputy->name }}
                                        </div>
                                    @endif
                                    
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
                            
                            {{-- 1. Disposisi dari (Pengirim) --}}
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="label me-4">Disposisi dari:</div>
                                    <div class="badge bg-warning-lt d-block text-wrap text-end" style="max-width: 70%">
                                        {{ $assignedBy->name ?? '-' }} 
                                    </div>
                                </div>
                            </div>
                            
                            {{-- 2. Petugas Analis (Penerima) --}}
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="label me-4">Petugas Analis:</div>
                                    <div class="badge bg-warning-lt d-block text-wrap text-end" style="max-width: 70%">
                                        {{ $assignedTo->name ?? 'Belum Ditugaskan' }}
                                    </div>
                                </div>
                            </div>
                            
                            {{-- 3. Status Analisis --}}
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="label me-4">Status Analisis:</div>
                                    
                                    @php
                                        // Menentukan warna badge berdasarkan status
                                        $badgeColor = 'bg-warning-lt'; // Default (misalnya, Menunggu Persetujuan)
                                        if ($status === 'approved') {
                                            $badgeColor = 'bg-success-lt'; // Hijau untuk Disetujui
                                        } elseif ($status === 'Perlu Perbaikan') {
                                            $badgeColor = 'bg-danger-lt'; // Merah untuk Perlu Perbaikan
                                        }
                                    @endphp

                                    <div class="badge {{ $badgeColor }} d-block text-wrap text-end" style="max-width: 70%">
                                        {{ $status }} 
                                    </div>
                                </div>
                            </div>

                            {{-- 4. Catatan Disposisi --}}
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="label me-4">Catatan Disposisi:</div>
                                    <div class="badge bg-secondary-lt d-block text-wrap text-end" style="max-width: 70%">
                                        {{ $notes }} 
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card card-border mb-3">
                <div class="card-header"><strong>Detail Laporan Lengkap </strong></div>
                <div class="p-3 text-start">{{ $report->details ?? '-' }}</div>
                </div>
                <div class="card card-border mb-3">
                <div class="card-header"><strong>Detail Tanggapan Aduan</strong></div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="label me-4">Klasifikasi Laporan:</div>
                        <div class="text-end fw-bold" style="max-width: 70%">{{ $report->classification ?? '-' }}</div>
                    </div>
                    </div>
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
                            <div class="badge bg-warning-lt d-block text-wrap text-end" style="max-width: 70%">
                                {{ $worksheet }} 
                            </div>
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
                <div class="d-flex justify-content-between align-items-start">
                    <div class="label me-4">Waktu Pengaduan:</div>
                    <div class="text-end" style="max-width: 70%">{{ $report->created_at?->format('d/m/Y H:i') ?? '-' }}</div>
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
            <div class="card-footer d-flex flex-column flex-md-row gap-2 justify-content-between align-items-center">
                <a href="{{ route('reports.download.user', ['uuid' => $report->uuid]) }}" class="btn btn-primary w-100 w-md-auto" target="_blank">
                    <i class="ti ti-download me-2"></i>Tanda Terima Pengadu
                </a>
                <a href="{{ route('reports.download.government', ['uuid' => $report->uuid]) }}" class="btn btn-secondary w-100 w-md-auto" target="_blank">
                    <i class="ti ti-download me-2"></i>Tanda Terima K/L/D
                </a>
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

<div class="modal fade" id="modal-kk-history" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Riwayat Laporan KK</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="hideKKHistoryModal()"></button>
            </div>
            <div class="modal-body" id="kk-history-content">
                {{-- Konten AJAX akan dimuat di sini --}}
                <div class="text-center p-5 text-muted">Memuat...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hideKKHistoryModal()">Tutup</button>
            </div>
        </div>
    </div>
</div>
{{-- MODAL DISPOSISI --}}
<div class="modal fade" id="modal-disposisi-cepat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <form action="{{ route('reports.assign-quick', $report->uuid) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Disposisikan Laporan #{{ $report->ticket_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Tugaskan Kepada Analis:</label>
                        {{-- Dropdown Analis yang Difilter dan Dikelompokkan --}}
                        <select name="analyst_id" id="select-analyst-cepat" class="form-select" required>
                            <option value="" selected disabled>-- Pilih Analis --</option>
                            @foreach ($availableAnalysts as $deputyName => $unitGroups)
                                <optgroup label="{{ $deputyName }}">
                                    @foreach ($unitGroups as $unitName => $analysts)
                                        <optgroup label="&nbsp;&nbsp;&nbsp;&nbsp;{{ $unitName }}">
                                            @foreach ($analysts as $analyst)
                                                <option value="{{ $analyst->id }}">
                                                    {{ $analyst->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Catatan Disposisi (Opsional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Instruksi singkat untuk Analis..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-2"></i> Tugaskan Laporan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="modal-quick-action" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="quick-action-form" action="{{ route('reports.update-response', $report->uuid) }}" method="POST">
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
                    
                    <div class="mb-4">
                        <label class="form-check form-switch">
                            <input 
                                type="checkbox" 
                                class="form-check-input" 
                                name="is_benefit_provided" 
                                value="1"
                                {{ $report->is_benefit_provided ? 'checked' : '' }}
                            >
                            <span class="form-check-label fw-bold text-success">
                                Pengadu telah mendapatkan Bantuan/Manfaat
                            </span>
                        </label>
                        <small class="form-hint">Centang ini jika tindak lanjut laporan telah memberikan manfaat atau solusi nyata kepada pelapor.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggapan</label>
                        <textarea name="response" class="form-control" rows="7" required>{{ $report->response ?? '' }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="showQuickActionConfirmation()">Simpan Perubahan</button> 
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="modal-konfirmasi-simpan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-warning"><i class="ti ti-alert-triangle me-2"></i>Konfirmasi Perubahan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="hideConfirmationModal()"></button>
            </div>
            <div class="modal-body text-start">
                <p>Anda akan <strong>menyimpan perubahan</strong> berikut:</p>
                <ul class="list-unstyled p-0 mb-3">
                    <li class="mb-2 border-bottom pb-2">
                        <small class="text-muted d-block">Status Laporan:</small>
                        <span class="d-block">Dari: <strong class="text-secondary">{{ $report->status ?? 'N/A' }}</strong></span>
                        <span class="d-block">Ke: <strong id="konfirmasi-status-baru" class="text-primary">Memuat...</strong></span>
                    </li>
                    <li class="mb-2">
                        <small class="text-block text-muted d-block">Tanggapan:</small>
                        <span class="d-block">Dari: <strong class="text-secondary">{{ Str::limit($report->response, 100) ?? 'Kosong' }}</strong></span>
                        <span class="d-block">Ke: <strong id="konfirmasi-tanggapan-baru" class="text-primary">Memuat...</strong></span>
                    </li>
                </ul>
                <p class="fw-bold text-center">Pastikan semua perubahan sudah sesuai.</p>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="hideConfirmationModal()">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitQuickActionForm()">
                    Ya, Simpan Perubahan
                </button>
            </div>
        </div>
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
            <form id="approval-form" action="{{ route('reports.approve', ['uuid' => $report->uuid]) }}" method="POST">
                @csrf
                <input type="hidden" name="action" id="approval-action-input" value="">
                <div class="modal-body">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Hasil Analisis dari Analis</label>
                        <textarea 
                            class="form-control bg-light" 
                            rows="7" 
                            readonly 
                            placeholder="Tidak ada hasil analisis yang diserahkan..."
                        >{{ $worksheet }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" id="approval-notes" class="form-control" rows="5" placeholder="Tambahkan catatan persetujuan atau perbaikan..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-green" onclick="submitApprovalForm('approve')">
                        Setujui
                    </button>
                    <button type="button" class="btn btn-red" onclick="submitApprovalForm('revise')">
                        Perbaiki
                    </button>
                </div>
            </form>
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
    function submitApprovalForm(action) {
        document.getElementById('approval-action-input').value = action;
        document.getElementById('approval-form').submit();
    }
</script>
<script>
    function showModalPure(modalElement) {
        if (!modalElement) return;
        
        // 1. Tampilkan modal
        modalElement.style.display = 'block';
        modalElement.setAttribute('aria-modal', 'true');
        modalElement.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');

        // 2. Tambahkan backdrop jika belum ada
        if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.classList.add('modal-backdrop', 'fade', 'show');
            document.body.appendChild(backdrop);
            // Tambahkan listener untuk menutup modal saat klik backdrop (hanya jika diperlukan)
            // backdrop.onclick = () => hideModalPure(modalElement);
        }

        // 3. Tambahkan kelas 'show' setelah delay agar transisi berfungsi
        setTimeout(() => {
            modalElement.classList.add('show');
        }, 10);
    }

    function hideModalPure(modalElement) {
        if (!modalElement) return;

        // 1. Hapus kelas 'show'
        modalElement.classList.remove('show');
        
        // 2. Hapus backdrop
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
             backdrop.remove();
        }

        // 3. Sembunyikan modal sepenuhnya setelah transisi
        setTimeout(() => {
            modalElement.style.display = 'none';
            modalElement.setAttribute('aria-hidden', 'true');
            modalElement.removeAttribute('aria-modal');
            document.body.classList.remove('modal-open');
        }, 150); // Sesuaikan dengan durasi transisi CSS Anda
    }
    
    // Fungsi untuk menutup modal saat tombol close diklik
    document.addEventListener('click', function(e) {
        const closeBtn = e.target.closest('[data-bs-dismiss="modal"]');
        if (closeBtn) {
            const modal = closeBtn.closest('.modal');
            if (modal) {
                hideModalPure(modal);
                // Tambahan: Pastikan modal pertama (quick-action) dibuka lagi jika modal konfirmasi di-batal
                if (modal.id === 'modal-konfirmasi-simpan' && e.target.innerText.toLowerCase().includes('batal')) {
                     showModalPure(document.getElementById('modal-quick-action'));
                }
            }
        }
    });

    // --- LOGIKA KONFIRMASI EDIT TANGGAPAN ---
    // Fungsi untuk menampilkan modal konfirmasi
    function showQuickActionConfirmation() {
        const quickActionModalElement = document.getElementById('modal-quick-action');
        const confirmationModalElement = document.getElementById('modal-konfirmasi-simpan');

        // 1. Ambil Nilai Baru dari Form Edit Tanggapan
        const newStatus = quickActionModalElement.querySelector('select[name="status"]').value;
        const newResponse = quickActionModalElement.querySelector('textarea[name="response"]').value;

        // 2. Update konten modal konfirmasi
        document.getElementById('konfirmasi-status-baru').textContent = newStatus;
        
        // TANGGAPAN : Gunakan Str.limit (dari Blade) di JS
        // Karena JS tidak memiliki helper Str::limit, kita tampilkan 50 karakter pertama
        const responseText = newResponse.substring(0, 100) + (newResponse.length > 100 ? '...' : '');
        document.getElementById('konfirmasi-tanggapan-baru').textContent = responseText;

        // 3. Sembunyikan modal Edit Tanggapan (hideModalPure harus didefinisikan di suatu tempat)
        // Asumsi hideModalPure(quickActionModalElement) dan showModalPure(confirmationModalElement) tersedia
        hideModalPure(quickActionModalElement);
        
        // 4. Tampilkan modal konfirmasi setelah jeda singkat
        setTimeout(() => {
            showModalPure(confirmationModalElement);
        }, 150);
    }

    // Fungsi untuk menyembunyikan modal konfirmasi dan kembali ke modal aksi cepat (jika batal)
    function hideConfirmationModal() {
        const confirmationModalElement = document.getElementById('modal-konfirmasi-simpan');
        hideModalPure(confirmationModalElement);
        
        // Jika dibatalkan, buka kembali modal aksi cepat
        const quickActionModalElement = document.getElementById('modal-quick-action');
        setTimeout(() => {
             showModalPure(quickActionModalElement);
        }, 150);
    }

    // Fungsi untuk melanjutkan submit form setelah konfirmasi
    function submitQuickActionForm() {
        const confirmationModalElement = document.getElementById('modal-konfirmasi-simpan');
        
        // 1. Sembunyikan modal konfirmasi
        hideModalPure(confirmationModalElement);
        
        // 2. Lanjutkan submit form Edit Tanggapan (Quick Action Form)
        // Kita tidak perlu delay di sini, karena browser akan segera pindah halaman.
        document.getElementById('quick-action-form').submit();
    }

    let kkHistoryModalElement;
    let kkHistoryContent;

    window.showKKHistoryModal = function (kkNumber) {
        if (!kkHistoryModalElement) {
            console.error('Modal element not initialized.');
            return;
        }

        if (!kkNumber || kkNumber === '-') {
            console.warn('Nomor KK tidak tersedia.');
            return;
        }

        // 1. Tampilkan Backdrop
        let backdrop = document.querySelector('.modal-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.classList.add('modal-backdrop', 'fade', 'show'); 
            document.body.appendChild(backdrop);
            backdrop.onclick = window.hideKKHistoryModal; // Tutup saat klik backdrop
        }

        // 2. Tampilkan Modal
        kkHistoryModalElement.style.display = 'block';
        kkHistoryModalElement.removeAttribute('aria-hidden');
        kkHistoryModalElement.setAttribute('aria-modal', 'true');
        document.body.classList.add('modal-open');
        
        setTimeout(() => {
            kkHistoryModalElement.classList.add('show');
        }, 1);

        // 3. Panggil AJAX untuk memuat konten
        loadKKHistoryContent(kkNumber);
    };

    // --- Fungsi Pure JS untuk Menyembunyikan Modal ---
    window.hideKKHistoryModal = function () {
        if (kkHistoryModalElement) {
            kkHistoryModalElement.classList.remove('show');
            kkHistoryModalElement.setAttribute('aria-hidden', 'true');
            
            setTimeout(() => {
                kkHistoryModalElement.style.display = 'none';
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            }, 150); // Sesuaikan dengan durasi transisi CSS
        }
    };

    // --- Fungsi AJAX untuk Memuat Konten ---
    function loadKKHistoryContent(kkNumber) {
        if (!kkHistoryContent) {
            console.error('Modal content element not initialized.');
            return;
        }
        
        // Tampilkan loading spinner
        kkHistoryContent.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Memuat riwayat laporan...</p></div>';

        // Panggil endpoint AJAX
        fetch('{{ route('reports.by.kk') }}?kk_number=' + kkNumber)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Gagal memuat data riwayat.');
                }
                return response.json();
            })
            .then(data => {
                // Masukkan konten HTML yang dirender dari controller
                kkHistoryContent.innerHTML = data.html;
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                kkHistoryContent.innerHTML = `<div class="alert alert-danger">Gagal memuat riwayat: ${error.message}</div>`;
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Inisialisasi variabel di sini setelah DOM siap
        kkHistoryModalElement = document.getElementById('modal-kk-history');
        kkHistoryContent = document.getElementById('kk-history-content');
        
        // Cek jika elemen tidak ditemukan, log error
        if (!kkHistoryModalElement) {
            console.error("Error: Element with ID 'modal-kk-history' not found.");
            return; // Hentikan eksekusi jika modal tidak ditemukan
        }
        if (!kkHistoryContent) {
            console.error("Error: Element with ID 'kk-history-content' not found.");
            // Lanjutkan jika hanya konten yang hilang, tetapi log error
        }

        // Daftarkan listener untuk tombol close bawaan modal
        const closeButton = kkHistoryModalElement.querySelector('.btn-close');
        if (closeButton) {
            closeButton.onclick = window.hideKKHistoryModal;
        }
    });
</script>
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