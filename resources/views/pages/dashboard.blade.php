@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_pretitle', 'Halo, Hilman')

@section('styles')
    <link href="{{ asset('tabler/libs/jsvectormap/dist/jsvectormap.css') }}" rel="stylesheet" />
@endsection

@section('content')
<div class="row row-deck row-cards">
    <div class="col-sm-6 col-md-12 col-lg-12 col-xl-9">
        <div class="card">
            <div class="card-body">
                <div class="row gy-3">
                    <div class="col-12 col-sm d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="d-flex flex-column">
                                <h3 class="h2">Total Data Laporan</h3>
                                <div class="row gr-5 gy-2 mb-6 mt-auto">
                                    <div class="col-auto">
                                        <div class="text-green fw-semibold">WhatsApp</div>
                                        <div class="d-flex align-items-baseline">
                                            <div class="h3 text-secondary me-2">6,782</div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="text-primary fw-semibold">Tatap Muka</div>
                                        <div class="d-flex align-items-baseline">
                                            <div class="h3 text-secondary me-2">1,782</div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="text-yellow fw-semibold">Surat</div>
                                        <div class="d-flex align-items-baseline">
                                            <div class="h3 text-secondary me-2">82</div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="text-black fw-semibold">Total</div>
                                        <div class="d-flex align-items-baseline">
                                            <div class="h3 text-secondary me-2">7,646</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a class="dropdown-toggle text-secondary" id="sales-dropdown" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Select time range for sales data">Last 7 days</a>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="sales-dropdown">
                                    <a class="dropdown-item active" href="#" aria-current="true">Last 7 days</a>
                                    <a class="dropdown-item" href="#">Last 30 days</a>
                                    <a class="dropdown-item" href="#">Last 3 months</a>
                                </div>
                            </div>
                        </div>
                        <div id="chart-data-laporan" class="position-relative"></div>
                        <p class="text-secondary text-center mt-6 fst-italic">Scroll untuk zoom in/out</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="col-sm-6 col-lg-12 gap-2 d-flex flex-column">
            <div class="card">
                <div class="card-body">
                    <div class="h3 fw-bolder">Deputi 1</div>
                    <div class="row gr-2 mt-auto d-flex justify-content-between">
                        <div class="col-auto">
                            <div class="subheader">WhatsApp</div>
                            <div class="d-flex align-items-baseline">
                                <div class="h3 text-secondary me-2">6,782</div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="subheader">Tatap Muka</div>
                            <div class="d-flex align-items-baseline">
                                <div class="h3 text-secondary me-2">1,782</div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="subheader">Surat</div>
                            <div class="d-flex align-items-baseline">
                                <div class="h3 text-secondary me-2">2</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="h3 fw-bolder">Deputi 2</div>
                    <div class="row gr-2 mt-auto d-flex justify-content-between">
                        <div class="col-auto">
                            <div class="subheader">WhatsApp</div>
                            <div class="d-flex align-items-baseline">
                                <div class="h3 text-secondary me-2">6,782</div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="subheader">Tatap Muka</div>
                            <div class="d-flex align-items-baseline">
                                <div class="h3 text-secondary me-2">1,782</div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="subheader">Surat</div>
                            <div class="d-flex align-items-baseline">
                                <div class="h3 text-secondary me-2">2</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="h3 fw-bolder">Deputi 3</div>
                    <div class="row gr-2 mt-auto d-flex justify-content-between">
                        <div class="col-auto">
                            <div class="subheader">WhatsApp</div>
                            <div class="d-flex align-items-baseline">
                                <div class="h3 text-secondary me-2">6,782</div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="subheader">Tatap Muka</div>
                            <div class="d-flex align-items-baseline">
                                <div class="h3 text-secondary me-2">1,782</div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="subheader">Surat</div>
                            <div class="d-flex align-items-baseline">
                                <div class="h3 text-secondary me-2">2</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="h3 fw-bolder">Deputi 4</div>
                    <div class="row gr-2 mt-auto d-flex justify-content-between">
                        <div class="col-auto">
                            <div class="subheader">WhatsApp</div>
                            <div class="d-flex align-items-baseline">
                                <div class="h3 text-secondary me-2">6,782</div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="subheader">Tatap Muka</div>
                            <div class="d-flex align-items-baseline">
                                <div class="h3 text-secondary me-2">1,782</div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="subheader">Surat</div>
                            <div class="d-flex align-items-baseline">
                                <div class="h3 text-secondary me-2">2</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card card-md">
            <div class="card-body">
                <div id="chart-pie-deputi_1" class="position-relative h-full"></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-md">
            <div class="card-body">
                <div id="chart-pie-deputi_2" class="position-relative h-full"></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-md">
            <div class="card-body">
                <div id="chart-pie-deputi_3" class="position-relative h-full"></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-md">
            <div class="card-body">
                <div id="chart-pie-deputi_4" class="position-relative h-full"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">Locations</h3>
                <div class="ratio ratio-21x9">
                    <div>
                        <div id="map-world1" class="w-100 h-100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="row gy-3">
                    <div class="col-12 col-sm d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between">
                            <h3 class="h2">Jumlah Laporan Per Provinsi</h3>
                            <div class="dropdown">
                                <a class="dropdown-toggle text-secondary" id="sales-dropdown" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Select time range for jumlah laporan per provinsi data">Last 7 days</a>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="laporan-per-provinsi-dropdown">
                                    <a class="dropdown-item active" href="#" aria-current="true">Last 7 days</a>
                                    <a class="dropdown-item" href="#">Last 30 days</a>
                                    <a class="dropdown-item" href="#">Last 3 months</a>
                                </div>
                            </div>
                        </div>
                        <div id="chart-data-laporan-per-kategori" class="position-relative chart-lg"></div>
                    </div>
                </div>
            </div>
            <p class="text-secondary text-center fst-italic">Scroll untuk zoom in/out</p>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body mb-4">
                <div class="row gy-3">
                    <div class="col-12 col-sm d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between">
                            <h3 class="h2">Judul Pengaduan Paling Sering Diadukan</h3>
                            <div class="dropdown">
                                <a class="dropdown-toggle text-secondary" id="sales-dropdown" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Select time range for judul pengaduan tersering data">Last 7 days</a>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="judul-tersering-dropdown">
                                    <a class="dropdown-item active" href="#" aria-current="true">Last 7 days</a>
                                    <a class="dropdown-item" href="#">Last 30 days</a>
                                    <a class="dropdown-item" href="#">Last 3 months</a>
                                </div>
                            </div>
                        </div>
                        <div id="chart-data-laporan-per-judul" class="position-relative chart-lg"></div>
                    </div>
                </div>
            </div>
            <p class="text-secondary text-center fst-italic">Scroll untuk zoom in/out</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Jumlah Laporan Berdasarkan Kategori</h3>
            </div>
            <div class="row row-cards card-body">
                <div class="col-sm-6 col-lg-2">
                    <div class="card card-sm">
                        <div class="card-body d-flex align-items-center">
                            <span class="bg-primary text-white avatar me-3">
                                <i class="icon ti ti-folder"></i>
                            </span>
                            <div>
                                <div class="font-weight-medium"><a href="#">Topik Khusus</a></div>
                                <div class="text-muted">100 laporan</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <div class="card card-sm">
                        <div class="card-body d-flex align-items-center">
                            <span class="bg-primary text-white avatar me-3">
                                <i class="icon ti ti-folder"></i>
                            </span>
                            <div>
                                <div class="font-weight-medium"><a href="#">Topik Khusus</a></div>
                                <div class="text-muted">100 laporan</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <div class="card card-sm">
                        <div class="card-body d-flex align-items-center">
                            <span class="bg-primary text-white avatar me-3">
                                <i class="icon ti ti-folder"></i>
                            </span>
                            <div>
                                <div class="font-weight-medium"><a href="#">Topik Khusus</a></div>
                                <div class="text-muted">100 laporan</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <div class="card card-sm">
                        <div class="card-body d-flex align-items-center">
                            <span class="bg-primary text-white avatar me-3">
                                <i class="icon ti ti-folder"></i>
                            </span>
                            <div>
                                <div class="font-weight-medium"><a href="#">Topik Khusus</a></div>
                                <div class="text-muted">100 laporan</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <div class="card card-sm">
                        <div class="card-body d-flex align-items-center">
                            <span class="bg-primary text-white avatar me-3">
                                <i class="icon ti ti-folder"></i>
                            </span>
                            <div>
                                <div class="font-weight-medium"><a href="#">Topik Khusus</a></div>
                                <div class="text-muted">100 laporan</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <div class="card card-sm">
                        <div class="card-body d-flex align-items-center">
                            <span class="bg-primary text-white avatar me-3">
                                <i class="icon ti ti-folder"></i>
                            </span>
                            <div>
                                <div class="font-weight-medium"><a href="#">Topik Khusus</a></div>
                                <div class="text-muted">100 laporan</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-topik">
                        Lihat Semua Topik
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('modals')
    <div class="modal modal-blur fade" id="modal-topik" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Daftar Semua Topik</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-table">
                            <div class="card-header">
                                <div class="row w-full">
                                    <div class="col">
                                        <h3 class="card-title mb-0">Kategori Laporan</h3>
                                        <p class="text-secondary m-0">Topik Kategori Laporan</p>
                                    </div>
                                    <div class="col-md-auto col-sm-12">
                                        <div class="ms-auto d-flex flex-wrap btn-list">
                                            <div class="input-group input-group-flat w-auto">
                                                <span class="input-group-text">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                                <input id="advanced-table-search" type="text" class="form-control" autocomplete="off" placeholder="Cari Topik Kategori"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="advanced-table">
                                <div class="table-responsive" style="max-height: 60vh; overflow-y: auto">
                                    <table class="table table-vcenter table-selectable">
                                        <thead class="sticky-top bg-white">
                                            <tr>
                                                <th>
                                                    <button class="table-sort d-flex justify-content-between" data-sort="sort-topik">
                                                        Topik
                                                    </button>
                                                </th>
                                                <th>
                                                    <button class="table-sort d-flex justify-content-between" data-sort="sort-jumlah-laporan">
                                                        Jumlah Laporan
                                                    </button>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="table-tbody">
                                            <tr>
                                                <td class="sort-topik">
                                                    <a href="#">SP4N Lapor</a>
                                                </td>
                                                <td class="sort-jumlah-laporan">8 Laporan</td>
                                            </tr>
                                            <tr>
                                                <td class="sort-topik">
                                                    <a href="#">Bantuan Masyarakat</a>
                                                </td>
                                                <td class="sort-jumlah-laporan">13 Laporan</td>
                                            </tr>
                                            <tr>
                                                <td class="sort-topik">
                                                    <a href="#">Pendidikan dan Kebudayaan</a>
                                                </td>
                                                <td class="sort-jumlah-laporan">31 Laporan</td>
                                            </tr>
                                            <tr>
                                                <td class="sort-topik">
                                                    <a href="#">Ekonomi dan Keuangan</a>
                                                </td>
                                                <td class="sort-jumlah-laporan">18 Laporan</td>
                                            </tr>
                                            <tr>
                                                <td class="sort-topik">
                                                    <a href="#">Politik dan Hukum</a>
                                                </td>
                                                <td class="sort-jumlah-laporan">12 Laporan</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="card-footer d-flex align-items-center">
                                    <div class="dropdown">
                                        <a class="btn dropdown-toggle" data-bs-toggle="dropdown">
                                            <span id="page-count" class="me-1">20</span>
                                            <span>records</span>
                                        </a>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" onclick="setPageListItems(event)" data-value="10">10 records</a>
                                            <a class="dropdown-item" onclick="setPageListItems(event)" data-value="20">20 records</a>
                                            <a class="dropdown-item" onclick="setPageListItems(event)" data-value="50">50 records</a>
                                            <a class="dropdown-item" onclick="setPageListItems(event)" data-value="100">100 records</a>
                                        </div>
                                    </div>
                                    <ul class="pagination m-0 ms-auto">
                                        <li class="page-item disabled">
                                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                                    <path d="M15 6l-6 6l6 6" />
                                                </svg>
                                            </a>
                                        </li>
                                        <li class="page-item"><a class="page-link" href="#">1</a></li>
                                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                                        <li class="page-item"><a class="page-link" href="#">4</a></li>
                                        <li class="page-item"><a class="page-link" href="#">5</a></li>
                                        <li class="page-item">
                                            <a class="page-link" href="#">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                                    <path d="M9 6l6 6l-6 6" />
                                                </svg>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_scripts')
