@extends('layouts.app')
@inject('Str', 'Illuminate\Support\Str')

@section('title', 'Dashboard')
@section('styles')
    <link href="{{ asset('tabler/libs/jsvectormap/dist/jsvectormap.css') }}" rel="stylesheet" />
@endsection

@section('page_header')
    {{-- PAGE HEADER DINAMIS (Mengandung Greetings dan Kartu Statistik) --}}
    @php
        use Illuminate\Support\Facades\Auth;
        
        $hour = now()->format('H');
        if ($hour >= 5 && $hour < 12) {
            $greeting = 'Selamat Pagi';
        } elseif ($hour >= 12 && $hour < 17) {
            $greeting = 'Selamat Siang';
        } elseif ($hour >= 17 && $hour < 20) {
            $greeting = 'Selamat Sore';
        } else {
            $greeting = 'Selamat Malam';
        }
        $userName = Auth::user()->name ?? 'Pengguna';
        $userRole = Auth::user()->getRoleNames()->first() ?? 'User';
        
        // --- LOGIKA KARTU STATISTIK (Disederhanakan untuk View) ---
        $isSuperAdminOrAdmin = Auth::user()->hasAnyRole(['superadmin', 'admin']);
        $isDeputyOrAsdep = Auth::user()->hasAnyRole(['deputy', 'asdep_karo']);
        
        // Kartu 1: Pending/Belum
        if ($isSuperAdminOrAdmin) {
            $card1Title = 'Belum Terdistribusi';
            $card1Value = $reportStats['undistributed_count'] ?? 0;
            $card1Color = 'danger';
        } elseif ($isDeputyOrAsdep) {
            $card1Title = 'Belum Di-Disposisi';
            $card1Value = $reportStats['undisposed_count'] ?? 0;
            $card1Color = 'warning';
        } else {
            $card1Title = null;
        }

        // Kartu 2: Selesai/Sudah
        if ($isSuperAdminOrAdmin) {
            $card2Title = 'Sudah Terdistribusi';
            $card2Value = $reportStats['distributed_count'] ?? 0;
            $card2Color = 'success';
        } elseif ($isDeputyOrAsdep) {
            $card2Title = 'Sudah Di-Disposisi';
            $card2Value = $reportStats['disposed_count'] ?? 0;
            $card2Color = 'primary';
        } else {
            $card2Title = null;
        }
    @endphp

    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-4 col-lg-4 col-xl-4">
                    <div class="page-pretitle">{{ $greeting }}, {{ $userName }}</div>
                    <h2 class="page-title">Dashboard Lapor Mas Wapres!</h2>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
