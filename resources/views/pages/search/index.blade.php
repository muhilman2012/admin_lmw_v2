@extends('layouts.app')

@section('title', 'Pencarian Laporan')
@section('page_pretitle', 'Alat')
@section('page_title', 'Pencarian Universal')

@section('page_header')
    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="d-flex g-2 align-items-center justify-content-between">
                <h2 class="page-title">Pencarian Laporan</h2>
            </div>
        </div>
    </div>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form id="form-search" class="row g-2 align-items-center mb-3" role="search">
            @csrf
            <div class="col-12 col-md-8">
                <div class="input-icon">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input
                        id="q"
                        name="q"
                        type="search"
                        class="form-control"
                        placeholder="Cari judul, NIK, atau nomor tiket..."
                        required
                    />
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select id="status" name="status" class="form-select">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-2 justify-content-end">
                <button class="btn btn-primary" type="submit"><i class="ti ti-search me-1"></i>Cari</button>
                <button class="btn btn-link" type="reset">Reset</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Nomor Tiket</th>
                        <th>Nama Pengadu</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="result-body">
                    <tr id="initial-message">
                        <td colspan="7" class="text-center py-5 text-muted">Belum ada hasil. Mulai dengan pencarian di atas.</td>
                    </tr>
                </tbody>
            </table>
            <div id="loading-indicator" class="text-center py-3 d-none">
                <div class="spinner-border text-primary spinner-border-sm" role="status"></div> Loading...
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const formSearch = document.getElementById('form-search');
    const resultBody = document.getElementById('result-body');
    const loadingIndicator = document.getElementById('loading-indicator');

    formSearch.addEventListener('submit', function (e) {
        e.preventDefault();
        
        const query = document.getElementById('q').value;
        if (query.trim() === '') {
            return; // Jangan lakukan pencarian jika input kosong
        }

        resultBody.innerHTML = ''; // Kosongkan hasil sebelumnya
        loadingIndicator.classList.remove('d-none');

        const formData = new FormData(formSearch);
        
        fetch("{{ route('search.run') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            loadingIndicator.classList.add('d-none');
            renderResults(data.reports);
        })
        .catch(error => {
            console.error('Error during search:', error);
            loadingIndicator.classList.add('d-none');
            Swal.fire('Error', 'Gagal memuat hasil pencarian.', 'error');
            resultBody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-danger">Gagal memuat data.</td></tr>';
        });
    });

    function renderResults(reports) {
        if (reports.length === 0) {
            resultBody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">Tidak ditemukan laporan yang sesuai.</td></tr>';
            return;
        }

        let html = '';
        reports.forEach((report, index) => {
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${report.ticket_number}</td>
                    <td>${report.reporter.name}</td>
                    <td>${report.subject}</td>
                    <td>${report.category.name || '-'}</td>
                    <td><span class="badge bg-primary-lt">${report.status}</span></td>
                    <td>
                        <a href="/admin/reports/${report.uuid}/detail" class="btn btn-sm btn-outline-primary">Detail</a>
                    </td>
                </tr>
            `;
        });
        resultBody.innerHTML = html;
    }
});
</script>
@endpush