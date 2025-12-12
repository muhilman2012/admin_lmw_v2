@extends('layouts.app')

@section('title', 'Edit Pengaduan')
@section('page_pretitle', 'Laporan & Pengaduan')
@section('page_title', 'Edit Pengaduan')

@section('page_header')
<div class="page-header d-print-none" aria-label="Page header">
    <div class="container-xl">
        <div class="d-flex g-2 align-items-center justify-content-between flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title m-0">Form Edit Pengaduan</h2>
            </div>
            <a href="{{ route('reports.show', $report->uuid) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali ke Detail
            </a>
        </div>
    </div>
</div>
@endsection

@section('content')
<form id="form-edit-pengaduan" action="{{ route('reports.update', $report->uuid) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PATCH')
    <div class="card">
        <div class="card-body">
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6">
                    <label class="form-label">Nama Lengkap<span class="text-danger">*</span></label>
                    <input type="text" name="name" placeholder="Masukkan Nama Lengkap" class="form-control" value="{{ old('name', $report->reporter->name ?? '') }}" required />
                    <div class="invalid-feedback">Nama Lengkap wajib diisi.</div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">NIK<span class="text-danger">*</span></label>
                    <input type="text" name="nik" placeholder="Masukkan Nomor NIK" class="form-control" value="{{ old('nik', $report->reporter->nik ?? '') }}" required />
                    <div class="invalid-feedback">NIK harus 16 digit.</div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Nomor Kartu Keluarga <span class="text-secondary"> (opsional)</span></label>
                    <input type="text" name="kk_number" placeholder="Masukkan Nomor Kartu Keluarga" class="form-control" value="{{ old('kk_number', $report->reporter->kk_number ?? '') }}" />
                    <div class="invalid-feedback">Nomor KK harus 16 digit.</div>
                </div>
            </div>
            
            <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
                <div>
                    <label class="form-label">Sumber Pengaduan<span class="text-danger">*</span></label>
                    <select class="form-select" name="source" required>
                        <option value="" disabled selected>Pilih Sumber</option>
                        <option value="tatap muka" {{ old('source', $report->source) == 'tatap muka' ? 'selected' : '' }}>Tatap Muka</option>
                        <option value="whatsapp" {{ old('source', $report->source) == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                        <option value="surat fisik" {{ old('source', $report->source) == 'surat fisik' ? 'selected' : '' }}>Surat</option>
                    </select>
                    <div class="invalid-feedback">Harap pilih Sumber Pengaduan</div>
                </div>
                <div>
                    <label class="form-label">Email Pengadu</label>
                    <input type="email" name="email" placeholder="Masukkan Email" class="form-control" value="{{ old('email', $report->reporter->email ?? '') }}" />
                    <div class="invalid-feedback">Perbaiki Email Pengadu.</div>
                </div>
                <div>
                    <label class="form-label">Nomor HP Pengadu<span class="text-danger">*</span></label>
                    <input type="tel" name="phone_number" placeholder="Masukkan Nomor HP" class="form-control" value="{{ old('phone_number', $report->reporter->phone_number ?? '') }}" required/>
                    <div class="invalid-feedback">Perbaiki Nomor HP Pengadu.</div>
                </div>
                <div>
                    <label class="form-label">Alamat Lengkap<span class="text-danger">*</span></label>
                    <textarea name="address" placeholder="Masukkan Alamat" rows="3" class="form-control" required>{{ old('address', $report->reporter->address ?? '') }}</textarea>
                    <div class="invalid-feedback">Perbaiki Alamat Lengkap Pengadu.</div>
                </div>
            </div>
            
            <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
                <div>
                    <label class="form-label">Judul Laporan<span class="text-danger">*</span></label>
                    <input type="text" name="subject" placeholder="Masukkan Judul Laporan" class="form-control" value="{{ old('subject', $report->subject ?? '') }}" required />
                    <div class="invalid-feedback">Harap isi Judul Laporan.</div>
                </div>
                <div>
                    <label class="form-label">Lokasi Kejadian</label>
                    <input type="text" name="location" placeholder="Masukkan Lokasi Kejadian" class="form-control" value="{{ old('location', $report->location ?? '') }}" />
                    <div class="invalid-feedback"></div>
                </div>
                <div>
                    <label class="form-label">Tanggal Kejadian</label>
                    <div class="input-icon">
                        <input class="form-control" name="event_date" placeholder="DD/MM/YYYY" id="datepicker-tgl-kejadian" value="{{ old('event_date', $report->event_date ? \Carbon\Carbon::parse($report->event_date)->format('d/m/Y') : '') }}" />
                        <span class="input-icon-addon"><i class="icon ti ti-calendar"></i></span>
                    </div>
                    <div class="invalid-feedback">Harap isi Tanggal Kejadian.</div>
                </div>
                <div class="col-12" id="field-kategori">
                    <label class="form-label">Kategori<span class="text-danger">*</span></label>
                    <select name="category_id" id="select-optgroups" class="form-select" placeholder="Pilih Kategori" required>
                        <option value="" disabled>Pilih Kategori</option>
                        
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" 
                                {{ old('category_id', $selectedCategoryId) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }} (Kategori Utama)
                            </option>

                            @if($category->children->count() > 0)
                                <optgroup label="--- {{ $category->name }} ---">
                                    @foreach($category->children as $childCategory)
                                        <option value="{{ $childCategory->id }}" 
                                            {{ old('category_id', $selectedCategoryId) == $childCategory->id ? 'selected' : '' }}>
                                            {{ $childCategory->name }}
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
                <textarea name="details" placeholder="Isi Detail Laporan" rows="6" class="form-control" required>{{ old('details', $report->details ?? '') }}</textarea>
                <div class="invalid-feedback mb-3">Harap isi Detail Laporan.</div>
            </div>

            <input type="hidden" name="status" value="{{ $report->status ?? 'Proses verifikasi dan telaah' }}" />
            <textarea name="response" style="display: none;">{{ $report->response ?? '' }}</textarea>
            
            <div class="col-12">
                <label class="form-label">Lampiran Lama</label>
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

                <label class="form-label">Unggah Dokumen Baru</label>
                <div class="dropzone" id="dropzone-multiple">
                    <div class="fallback">
                        <input name="attachments[]" type="file" multiple />
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
                    <a href="{{ route('reports.show', $report->uuid) }}" class="btn btn-1">Batal</a>
                    <button type="submit" class="btn btn-primary btn-2">Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- MODAL QUICK ACTION --}}