<div class="row row-deck row-cards">
    <div class="col-12">
        <div class="row row-deck row-cards">
            <div class="col-lg-12 col-xl-9">
                <div class="card">
                    <div class="card-body">
                        <div class="row gy-3">
                            <div class="col-12 col-sm d-flex flex-column">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div class="d-flex flex-column">
                                        <h3 class="h2">Total Data Laporan</h3>
                                        <div class="row gr-5 gy-2 mb-6 mt-auto">
                                            {{-- Data Source (WA, Tatap Muka, Surat, Total) --}}
                                            <div class="col-auto">
                                                <div class="text-green fw-semibold">WhatsApp</div>
                                                <div class="d-flex align-items-baseline">
                                                    <div class="h3 text-secondary me-2">{{ number_format($reportStats['whatsapp'] ?? 0, 0, ',', '.') }}</div>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="text-primary fw-semibold">Tatap Muka</div>
                                                <div class="d-flex align-items-baseline">
                                                    <div class="h3 text-secondary me-2">{{ number_format($reportStats['tatap muka'] ?? 0, 0, ',', '.') }}</div>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="text-yellow fw-semibold">Surat</div>
                                                <div class="d-flex align-items-baseline">
                                                    <div class="h3 text-secondary me-2">{{ number_format($reportStats['surat fisik'] ?? 0, 0, ',', '.') }}</div>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="text-black fw-semibold">Total</div>
                                                <div class="d-flex align-items-baseline">
                                                    <div class="h3 text-secondary me-2">{{ number_format($reportStats['total'] ?? 0, 0, ',', '.') }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Dropdown Filter Waktu Dinamis --}}
                                    <div class="dropdown">
                                        <a class="dropdown-toggle text-secondary" id="sales-dropdown" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Select time range for sales data">
                                            {{ $currentRange ?? 'Last 7 days' }}
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="sales-dropdown">
                                            @php
                                                $defaultKey = 'total';
                                                $currentActiveKey = $currentRangeKey ?? $defaultKey;
                                            @endphp
                                            <a class="dropdown-item @if($currentActiveKey == '7_days') active @endif" href="{{ route('dashboard', ['range' => '7_days']) }}" aria-current="{{ $currentActiveKey == '7_days' ? 'true' : 'false' }}">Last 7 days</a>
                                            <a class="dropdown-item @if($currentActiveKey == '30_days') active @endif" href="{{ route('dashboard', ['range' => '30_days']) }}" aria-current="{{ $currentActiveKey == '30_days' ? 'true' : 'false' }}">Last 30 days</a>
                                            <a class="dropdown-item @if($currentActiveKey == '3_months') active @endif" href="{{ route('dashboard', ['range' => '3_months']) }}" aria-current="{{ $currentActiveKey == '3_months' ? 'true' : 'false' }}">Last 3 months</a>
                                            <a class="dropdown-item @if($currentActiveKey == '1_years') active @endif" href="{{ route('dashboard', ['range' => '1_years']) }}" aria-current="{{ $currentActiveKey == '1_years' ? 'true' : 'false' }}">Last 1 years</a>
                                            <a class="dropdown-item @if($currentActiveKey == 'total') active @endif" href="{{ route('dashboard', ['range' => 'total']) }}" aria-current="{{ $currentActiveKey == 'total' ? 'true' : 'false' }}">Total Semua Data</a>
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
            
            <div class="col-sm-6 col-md-12 col-lg-12 col-xl-3">
                @hasanyrole(['superadmin', 'admin', 'deputy', 'asdep_karo'])
                    <div class="row row-cards g-3">
                        
                        {{-- 1. CARD STATUS (DISPOSISI/DISTRIBUSI) --}}
                        @if ($card1Title || $card2Title)
                        <div class="col-12">
                            <div class="row row-cards g-3">
                                @if ($card1Title)
                                {{-- MENGUBAH LEBAR DARI col-lg-6 MENJADI col-12 --}}
                                <div class="col-12"> 
                                    <div class="card card-sm h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <span class="avatar avatar-md bg-{{ $card1Color }}-lt me-3"><i class="ti ti-alert-triangle text-{{ $card1Color }}"></i></span>
                                                <div>
                                                    <div class="font-weight-medium">{{ $card1Title }}</div>
                                                    <div class="text-secondary">{{ number_format($card1Value, 0, ',', '.') }} Laporan</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if ($card2Title)
                                {{-- MENGUBAH LEBAR DARI col-lg-6 MENJADI col-12 --}}
                                <div class="col-12">
                                    <div class="card card-sm h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <span class="avatar avatar-md bg-{{ $card2Color }}-lt me-3"><i class="ti ti-file-check text-{{ $card2Color }}"></i></span>
                                                <div>
                                                    <div class="font-weight-medium">{{ $card2Title }}</div>
                                                    <div class="text-secondary">{{ number_format($card2Value, 0, ',', '.') }} Laporan</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        @hasanyrole(['superadmin', 'admin'])
                            {{-- KONDISI 1: SUPERADMIN / ADMIN (Melihat Total Semua Deputi) --}}
                            <div class="col-12 mt-3"> {{-- Tambahkan margin-top agar tidak menempel pada card di atasnya --}}
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="h5 fw-bolder">Status Pengaduan (Total Semua Deputi)</div>
                                        
                                        <div class="d-flex justify-content-center align-items-center" style="min-height: 250px;">
                                            <div id="chart-pie-total-complaint-status" style="width: 100%;"></div> 
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endhasanyrole

                        @hasanyrole(['deputy', 'asdep_karo'])
                            <div class="col-12 mt-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="h5 fw-bolder">Status Pengaduan</div>
                                        <div class="d-flex justify-content-center align-items-center" style="min-height: 250px;">
                                            <div id="chart-pie-deputy-status" style="width: 100%;">
                                                @if (isset($userDeputyPieChartDataJson) && strlen($userDeputyPieChartDataJson) > 20)
                                                    {{-- Placeholder akan diisi JS --}}
                                                @else
                                                    <div class="alert alert-info text-center">Data status laporan untuk Divisi Anda tidak ditemukan.</div>
                                                @endif
                                            </div> 
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endhasanyrole
                    </div>
                @endhasanyrole

                {{-- MINI-DATATABLE: MONITORING (ANALYST) --}}
                @hasrole('analyst')
                    <div class="card card-body-scrollable h-100 mt-0"> {{-- Hapus margin top 3 dan set mt-0 (atau hapus mt-3) --}}
                        <div class="card-header border-0">
                            <h3 class="card-title h4">Tugas Pending Anda ({{ $reportStats['pending_assignment'] ?? 0 }})</h3>
                            <div class="card-options">
                                <a href="{{ route('reports.index', ['status' => 'pending']) }}" class="btn btn-sm btn-warning-outline">Lihat Semua</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            @if ($pendingAssignments->isEmpty())
                                <div class="text-center text-muted py-5">🚀 Tidak ada tugas pending saat ini.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table card-table table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>Tiket</th>
                                                <th>Kategori</th>
                                                <th>Status</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($pendingAssignments as $report)
                                                <tr>
                                                    <td class="text-secondary">{{ $report->ticket_number ?? $report->id }}</td>
                                                    <td>{{ $report->category->name ?? '-' }}</td>
                                                    <td><span class="badge bg-warning-lt">Pending</span></td>
                                                    <td class="text-end">
                                                        <a href="{{ route('reports.show', ['uuid' => $report->uuid]) }}" class="btn btn-sm btn-ghost-warning">Kerjakan</a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                        @if ($reportStats['pending_assignment'] > 7)
                            <div class="card-footer text-center border-0">
                                <small class="text-muted">Menampilkan 7 dari {{ $reportStats['pending_assignment'] }} tugas.</small>
                            </div>
                        @endif
                    </div>
                @endhasrole
            </div>
        </div>
    </div>
    
    {{-- BARIS 2: CARD DEPUTI (SEMUA CARD DEPUTI DI BAWAH CHART UTAMA) --}}
    <div class="col-12 mt-3">
        @hasanyrole(['superadmin', 'admin'])
            <div class="row row-cards g-3">
                {{-- LIST CARD STATISTIK PER DEPUTI (Menggunakan Loop yang Membagi Kolom) --}}
                @forelse ($deputyStats as $deputy)
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                {{-- Nama Deputi (Fleksibel, bisa membungkus) --}}
                                <div class="h5 fw-bolder text-center mb-3">{{ $deputy['name'] }}</div>
                                
                                {{-- BARIS ANGKA: Menggunakan GRID TETAP col-4 (4+4+4=12) --}}
                                {{-- Hapus 'justify-content-between' dan 'gr-2' --}}
                                <div class="row mt-auto"> 
                                    
                                    {{-- 1. WhatsApp --}}
                                    {{-- Menggunakan col-4 --}}
                                    <div class="col-4 text-center"> 
                                        <div class="subheader">WhatsApp</div>
                                        <div class="d-flex flex-column align-items-center">
                                            {{-- Tambahkan text-center/align-items-center jika Anda ingin angka benar-benar di tengah --}}
                                            <div class="h3 text-secondary">{{ number_format($deputy['counts']['whatsapp'] ?? 0, 0, ',', '.') }}</div>
                                        </div>
                                    </div>
                                    
                                    {{-- 2. Tatap Muka --}}
                                    <div class="col-4 text-center">
                                        <div class="subheader">Tatap Muka</div>
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="h3 text-secondary">{{ number_format($deputy['counts']['tatap muka'] ?? 0, 0, ',', '.') }}</div>
                                        </div>
                                    </div>
                                    
                                    {{-- 3. Surat --}}
                                    <div class="col-4 text-center">
                                        <div class="subheader">Surat</div>
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="h3 text-secondary">{{ number_format($deputy['counts']['surat fisik'] ?? 0, 0, ',', '.') }}</div>
                                        </div>
                                    </div>
                                </div>
                                @php
                                    $total = ($deputy['counts']['whatsapp'] ?? 0) + 
                                            ($deputy['counts']['tatap muka'] ?? 0) + 
                                            ($deputy['counts']['surat fisik'] ?? 0);
                                @endphp

                                <div class="row pt-3 mt-3 border-top"> 
                                    <div class="col-12 text-center">
                                        <div class="subheader">Total Semua Sumber</div>
                                        {{-- Menggunakan text-primary/text-success untuk menonjolkan Total --}}
                                        <div class="h2 text-success">{{ number_format($total, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12"><div class="alert alert-info">Tidak ada data Deputi yang ditemukan.</div></div>
                @endforelse
            </div>
        @endhasanyrole
    </div>
            
    <div class="col-12">
        @hasanyrole(['superadmin', 'admin'])
            <div class="row row-cards">
                @if (isset($deputyPieChartDataJson) && count(json_decode($deputyPieChartDataJson, true)) > 0)
                    @php
                        $deputyCharts = json_decode($deputyPieChartDataJson, true);
                    @endphp
                    @foreach ($deputyCharts as $key => $data)
                        {{-- Key adalah deputi_1, deputi_2, dst --}}
                        <div class="col-sm-6 col-lg-3">
                            {{-- Min-height menjaga tinggi card agar seragam --}}
                            <div class="card card-md" style="min-height: 300px;"> 
                                <div class="card-body d-flex flex-column">
                                    <div 
                                        class="h5 fw-bolder text-center mb-3" 
                                        title="{{ $data['title'] }}"
                                        style="
                                            overflow: hidden;
                                            display: -webkit-box;
                                            -webkit-line-clamp: 2;
                                            -webkit-box-orient: vertical;
                                            height: 3rem;
                                        "
                                    >
                                        {{ $data['title'] }}
                                    </div>
                                    <div id="chart-pie-{{ $key }}" class="position-relative flex-grow-1" style="min-height: 200px;"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="col-12"><div class="alert alert-info">Tidak ada data status laporan untuk dirender per Deputi.</div></div>
                @endif
            </div>
        @endhasanyrole
    </div>
    <div class="col-12 mt-3">
        <div class="row row-deck row-cards g-3">
            <div class="col-lg-6">
                
                @hasanyrole(['superadmin', 'admin'])
                    {{-- 1. SEBARAN LAPORAN (PETA) untuk ADMIN/SUPERADMIN --}}
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="h4">Sebaran Laporan Berdasarkan Provinsi</h4>
                            <div class="ratio ratio-21x9">
                                <div>
                                    <div id="map-indonesia" class="w-100 h-100"></div> 
                                </div>
                            </div>
                        </div>
                    </div>
                @endhasanyrole

                @hasanyrole(['deputy', 'asdep_karo'])
                    {{-- 2. MINI-DATATABLE: TINJAUAN LAPORAN DISERAHKAN (SUBMITTED) untuk DEPUTY/ASDEP --}}
                    <div class="card card-body-scrollable h-100">
                        <div class="card-header border-0">
                            <h3 class="card-title h4">Tinjauan Cepat Laporan Diserahkan ({{ $reportStats['submitted'] ?? 0 }})</h3>
                            <div class="card-options">
                                <a href="{{ route('reports.index', ['status' => 'submitted']) }}" class="btn btn-sm btn-danger-outline">Lihat Semua</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            @if ($submittedReports->isEmpty())
                                <div class="text-center text-muted py-5">🎉 Tidak ada laporan submitted yang perlu ditinjau.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table card-table table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>Tiket</th>
                                                <th>Kategori</th>
                                                <th>Status</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($submittedReports as $report)
                                                <tr>
                                                    <td class="text-secondary">{{ $report->ticket_number ?? $report->id }}</td>
                                                    <td>{{ $report->category->name ?? '-' }}</td>
                                                    <td><span class="badge bg-danger-lt">Submitted</span></td>
                                                    <td class="text-end">
                                                        <a href="{{ route('reports.show', ['uuid' => $report->uuid]) }}" class="btn btn-sm btn-ghost-primary">Tinjau</a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                        @if ($reportStats['submitted'] > 5)
                            <div class="card-footer text-center border-0">
                                <small class="text-muted">Menampilkan 5 dari {{ $reportStats['submitted'] }} laporan.</small>
                            </div>
                        @endif
                    </div>
                @endhasanyrole

                @hasrole('analyst')
                    {{-- 3. MATRIX JUMLAH LAPORAN BERDASARKAN KATEGORI untuk ANALYST --}}
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Matrix Jumlah Laporan Berdasarkan Kategori</h3>
                        </div>
                        <div class="row row-cards card-body">
                            @php
                                $maxDisplay = min(count($categoryStats), 11);
                                $tilesToShow = array_slice($categoryStats, 0, $maxDisplay);
                                $showViewAll = count($categoryStats) > $maxDisplay;
                            @endphp

                            @forelse ($tilesToShow as $index => $stat)
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-4"> 
                                    <div class="card card-sm h-100">
                                        <div class="card-body d-flex align-items-center p-3">
                                            <div style="min-width: 0;"> 
                                                <div class="font-weight-medium text-truncate" style="max-width: 100%; line-height: 1.2;">
                                                    
                                                    <button 
                                                        class="text-body text-decoration-none text-truncate btn btn-link p-0 m-0 border-0 text-start open-category-detail"
                                                        data-category-id="{{ $stat['id'] }}"
                                                        data-category-name="{{ $stat['name'] }}"
                                                        data-total="{{ $stat['total'] }}"
                                                        data-children="{{ json_encode($stat['children']) }}"
                                                        type="button" 
                                                    >
                                                        {{ $stat['name'] }}
                                                    </button>
                                                </div>
                                                <div class="text-muted">{{ $stat['total'] }} laporan</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-center text-muted p-4">Tidak ada laporan yang diklasifikasikan dalam cakupan Anda.</div>
                            @endforelse

                            @if ($showViewAll)
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-4">
                                    <div class="card card-sm h-100 d-flex align-items-center justify-content-center">
                                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-category-all">
                                            Lihat Semua ({{ count($categoryStats) }})
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endhasrole
            </div>
            
            {{-- BLOK KANAN (col-lg-6) --}}
            @hasanyrole(['superadmin', 'admin', 'deputy', 'asdep_karo', 'analyst'])
                <div class="col-lg-6"> 
                    
                    {{-- 4. CHART KATEGORI (Top 10 Category) untuk SEMUA ROLE (Kiri Atas) --}}
                    <div class="card h-100">
                        <div class="card-body mb-4">
                            <div class="row gy-3">
                                <div class="col-12 col-sm d-flex flex-column">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <h4 class="h4">Kategori Laporan Paling Sering Diadukan</h4> 
                                        <div class="dropdown">
                                            <a class="dropdown-toggle text-secondary" id="judul-tersering-dropdown" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Select time range for judul pengaduan tersering data">{{ $currentSubjectRangeLabel }}</a>
                                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="judul-tersering-dropdown">
                                                @php
                                                    $defaultKey = 'total';
                                                    $currentActiveKey = $currentRangeKey ?? $defaultKey;
                                                @endphp
                                                <a class="dropdown-item @if($currentActiveKey == '7_days') active @endif" href="{{ route('dashboard', ['range' => '7_days']) }}" aria-current="{{ $currentActiveKey == '7_days' ? 'true' : 'false' }}">Last 7 days</a>
                                                <a class="dropdown-item @if($currentActiveKey == '30_days') active @endif" href="{{ route('dashboard', ['range' => '30_days']) }}" aria-current="{{ $currentActiveKey == '30_days' ? 'true' : 'false' }}">Last 30 days</a>
                                                <a class="dropdown-item @if($currentActiveKey == '3_months') active @endif" href="{{ route('dashboard', ['range' => '3_months']) }}" aria-current="{{ $currentActiveKey == '3_months' ? 'true' : 'false' }}">Last 3 months</a>
                                                <a class="dropdown-item @if($currentActiveKey == '1_years') active @endif" href="{{ route('dashboard', ['range' => '1_years']) }}" aria-current="{{ $currentActiveKey == '1_years' ? 'true' : 'false' }}">Last 1 years</a>
                                                <a class="dropdown-item @if($currentActiveKey == 'total') active @endif" href="{{ route('dashboard', ['range' => 'total']) }}" aria-current="{{ $currentActiveKey == 'total' ? 'true' : 'false' }}">Total Semua Data</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="chart-kategori" class="position-relative chart-lg"></div>
                                </div>
                            </div>
                        </div>
                        <p class="text-secondary text-center fst-italic">Top 10 Kategori Paling Sering dalam rentang waktu yang dipilih.</p>
                    </div>
                </div>
            @endhasanyrole
            
            {{-- FALLBACK / OTHER ROLE --}}
            @if (!Auth::user()->hasAnyRole(['superadmin', 'admin', 'deputy', 'asdep_karo', 'analyst']))
                <div class="col-12"><div class="card h-100"><div class="card-body text-center text-muted">Akses informasi tambahan tidak tersedia untuk peran Anda.</div></div></div>
            @endif
            
        </div>
    </div>
    @hasanyrole(['superadmin', 'admin', 'deputy', 'asdep_karo'])
    <div class="col-md-6 col-lg-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Matrix Jumlah Laporan Berdasarkan Kategori</h3>
            </div>
            <div class="row row-cards card-body">
                {{-- TILE 11 TERATAS DAN 1 TOMBOL 'LIHAT SEMUA' --}}
                @php
                    $maxDisplay = min(count($categoryStats), 11);
                    $tilesToShow = array_slice($categoryStats, 0, $maxDisplay);
                    $showViewAll = count($categoryStats) > $maxDisplay;
                    
                    $colors = ['bg-primary-lt', 'bg-info-lt', 'bg-warning-lt', 'bg-danger-lt', 'bg-success-lt'];
                @endphp

                @forelse ($tilesToShow as $index => $stat)
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <div class="card card-sm h-100" style="min-height: 100px;">
                            <div class="card-body d-flex align-items-center p-3">
                                <div style="min-width: 0;"> 
                                    <div class="font-weight-medium text-truncate" style="max-width: 100%; line-height: 1.2;">
                                        
                                        <button 
                                            class="text-body text-decoration-none text-truncate btn btn-link p-0 m-0 border-0 text-start open-category-detail"
                                            data-category-id="{{ $stat['id'] }}"
                                            data-category-name="{{ $stat['name'] }}"
                                            data-total="{{ $stat['total'] }}"
                                            data-children="{{ json_encode($stat['children']) }}"
                                            type="button" 
                                        >
                                            {{ $stat['name'] }}
                                        </button>
                                    </div>
                                    <div class="text-muted">{{ $stat['total'] }} laporan</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center text-muted p-4">Tidak ada laporan yang diklasifikasikan dalam cakupan Anda.</div>
                @endforelse

                @if ($showViewAll)
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <div class="card card-sm h-100 d-flex align-items-center justify-content-center">
                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-category-all">
                                Lihat Semua ({{ count($categoryStats) }})
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endhasanyrole
    <div class="col-12 mt-3">
        @hasanyrole(['deputy', 'asdep_karo'])
            <div class="card card-body-scrollable h-100">
                <div class="card-header border-0">
                    <h3 class="card-title h4">Monitoring Analis di Unit Kerja Anda ({{ $currentRange ?? 'Total' }})</h3>
                </div>
                
                <div class="card-body p-0">
                    @if (empty($analystMonitoringData))
                        <div class="text-center text-muted py-5">Tidak ada Analis atau Laporan yang ditugaskan dalam cakupan Anda.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table card-table table-vcenter">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th>Analis / Unit Kerja</th>
                                        <th class="text-center">Total Tugas</th>
                                        {{-- <th>Status Tugas</th>
                                        <th class="w-1">Aksi</th> --}}
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($analystMonitoringData as $unitName => $analysts)
                                        {{-- Group Header (Jika user adalah Deputi) --}}
                                        @if (Auth::user()->hasRole('deputy'))
                                        <tr class="table-secondary fw-bold">
                                            <td colspan="4" class="py-1">{{ $unitName }}</td>
                                        </tr>
                                        @endif

                                        @foreach ($analysts as $analyst)
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $analyst['name'] }}</div>
                                                @if (!Auth::user()->hasRole('deputy'))
                                                    <div class="text-muted small">{{ $unitName }}</div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary-lt">{{ $analyst['total'] }}</span>
                                            </td>
                                            {{-- <td class="text-center">
                                                @if ($analyst['total'] > 0)
                                                    <button 
                                                        type="button" 
                                                        class="badge bg-primary-lt"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modal-analyst-status-detail" 
                                                        data-analyst-name="{{ $analyst['name'] }}"
                                                        data-analyst-total="{{ $analyst['total'] }}"
                                                        data-statuses="{{ htmlspecialchars(json_encode($analyst['statuses']), ENT_QUOTES, 'UTF-8') }}"
                                                        onclick="loadAnalystStatusDetail(this)"
                                                    >
                                                        {{ $analyst['total'] }} Laporan
                                                    </button>
                                                @else
                                                    <span class="badge bg-secondary-lt">0</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('reports.index', ['filterDisposisi' => $analyst['name']]) }}" class="btn btn-sm btn-ghost-primary">Filter</a>
                                            </td> --}}
                                        </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endhasanyrole
    </div>
