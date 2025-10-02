@extends('layouts.app')

@section('title', 'Export Data Laporan')
@section('page_pretitle', 'Data')
@section('page_title', 'Export Laporan Pengaduan')


@section('page_header')
    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="d-flex g-2 align-items-center justify-content-between">
                <h2 class="page-title">Export Data Pengaduan</h2>
            </div>
        </div>
    </div>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <h3 class="card-title">Pilih Filter Data</h3>
        <p class="card-subtitle">Pilih kriteria untuk data yang akan diexport. Untuk data yang sangat besar, disarankan menggunakan format Excel.</p>

        <form id="export-form" method="POST">
            @csrf
            <div class="row g-4 mt-1">
                {{-- Kategori --}}
                <div class="col-md-4">
                    <label class="form-label">Kategori</label>
                    <select class="form-select" name="filterKategori" id="filter-export-kategori">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $category)
                            <option>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                
                {{-- Status Laporan --}}
                <div class="col-md-4">
                    <label class="form-label">Status Laporan</label>
                    <select class="form-select" name="filterStatus" id="filter-export-status">
                        <option value="">Semua Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                
                {{-- Klasifikasi --}}
                <div class="col-md-4">
                    <label class="form-label">Klasifikasi</label>
                    <select class="form-select" name="filterKlasifikasi" id="filter-export-klasifikasi">
                        <option value="">Semua Klasifikasi</option>
                        @foreach ($classifications as $classification)
                            <option value="{{ $classification }}">{{ $classification }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Distribusi (Deputi / Unit Kerja) --}}
                <div class="col-md-4">
                    <label class="form-label">Distribusi</label>
                    <select class="form-select" name="filterDistribusi" id="filter-export-distribusi">
                        <option value="">Semua Distribusi</option>
                        <optgroup label="Deputi">
                            @foreach ($deputies as $deputy)
                                <option value="deputy_{{ $deputy->id }}">{{ $deputy->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Unit Kerja">
                            @foreach ($unitKerjas as $unit)
                                <option value="unit_{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                
                {{-- Status Analisis --}}
                <div class="col-md-4">
                    <label class="form-label">Status Analisis</label>
                    <select class="form-select" name="filterStatusAnalisis" id="filter-export-status-analisis">
                        <option value="">Semua Status Analisis</option>
                        @foreach ($analysisStatuses as $statusAnalisis)
                            <option>{{ $statusAnalisis }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Sumber --}}
                <div class="col-md-4">
                    <label class="form-label">Sumber Pengaduan</label>
                    <select class="form-select" name="filterSumber" id="filter-export-sumber">
                        <option value="">Semua Sumber</option>
                        @foreach ($sources as $source)
                            <option>{{ $source }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tanggal Range --}}
                <div class="col-md-4">
                    <label class="form-label">Periode Tanggal Dibuat</label>
                    <input type="text" class="form-control" name="filterDateRange" id="tanggal-range-export" placeholder="dd/mm/yyyy - dd/mm/yyyy" />
                </div>
                
                {{-- Pencarian Universal --}}
                <div class="col-md-8">
                    <label class="form-label">Pencarian Kata Kunci</label>
                    <input type="text" class="form-control" name="q" placeholder="Cari Judul, NIK, atau Nomor Tiket" />
                </div>
            </div>

            <div class="card-footer bg-white mt-4 pt-4">
                <button type="button" class="btn btn-primary me-2" onclick="startExport('excel')">
                    <i class="ti ti-file-spreadsheet me-1"></i> Export ke Excel
                </button>
                <button type="button" class="btn btn-secondary" onclick="startExport('pdf')">
                    <i class="ti ti-file-text me-1"></i> Export ke PDF
                </button>
            </div>
        </form>
        <div class="mt-5 pt-4 border-top">
            <h3 class="card-title text-warning">Import Data (Migrasi LMW Lama)</h3>
            <p class="card-subtitle">Unggah file Excel (XLSX) yang berisi data laporan lama untuk migrasi. Pastikan menggunakan template yang telah disediakan.</p>
            
            <form action="{{ route('import.reports') }}" method="POST" enctype="multipart/form-data" class="row g-3 mt-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Pilih File Excel Migrasi (.xlsx)</label>
                    <input type="file" name="file" class="form-control" required accept=".xlsx">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="ti ti-upload me-1"></i> Mulai Migrasi Data
                    </button>
                </div>
            </form>
            
            <div class="mt-3">
                <a href="{{ route('export.template') }}" class="small text-secondary">
                    <i class="ti ti-download me-1"></i> Unduh Template Excel Migrasi
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Inisialisasi Litepicker untuk range tanggal
        if (window.Litepicker) {
            new Litepicker({
                element: document.getElementById('tanggal-range-export'),
                singleMode: false,
                format: 'DD/MM/YYYY',
                autoApply: true,
                lang: 'id',
            });
        }
        
        // 2. Inisialisasi TomSelect untuk semua dropdown filter
        const selectIds = [
            'filter-export-kategori', 'filter-export-status', 'filter-export-klasifikasi', 
            'filter-export-distribusi', 'filter-export-status-analisis', 'filter-export-sumber'
        ];
        
        selectIds.forEach(id => {
            const selectElement = document.getElementById(id);
            if (window.TomSelect && selectElement) {
                new TomSelect(`#${id}`, {
                    plugins: { dropdown_input: {} },
                    create: false,
                    allowEmptyOption: true,
                    sortField: { field: "text", direction: "asc" },
                });
            }
        });

    });

    /**
     * Fungsi utama yang dipanggil saat tombol Export ditekan.
     */
    function startExport(format) {
        const form = document.getElementById('export-form');
        const actionUrl = format === 'excel' ? "{{ route('export.excel') }}" : "{{ route('export.pdf') }}";
        
        // Set Action URL form
        form.action = actionUrl;
        
        // 1. Tampilkan Loader
        window.appLoader.show(`Menyiapkan data untuk export ke ${format.toUpperCase()}. Mohon tunggu...`);

        if (format === 'pdf') {
            
            // --- Logika Sinkron untuk PDF ---

            // A. Pasang listener yang akan menghilangkan loader setelah download dimulai.
            // Metode ini bekerja di sebagian besar browser yang memblokir window.blur() selama download.
            let isFocused = true;
            const handleBlur = () => {
                isFocused = false;
            };
            const handleFocus = () => {
                // Ketika window kembali focus, asumsikan download sudah selesai
                if (!isFocused) {
                    window.removeEventListener('blur', handleBlur);
                    window.removeEventListener('focus', handleFocus);
                    
                    // Sembunyikan loader 1 detik setelah focus kembali
                    setTimeout(() => {
                        window.appLoader.hide();
                    }, 1000); 
                }
            };

            window.addEventListener('blur', handleBlur);
            window.addEventListener('focus', handleFocus);

            // B. Submit Form Sinkron
            form.submit();
            
            // C. Fallback Timeout (Jika metode blur/focus gagal)
            setTimeout(() => {
                 window.appLoader.hide();
            }, 15000); // Batas maksimal 15 detik untuk download PDF
            
        } else {
            // EXCEL: Kirim AJAX untuk Queued Export
            const formData = new FormData(form);

            fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                }
            })
            // ... (Logika Polling Excel tetap sama)
            .then(response => {
                if (!response.ok) throw new Error('Export API Gagal');
                return response.json();
            })
            .then(data => {
                if (data.success && data.fileName) {
                    window.appLoader.show(`Export diproses (${data.fileName}). Memeriksa status file...`);
                    startPolling(data.fileName);
                } else {
                    window.appLoader.hide();
                    Swal.fire('Gagal', data.message || 'Gagal memulai proses export.', 'error');
                }
            })
            .catch(error => {
                window.appLoader.hide();
                Swal.fire('Kesalahan Server', 'Gagal memulai proses export Excel. Cek log server.', 'error');
            });
        }
    }
