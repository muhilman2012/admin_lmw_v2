@extends('layouts.app')

@section('title', 'Detail Laporan')
@section('page_pretitle', 'Laporan & Pengaduan')
@section('page_title', 'Detail Laporan')

@section('content')
<div class="card">
    <div class="card-header sticky-top bg-white" style="top: 56px">
        <div class="d-flex align-items-center justify-content-between w-100">
        <h2 class="page-title">Detail Data Laporan/Aduan</h2>
        <div class="row">
            <div class="col">
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary me-1">
                <i class="ti ti-chevron-left me-2"></i>Kembali ke Daftar Laporan
            </a>
            <a id="vw-edit-link" href="#" class="btn btn-outline-primary">
                <i class="ti ti-edit me-2"></i>Edit Laporan
            </a>
            <button class="btn btn-green" data-bs-toggle="modal" data-bs-target="#modal-persetujuan-analis">
                <i class="icon ti ti-pencil me-2"></i>Setujui/Perbaiki Analis
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
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="label me-4">Disposisi dari:</div>
                        <div class="text-end" style="max-width: 70%">{{ $report->distribution ?? '-' }}</div>
                    </div>
                    </div>
                    <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="label me-4">Petugas Analis:</div>
                        <div class="text-end" style="max-width: 70%">{{ $report->analyst_user?->name ?? '-' }}</div>
                    </div>
                    </div>
                    <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="label me-4">Status Analisis:</div>
                        <div class="text-end" style="max-width: 70%">{{ $report->analysis_status ?? '-' }}</div>
                    </div>
                    </div>
                    <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="label me-4">Catatan Disposisi:</div>
                        <div class="text-end" style="max-width: 70%">{{ $report->disposition_notes ?? '-' }}</div>
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
                <div class="d-flex justify-content-between align-items-start">
                    <div class="label me-4">Kategori:</div>
                    <div class="text-end badge bg-purple-lt" style="max-width: 70%">{{ $report->category?->name ?? '-' }}</div>
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
                    @forelse($reportLogs as $log)
                        <tr>
                        <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ $log->description }}</td>
                        <td>{{ $log->user?->name ?? '-' }}</td>
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
@endsection