</div>

{{-- MODAL: RINCIAN STATUS PER ANALIS --}}
@parent
<div class="modal modal-blur fade" id="modal-analyst-status-detail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rincian Status Laporan: <span id="analyst-name-span"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h4 class="mb-3">Total Tugas: <span id="analyst-total-span" class="badge bg-primary">0</span></h4>
                <p class="text-muted">Rincian laporan berdasarkan status saat ini:</p>
                
                <ul class="list-group list-group-flush" id="analyst-status-list">
                    {{-- Konten rincian status akan diisi oleh JavaScript --}}
                </ul>
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@parent
    {{-- MODAL: DETAIL KATEGORI (PARENT & CHILD) --}}
    <div class="modal modal-blur fade" id="modal-category-detail" tabindex="-1" aria-hidden="true">
        {{-- Struktur modal tetap sama --}}
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="category-modal-title">Detail Kategori: <span id="category-name-span"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4 class="mb-3">Total Laporan: <span id="category-total-span" class="badge bg-primary-lt">0</span></h4>
                    <p class="text-muted">Rincian Sub-Kategori yang termasuk dalam klaster ini:</p>
                    
                    <div class="table-responsive">
                        <table class="table table-vcenter table-striped">
                            <thead>
                                <tr>
                                    <th>Sub-Kategori</th>
                                    <th class="w-1">Jumlah</th>
                                    <th class="w-1">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="sub-category-list">
                                {{-- Konten akan diisi oleh JavaScript --}}
                            </tbody>
                        </table>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <a id="btn-view-reports" href="#" class="btn btn-primary me-auto">
                        <i class="ti ti-list me-2"></i> Lihat Daftar Laporan
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    
    {{-- MODAL : DAFTAR SEMUA TOPIK (Diperbaiki untuk Hirarki dan Dilipat) --}}
    <div class="modal modal-blur fade" id="modal-category-all" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Daftar Semua Topik & Sub-Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="card-table">
                        <div class="mb-3">
                            {{-- Input pencarian di dalam modal --}}
                            <input type="text" class="form-control" placeholder="Cari Topik atau Sub-Kategori..." id="modal-category-all-search">
                        </div>
                        
                        <div class="table-responsive" style="max-height: 60vh; overflow-y: auto">
                            <table class="table table-vcenter table-selectable card-table">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th style="width: 5%;"></th>
                                        <th>Topik</th>
                                        <th class="w-1">Jumlah Laporan</th>
                                        <th class="w-1">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="list">
                                    @php $index_id = 0; @endphp
                                    @forelse ($categoryStats as $stat)
                                        @php $index_id++; @endphp
                                        
                                        {{-- BARIS KATEGORI UTAMA (PARENT) --}}
                                        <tr class="table-primary fw-bold category-parent" 
                                            data-toggle-id="sub-list-{{ $index_id }}"
                                            style="cursor: pointer;"
                                        >
                                            <td>
                                                {{-- Ikon panah yang akan berputar --}}
                                                <i class="ti ti-chevron-right toggle-icon" id="icon-{{ $index_id }}"></i>
                                            </td>
                                            <td class="sort-topik">{{ $stat['name'] }}</td>
                                            <td class="sort-jumlah-laporan">{{ $stat['total'] }} Laporan</td>
                                            <td>
                                                <a href="{{ route('reports.index', ['filterKategori' => $stat['name']]) }}" class="btn btn-sm btn-ghost-primary">Lihat</a>
                                            </td>
                                        </tr>
                                        
                                        {{-- SUB-KATEGORI (CHILDREN) - Disembunyikan secara default --}}
                                        <tr class="sub-category-row" id="sub-list-{{ $index_id }}" style="display: none;">
                                            <td colspan="4" class="p-0 border-top-0">
                                                <table class="table table-vcenter table-sm m-0 border-0">
                                                    <tbody>
                                                        @forelse ($stat['children'] as $childStat)
                                                            <tr class="data-child-row">
                                                                <td style="width: 5%; padding-left: 2rem;"></td>
                                                                <td>
                                                                    <i class="ti ti-arrow-merge-right me-2 text-secondary"></i> {{ $childStat['name'] }}
                                                                </td>
                                                                <td class="w-1">{{ $childStat['total'] }} Laporan</td>
                                                                <td class="w-1">
                                                                    <a href="{{ route('reports.index', ['filterKategori' => $childStat['name']]) }}" class="btn btn-sm btn-ghost-secondary">Lihat</a>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr class="data-child-row">
                                                                <td colspan="4" class="text-center text-muted py-2">Tidak ada Sub-Kategori.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted p-4">Tidak ada kategori laporan yang tersedia.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('tabler/libs/jsvectormap/dist/jsvectormap.min.js') }}"></script>
