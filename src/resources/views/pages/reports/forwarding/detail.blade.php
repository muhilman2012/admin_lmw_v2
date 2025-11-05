@extends('layouts.app')

@section('title', 'Tindak Lanjut Laporan')
@section('page_pretitle', 'Laporan Diteruskan')
@section('page_title', 'Tindak Lanjut')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-header sticky-top bg-white" style="top: 56px; z-index:10;">
        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between w-100">
            <h2 class="page-title me-md-4 mb-2 mb-md-0">Tindak Lanjut untuk Tiket #{{ $report->ticket_number }}</h2>
            <a href="{{ route('forwarding.index') }}" class="btn btn-outline-secondary">
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

                <div class="card card-sm mb-3 border-start border-3 border-danger-subtle">
                    <div class="card-body py-2">
                        <div class="text-muted small">Judul Pengaduan</div>
                        <p class="fw-bold mb-0 text-dark">{{ $report->subject ?? 'Tanpa Subjek' }}</p>
                    </div>
                </div>
                
                <div class="card card-sm mb-4 border-start border-3 border-secondary-subtle">
                    <div class="card-body py-2">
                        <div class="text-muted small">Isi Pengaduan</div>
                        <p class="text-justify mb-0 small text-secondary" style="max-height: 150px; overflow-y: auto;">
                            {{ $report->details ?? 'Tidak ada detail laporan.' }}
                        </p>
                    </div>
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
                    @forelse ($renderedActivities as $log) 
                    <div class="card mb-2 border-start border-4 border-info">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="me-auto">
                                    
                                    @php
                                        $institutionFromName = $log['institution_from_name'] ?? 'Sistem';
                                        $institutionToName = $log['institution_to_name'] ?? 'Sistem';
                                        $attachments = $log['attachments'] ?? [];
                                    @endphp

                                    {{-- 1. HEADER: INSTITUTION FROM -> TO --}}
                                    <div class="fw-bold mb-1">
                                        {{ $institutionFromName }} 
                                        <span class="text-secondary">→</span> 
                                        {{ $institutionToName }}
                                    </div>
                                    
                                    {{-- 2. CONTENT / DESKRIPSI UTAMA (Menggunakan data yang sudah dirender) --}}
                                    <div class="text-secondary small mt-1">
                                        {!! $log['rendered_content'] !!} 
                                    </div>

                                    {{-- DISPLAY ATTACHMENTS --}}
                                    @if (!empty($attachments))
                                    <div class="mt-3 border-top pt-2">
                                        <span class="fw-bold small text-dark d-block mb-1">Lampiran:</span>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($attachments as $attachment)
                                                @php
                                                    // Gunakan path/url yang sudah disediakan API atau buat link download jika API menyediakan ID
                                                    // Dalam JSON Anda, 'path' berisi URL yang sudah di-sign.
                                                    $fileUrl = $attachment['path'] ?? '#'; 
                                                    $fileName = $attachment['file_name'] ?? 'Dokumen';
                                                    $fileExtension = $attachment['extension'] ?? 'file';
                                                    
                                                    // Tentukan ikon berdasarkan ekstensi
                                                    $icon = 'ti-file';
                                                    if (in_array($fileExtension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png'])) {
                                                        $icon = "ti-file-{$fileExtension}"; // Jika menggunakan Tabler Icons yang spesifik
                                                        if ($fileExtension === 'pdf') $icon = 'ti-file-type-pdf';
                                                        elseif (in_array($fileExtension, ['doc', 'docx'])) $icon = 'ti-file-type-word';
                                                        elseif (in_array($fileExtension, ['xls', 'xlsx'])) $icon = 'ti-file-type-excel';
                                                        else $icon = 'ti-file';
                                                    }
                                                @endphp
                                                @if ($fileUrl && $fileUrl !== '#')
                                                    {{-- HANYA RENDER TOMBOL JIKA ADA URL DOWNLOAD VALID --}}
                                                    <a href="{{ $fileUrl }}" 
                                                    target="_blank" 
                                                    class="badge bg-light text-dark d-flex align-items-center gap-1 border border-secondary-subtle text-decoration-none" 
                                                    title="{{ $fileName }}">
                                                        <i class="ti {{ $icon }} ti-xs me-1"></i>
                                                        <span class="text-truncate" style="max-width: 150px;">{{ $fileName }}</span>
                                                        <i class="ti ti-download ti-xs ms-1"></i>
                                                    </a>
                                                    @else
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                    
                                    {{-- 3. LOGIC STATUS CHANGE --}}
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
                                {{-- TANGGAL LOG --}}
                                <div class="text-secondary small ms-3 text-nowrap" title="Waktu Log">{{ $log['created_at'] }}</div>
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
                        <h5 class="text-info">Kirim Tindak Lanjut/Balasan ke LAPOR!</h5>
                        
                        {{-- Form akan POST ke route forwarding.followup.add --}}
                        <form id="reply-form" action="{{ route('forwarding.followup.add', $complaintId) }}" method="POST"enctype="multipart/form-data">
                            @csrf
                            
                            {{-- Tampilkan Validation Errors (jika ada redirect back dengan errors) --}}
                            @if ($errors->any())
                                <div class="alert alert-danger small">
                                    Terdapat kesalahan saat mengirim balasan. Silakan cek input Anda.
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- 1. PILIH TEMPLATE (Diisi via AJAX/JS) --}}
                            {{-- <div class="mb-3">
                                <label class="form-label">Pilih Template Balasan (Opsional)</label>
                                <select id="template-select" name="template_code" class="form-select">
                                    <option value="">-- Pilih Template Cepat --</option>
                                </select>
                                <small class="form-hint" id="template-hint"></small>
                            </div> --}}

                            {{-- 2. KONTEN BALASAN --}}
                            <div class="mb-3">
                                <label class="form-label">Konten Balasan/Tindak Lanjut</label>
                                <textarea id="reply-content" name="content" class="form-control" rows="3" required placeholder="Tulis konten tindak lanjut di sini."></textarea>
                            </div>

                            {{-- 3. ATTACHMENT --}}
                            <div class="mb-3">
                                <label class="form-label">Lampiran Dokumen (Word atau PDF)</label>
                                {{-- Gunakan 'attachment[]' agar bisa menerima multiple files, jika dibutuhkan --}}
                                <input 
                                    type="file" 
                                    name="attachment[]" 
                                    class="form-control" 
                                    multiple 
                                    accept=".doc,.docx,.pdf"
                                />
                                <small class="form-hint">Maksimal 5 file. Hanya menerima format .doc, .docx, atau .pdf.</small>
                            </div>

                            {{-- INPUT TERSEMBUNYI WAJIB API --}}
                            <input type="hidden" name="complaint_id" value="{{ $complaintId }}">
                            <input type="hidden" name="user_id" value="{{ auth()->user()->lapor_user_id ?? 13 }}">
                            <input type="hidden" name="institution_from_id" value="151345">
                            <input type="hidden" name="institution_to_id" value="151345">
                            <input type="hidden" name="rating" value="0">
                            <input type="hidden" name="template_code" id="hidden-template-code">

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is-secret-toggle" name="is_secret" value="1">
                                    <label class="form-check-label" for="is-secret-toggle">Tandai Balasan sebagai Rahasia (Tidak ditampilkan publik)</label>
                                </div>
                            </div>
                            
                            <div class="form-actions mt-3">
                                <button type="submit" class="btn btn-info btn-sm">
                                    <i class="ti ti-send me-1"></i> Kirim Tanggapan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        
        // --- ELEMENT INLINE FORM REPLY ---
        const templateSelect = document.getElementById('template-select');
        const replyContent = document.getElementById('reply-content');
        const hiddenTemplateCode = document.getElementById('hidden-template-code');
        const templateHint = document.getElementById('template-hint');
        const replyForm = document.getElementById('reply-form');
        
        // Elemen Loader (asumsi ada di layouts/partials/page-loader.blade.php)
        const pageLoader = document.getElementById('page-loader-overlay'); 
        const loaderText = document.getElementById('loader-text'); 
        
        // --- A. LOGIKA MEMUAT TEMPLATE VIA AJAX ---
        const loadTemplates = async () => {
            if (!templateSelect) return;
            templateSelect.innerHTML = '<option value="">-- Memuat Template... --</option>';
            
            try {
                // Menggunakan route helper yang aman
                const response = await fetch('{{ route('forwarding.templates') }}');
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('API Template Gagal. Status:', response.status, 'Response:', errorText);
                    throw new Error('Gagal koneksi atau server error.');
                }
                
                const data = await response.json();
                
                let options = '<option value="">-- Pilih Template Cepat --</option>';
                
                if (data.results && data.results.data) {
                    data.results.data.forEach(tpl => {
                        // Menggunakan JSON.stringify untuk menyimpan konten kompleks
                        options += `<option value="${tpl.template_code}" data-content="${JSON.stringify(tpl.content)}">${tpl.title} (${tpl.type})</option>`;
                    });
                }
                templateSelect.innerHTML = options;
            } catch (e) {
                templateSelect.innerHTML = `<option value="">Gagal memuat template (${e.message})</option>`;
                console.error("Fatal Error load template:", e);
            }
        };

        loadTemplates();

        // 2. Event listener saat template dipilih
        if (templateSelect) {
            templateSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const templateCode = selectedOption.value;
                const templateContentJSON = selectedOption.getAttribute('data-content');
                
                if (templateCode && templateContentJSON) {
                    try {
                        const content = JSON.parse(templateContentJSON);
                        replyContent.value = content;
                        hiddenTemplateCode.value = templateCode;
                        templateHint.textContent = `Template ${selectedOption.textContent} telah dimuat.`;
                    } catch (e) {
                        console.error("Gagal parse template content:", e);
                        replyContent.value = "Error: Gagal memuat konten template.";
                    }
                } else {
                    replyContent.value = '';
                    hiddenTemplateCode.value = '';
                    templateHint.textContent = '';
                }
            });
        }
        
        // ⭐ BARU: LOGIKA PAGE LOADER SAAT SUBMIT FORM INLINE ⭐
        if (replyForm && pageLoader) {
            replyForm.addEventListener('submit', function(event) {
                // Tampilkan loader saat form disubmit
                pageLoader.classList.remove('d-none');
                if (loaderText) {
                    loaderText.textContent = "Mengirim tindak lanjut ke LAPOR! API (Mohon tunggu)...";
                }
                // Biarkan form POST berjalan
            });
        }
    });
</script>
@endpush