<div class="modal fade" id="quick-status-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Status & Tanggapan Cepat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Status Laporan</label>
                    <select class="form-select" id="quick-status-select">
                        <option value="" disabled>Pilih Status Template...</option> 
                        @foreach ($statusTemplates as $template)
                            <option 
                                value="{{ $template->status_code }}"
                                data-template="{{ $template->response_template }}"
                                {{ ($report->status == $template->name) ? 'selected' : '' }} 
                            >
                                {{ $template->name }}
                            </option>
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
    document.addEventListener("DOMContentLoaded", function () {
        // --- PENTING: Pengecekan Dropzone (Harus di luar scope fungsi) ---
        const dropzoneElement = document.querySelector("#dropzone-multiple");
        if (window.Dropzone && dropzoneElement && !dropzoneElement.dropzone) { 
            Dropzone.autoDiscover = false;
            
            // Konfigurasi Dropzone
            const myDropzone = new Dropzone(dropzoneElement, {
                url: "{{ route('reports.update', $report->uuid) }}",
                paramName: "attachments",
                uploadMultiple: true,
                autoProcessQueue: false,
                addRemoveLinks: true,
                maxFilesize: 20,
                acceptedFiles: "image/*,application/pdf,.doc,.docx",
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                dictDefaultMessage: `<div class="dz-message"><h3 class="dropzone-msg-title">Drop file atau klik untuk upload</h3><span class="dropzone-msg-desc">-maks 20MB per file-</span></div>`,
                
                // Hook Init Dropzone
                init: function() {
                    const dropzone = this;

                    document.getElementById('form-edit-pengaduan').addEventListener('submit', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                        const form = this;
                        const formData = new FormData(form);
                        
                        // Append Dropzone files
                        if (dropzone.files.length > 0) {
                            dropzone.files.forEach((file, index) => {
                                // Pastikan nama field sama dengan yang di-handle oleh Controller
                                formData.append('attachments[' + index + ']', file);
                            });
                        }
                        
                        // Append the PATCH method
                        formData.append('_method', 'PATCH');

                        // Submit via Fetch API (untuk menangani JSON response)
                        fetch(form.action, {
                            method: 'POST', 
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        }).then(response => {
                             if (response.ok) {
                                 return response.json();
                             }
                             // Tangani error validasi dari server (422)
                             return response.json().then(errorData => {
                                 throw errorData;
                             });
                        }).then(data => {
                            if (data.success && data.uuid) {
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'success',
                                    title: 'Laporan berhasil diperbarui!',
                                    showConfirmButton: false,
                                    timer: 1500,
                                    timerProgressBar: true,
                                });
                                setTimeout(() => {
                                    window.location.href = "{{ url('admin/reports/') }}" + "/" + data.uuid + "/detail";
                                }, 1500);
                            }
                            // Tambahan: Tambahkan else case untuk kegagalan non-validasi jika perlu
                        }).catch(error => {
                            if (error.errors) {
                                // Menampilkan error validasi
                                for (const [key, messages] of Object.entries(error.errors)) {
                                     const inputElement = document.querySelector(`[name="${key}"]`);
                                     // ... (Logika penampilan error tetap sama) ...
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
                                // Error handling umum (Misal 500 server error)
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'error',
                                    title: 'Terjadi kesalahan pada server.',
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

    // ==========================================================
    // LOGIKA QUICK ACTION
    // ==========================================================
    document.addEventListener("DOMContentLoaded", function () {
        // --- SELECTOR UTAMA ---
        const quickStatusModal = document.getElementById('quick-status-modal');
        const statusSelect = document.getElementById('quick-status-select');
        const additionalDocsSection = document.getElementById('additional-docs-section');
        const responseTextarea = document.getElementById('quick-response-textarea'); 
        const applyBtn = document.getElementById('apply-quick-status-btn');
        const modalTriggerBtn = document.querySelector('[data-bs-toggle="modal"][data-bs-target="#quick-status-modal"]');
        const modalCloseBtns = quickStatusModal ? quickStatusModal.querySelectorAll('[data-bs-dismiss="modal"]') : [];
        
        // SELECTOR FIELD UTAMA DI FORM EDIT
        const mainStatusInput = document.querySelector('input[name="status"]'); 
        const mainResponseTextarea = document.querySelector('textarea[name="response"]');
        
        // --- PURE JS MODAL HANDLER ---
        function showModal() {
            if (!quickStatusModal) return;

            quickStatusModal.classList.add('show');
            quickStatusModal.style.display = 'block';
            quickStatusModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden'; 
            
            if (!document.getElementById('quick-status-backdrop')) {
                const backdrop = document.createElement('div');
                backdrop.classList.add('modal-backdrop', 'fade', 'show');
                backdrop.id = 'quick-status-backdrop';
                document.body.appendChild(backdrop);
            }
            
            // 🔥 INISIALISASI MODAL DENGAN NILAI FORM UTAMA SAAT DIBUKA
            if (mainStatusInput) {
                const statusName = mainStatusInput.value.trim();
                const statusOption = Array.from(statusSelect.options).find(opt => opt.textContent.trim() === statusName);
                if (statusOption) {
                    statusSelect.value = statusOption.value;
                    if (statusSelect.tomselect) statusSelect.tomselect.setValue(statusOption.value);
                }
            }
            if (mainResponseTextarea) {
                responseTextarea.value = mainResponseTextarea.value;
            }

            // Panggil updateQuickResponse untuk mengatur visibilitas dokumen/template awal
            // Kita panggil di sini, tapi tanpa menimpa response lama (kecuali jika status baru memerlukannya)
            updateQuickResponse(false); 

            // TomSelect for Category (Optional - jika TomSelect untuk kategori juga diinisialisasi di sini)
            const selectOptgroups = document.getElementById("select-optgroups");
            if (window.TomSelect && selectOptgroups && !selectOptgroups.tomselect) {
                new TomSelect("#select-optgroups", {
                    plugins: { dropdown_input: {} },
                    create: false,
                    allowEmptyOption: true,
                    sortField: { field: "text", direction: "asc" },
                });
            }
        }

        function hideModal() {
            if (!quickStatusModal) return;

            quickStatusModal.classList.remove('show');
            quickStatusModal.style.display = 'none';
            quickStatusModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            document.body.style.overflow = ''; 

            const modalBackdrop = document.getElementById('quick-status-backdrop');
            if (modalBackdrop) {
                modalBackdrop.remove();
            }

            const allBackdrops = document.querySelectorAll('.modal-backdrop');
            allBackdrops.forEach(backdrop => backdrop.remove());
        }

        // FUNGSI UTAMA: Update Response Textarea Modal
        function updateQuickResponse(isManualChange = false) {
            const selectedStatusOption = statusSelect.options[statusSelect.selectedIndex];
            const template = selectedStatusOption ? selectedStatusOption.dataset.template : '';
            const isAdditionalDataRequired = selectedStatusOption && selectedStatusOption.value === 'additional_data_required';
            
            let newResponseContent = template;

            // 1. Tentukan visibilitas Data Pendukung
            if (isAdditionalDataRequired) {
                additionalDocsSection.style.display = 'block';
                const selectedDocs = Array.from(document.querySelectorAll('#additional-docs-section input[type="checkbox"]:checked'))
                                                     .map(checkbox => checkbox.value);
                newResponseContent = template.replace('[dokumen_yang_dibutuhkan]', selectedDocs.join(', '));
            } else {
                additionalDocsSection.style.display = 'none';
            }
            
            // 2. KONTROL PENIMPAAN (Langsung timpa jika status diubah)
            if (isManualChange || isAdditionalDataRequired) {
                 responseTextarea.value = newResponseContent;
            }
        }

        // --- ATTACH EVENT LISTENERS ---
        
        // Event listener saat status di modal berubah
        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                updateQuickResponse(true); // Panggil sebagai perubahan manual
            });
        }

        // Event listener saat checkbox dokumen berubah
        document.querySelectorAll('#additional-docs-section input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateQuickResponse(false); // Panggil sebagai perubahan checkbox
            });
        });
        
        // Event saat tombol 'Aksi Cepat' diklik
        if (modalTriggerBtn) {
            modalTriggerBtn.removeAttribute('data-bs-toggle');
            modalTriggerBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showModal();
            });
        }
        
        // Listener untuk tombol close modal
        modalCloseBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.target.blur();
                hideModal();
            });
        });
        
        // --- LOGIKA TERAPKAN (APPLY) ---
        if (applyBtn) {
            applyBtn.addEventListener('click', function(e) {
                const selectedStatusOption = statusSelect.options[statusSelect.selectedIndex];
                const selectedStatusName = selectedStatusOption.textContent.trim();
                
                // 🔥 Terapkan status ke input tersembunyi
                if (mainStatusInput) {
                    mainStatusInput.value = selectedStatusName;
                }
                // 🔥 Terapkan response ke textarea tersembunyi
                if (mainResponseTextarea) {
                    mainResponseTextarea.value = responseTextarea.value; 
                }
                
                e.target.blur();
                hideModal();
            });
        }
        
        // PENTING: Inisialisasi awal nilai field tersembunyi agar terkirim
        if (mainStatusInput) {
             const statusValue = '{{ $report->status ?? 'Proses verifikasi dan telaah' }}'; 
             mainStatusInput.value = statusValue; 
        }
        if (mainResponseTextarea) {
             mainResponseTextarea.value = '{{ $report->response ?? '' }}';
        }

        // --- Logika TomSelect (Kategori Utama) ---
        const selectOptgroups = document.getElementById("select-optgroups");
        if (window.TomSelect && selectOptgroups && !selectOptgroups.tomselect) {
             new TomSelect("#select-optgroups", {
                 plugins: { dropdown_input: {} },
                 create: false,
                 allowEmptyOption: true,
                 sortField: { field: "text", direction: "asc" },
             });
         }
         
         // Logika Litepicker tetap dipertahankan
         window.Litepicker &&
         new Litepicker({
             element: document.getElementById("datepicker-tgl-kejadian"),
             format: "DD/MM/YYYY",
             buttonText: {
                 previousMonth: `<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" class=\"icon icon-1\"><path d=\"M15 6l-6 6l6 6\" /></svg>`,
                 nextMonth: `<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" class=\"icon icon-1\"><path d=\"M9 6l6 6l-6 6\" /></svg>`,
             },
         });
    });
</script>
@endpush