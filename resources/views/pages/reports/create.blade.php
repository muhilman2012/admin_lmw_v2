@extends('layouts.app')

@section('title', 'Tambah Pengaduan Baru')
@section('page_pretitle', 'Laporan & Pengaduan')
@section('page_title', 'Tambah Pengaduan Baru')

@section('page_header')
<div class="page-header d-print-none" aria-label="Page header">
    <div class="container-xl">
        <div class="d-flex g-2 align-items-center justify-content-between flex-wrap">
        <div class="d-flex align-items-center gap-2">
            <h2 class="page-title m-0">Form Pengaduan Baru</h2>
        </div>
        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Kembali
        </a>
        </div>
    </div>
</div>
@endsection

@section('content')
<form id="form-tambah-pengaduan" action="{{ route('reports.store') }}" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="status" value="Proses verifikasi dan telaah">
    <textarea name="response" style="display:none;">Laporan pengaduan Saudara dalam proses verifikasi & penelaahan.</textarea>
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6">
                    <label class="form-label">Nama Lengkap<span class="text-danger">*</span></label>
                    <input type="text" name="name" placeholder="Masukkan Nama Lengkap" class="form-control" value="{{ $reporter->name ?? '' }}" required autocomplete="off"/>
                    <div class="invalid-feedback">Nama Lengkap wajib diisi.</div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">NIK<span class="text-danger">*</span></label>
                    <input type="text" name="nik" placeholder="Masukkan Nomor NIK" class="form-control" value="{{ $reporter->nik ?? '' }}" required autocomplete="off" />
                    <div class="invalid-feedback">NIK harus 16 digit.</div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Nomor Kartu Keluarga <span class="text-secondary"> (opsional)</span></label>
                    <input type="text" name="kk_number" placeholder="Masukkan Nomor Kartu Keluarga" class="form-control" value="{{ $reporter->kk_number ?? '' }}" autocomplete="off"/>
                    <div class="invalid-feedback">Nomor KK harus 16 digit.</div>
                </div>
            </div>
            
            <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
                <div>
                    <label class="form-label">Sumber Pengaduan<span class="text-danger">*</span></label>
                    <select class="form-select" name="source" required>
                        <option value="" disabled selected>Pilih Sumber</option>
                        <option value="tatap muka" @selected(old('source', $defaultSource ?? null) === 'tatap muka')>Tatap Muka</option>
                        <option value="whatsapp" @selected(old('source') === 'whatsapp')>WhatsApp</option>
                        <option value="surat fisik" @selected(old('source') === 'surat fisik')>Surat</option>
                    </select>
                    <div class="invalid-feedback">Harap pilih Sumber Pengaduan</div>
                </div>
                <div>
                    <label class="form-label">Email Pengadu</label>
                    <input type="email" name="email" placeholder="Masukkan Email" class="form-control" value="{{ $reporter->email ?? '' }}" autocomplete="off" />
                    <div class="invalid-feedback">Perbaiki Email Pengadu.</div>
                </div>
                <div>
                    <label class="form-label">Nomor HP Pengadu<span class="text-danger">*</span></label>
                    <input type="tel" name="phone_number" placeholder="Masukkan Nomor HP" class="form-control" value="{{ $reporter->phone_number ?? '' }}" required autocomplete="off"/>
                    <div class="invalid-feedback">Perbaiki Nomor HP Pengadu.</div>
                </div>
                <div>
                    <label class="form-label">Alamat Lengkap<span class="text-danger">*</span></label>
                    <textarea name="address" placeholder="Masukkan Alamat" rows="3" class="form-control" required>{{ $reporter->address ?? '' }}</textarea>
                    <div class="invalid-feedback">Perbaiki Alamat Lengkap Pengadu.</div>
                </div>
            </div>
            
            <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
                <div>
                    <label class="form-label">Judul Laporan<span class="text-danger">*</span></label>
                    <input type="text" name="subject" placeholder="Masukkan Judul Laporan" class="form-control" required autocomplete="off" />
                    <div class="invalid-feedback">Harap isi Judul Laporan.</div>
                </div>
                <div>
                    <label class="form-label">Lokasi Kejadian</label>
                    <input type="text" name="location" placeholder="Masukkan Lokasi Kejadian" class="form-control" />
                    <div class="invalid-feedback"></div>
                </div>
                <div>
                    <label class="form-label">Tanggal Kejadian</label>
                    <div class="input-icon">
                        <input class="form-control" name="event_date" placeholder="DD/MM/YYYY" id="datepicker-tgl-kejadian" autocomplete="off"/>
                        <span class="input-icon-addon"><i class="icon ti ti-calendar"></i></span>
                    </div>
                    <div class="invalid-feedback">Harap isi Tanggal Kejadian.</div>
                </div>
                <div class="col-12" id="field-kategori">
                    <label class="form-label">Kategori<span class="text-danger">*</span></label>
                    <select name="category_id" id="select-optgroups" class="form-select" placeholder="Pilih Kategori" required>
                        <option value="" selected disabled hidden>Pilih Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" 
                                    @if(isset($report) && $report->category_id == $category->id) selected @endif
                                    data-type="parent">
                                {{ $category->name }} (Utama)
                            </option>
                            @if ($category->children->count() > 0)
                                <optgroup label="↳ Sub-Kategori {{ $category->name }}">
                                    @foreach($category->children as $childCategory)
                                        <option value="{{ $childCategory->id }}"
                                                @if(isset($report) && $report->category_id == $childCategory->id) selected @endif
                                                data-parent-id="{{ $category->id }}">
                                            &nbsp;&nbsp;{{ $childCategory->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                    <div class="invalid-feedback">Harap pilih Kategori.</div>
                </div>
            </div>
            
            <div class="col-12 mb-4">
                <label class="form-label">Detail Laporan<span class="text-danger">*</span></label>
                <textarea name="details" placeholder="Isi Detail Laporan" rows="6" class="form-control" required>{{ $reporter->details ?? '' }}</textarea>
                <div class="invalid-feedback mb-3">Harap isi Detail Laporan.</div>
            </div>
            
            <div class="col-12">
                <label class="form-label">Upload Lampiran</label>
                <div class="dropzone" id="dropzone-multiple">
                    <div class="fallback">
                        <input name="file" type="file" multiple />
                    </div>
                    <div class="dz-message">
                        <h3 class="dropzone-msg-title">Drop file atau klik untuk upload</h3>
                        <span class="dropzone-msg-desc">-maks 20MB per file-</span>
                    </div>
                </div>
            </div>
            
            <div class="card-footer d-flex align-items-center justify-content-between">
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#quick-status-modal">
                    <i class="ti ti-rotate-2 me-1"></i> Aksi Cepat
                </button>
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.index') }}" class="btn btn-1">Batal</a>
                    <button type="submit" class="btn btn-primary btn-2">Simpan</button>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="modal fade" id="quick-status-modal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Status & Tanggapan Cepat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Status Laporan</label>
                    <select class="form-select" id="quick-status-select">
                        @foreach ($statusTemplates as $template)
                            <option value="{{ $template->status_code }}" data-template="{{ $template->response_template }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3" id="additional-docs-section" style="display: none;">
                    <label class="form-label">Data Pendukung yang Diperlukan</label>
                    <div>
                        @foreach ($documentTemplates as $docTemplate)
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" value="{{ $docTemplate->name }}" data-doc-name="{{ $docTemplate->name }}">
                                <span class="form-check-label">Dokumen {{ $docTemplate->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggapan</label>
                    <textarea id="quick-response-textarea" class="form-control" rows="7"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="apply-quick-status-btn">Terapkan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", () => {
        if (window.Dropzone) {
            // Matikan autodiscovery untuk kontrol manual
            Dropzone.autoDiscover = false;

            const myDropzone = new Dropzone("#dropzone-multiple", {
                url: "{{ route('reports.store') }}",
                paramName: "attachments",
                uploadMultiple: true,
                autoProcessQueue: false,
                addRemoveLinks: true,
                maxFilesize: 20,
                acceptedFiles: "image/*,application/pdf,.doc,.docx",
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                dictDefaultMessage: `
                    <div class="dz-message">
                        <h3 class="dropzone-msg-title">Drop file atau klik untuk upload</h3>
                        <span class="dropzone-msg-desc">-maks 20MB per file-</span>
                    </div>
                `,
                init: function() {
                    const dropzone = this;

                    document.getElementById('form-tambah-pengaduan').addEventListener('submit', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                        const form = this;
                        const formData = new FormData(form);
                        
                        // Append Dropzone files to the FormData object
                        if (dropzone) {
                            dropzone.files.forEach((file, index) => {
                                formData.append('attachments[' + index + ']', file);
                            });
                        }

                        fetch(form.action, {
                            method: form.method,
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        }).then(response => {
                            // Cek status respons. Jika tidak OK (misal 422), langsung tangani error.
                            if (!response.ok) {
                                return response.json().then(errorData => {
                                    throw errorData;
                                });
                            }
                            // Jika OK (200-299), lanjutkan dengan respons JSON
                            return response.json();
                        }).then(data => {
                            // Blok ini hanya akan dieksekusi jika respons.ok = true
                            if (data.success && data.uuid) {
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'success',
                                    title: 'Laporan berhasil dibuat!',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true,
                                });

                                setTimeout(() => {
                                    window.location.href = "{{ url('admin/reports/') }}" + "/" + data.uuid + "/detail";
                                }, 3000);
                            } else {
                                console.error('UUID not found in server response or success is false.');
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'error',
                                    title: 'Terjadi kesalahan saat menyimpan laporan.',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true,
                                });
                            }
                        }).catch(error => {
                            // Blok ini akan menangkap error dari server (seperti 422) atau network error
                            if (error.errors) {
                                // Handle validation errors from the server
                                for (const [key, messages] of Object.entries(error.errors)) {
                                    const inputElement = document.querySelector(`[name="${key}"]`);
                                    if (inputElement) {
                                        inputElement.classList.add('is-invalid');
                                        const parent = inputElement.closest('div');
                                        if (parent) {
                                        let errorDiv = parent.querySelector('.invalid-feedback');
                                        if (!errorDiv) {
                                            errorDiv = document.createElement('div');
                                            errorDiv.classList.add('invalid-feedback');
                                            parent.appendChild(errorDiv);
                                        }
                                        errorDiv.textContent = messages[0];
                                        }
                                    }
                                }
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'error',
                                    title: 'Mohon periksa kembali form Anda.',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true,
                                });
                            } else {
                                // Handle other unexpected errors
                                console.error('Fetch error:', error);
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'error',
                                    title: 'Terjadi kesalahan koneksi.',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true,
                                });
                            }
                        });
                    });
                }
            });
        }
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Hapus atribut 'data-bs-toggle' untuk mencegah konflik dengan Bootstrap JS
        const modalTriggerBtn = document.querySelector('[data-bs-toggle="modal"][data-bs-target="#quick-status-modal"]');
        if (modalTriggerBtn) {
            modalTriggerBtn.removeAttribute('data-bs-toggle');
        }

        // Logika Quick Action Modal
        const quickStatusModal = document.getElementById('quick-status-modal');
        const statusSelect = document.getElementById('quick-status-select');
        const additionalDocsSection = document.getElementById('additional-docs-section');
        const responseTextarea = document.getElementById('quick-response-textarea');
        const applyBtn = document.getElementById('apply-quick-status-btn');
        const mainStatusInput = document.querySelector('input[name="status"]');
        const mainResponseTextarea = document.querySelector('textarea[name="response"]');
        const modalCloseBtns = quickStatusModal.querySelectorAll('[data-bs-dismiss="modal"]');

        function updateQuickResponse() {
            const selectedStatusOption = statusSelect.options[statusSelect.selectedIndex];
            const template = selectedStatusOption.dataset.template;
            
            if (selectedStatusOption.value === 'additional_data_required') {
                const selectedDocs = Array.from(document.querySelectorAll('#additional-docs-section input[type="checkbox"]:checked'))
                                                     .map(checkbox => checkbox.value);
                let finalResponse = template.replace('[dokumen_yang_dibutuhkan]', selectedDocs.join(', '));
                responseTextarea.value = finalResponse;
            } else {
                responseTextarea.value = template;
            }
        }
        
        statusSelect.addEventListener('change', function() {
            if (this.value === 'additional_data_required') {
                additionalDocsSection.style.display = 'block';
            } else {
                additionalDocsSection.style.display = 'none';
            }
            updateQuickResponse();
        });

        document.querySelectorAll('#additional-docs-section input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', updateQuickResponse);
        });
        
        // --- Logika Buka/Tutup Modal dengan Perbaikan ---
        function showModal() {
            quickStatusModal.classList.add('show');
            quickStatusModal.style.display = 'block';
            quickStatusModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden'; 
            
            // Cek apakah backdrop sudah ada sebelum membuat yang baru
            if (!document.getElementById('quick-status-backdrop')) {
                const backdrop = document.createElement('div');
                backdrop.classList.add('modal-backdrop', 'fade', 'show');
                backdrop.id = 'quick-status-backdrop';
                document.body.appendChild(backdrop);
            }
        }

        function hideModal() {
            quickStatusModal.classList.remove('show');
            quickStatusModal.style.display = 'none';
            quickStatusModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            document.body.style.overflow = ''; 

            const modalBackdrop = document.getElementById('quick-status-backdrop');
            if (modalBackdrop) {
                modalBackdrop.remove();
            }

            // Fallback agresif: hapus semua backdrop jika yang di atas gagal
            const allBackdrops = document.querySelectorAll('.modal-backdrop');
            allBackdrops.forEach(backdrop => backdrop.remove());
        }

        // Gunakan event listener pada tombol yang sudah dimodifikasi
        modalTriggerBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showModal();
        });

        modalCloseBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.target.blur();
                hideModal();
            });
        });
        
        applyBtn.addEventListener('click', function(e) {
            const selectedStatusOption = statusSelect.options[statusSelect.selectedIndex];
            const selectedStatusName = selectedStatusOption.textContent.trim();
            
            if (mainStatusInput) {
                mainStatusInput.value = selectedStatusName;
            }
            if (mainResponseTextarea) {
                mainResponseTextarea.value = responseTextarea.value;
            }
            
            e.target.blur();
            hideModal();
        });
        
        statusSelect.dispatchEvent(new Event('change'));
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Datepicker (same as on the modal)
        window.Litepicker &&
        new Litepicker({
            element: document.getElementById("datepicker-tgl-kejadian"),
            format: "DD/MM/YYYY",
            buttonText: {
            previousMonth: `<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" class=\"icon icon-1\"><path d=\"M15 6l-6 6l6 6\" /></svg>`,
            nextMonth: `<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" class=\"icon icon-1\"><path d=\"M9 6l6 6l-6 6\" /></svg>`,
            },
        });
        // Tom Select on kategori (keperluan UX)
        window.TomSelect &&
        new TomSelect("#select-optgroups", {
            plugins: { dropdown_input: {} },
            create: false,
            allowEmptyOption: true,
            sortField: { field: "text", direction: "asc" },
        });
    });
</script>