</script>
<script>
function startPolling(fileName) {
    const statusUrl = "{{ route('export.status') }}";
    let attempts = 0;
    const maxAttempts = 300; // ~15 menit jika interval 3s

    const intervalId = setInterval(() => {
        attempts++;

        const url = new URL(statusUrl, window.location.origin);
        url.searchParams.set('fileName', fileName);

        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(r => {
            if (!r.ok) throw new Error('Status API gagal');
            return r.json();
        })
        .then(data => {
            if (data.ready) {
                clearInterval(intervalId);
                window.appLoader.hide();

                // 1) Jika kamu expose storage lewat /storage symlink:
                // const publicUrl = `/storage/${data.path.replace(/^exports\//, 'exports/')}`;

                // 2) Atau pakai endpoint download khusus (lebih aman):
                const dlUrl = new URL("{{ route('export.download') }}", window.location.origin);
                dlUrl.searchParams.set('path', data.path);

                Swal.fire({
                    icon: 'success',
                    title: 'Export Selesai',
                    html: `File siap diunduh:<br><a href="${dlUrl.toString()}" class="btn btn-primary mt-2">Download Excel</a>`,
                    showConfirmButton: false
                });
            } else if (attempts >= maxAttempts) {
                clearInterval(intervalId);
                window.appLoader.hide();
                Swal.fire('Timeout', 'File belum siap. Coba ulangi atau persempit filter.', 'warning');
            }
        })
        .catch(err => {
            clearInterval(intervalId);
            window.appLoader.hide();
            Swal.fire('Kesalahan', 'Gagal memeriksa status export. Coba lagi.', 'error');
        });
    }, 3000);
}
</script>
@endpush