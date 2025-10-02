@extends('layouts.app')

@section('title', 'Tindak Lanjut Laporan')
@section('page_pretitle', 'Laporan Diteruskan')
@section('page_title', 'Tindak Lanjut')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-header sticky-top bg-white" style="top: 56px">
        <div class="d-flex align-items-center justify-content-between w-100">
            <h2 class="page-title">Tindak Lanjut untuk Tiket #{{ $report->ticket_number }}</h2>
            <a href="{{ route('forwarding.index') }}" class="btn btn-outline-secondary me-1">
                <i class="ti ti-chevron-left me-2"></i>Kembali ke Daftar Laporan Diteruskan
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- KOLOM KIRI: KONTEKS LMW & DATA DASAR --}}
            <div class="col-lg-5 border-end pe-4">
                <h4 class="text-primary mb-3">Informasi Laporan LMW</h4>
                
                {{-- 1. Informasi Pelapor & Sumber --}}
                <h2 class="fw-bold mb-1">{{ $report->reporter->name ?? 'Anonim' }}</h2>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-secondary-lt text-secondary">
                        <i class="ti ti-rotate-2 me-1"></i> Sumber: {{ Str::title($report->source ?? '-') }}
                    </span>
                    <span class="badge bg-purple-lt text-purple">
                        <i class="ti ti-ticket me-1"></i> No. Tiket LMW: {{ $report->ticket_number }}
                    </span>
                </div>

                {{-- 2. Tracking ID LAPOR! --}}
                <div class="mb-3">
                    <h5 class="fw-bold mb-1">Tracking ID LAPOR!:</h5>
                    <span class="text-secondary fs-4 fw-bold">{{ $report->lapor_complaint_id ?? 'Belum Terkirim' }}</span>
                </div>

                {{-- 3. Dokumen Pengaduan (dari LMW) --}}
                <div class="mb-4">
                    <h5 class="fw-bold mb-2">Dokumen Pengaduan (LMW):</h5>
                    <div class="d-flex flex-column gap-2">
                        @forelse($report->documents as $document)
                            {{-- Asumsi Anda memiliki helper untuk mendapatkan URL file yang aman --}}
                            @php
                                $fileUrl = asset('storage/' . $document->file_path); // Sesuaikan dengan helper Minio Anda
                            @endphp
                            <a class="btn btn-outline-primary btn-sm justify-content-start" 
                                target="_blank" href="{{ $fileUrl }}">
                                <i class="ti ti-file-text me-1"></i> 
                                {{ $document->description ?? Str::limit(basename($document->file_path), 30) }}
                            </a>
                        @empty
                            <div class="text-secondary small">Tidak ada dokumen dilampirkan.</div>
                        @endforelse
                    </div>
                </div>

                {{-- 4. Tombol Detail Laporan LMW --}}
                <a href="{{ route('reports.show', $report->uuid) }}" class="btn btn-outline-info w-100">
                    <i class="ti ti-eye me-1"></i> Lihat Detail Laporan LMW
                </a>
            </div>

            {{-- KOLOM KANAN: RIWAYAT TINDAK LANJUT LAPOR! --}}
            <div class="col-lg-7">
                <h4 class="text-success mb-3">Status dan Disposisi LAPOR!</h4>
                
                {{-- Status Aktif & Disposisi Terakhir --}}
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="badge bg-purple-lt text-purple">Status: {{ $laporData['status_name'] ?? 'N/A' }}</span>
                    <span class="badge bg-blue-lt text-blue">Disposisi: {{ $laporData['disposition_name'] ?? 'N/A' }}</span>
                </div>

                <!-- ===== Riwayat Tindak Lanjut (Log) ===== -->
                <section id="riwayat-tindak-lanjut" class="mt-4">
                    <div class="d-flex align-items-center mb-2">
                        <h5 class="me-auto mb-0">Riwayat Pergerakan Laporan</h5>
                    </div>

                    @forelse ($logActivities as $log)
                    <div class="card mb-2">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="me-auto">
                                    <div class="fw-bold mb-1">
                                        {{ $log['institution_from']['name'] ?? 'Sistem' }} 
                                        <span class="text-secondary">→</span> 
                                        {{ $log['institution_to']['name'] ?? 'Sistem' }}
                                    </div>
                                    <div class="text-secondary">
                                        {{ $log['content'] ?? 'Pembaruan status' }}
                                    </div>
                                    @if (!empty($log['status_new']))
                                    <div class="mt-1 small">
                                        Status berubah: 
                                        <span class="badge" style="background-color: {{ $log['status_old']['color'] ?? '#666' }}; color: white;">
                                            {{ $log['status_old']['name'] ?? 'Awal' }}
                                        </span> 
                                        → 
                                        <span class="badge" style="background-color: {{ $log['status_new']['color'] ?? 'green' }}; color: white;">
                                            {{ $log['status_new']['name'] ?? 'Terbaru' }}
                                        </span>
                                    </div>
                                    @endif
                                </div>
                                <div class="text-secondary small ms-3">{{ $log['date'] }}</div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="alert alert-info">Tidak ada riwayat tindak lanjut dari LAPOR! yang tersedia.</div>
                    @endforelse
                </section>
                <!-- Form Kirim Tanggapan Balasan -->
                <div class="card card-sm mt-4 mb-4 border border-info">
                    <div class="card-body">
                        <form action="{{ route('forwarding.reply', $complaintId) }}" method="PUT">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label text-info">Kirim Tanggapan Balasan ke LAPOR!</label>
                                <textarea name="admin_content" class="form-control" rows="3" required placeholder="Tulis tanggapan Anda untuk instansi tujuan atau pelapor melalui LAPOR! (Perlu konfirmasi isi string admin)..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-info btn-sm">Kirim Tanggapan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection