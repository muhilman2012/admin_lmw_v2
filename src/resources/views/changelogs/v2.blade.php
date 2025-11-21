@extends('layouts.app')

@section('title', 'Changelog v2.0.0')

@section('content')
<div class="container-xl">
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h1 class="card-title text-primary mb-3">
                        <i class="ti ti-rocket me-2"></i> Changelog Aplikasi Lapor Mas Wapres v2.0.0
                    </h1>
                    <p class="text-secondary">Tanggal Rilis: <strong>1 Desember 2025</strong></p>
                    <hr>

                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="ti ti-star me-2"></i>
                        <div>
                            Peningkatan LMW v2 ini adalah <strong>dedikasi khusus dari Tim Pengembang Aplikasi LMW</strong> sebagai <strong>kado ulang tahun pertama LMW</strong> yang jatuh pada tanggal <strong>11 November 2025</strong>. <br> <strong>Selamat Ulang Tahun, Lapor Mas Wapres!</strong>
                        </div>
                    </div>

                    <p class="fs-5">
                        Versi <strong>2.0.0</strong> menandai lompatan besar dalam fungsionalitas dan kinerja sistem. Kami memperkenalkan integrasi dengan sistem LAPOR!, memanfaatkan teknologi AI untuk klasifikasi laporan, dan menghadirkan optimasi performa yang signifikan di seluruh <strong>platform</strong>.
                    </p>

                    <hr class="mt-4 mb-4">

                    <h2 class="h3 mb-3 text-success">
                        <i class="ti ti-star me-2"></i> Fitur Baru Utama & Teknologi
                    </h2>
                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item">
                            <i class="ti ti-plug me-2 text-primary"></i>
                            <strong>Integrasi Penuh LAPOR!:</strong> Implementasi fitur <strong>Teruskan Laporan</strong> ke K/L/D dan sistem pemantauan status otomatis melalui API Lapor.
                        </li>
                        <li class="list-group-item">
                            <i class="ti ti-brain me-2 text-info"></i>
                            <strong>Klasifikasi Laporan berbasis AI:</strong> Pemanfaatan teknologi <strong>Artificial Intelligence (AI)</strong> untuk klasifikasi kategori laporan pengaduan yang masuk melalui WhatsApp.
                        </li>
                        <li class="list-group-item">
                            <i class="ti ti-chart-bar me-2 text-success"></i>
                            <strong>Modul Monitoring Analis Baru:</strong> Visualisasi pada <strong>Dashboard</strong> <strong>Deputi</strong> dan <strong>Asdep/Karo</strong> untuk memantau beban kerja Analis di bawah <strong>scope</strong> mereka.
                        </li>
                        <li class="list-group-item">
                            <i class="ti ti-book me-2 text-warning"></i>
                            <strong>Penambahan Modul KMS (Knowledge Management System):</strong> Modul baru untuk mendokumentasikan prosedur dan panduan sistem.
                        </li>
                    </ul>

                    <h2 class="h3 mb-3 text-warning">
                        <i class="ti ti-bolt me-2"></i> Peningkatan Performa & Otomatisasi
                    </h2>
                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item">
                            <i class="ti ti-speedboat me-2 text-danger"></i>
                            <strong>Optimasi Performa Menyeluruh:</strong> Peningkatan kecepatan pemrosesan data, termasuk pada pemuatan tabel dan <strong>handling</strong>.
                        </li>
                        <li class="list-group-item">
                            <i class="ti ti-dashboard me-2 text-success"></i>
                            <strong>Peningkatan Performa Dashboard:</strong> Peningkatan kecepatan <strong>loading</strong> pada <strong>dashboard</strong> utama, dan <strong>Visualisasi Chart</strong>.
                        </li>
                        <li class="list-group-item">
                            <i class="ti ti-clock me-2 text-secondary"></i>
                            <strong>Task Scheduling Otomatis:</strong>
                            <ul>
                                <li>Auto Update Status Lapor (setiap 3 jam).</li>
                                <li>Auto Resend Laporan yang gagal terkirim ke LAPOR!.</li>
                            </ul>
                        </li>
                    </ul>

                    <h2 class="h3 mb-3 text-secondary">
                        <i class="ti ti-layout-navbar me-2"></i> UI/UX, Struktur, dan Pemeliharaan
                    </h2>
                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item">
                            <i class="ti ti-refresh me-2 text-primary"></i>
                            <strong>Perubahan UI/UX Halaman Admin LMW:</strong> Pembaruan antarmuka pengguna admin Petugas Loket untuk desain yang lebih intuitif.
                        </li>
                        <li class="list-group-item">
                            <i class="ti ti-file-export me-2 text-info"></i>
                            <strong>Fitur Export Data Lebih Rapi:</strong> Peningkatan format hasil <strong>export</strong> data (Excel/PDF) agar lebih rapi dan terstruktur.
                        </li>
                        <li class="list-group-item">
                            <i class="ti ti-list-details me-2 text-warning"></i>
                            <strong>Penambahan Sub-Kategori:</strong> Penambahan struktur Sub-Kategori laporan yang lebih mendalam.
                        </li>
                        <li class="list-group-item">
                            <i class="ti ti-bug me-2 text-danger"></i>
                            <strong>Fixing Bug:</strong> Perbaikan berbagai <strong>bug</strong> minor dan <strong>glitches</strong> yang ditemukan di versi sebelumnya.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection