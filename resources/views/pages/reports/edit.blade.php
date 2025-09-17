@extends('layouts.app')

@section('title', 'Edit Laporan Pengaduan')

@section('styles')
    <link href="{{ asset('tabler/libs/jsvectormap/dist/jsvectormap.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/css/addon-css.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/libs/tom-select/dist/css/tom-select.bootstrap5.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/css/tabler-vendors.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/css/litepicker.css') }}" rel="stylesheet" />
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between w-100">
            <h3 class="page-title m-0">Edit Laporan</h3>
            <div class="d-flex gap-2">
                <a href="#" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Kembali ke Daftar Laporan
                </a>
                <a id="ed-view-link" href="#" class="btn btn-outline-primary">
                    <i class="ti ti-eye me-1"></i>Lihat Detail
                </a>
            </div>
        </div>
    </div>
    <form id="form-edit-laporan">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card card-border mb-3">
                        <div class="card-header"><strong>Info Laporan</strong></div>
                        <div class="list-group list-group-flush" id="vw-sect-info">
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="label me-4">Nomor Tiket:</div>
                                    <div class="text-end fw-bolder" style="max-width: 70%">1234567</div>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="label me-4">Nama:</div>
                                    <div class="text-end" style="max-width: 70%">Jhon Doe</div>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="label me-4">Judul Laporan:</div>
                                    <div class="text-end" style="max-width: 70%">Bantuan tebus Ijazah</div>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="label me-4">Kategori:</div>
                                    <div class="text-end" style="max-width: 70%">Bantuan Masyarakat</div>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="label me-4">Status Laporan:</div>
                                    <div class="text-end" style="max-width: 70%">Proses verifikasi dan telaah</div>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="label me-4">Diposisi dari:</div>
                                    <div class="text-end" style="max-width: 70%">-</div>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="label me-4">Status Diposisi:</div>
                                    <div class="text-end" style="max-width: 70%">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-border mb-3">
                        <div class="card-header"><strong>Dokumen Pendukung</strong></div>
                        <div class="p-3 text-start">KTP</div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="row">
                        <div class="card card-border mb-3">
                            <div class="card-header"><strong>Detail Laporan</strong></div>
                            <div class="p-3 text-start">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</div>
                        </div>
                        <div class="card card-border mb-3">
                            <div class="card-header"><strong>Tanggapan</strong></div>
                            <div class="p-3 text-start">Laporan pengaduan Saudara dalam proses verifikasi & penelaahan.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status Laporan</label>
                            <select class="form-select">Proses verifikasi dan telaah</select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggapan</label>
                            <textarea rows="4" class="form-control"></textarea>
                        </div>
                    </div>

                    <div class="col">
                        <button class="btn btn-outline-green" type="button" data-bs-toggle="modal" data-bs-target="#modal-forward-instansi">
                            <i class="ti ti-gps me-1"></i>Teruskan ke Instansi
                        </button>
                        <button class="btn btn-primary" type="submit">
                            <i class="ti ti-device-floppy me-1"></i>Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('modals')
    <div class="modal fade" id="modal-forward-instansi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Teruskan ke Instansi</h5>
                    <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="form-forward-instansi">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Pilih Instansi Tujuan</label>
                            <select id="fw-instansi" class="form-select">
                                <option value="" selected disabled>Pilih Instansi</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan untuk Instansi Tujuan (opsional)</label>
                            <textarea id="fw-keterangan" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="fw-anon" />
                            <label class="form-check-label" for="fw-anon">
                                Kirim sebagai Anonim (tanpa data identitas pengadu)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn" type="button" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-success" id="btn-forward-send" type="submit" data-bs-dismiss="modal">
                            Kirim ke Instansi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page_scripts')
    <script src="{{ asset('tabler/libs/litepicker/dist/litepicker.js') }}" defer></script>
    <script src="{{ asset('tabler/libs/tom-select/dist/js/tom-select.base.min.js') }}" defer></script>
    <script src="{{ asset('tabler/js/toast-trial.js') }}" defer></script>
    <script src="{{ asset('tabler/js/edit-laporan-pengaduan.js') }}"></script>
    <script>
        // ... kode JavaScript lainnya yang spesifik untuk halaman ini ...
    </script>
@endsection