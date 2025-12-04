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
                            {{-- Menggunakan helper Minio untuk mendapatkan URL file yang aman --}}
                            @php
                                $key = ltrim($document->file_path, '/');
                                // Gunakan helper Minio untuk membuat Signed URL
                                // Asumsi AWS_COMPLAINT_BUCKET adalah bucket yang benar
                                $fileUrl = signMinioUrlSmart(env('AWS_COMPLAINT_BUCKET'), $key, 10); 
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
                        <div class="card-body position-relative">
                            <div class="d-flex align-items-start">
                                <div class="me-auto">
                                    
                                    @php
                                        $institutionFromName = $log['institution_from_name'] ?? 'Lapor Mas Wapres';
                                        $institutionToName = $log['institution_to_name'] ?? 'Lapor Mas Wapres';
                                        $attachments = $log['attachments'] ?? [];
                                        // Ambil konten yang akan disalin
                                        $contentToCopy = strip_tags($log['rendered_content'] ?? '');
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

                                    {{-- DISPLAY ATTACHMENTS (Bagian ini tidak diubah) --}}
                                    @if (!empty($attachments))
                                    <div class="mt-3 border-top pt-2">
                                        <span class="fw-bold small text-dark d-block mb-1">Lampiran:</span>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($attachments as $attachment)
                                                @php
                                                    $fileUrl = $attachment['path'] ?? '#'; 
                                                    $fileName = $attachment['file_name'] ?? 'Dokumen';
                                                    $fileExtension = $attachment['extension'] ?? 'file';
                                                    
                                                    // Tentukan ikon berdasarkan ekstensi
                                                    $icon = 'ti-file';
                                                    if (in_array($fileExtension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png'])) {
                                                        if ($fileExtension === 'pdf') $icon = 'ti-file-type-pdf';
                                                        elseif (in_array($fileExtension, ['doc', 'docx'])) $icon = 'ti-file-type-word';
                                                        elseif (in_array($fileExtension, ['xls', 'xlsx'])) $icon = 'ti-file-type-excel';
                                                        else $icon = 'ti-file';
                                                    }
                                                @endphp
                                                @if ($fileUrl && $fileUrl !== '#')
                                                    <a href="{{ $fileUrl }}" 
                                                    target="_blank" 
                                                    class="badge bg-light text-dark d-flex align-items-center gap-1 border border-secondary-subtle text-decoration-none" 
                                                    title="{{ $fileName }}">
                                                        <i class="ti {{ $icon }} ti-xs me-1"></i>
                                                        <span class="text-truncate" style="max-width: 150px;">{{ $fileName }}</span>
                                                        <i class="ti ti-download ti-xs ms-1"></i>
                                                    </a>
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
                                    
                                    {{-- TOMBOL Gunakan sebagai Tanggapan (di sisi kiri bawah konten) --}}
                                    @if (!empty($contentToCopy))
                                        <div class="mt-3">
                                            <button 
                                                type="button" 
                                                class="btn btn-sm btn-ghost-primary p-0 copy-lapor-content position-absolute bottom-0 end-0 me-3 mb-2" 
                                                title="Gunakan sebagai Tanggapan"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modal-quick-action" 
                                                data-content="{{ $contentToCopy }}"
                                                style="font-size: 0.75rem;">
                                                <i class="ti ti-text-plus me-1 ti-sm"></i>
                                                Gunakan sebagai Tanggapan
                                            </button>
                                        </div>
                                    @endif
                                </div>
                                <div class="text-secondary small ms-3 text-nowrap">
                                    <span title="Waktu Log">{{ $log['created_at'] }}</span>
                                </div>
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

<div class="modal fade" id="modal-quick-action" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="quick-action-form" action="{{ route('reports.update-response', $report->uuid) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Tanggapan Pengaduan</h5><span>(dapat dilihat pelapor)</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Status Pengaduan --}}
                    <div class="mb-3">
                        <label class="form-label">Status Pengaduan</label>
                        <select name="status" class="form-select" required>
                            {{-- Gunakan $report->status untuk menampung status default saat ini --}}
                            <option value="Proses verifikasi dan telaah" {{ $report->status == 'Proses verifikasi dan telaah' ? 'selected' : '' }}>Proses verifikasi dan telaah</option>
                            <option value="Menunggu kelengkapan data dukung dari Pelapor" {{ $report->status == 'Menunggu kelengkapan data dukung dari Pelapor' ? 'selected' : '' }}>Menunggu kelengkapan data dukung dari Pelapor</option>
                            <option value="Diteruskan kepada instansi yang berwenang untuk penanganan lebih lanjut" {{ $report->status == 'Diteruskan kepada instansi yang berwenang untuk penanganan lebih lanjut' ? 'selected' : '' }}>Diteruskan kepada instansi yang berwenang untuk penanganan lebih lanjut</option>
                            <option value="Penanganan Selesai" {{ $report->status == 'Penanganan Selesai' ? 'selected' : '' }}>Penanganan Selesai</option>
                        </select>
                    </div>
                    
                    {{-- Klasifikasi Aduan --}}
                    <div class="mb-3">
                        <label class="form-label">Klasifikasi Aduan</label>
                        <select name="classification" class="form-select" required>
                            <option value="" disabled {{ is_null($report->classification) ? 'selected' : '' }}>-- Belum diklasifikasikan --</option>
                            <option value="Pengaduan berkadar pengawasan" {{ $report->classification == 'Pengaduan berkadar pengawasan' ? 'selected' : '' }}>Pengaduan berkadar pengawasan</option>
                            <option value="Pengaduan tidak berkadar pengawasan" {{ $report->classification == 'Pengaduan tidak berkadar pengawasan' ? 'selected' : '' }}>Pengaduan tidak berkadar pengawasan</option>
                            <option value="Aspirasi" {{ $report->classification == 'Aspirasi' ? 'selected' : '' }}>Aspirasi</option>
                        </select>
                    </div>
                    
                    {{-- Ceklis Bantuan/Manfaat --}}
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

                    {{-- Textarea Tanggapan (Response) --}}
                    <div class="mb-3">
                        <div class="row">
                            {{-- KOLOM KIRI: TANGGAPAN LAMA (READ-ONLY) --}}
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label text-secondary">Tanggapan Terakhir (Lama)</label>
                                <textarea 
                                    class="form-control bg-light" 
                                    rows="7" 
                                    disabled 
                                    title="Tanggapan yang saat ini tersimpan di sistem."
                                >{{ $report->response ?? '— Belum ada tanggapan tersimpan —' }}</textarea> 
                            </div>

                            {{-- KOLOM KANAN: TANGGAPAN BARU (INPUT) --}}
                            <div class="col-md-6">
                                <label class="form-label required">Tanggapan Baru / Salinan LAPOR!</label>
                                {{-- ID KRITIS UNTUK JAVASCRIPT --}}
                                <textarea 
                                    name="response" 
                                    class="form-control" 
                                    rows="7" 
                                    id="quick-action-response-area" 
                                    required
                                >{{ $report->response ?? '' }}</textarea> 
                                <small class="form-hint">
                                    Konten dari log LAPOR! akan disalin ke kolom ini saat tombol <i class="ti ti-text-plus"></i> Gunakan sebagai Tanggapan diklik.
                                </small>
                            </div>
                        </div>
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

        const quickActionModal = document.getElementById('modal-quick-action');
        const responseArea = document.getElementById('quick-action-response-area');
        
        // Pastikan modal dan textarea ditemukan
        if (!quickActionModal || !responseArea) {
            console.error("Modal atau Response Area tidak ditemukan.");
            return;
        }

        // 1. Listener untuk menangkap event saat MODAL dibuka
        quickActionModal.addEventListener('show.bs.modal', function (event) {
            // Elemen yang memicu modal (yaitu tombol 'copy-lapor-content' atau tombol lain)
            const button = event.relatedTarget; 
            
            // Cek apakah tombol yang diklik memiliki data-content (tombol 'copy-lapor-content' Anda)
            if (button && button.classList.contains('copy-lapor-content')) {
                const content = button.getAttribute('data-content');
                
                if (content) {
                    // 2. Isi textarea dengan content LAPOR! yang sudah di-strip tag HTML
                    responseArea.value = content.trim(); 
                }
            }
        });

        // 3. Fungsi konfirmasi (diambil dari button modal footer)
        window.showQuickActionConfirmation = function() {
            // Asumsi: Anda memiliki fungsi konfirmasi SweetAlert di tempat lain
            // Jika tidak, Anda dapat langsung melakukan submit form:
            document.getElementById('quick-action-form').submit();
            
            // Jika Anda ingin SweetAlert konfirmasi sebelum submit:
            /*
            Swal.fire({
                title: 'Simpan Tanggapan?',
                text: "Pastikan tanggapan ini yang akan dilihat oleh Pelapor.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Simpan!',
                // ... dst
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('quick-action-form').submit();
                }
            });
            */
        }
    });
</script>
@endpush