<script src="{{ asset('tabler/js/indonesia.js') }}"></script>
<script>
    // Fungsi untuk menyembunyikan modal
    function hideModal(modalElement) {
        if (modalElement) {
            modalElement.classList.remove('show');
            modalElement.setAttribute('aria-modal', 'false');
            modalElement.setAttribute('role', '');
            
            const backdrop = document.getElementById('custom-modal-backdrop');

            setTimeout(() => {
                modalElement.style.display = 'none';
                document.body.classList.remove('modal-open');
                if (backdrop) {
                    backdrop.remove();
                }
            }, 300); 
        }
    }

    // Fungsi untuk menampilkan dan mengisi modal Detail Kategori
    function loadCategoryDetail(stat) {
        
        const modalElement = document.getElementById('modal-category-detail');
        
        if (!modalElement) {
            console.error("Kesalahan: Elemen modal 'modal-category-detail' tidak ditemukan.");
            return;
        }

        // --- 1. Update konten modal ---
        const titleSpan = modalElement.querySelector('#category-name-span');
        const totalSpan = modalElement.querySelector('#category-total-span');
        const viewReportsBtn = modalElement.querySelector('#btn-view-reports');
        const tbody = modalElement.querySelector('#sub-category-list');

        if (titleSpan) titleSpan.textContent = stat.name;
        if (totalSpan) totalSpan.textContent = `${stat.total} Laporan`;
        if (viewReportsBtn) viewReportsBtn.href = `/admin/reports?filterKategori=${encodeURIComponent(stat.name)}`; 
        
        if (tbody) { 
            tbody.innerHTML = ''; 
            
            if (stat.children && stat.children.length > 0) {
                stat.children.forEach(child => {
                    const row = document.createElement('tr');
                    const linkHref = `/admin/reports?filterKategori=${encodeURIComponent(child.name)}`; 
                    
                    row.innerHTML = `
                        <td><i class="ti ti-file-text me-2 text-muted"></i> ${child.name}</td>
                        <td><span class="badge bg-secondary-lt">${child.total}</span></td>
                        <td>
                            <a href="${linkHref}" class="btn btn-sm btn-outline-primary">Lihat</a>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                const row = document.createElement('tr');
                row.innerHTML = `<td colspan="3" class="text-center text-muted">Tidak ada Sub-Kategori dengan laporan.</td>`;
                tbody.appendChild(row);
            }
        }

        // --- 2. PURE JS: Menampilkan Modal dengan memanipulasi Class CSS ---
        modalElement.style.display = 'block';
        modalElement.setAttribute('aria-modal', 'true');
        modalElement.setAttribute('role', 'dialog');
        
        setTimeout(() => {
            modalElement.classList.add('show');
            document.body.classList.add('modal-open');
            
            if (!document.getElementById('custom-modal-backdrop')) {
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.id = 'custom-modal-backdrop';
                document.body.appendChild(backdrop);
            }

        }, 10); 
    }
        
    document.addEventListener("DOMContentLoaded", function () {
        // --- DATA INJEKSI JSON (CLEANED) ---
        const safeJsonParse = (jsonString) => {
            try {
                const cleanedString = jsonString
                    .replace(/<script\b[^>]*>([\s\S]*?)<\/script>/gim, "")
                    .replace(/&quot;/g, '"')
                    .replace(/&#39;/g, "'");
                
                // Gunakan JSON.parse. Jika hasilnya null, kembalikan objek kosong
                const parsed = JSON.parse(cleanedString);
                return (typeof parsed === 'object' && parsed !== null) ? parsed : {}; 
            } catch (e) {
                console.error("Error parsing JSON:", e, jsonString);
                return {};
            }
        };

        const categoryStatsData = safeJsonParse('{!! json_encode($categoryStats) !!}');
        const dailyChartData = safeJsonParse('{!! $chartDataJson !!}');
        const deputyPieData = safeJsonParse('{!! $deputyPieChartDataJson !!}');
        const userDeputyPieData = safeJsonParse('{!! $userDeputyPieChartDataJson !!}');
        const mapData = safeJsonParse('{!! $mapDataJson !!}');
        const mapNames = safeJsonParse('{!! $mapNamesJson !!}') || {};
        const totalComplaintStatusPieData = safeJsonParse('{!! $totalStatusPieDataJson !!}');

        let currentMapInstance = null;

        function destroyCurrentMapInstance() {
            if (currentMapInstance && typeof currentMapInstance.destroy === 'function') {
                currentMapInstance.destroy();
            }
            currentMapInstance = null;
        }
        
        const renderDailyTrendChart = () => {
            const mainChartElement = document.getElementById('chart-data-laporan');
            
            // Pastikan data dan elemen tersedia
            if (typeof dailyChartData === 'undefined' || !mainChartElement || !dailyChartData.series || dailyChartData.series.length === 0) {
                return;
            }

            mainChartElement.innerHTML = '';
            
            const totalDataSeries = dailyChartData.series.length;

            // 1. Buat data untuk Series Label Individual (Whatsapp, Tatap Muka, Surat)
            const individualSeries = dailyChartData.series.map((series) => {
                return {
                    ...series,
                    dataLabels: {
                        enabled: true,
                        formatter: function(val) {
                            return val > 0 ? val : ''; // Tampilkan nilai individual saja
                        },
                        style: {
                            fontSize: '11px',
                            colors: ['#000'],
                        },
                        // Offset POSITIF: Menempatkan label INDIVIDUAL di tengah area tumpukan
                        offsetY: 20, 
                        background: {
                            enabled: true,
                            borderRadius: 2,
                            padding: 4,
                            opacity: 0.9,
                            borderWidth: 0,
                            foreColor: '#fff'
                        },
                    }
                };
            });

            // 2. Buat Series Tambahan KHUSUS untuk Label Total di Puncak
            // Kita buat series duplikat dari series teratas (Surat)
            // Series ini akan memiliki dataLabels yang berbeda dan offsetY negatif
            const topSeriesIndex = totalDataSeries - 1;
            const topSeriesData = dailyChartData.series[topSeriesIndex].data.map((val, dataPointIndex) => {
                // Series ini harus memiliki data yang sama agar Total Harian dihitung dengan benar
                return val;
            });

            // Gunakan series asli, tetapi tambahkan label total sebagai series tambahan 
            // DENGAN dataLabels.enabled = true, yang akan ditumpuk.
            // *CATATAN: Ini adalah teknik ApexCharts yang riskan tapi sering berhasil.*

            const chartOptions = {
                chart: {
                    type: 'area',
                    height: 350,
                    stacked: true,
                    toolbar: { show: false },
                },
                
                // Nonaktifkan dataLabels GLOBAL (diganti oleh konfigurasi series individual)
                dataLabels: { 
                    enabled: false, 
                },
                
                stroke: {
                    width: 2,
                    curve: 'smooth'
                },

                // Gabungkan semua series: Series Individual + Series Khusus Total di Puncak
                series: individualSeries, 
                
                // Kita hanya akan menggunakan dataLabels global untuk satu tujuan:
                // Memastikan label Total muncul di atas series teratas.
                // Kita perlu menyertakan konfigurasi dataLabels.
                dataLabels: {
                    enabled: true,
                    formatter: function (val, { seriesIndex, dataPointIndex, w }) {
                        // Hanya gunakan formatter ini untuk series yang paling atas.
                        if (seriesIndex === topSeriesIndex) {
                            const totalHarian = w.globals.stackedSeriesTotals[dataPointIndex];
                            if (totalHarian > 0) {
                                return totalHarian; // Tampilkan Total Harian
                            }
                        }
                        return '';
                    },
                    
                    style: {
                        fontSize: '12px',
                        colors: ['#000'],
                    },
                    // Offset NEGATIF: Menarik label Total Harian ke ATAS UJUNG CHART
                    offsetY: -10, 
                    
                    background: {
                        enabled: true,
                        borderRadius: 2,
                        padding: 4,
                        opacity: 0.9,
                        borderWidth: 0,
                        foreColor: '#fff'
                    },
                },


                fill: { opacity: 0.8, type: 'solid' },
                xaxis: {
                    type: 'category', 
                    categories: dailyChartData.labels,
                    labels: { style: { colors: '#666' } }
                },
                yaxis: {
                    title: { text: 'Jumlah Laporan' },
                    labels: { style: { colors: '#666' } }
                },
                
                // Tooltip Custom (Tidak perlu diubah)
                tooltip: {
                    enabled: true,
                    shared: true,
                    intersect: false, 
                    custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                        const date = w.globals.categoryLabels[dataPointIndex];
                        const waCount = w.globals.series[0][dataPointIndex] || 0;
                        const tatapMukaCount = w.globals.series[1][dataPointIndex] || 0;
                        const suratCount = w.globals.series[2][dataPointIndex] || 0;
                        const totalHarian = waCount + tatapMukaCount + suratCount;
                        
                        return `
                            <div class="apexcharts-tooltip-box" style="padding: 10px; background: #333; color: white; border-radius: 5px;">
                                <div class="fw-bold">${date}</div>
                                <hr style="margin: 5px 0; border-top: 1px solid rgba(255,255,255,0.3);">
                                <ul style="list-style: none; padding: 0; margin: 0; font-size: 13px;">
                                    <li>Whatsapp: ${waCount} Laporan</li>
                                    <li>Tatap Muka: ${tatapMukaCount} Laporan</li>
                                    <li>Surat: ${suratCount} Laporan</li>
                                </ul>
                                <hr style="margin: 5px 0; border-top: 1px solid rgba(255,255,255,0.3);">
                                <strong>Total Hari ini: ${totalHarian} Laporan</strong>
                            </div>
                        `;
                    }
                }
            };
            new ApexCharts(mainChartElement, chartOptions).render();
        };

        // --- 2. CHART KATEGORI (Top 10 Kategori - BAR VERTICAL) ---
        const renderCategoryBarChart = () => {
            const categoryChartElement = document.getElementById('chart-kategori');
            const top10Categories = categoryStatsData.slice(0, 10);

            if (!categoryChartElement || top10Categories.length === 0) {
                return;
            }
            
            // PERBAIKAN 1: Bersihkan DOM element
            categoryChartElement.innerHTML = '';
            
            // PERBAIKAN 2: Memotong nama kategori untuk label X (maks 15 karakter)
            const categoryNames = top10Categories.map(c => {
                const name = c.name;
                return name.length > 15 ? name.substring(0, 15) + '...' : name;
            });
            const categoryTotals = top10Categories.map(c => c.total);

            const chartOptions = {
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: { show: false },
                },
                series: [{
                    name: 'Jumlah Laporan',
                    data: categoryTotals
                }],
                
                // PERBAIKAN 3: Mengaktifkan Data Labels
                dataLabels: { 
                    enabled: true, 
                    formatter: (val) => `${val}`, // Tampilkan nilai
                    style: {
                        colors: ['#303030'] // Warna yang kontras
                    },
                    offsetY: -20 // Posisikan di atas bar
                },

                xaxis: {
                    categories: categoryNames, // Gunakan nama yang sudah dipotong
                    labels: { 
                        style: { colors: '#666' },
                        rotate: -45, 
                        rotateAlways: true,
                        trim: true // Tambahkan trim
                    }
                },
                yaxis: {
                    title: { text: 'Total Laporan' },
                    labels: { 
                        formatter: (val) => `${Math.round(val)}`,
                        style: { colors: '#666' } 
                    },
                    min: 0
                },
                plotOptions: {
                    bar: { horizontal: false, columnWidth: '70%' }
                },
                colors: ['#20c997'], 
                tooltip: {
                    y: {
                        formatter: (val) => `${val} laporan`
                    }
                }
            };
            new ApexCharts(categoryChartElement, chartOptions).render();
        };


        // --- 3. PIE CHART DEPUTI (STATUS LAPORAN) ---
        const renderDeputyPieCharts = () => {
            if (Object.keys(deputyPieData).length === 0) {
                return;
            }

            Object.keys(deputyPieData).forEach(key => {
                const data = deputyPieData[key];
                const pieElement = document.getElementById(`chart-pie-${key}`); 
                
                if (!pieElement) {
                    return;
                }
                
                const seriesWithValues = data.series.map(s => parseInt(s) || 0);
                // AMBIL DATA DETAIL KE VARIABEL LOKAL
                const sourceDetailsLocal = data.source_details || {}; 

                if (seriesWithValues.some(s => s > 0)) {
                    pieElement.innerHTML = '';

                    const pieOptions = {
                        chart: {
                            type: 'donut',
                            height: 250,
                        },
                        series: seriesWithValues,
                        labels: data.labels,
                        
                        legend: {
                            position: 'bottom',
                            fontSize: '12px',
                            itemMargin: { horizontal: 0, vertical: 0 },
                            formatter: function (seriesName, opts) {
                                const maxLen = 30; 
                                const total = opts.w.globals.seriesTotals[opts.seriesIndex];
                                const name = seriesName.length > maxLen ? seriesName.substring(0, maxLen) + '...' : seriesName;
                                return `${name}: ${total}`;
                            }
                        },
                        
                        dataLabels: {
                            formatter: function (val, opts) {
                                return opts.w.globals.seriesTotals[opts.seriesIndex];
                            }
                        },
                        
                        tooltip: {
                            y: {
                                // PERBAIKAN KRITIS UNTUK MENGHINDARI CRASH:
                                formatter: function (val, { seriesIndex, w }) {
                                    
                                    // 1. Ambil Nama Status (Label Pendek) DENGAN Pengecekan Keamanan KETAT
                                    let statusName = "Status Tidak Diketahui";
                                    if (w && w.config && w.config.labels && w.config.labels[seriesIndex]) {
                                        statusName = w.config.labels[seriesIndex];
                                    } else if (data.labels && data.labels[seriesIndex]) {
                                        // Fallback jika w.config gagal (paling aman)
                                        statusName = data.labels[seriesIndex]; 
                                    }

                                    // 2. Akses Data Detail dari Variabel Lokal (CLOSURE)
                                    const details = sourceDetailsLocal[statusName] || {};

                                    // 3. Verifikasi Kunci Source
                                    const whatsappCount = details['whatsapp'] || 0;
                                    const tatapMukaCount = details['tatap muka'] || 0;
                                    const suratCount = details['surat fisik'] || 0;
                                    
                                    // 4. Format Output Tooltip
                                    let detailHtml = `
                                        <div style="padding: 0;">
                                            <ul style="list-style: none; padding-left: 10px; margin-top: 5px; margin-bottom: 0; font-size: 11px;">
                                                <li>- Whatsapp: ${whatsappCount} Laporan</li>
                                                <li>- Tatap Muka: ${tatapMukaCount} Laporan</li>
                                                <li>- Surat: ${suratCount} Laporan</li>
                                            </ul>
                                        </div>
                                    `;
                                    
                                    if (whatsappCount + tatapMukaCount + suratCount === 0 && val > 0) {
                                        detailHtml = `${statusName}: ${val} Laporan<br/>(Sumber laporan belum terdata)`;
                                    }

                                    return detailHtml;
                                }
                            }
                        },
                        
                        responsive: [{
                            breakpoint: 480,
                            options: { chart: { width: 200 } }
                        }]
                    };
                    
                    new ApexCharts(pieElement, pieOptions).render();
                } else {
                    pieElement.innerHTML = '<div class="text-center text-muted p-4">Tidak ada data laporan ditemukan untuk Deputi ini.</div>';
                }
            });
        };

        const renderUserDeputyPieChart = () => {
            const pieElement = document.getElementById('chart-pie-deputy-status'); 
            if (!pieElement || Object.keys(userDeputyPieData).length === 0) {
                return;
            }

            const data = userDeputyPieData;
            const seriesWithValues = data.series.map(s => parseInt(s) || 0);
            const sourceDetailsLocal = data.source_details || {}; 

            if (seriesWithValues.some(s => s > 0)) {
                pieElement.innerHTML = ''; // Clear placeholder

                const pieOptions = {
                    chart: { type: 'donut', height: 250, },
                    series: seriesWithValues,
                    labels: data.labels,
                    legend: { /* ... (Logika legend yang sama) ... */ position: 'bottom', fontSize: '12px', itemMargin: { horizontal: 0, vertical: 0 }, formatter: function (seriesName, opts) {
                        const maxLen = 30; 
                        const total = opts.w.globals.seriesTotals[opts.seriesIndex];
                        const name = seriesName.length > maxLen ? seriesName.substring(0, maxLen) + '...' : seriesName;
                        return `${name}: ${total}`;
                    }},
                    dataLabels: { /* ... (Logika dataLabels yang sama) ... */ formatter: function (val, opts) {
                        return opts.w.globals.seriesTotals[opts.seriesIndex];
                    }},
                    tooltip: { y: {
                        formatter: function (val, { seriesIndex, w }) {
                            // KODE TOOLTIP LENGKAP DARI KODE ANDA
                            let statusName = "Status Tidak Diketahui";
                            if (w && w.config && w.config.labels && w.config.labels[seriesIndex]) {
                                statusName = w.config.labels[seriesIndex];
                            } else if (data.labels && data.labels[seriesIndex]) {
                                statusName = data.labels[seriesIndex]; 
                            }

                            const details = sourceDetailsLocal[statusName] || {};
                            const whatsappCount = details['whatsapp'] || 0;
                            const tatapMukaCount = details['tatap muka'] || 0;
                            const suratCount = details['surat fisik'] || 0;

                            let detailHtml = `
                                <div style="padding: 0;">
                                    <ul style="list-style: none; padding-left: 10px; margin-top: 5px; margin-bottom: 0; font-size: 11px;">
                                        <li>- Whatsapp: ${whatsappCount} Laporan</li>
                                        <li>- Tatap Muka: ${tatapMukaCount} Laporan</li>
                                        <li>- Surat: ${suratCount} Laporan</li>
                                    </ul>
                                </div>
                            `;

                            if (whatsappCount + tatapMukaCount + suratCount === 0 && val > 0) {
                                detailHtml = `${statusName}: ${val} Laporan<br/>(Sumber laporan belum terdata)`;
                            }
                            return detailHtml;
                        }
                    }},
                    responsive: [{ breakpoint: 480, options: { chart: { width: 200 } } }]
                };

                new ApexCharts(pieElement, pieOptions).render();
            } else {
                pieElement.innerHTML = '<div class="alert alert-info text-center">Data status laporan untuk Divisi Anda tidak ditemukan.</div>';
            }
        };

        // --- 4. CHART LOKASI (MAP) ---
        function renderMap() {
            const mapElement = document.getElementById('map-indonesia');
            if (typeof jsVectorMap === 'undefined' || !mapElement) return;

            destroyCurrentMapInstance?.();
            mapElement.innerHTML = '';

            const mapName = 'indonesia-adm1_merc';

            // 1) Siapkan kamus ID→Nama dari file peta (jika sudah ter-load)
            const regionNames = {};
            const baseMap = jsVectorMap?.maps?.[mapName];
            if (baseMap && baseMap.paths) {
                Object.keys(baseMap.paths).forEach(k => {
                regionNames[String(k)] = baseMap.paths[k]?.name || String(k);
                });
            }

            // 2) Data pewarnaan
            const mapDataValues = (typeof mapData === 'object' && mapData !== null) ? mapData : {};
            const cleanedMapData = {};
            let hasValidData = false, minVal = Infinity, maxVal = -Infinity;

            for (const code in mapDataValues) {
                const val = parseInt(mapDataValues[code], 10) || 0;
                const key = String(code);
                cleanedMapData[key] = val;
                if (val > 0) {
                hasValidData = true;
                if (val < minVal) minVal = val;
                if (val > maxVal) maxVal = val;
                }
            }
            if (!hasValidData) { minVal = 0; maxVal = 10; }
            else if (minVal === maxVal) { maxVal = maxVal + 1; }

            try {
                currentMapInstance = new jsVectorMap({
                selector: '#map-indonesia',
                map: mapName,

                series: hasValidData ? {
                    regions: [{
                    attribute: 'fill',
                    values: cleanedMapData,
                    scale: ['#DCE0EB', '#4676fe'],
                    normalizeFunction: 'linear'
                    }]
                } : {},

                regionStyle: {
                    initial: { fill: '#DCE0EB', 'fill-opacity': 1, stroke: 'none' },
                    hover:   { 'fill-opacity': 0.7, cursor: 'pointer' },
                    selected:{ fill: '#4676fe' }
                },

                // jangan tampilkan label permanen
                labels: { regions: { render: () => '' } },

                // 3) Tooltip saat hover — pakai nama provinsi dari kamus
                onRegionTooltipShow: (event, tooltip, code) => {
                    const key = String(code);
                    const provinceName = mapNames[key] || key;
                    const count = mapData[key] || 0;
                    const html = `
                        <div class="p-1">
                        <div class="fw-bold">${provinceName}</div>
                        <div>Total Laporan: ${count}</div>
                        </div>
                    `;
                    if (tooltip && typeof tooltip.text === 'function') tooltip.text(html, true);
                    else if (tooltip && tooltip.tooltip) tooltip.tooltip.innerHTML = html;
                },

                markers: []
                });

                if (!hasValidData) currentMapInstance.setColors({});

            } catch (e) {
                console.error('Gagal menginisialisasi jsVectorMap:', e);
                mapElement.innerHTML = `<div class="alert alert-danger">Gagal memuat peta: ${e.message}. Cek konsol.</div>`;
            }
        }

        // --- FUNGSI CHART STATUS PENGADUAN TOTAL (SEMUA DEPUTI) ---
        const renderTotalComplaintStatusPieChart = () => {
            // Data diambil dari variabel yang diinjeksi di atas
            const data = totalComplaintStatusPieData; 
            const pieElement = document.getElementById('chart-pie-total-complaint-status'); 
            
            if (!pieElement) {
                return; 
            }
            
            // Pastikan data ada. Jika tidak, tampilkan pesan tanpa crash.
            if (!data || !data.series || data.series.length === 0) {
                pieElement.innerHTML = '<div class="text-center text-muted p-4">Tidak ada data status pengaduan yang ditemukan.</div>';
                return;
            }

            const seriesWithValues = data.series.map(s => parseInt(s) || 0);
            const sourceDetailsLocal = data.source_details || {};

            // Render hanya jika ada nilai di series
            if (seriesWithValues.some(s => s > 0)) {
                pieElement.innerHTML = ''; // Bersihkan placeholder/loading

                const pieOptions = {
                    chart: {
                        type: 'donut',
                        height: 250,
                    },
                    series: seriesWithValues,
                    labels: data.labels,
                    legend: {
                        position: 'bottom',
                        fontSize: '12px',
                        itemMargin: { horizontal: 0, vertical: 0 },
                        formatter: function (seriesName, opts) {
                            const maxLen = 30; 
                            const total = opts.w.globals.seriesTotals[opts.seriesIndex];
                            const name = seriesName.length > maxLen ? seriesName.substring(0, maxLen) + '...' : seriesName;
                            return `${name}: ${total}`;
                        }
                    }, // Koma sudah dihapus oleh Anda
                    dataLabels: {
                        formatter: function (val, opts) {
                            // Menampilkan total count, bukan persentase
                            return opts.w.globals.seriesTotals[opts.seriesIndex];
                        }
                    },
                    tooltip: {
                        y: {
                            // Logika Tooltip kompleks yang memanfaatkan sourceDetailsLocal (closure)
                            formatter: function (val, { seriesIndex, w }) {
                                
                                let statusName = "Status Tidak Diketahui";
                                if (w && w.config && w.config.labels && w.config.labels[seriesIndex]) {
                                    statusName = w.config.labels[seriesIndex];
                                } else if (data.labels && data.labels[seriesIndex]) {
                                    statusName = data.labels[seriesIndex]; 
                                }

                                const details = sourceDetailsLocal[statusName] || {};

                                const whatsappCount = details['whatsapp'] || 0;
                                const tatapMukaCount = details['tatap muka'] || 0;
                                const suratCount = details['surat fisik'] || 0;
                                
                                let detailHtml = `
                                    <div style="padding: 0;">
                                        <ul style="list-style: none; padding-left: 10px; margin-top: 5px; margin-bottom: 0; font-size: 11px;">
                                            <li>- Whatsapp: ${whatsappCount} Laporan</li>
                                            <li>- Tatap Muka: ${tatapMukaCount} Laporan</li>
                                            <li>- Surat: ${suratCount} Laporan</li>
                                        </ul>
                                    </div>
                                `;
                                
                                if (whatsappCount + tatapMukaCount + suratCount === 0 && val > 0) {
                                    detailHtml = `${statusName}: ${val} Laporan<br/>(Sumber laporan belum terdata)`;
                                }

                                return detailHtml;
                            }
                        }
                    }, 
                    responsive: [{
                        breakpoint: 480,
                        options: { chart: { width: 200 } }
                    }]
                }; 
                
                new ApexCharts(pieElement, pieOptions).render();
            } else {
                pieElement.innerHTML = '<div class="text-center text-muted p-4">Semua laporan telah selesai atau tidak ada data yang tercatat.</div>';
            }
        };

        document.querySelectorAll('.open-category-detail').forEach(button => {
            button.addEventListener('click', function() {
                
                const categoryId = this.dataset.categoryId;
                const categoryName = this.dataset.categoryName;
                const total = this.dataset.total;
                const childrenJson = this.dataset.children;
                
                let childrenData = [];
                try {
                    childrenData = JSON.parse(childrenJson);
                } catch (e) {
                    console.error("Gagal memparsing data children:", e);
                }

                const stat = {
                    id: parseInt(categoryId),
                    name: categoryName,
                    total: parseInt(total),
                    children: childrenData 
                };
                
                loadCategoryDetail(stat);
            });
        });

        // Fungsi untuk menampilkan rincian status Analis di modal
        function loadAnalystStatusDetail(buttonElement) {
            const modalElement = document.getElementById('modal-analyst-status-detail');
            
            // Ambil data dari Button Dataset
            const analystName = buttonElement.dataset.analystName;
            const total = buttonElement.dataset.analystTotal;
            const statusesString = buttonElement.dataset.statuses;

            const statusList = modalElement.querySelector('#analyst-status-list');
            const totalSpan = modalElement.querySelector('#analyst-total-span');
            const nameSpan = modalElement.querySelector('#analyst-name-span');
            const filterBtn = modalElement.querySelector('#btn-analyst-filter');

            // 1. Inisialisasi dan Parsing JSON
            let statuses = {};
            try {
                // PARSE STRING JSON DARI DATA ATTRIBUTE
                statuses = JSON.parse(statusesString);
            } catch (e) {
                console.error("Gagal mem-parse data status Analis:", e);
                statuses = {};
            }

            statusList.innerHTML = ''; // Bersihkan list
            
            // 2. Set Header dan Total
            nameSpan.textContent = analystName;
            totalSpan.textContent = total;
            filterBtn.href = `/admin/reports?filterDisposisi=${encodeURIComponent(analystName)}`;
            
            // Mapping warna
            const statusColorMap = {
                'Proses verifikasi dan telaah': 'warning',
                'Menunggu kelengkapan data dukung dari Pelapor': 'danger',
                'Diteruskan kepada instansi yang berwenang untuk penanganan lebih lanjut': 'info',
                'Penanganan Selesai': 'success',
                'Selesai Tuntas': 'success',
                'Ditolak': 'dark',
            };

            let itemsRendered = 0;

            // 3. Loop Status dan Tambahkan ke List
            Object.keys(statuses).forEach(statusName => {
                const count = statuses[statusName];
                
                if (count > 0) { // Hanya tampilkan status dengan jumlah > 0
                    const color = statusColorMap[statusName] || 'secondary';
                    const listItem = document.createElement('li');
                    listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                    
                    let displayStatusName = statusName.length > 45 ? statusName.substring(0, 45) + '...' : statusName;

                    listItem.innerHTML = `
                        <span>${displayStatusName}</span>
                        <span class="badge bg-${color}-lt">${count} laporan</span>
                    `;
                    statusList.appendChild(listItem);
                    itemsRendered++;
                }
            });

            // 4. Fallback 
            if (itemsRendered === 0) {
                statusList.innerHTML = `<li class="list-group-item text-center text-muted">Belum ada laporan dengan status yang tercatat dalam rentang ini.</li>`;
            }
        }

        // =========================================================================
        // ✅ EVENT LISTENER UNTUK MENUTUP MODAL PURE JS
        // =========================================================================
        const modalElementDetail = document.getElementById('modal-category-detail');
        if (modalElementDetail) {
            modalElementDetail.querySelectorAll('[data-bs-dismiss="modal"]').forEach(closeButton => {
                closeButton.addEventListener('click', () => hideModal(modalElementDetail));
            });
            modalElementDetail.addEventListener('click', function(e) {
                if (e.target === modalElementDetail) {
                    hideModal(modalElementDetail);
                }
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modalElementDetail.classList.contains('show')) {
                    hideModal(modalElementDetail);
                }
            });
        }

        document.querySelectorAll('.category-parent').forEach(row => {
            row.addEventListener('click', function(event) {
                if (event.target.closest('a') || event.target.closest('button')) {
                    return;
                }
                
                const targetId = this.getAttribute('data-toggle-id');
                const targetRow = document.getElementById(targetId);
                const icon = this.querySelector('.toggle-icon');

                if (targetRow) {
                    const isHidden = targetRow.style.display === 'none' || targetRow.style.display === '';

                    if (isHidden) {
                        targetRow.style.display = 'table-row';
                        if (icon) {
                            icon.classList.remove('ti-chevron-right');
                            icon.classList.add('ti-chevron-down');
                        }
                    } else {
                        targetRow.style.display = 'none';
                        if (icon) {
                            icon.classList.remove('ti-chevron-down');
                            icon.classList.add('ti-chevron-right');
                        }
                    }
                }
            });
        });

        const setupSearch = () => {
            const searchInput = document.getElementById('modal-category-all-search');
            const tableBody = document.querySelector('#modal-category-all .list'); // Mengambil tbody dengan class 'list'

            if (!searchInput || !tableBody) {
                // Jika elemen tidak ditemukan (misalnya modal belum dimuat), hentikan fungsi
                return;
            }

            searchInput.addEventListener('keyup', function() {
                const filter = this.value.toLowerCase();
                const rows = tableBody.querySelectorAll('tr');

                rows.forEach(row => {
                    // Kita hanya ingin menyaring baris parent category atau baris sub-category yang sudah terpisah (bukan baris lipatan inner table)
                    if (row.classList.contains('category-parent') || row.classList.contains('sub-category-row')) {
                        
                        // Ambil teks dari kolom Topik
                        const categoryName = row.querySelector('.sort-topik')?.textContent || '';
                        
                        // Jika itu adalah baris sub-category yang dilipat, kita harus memeriksa isi tabel dalamnya
                        let subCategoryContent = '';
                        if (row.classList.contains('sub-category-row')) {
                            subCategoryContent = row.querySelector('td')?.textContent || '';
                        }

                        // Gabungkan teks untuk pencarian
                        const textContent = (categoryName + ' ' + subCategoryContent).toLowerCase();

                        if (textContent.includes(filter)) {
                            // Tampilkan baris jika cocok
                            row.style.display = ''; // Menampilkan baris (default display)

                            if (row.classList.contains('sub-category-row')) {
                                const prevRow = row.previousElementSibling;
                                if (prevRow && prevRow.classList.contains('category-parent')) {
                                    prevRow.style.display = '';
                                }
                            }

                        } else {
                            // Sembunyikan baris jika tidak cocok
                            row.style.display = 'none';
                        }

                        if (filter === '') {
                             if (isParent) {
                                row.style.display = 'table-row';
                                row.querySelector('.toggle-icon')?.classList.add('ti-chevron-right');
                                row.querySelector('.toggle-icon')?.classList.remove('ti-chevron-down');
                             } else if (isContainer) {
                                row.style.display = 'none';
                             }
                        }
                    }
                });
            });
        };
        setupSearch();

        // --- EXECUTION ---
        renderDailyTrendChart();
        renderCategoryBarChart();
        renderDeputyPieCharts();
        renderUserDeputyPieChart();
        renderTotalComplaintStatusPieChart();
        renderMap();
    });
</script>
@endpush