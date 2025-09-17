@extends('layouts.app')

@section('title', 'Tambah Pengaduan Baru')
@section('page_pretitle', 'Laporan & Pengaduan')
@section('page_title', 'Tambah Pengaduan Baru')

@section('page_header')
<div class="page-header d-print-none" aria-label="Page header">
    <div class="container-xl">
        <div class="d-flex g-2 align-items-center justify-content-between flex-wrap">
        <div class="d-flex align-items-center gap-2">
            <h2 class="page-title m-0">Tambah Pengaduan Baru</h2>
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
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
                <div>
                    <label class="form-label">Nama Lengkap<span class="text-danger">*</span></label>
                    <input type="text" name="name" placeholder="Masukkan Nama Lengkap" class="form-control" required />
                </div>
                <div>
                    <label class="form-label">NIK<span class="text-danger">*</span></label>
                    <input type="text" name="nik" placeholder="Masukkan Nomor NIK" class="form-control" required />
                </div>
                <div>
                    <label class="form-label">Sumber Pengaduan<span class="text-danger">*</span></label>
                    <select class="form-select" name="source" required>
                        <option value="" disabled selected>Pilih Sumber</option>
                        <option value="Tatap Muka">Tatap Muka</option>
                        <option value="WhatsApp">WhatsApp</option>
                        <option value="Email">Email</option>
                        <option value="Surat">Surat</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Email Pengadu</label>
                    <input type="email" name="email" placeholder="Masukkan Email" class="form-control" />
                </div>
                <div>
                    <label class="form-label">Nomor HP Pengadu</label>
                    <input type="tel" name="phone_number" placeholder="Masukkan Nomor HP" class="form-control" />
                </div>
                <div class="col-12">
                    <label class="form-label">Alamat Lengkap<span class="text-danger">*</span></label>
                    <textarea name="address" placeholder="Masukkan Alamat" rows="3" class="form-control" required></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Judul Laporan<span class="text-danger">*</span></label>
                    <input type="text" name="subject" placeholder="Masukkan Judul Laporan" class="form-control" required />
                </div>
                <div>
                    <label class="form-label">Lokasi Kejadian<span class="text-danger">*</span></label>
                    <input type="text" name="location" placeholder="Masukkan Lokasi Kejadian" class="form-control" required />
                </div>
                <div>
                <label class="form-label">Tanggal Kejadian</label>
                    <div class="input-icon">
                        <input class="form-control" name="event_date" placeholder="mm/dd/yyyy" id="datepicker-tgl-kejadian" />
                        <span class="input-icon-addon"><i class="icon ti ti-calendar"></i></span>
                    </div>
                </div>
                <div class="col-12" id="field-kategori">
                    <label class="form-label">Kategori<span class="text-danger">*</span></label>
                    <select name="category_id" id="select-optgroups" class="form-select" placeholder="Pilih Kategori" required>
                        <option value="" selected disabled hidden>Pilih Kategori</option>
                        @foreach($categories as $category)
                            <optgroup label="{{ $category->name }}">
                                @foreach($category->children as $childCategory)
                                    <option value="{{ $childCategory->id }}">{{ $childCategory->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12 mb-4">
                <label class="form-label">Detail Laporan<span class="text-danger">*</span></label>
                <textarea name="details" placeholder="Isi Detail Laporan" rows="6" class="form-control" required></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Upload Lampiran</label>
                <div class="dropzone" id="dropzone-multiple">
                    <div class="fallback">
                        <input name="file" type="file" multiple />
                    </div>
                    <div class="dz-message">
                        <h3 class="dropzone-msg-title">Drop file atau klik untuk upload</h3>
                        <span class="dropzone-msg-desc">-mungkin bisa ditulis keterangan max berapa MB-</span>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('reports.index') }}" class="btn btn-1">Batal</a>
                <button type="submit" class="btn btn-primary btn-2">Simpan</button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", () => {
        // Matikan autodiscover biar kita kontrol inisialisasinya
        if (window.Dropzone) {
            new Dropzone("#dropzone-multiple", {
            url: "./",
            paramName: "file",
            uploadMultiple: true,
            maxFilesize: 2,
            acceptedFiles: "image/*,application/pdf,.doc,.docx",
            addRemoveLinks: true,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            dictDefaultMessage: "Drop file atau klik untuk upload",
            init: function() {
                this.on("sending", function(file, xhr, formData) {
                    // Append the report ID to the form data
                    formData.append('report_id', document.querySelector('input[name="report_id"]').value);
                });
            }
        });
    }
});
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Datepicker (same as on the modal)
        window.Litepicker &&
        new Litepicker({
            element: document.getElementById("datepicker-tgl-kejadian"),
            format: "MM/DD/YYYY",
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
@endpush