<script src="{{ asset('tabler/libs/apexcharts/dist/apexcharts.min.js') }}" defer></script>
<script src="{{ asset('tabler/libs/jsvectormap/dist/jsvectormap.min.js') }}" defer></script>
<script src="{{ asset('tabler/libs/jsvectormap/dist/maps/world.js') }}" defer></script>
<script src="{{ asset('tabler/libs/jsvectormap/dist/maps/world-merc.js') }}" defer></script>

<script src="{{ asset('tabler/js/chart-data/chart-data-laporan.js') }}" defer></script>
<script src="{{ asset('tabler/js/chart-data/chart-data-laporan-per-kategori.js') }}" defer></script>
<script src="{{ asset('tabler/js/chart-data/chart-data-laporan-per-judul.js') }}" defer></script>
<script src="{{ asset('tabler/js/chart-data/chart-map-data.js') }}" defer></script>
<script src="{{ asset('tabler/js/chart-data/chart-pie-deputi.js') }}" defer></script>
<script>
    const advancedTable = {
        headers: [
            { "data-sort": "sort-topik", name: "Topik" },
            { "data-sort": "sort-jumlah-laporan", name: "Jumlah Laporan" },
        ],
    };
    const setPageListItems = (e) => {
        window.tabler_list["advanced-table"].page = parseInt(e.target.dataset.value);
        window.tabler_list["advanced-table"].update();
        document.querySelector("#page-count").innerHTML = e.target.dataset.value;
    };
    window.tabler_list = window.tabler_list || {};
    document.addEventListener("DOMContentLoaded", function () {
        const list = (window.tabler_list["advanced-table"] = new List("advanced-table", {
            sortClass: "table-sort",
            listClass: "table-tbody",
            page: parseInt("20"),
            pagination: {
                item: (value) => {
                    return `<li class="page-item"><a class="page-link cursor-pointer">${value.page}</a></li>`;
                },
                innerWindow: 1,
                outerWindow: 1,
                left: 0,
                right: 0,
            },
            valueNames: advancedTable.headers.map((header) => header["data-sort"]),
        }));
        const searchInput = document.querySelector("#advanced-table-search");
        if (searchInput) {
            searchInput.addEventListener("input", () => {
                list.search(searchInput.value);
                toggleEmptyRow(list);
            });
        }
        function ensureEmptyRow() {
            const tbody = document.querySelector("#advanced-table .table-tbody") || document.querySelector(".table-tbody");
            let emptyRow = tbody.querySelector("tr.empty-row");
            if (!emptyRow) {
                const colCount =
                    document.querySelector("#advanced-table thead tr")?.children.length ||
                    advancedTable.headers.length ||
                    1;
                emptyRow = document.createElement("tr");
                emptyRow.className = "empty-row";
                const td = document.createElement("td");
                td.colSpan = colCount;
                td.className = "text-center text-muted p-4";
                td.innerHTML = `
                <div class="d-flex flex-column align-items-center gap-1">
                    <div style="font-size: 2rem; line-height: 1;">😕</div>
                    <div><strong>Tidak ada data</strong></div>
                    <div class="small">Coba ubah filter atau kata kunci pencarian.</div>
                </div>
                `;
                emptyRow.appendChild(td);
                tbody.appendChild(emptyRow);
            }
            return emptyRow;
        }

        function toggleEmptyRow(list) {
            const emptyRow = ensureEmptyRow();
            const hasNoItems = list.matchingItems.length === 0;
            emptyRow.style.display = hasNoItems ? "" : "none";
            const paginationEl = document.querySelector("#advanced-table-pagination") || document.querySelector("#advanced-table .pagination") || document.querySelector(".pagination");
            if (paginationEl) {
                paginationEl.style.visibility = hasNoItems ? "hidden" : "visible";
            }
        }
        toggleEmptyRow(list);
    });
</script>
@endsection