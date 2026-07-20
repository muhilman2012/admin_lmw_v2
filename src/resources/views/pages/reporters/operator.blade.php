@extends('layouts.app')

@section('title', 'Operator Workspace')
@section('page_pretitle', 'Layanan')
@section('page_title', 'Panggilan & Pelayanan Pengadu')

@section('content')
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="row align-items-center">
            <div class="col">
                <div class="text-muted small">Loket Aktif</div>
                <div class="h3 mb-0 fw-bold" id="label-loket">Loket {{ session('kiosk_counter_number', '-') }}</div>
            </div>

            <div class="col-auto d-flex gap-2">
                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modal-list-pengadu">
                    <i class="ti ti-list me-1"></i> Daftar Antrean Hari Ini
                </button>

                <button class="btn btn-sm btn-outline-secondary" 
                    data-bs-toggle="modal" 
                    data-bs-target="#modal-select-counter"
                    {{ $activeQueue ? 'disabled' : '' }}>
                    {{ $activeQueue ? 'Layanan Aktif' : 'Ganti' }}
                </button>
            </div>
        </div>
        @if($activeQueue)
            <small class="text-danger mt-1 d-block">* Selesaikan pelayanan untuk mengganti loket.</small>
        @endif
    </div>
</div>

<div class="modal modal-blur fade" id="modal-list-pengadu" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            @livewire('admin.daftar-pengadu-hari-ini')
        </div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-lg-4">
        @livewire('admin.queue-counter')

        <div class="card card-md shadow-sm border-primary mb-3">
            <div class="card-body text-center py-4">
                <div class="text-muted mb-2">Antrean Sedang Dipanggil</div>
                <div class="display-1 fw-bold mb-1 text-primary" id="current-call-number">
                    {{ $activeQueue->queue_number ?? '---' }}
                </div>
                <div class="h3 mb-4 text-truncate text-muted" id="current-call-name">
                    {{ $activeQueue->name ?? 'Menunggu...' }}
                </div>
                
                <div class="d-grid gap-2">
                    <button id="btn-call-next" class="btn btn-primary btn-lg py-3 shadow" 
                        onclick="panggilBerikutnya()" 
                        {{ ($activeQueue || !$sessionCounter) ? 'disabled' : '' }}>
                        <i class="ti ti-player-play-filled me-2"></i> PANGGIL BERIKUTNYA
                    </button>
                    
                    <div class="row g-2">
                        <div class="col-12">
                            <button id="btn-recall" class="btn btn-outline-info w-100 py-2" 
                                onclick="reCall()" {{ !$activeQueue ? 'disabled' : '' }}>
                                <i class="ti ti-volume me-2"></i> Panggil Ulang Suara
                            </button>
                        </div>
                        <div class="col-12">
                            <button id="btn-cancel" class="btn btn-outline-danger w-100 py-2" onclick="batalkanAntrean()" {{ !$activeQueue ? 'disabled' : '' }}>
                                <i class="ti ti-x me-2"></i> Batalkan Pengaduan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm" id="detail-card" style="{{ !$activeQueue ? 'display:none' : '' }}">
            <div class="card-header bg-blue-lt text-white">
                <h3 class="card-title">Informasi Calon Pengadu</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Nama Lengkap</label>
                        <div class="h3 fw-bold mb-0" id="det-name">{{ $activeQueue->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">NIK</label>
                        <div class="h3 fw-bold mb-0" id="det-nik">{{ $activeQueue->nik ?? '-' }}</div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label text-muted small">Topik Pengaduan</label>
                        <div class="alert alert-info border-0 mb-0 shadow-none">
                            <i class="ti ti-info-circle me-2"></i>
                            <span id="det-subject" class="fw-bold">{{ $activeQueue->subject ?? 'Belum ada topik spesifik' }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Nomor WhatsApp/HP</label>
                        <div class="h4" id="det-phone">{{ $activeQueue->phone ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Alamat</label>
                        <div class="h4" id="det-address">{{ $activeQueue->address ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light text-end py-3">
                <p class="text-muted small float-start mt-2 mb-0">Klik tombol di samping untuk lanjut membuat laporan pengaduan.</p>
                <button class="btn btn-primary px-4 btn-lg" onclick="mulaiMelayani()">
                    Isi Laporan Pengaduan <i class="ti ti-arrow-right ms-2"></i>
                </button>
            </div>
        </div>

        <div id="empty-state" class="card bg-transparent border-0" style="{{ $activeQueue ? 'display:none' : '' }}">
            <div class="card-body text-center py-6">
                <div class="avatar avatar-xl rounded-circle bg-blue-lt mb-3">
                    <i class="ti ti-user-plus text-blue" style="font-size: 2.5rem;"></i>
                </div>
                <h2 class="text-muted">Siap Melayani</h2>
                <p class="text-muted">Gunakan panel kiri untuk memanggil antrean yang sudah check-in.</p>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="modal-select-counter" 
    data-bs-backdrop="static" 
    data-bs-keyboard="false" 
    tabindex="-1">
    
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title">Sesi Meja Pelayanan</h5>
                @if(session('kiosk_counter_number'))
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                @endif
            </div>
            <div class="modal-body text-center py-4">
                <p class="text-muted">Silakan pilih nomor loket Anda untuk memulai pelayanan hari ini.</p>
                <div class="row g-2 mt-2">
                    @foreach(range(1, 10) as $num)
                        @php $isTaken = in_array($num, $takenCounters); @endphp
                        <div class="col-4">
                            <button class="btn {{ $isTaken ? 'btn-light' : 'btn-outline-primary' }} w-100 py-3 fw-bold" 
                                onclick="setCounter({{ $num }})"
                                {{ $isTaken ? 'disabled' : '' }}
                                style="position: relative;">
                                {{ $num }}
                                @if($isTaken)
                                    <span class="badge bg-danger" style="position: absolute; top: -5px; right: -5px; font-size: 0.6rem;">Aktif</span>
                                @endif
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                @if(session('kiosk_counter_number'))
                    <button type="button" class="btn btn-link link-danger" onclick="unsetCounter()">
                        <i class="ti ti-logout me-1"></i> Lepas Loket (Selesai Tugas)
                    </button>
                @else
                    <a href="{{ route('dashboard') }}" class="btn btn-link link-secondary">
                        Kembali ke Dashboard
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/axios.min.js') }}"></script>
    <script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>

    <script>
        var operatorCallProcessing = {{ $activeQueue ? 'true' : 'false' }};

        document.addEventListener('DOMContentLoaded', function () {
            // CSRF Setup untuk Axios
            const token = document.querySelector('meta[name="csrf-token"]');
            if (token) axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;

            @if(!$sessionCounter && !auth()->user()->hasRole(['superadmin', 'admin']))
                const modalEl = document.getElementById('modal-select-counter');
                const modalInstance = new bootstrap.Modal(modalEl);
                modalInstance.show();
            @endif

            @if($activeQueue)
                document.getElementById('btn-recall').disabled = false;
            @endif
        });

        function updateUI(data) {
            document.getElementById('current-call-number').innerText = data.queue_number || '---';
            document.getElementById('current-call-name').innerText = data.name || 'Menunggu...';

            document.getElementById('det-name').innerText = data.name || '-';
            document.getElementById('det-nik').innerText = data.nik || '-';
            document.getElementById('det-phone').innerText = data.phone || '-';
            document.getElementById('det-address').innerText = data.address || '-';
            
            const subjectEl = document.getElementById('det-subject');
            if (data.subject) {
                subjectEl.innerText = data.subject;
            } else {
                subjectEl.innerText = 'Belum ada topik spesifik';
            }

            const detailCard = document.getElementById('detail-card');
            const emptyState = document.getElementById('empty-state');
            const btnRecall = document.getElementById('btn-recall');

            if (detailCard) detailCard.style.display = 'block';
            if (emptyState) emptyState.style.display = 'none';
            if (btnRecall) btnRecall.disabled = false;
        }

        async function panggilBerikutnya() {
            if (operatorCallProcessing) return;
            
            const btn = document.getElementById('btn-call-next');
            operatorCallProcessing = true;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses...';

            try {
                const res = await axios.post("{{ route('operator.call-next') }}");
                
                Swal.fire({ 
                    icon: 'success', 
                    title: 'Berhasil Memanggil ' + res.data.data.queue_number, 
                    toast: true, 
                    position: 'top-end', 
                    showConfirmButton: false, 
                    timer: 800,
                    timerProgressBar: true
                });

                setTimeout(() => {
                    window.location.reload();
                }, 800);

            } catch (e) {
                console.error('Error Panggil:', e);
                const msg = e.response?.data?.message || 'Gagal memanggil antrean.';
                
                Swal.fire('Info', msg, 'info');
                
                operatorCallProcessing = false;
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-player-play-filled me-2"></i> PANGGIL BERIKUTNYA';
            }
        }

        async function reCall() {
            const num = document.getElementById('current-call-number').innerText.trim();
            
            if (num === '---' || num === '') {
                Swal.fire('Info', 'Tidak ada antrean aktif untuk dipanggil ulang.', 'info');
                return;
            }

            const btn = document.getElementById('btn-recall');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memanggil...';

            try {
                await axios.post("{{ route('operator.recall-trigger') }}", { 
                    queue_number: num 
                });
                
                Swal.fire({ 
                    icon: 'success', 
                    title: 'Panggilan ulang dikirim', 
                    toast: true, 
                    position: 'top-end', 
                    showConfirmButton: false,
                    timer: 2000 
                });
            } catch (e) {
                console.error(e);
                Swal.fire('Gagal', 'Koneksi ke server terputus atau rute tidak ditemukan.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }

        async function mulaiMelayani() {
            const num = document.getElementById('current-call-number').innerText;
            if (num === '---') return;

            try {
                const res = await axios.post("{{ route('operator.start-serving') }}", { 
                    queue_number: num 
                });
                
                if (res.data.status === 'success') {
                    // Otomatis pindah ke halaman pengaduan
                    window.location.href = res.data.redirect_url;
                }
            } catch (e) {
                Swal.fire('Error', e.response?.data?.message || 'Gagal memproses.', 'error');
            }
        }

        // --- Fungsi Set/Unset Counter Tetap Seperti Sebelumnya ---
        async function setCounter(num) {
            const currentNumber = document.getElementById('current-call-number').innerText;
            if (currentNumber !== '---') {
                Swal.fire({ icon: 'warning', title: 'Loket Terkunci', text: 'Selesaikan pelayanan dahulu.' });
                return;
            }
            try {
                Swal.showLoading();
                const res = await axios.post("{{ route('operator.set-counter') }}", { counter: num });
                if (res.data.status === 'success') window.location.reload();
            } catch (e) {
                Swal.fire('Gagal', 'Gagal mengatur loket.', 'error');
            }
        }

        async function unsetCounter() {
            const confirm = await Swal.fire({
                title: 'Akhiri Sesi Loket?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Selesai'
            });
            if (confirm.isConfirmed) {
                try {
                    await axios.post("{{ route('operator.unset-counter') }}");
                    window.location.reload();
                } catch (e) {
                    Swal.fire('Gagal', 'Selesaikan pelayanan aktif dahulu.', 'error');
                }
            }
        }

        async function batalkanAntrean() {
            const num = document.getElementById('current-call-number').innerText;
            if (num === '---') return;

            const result = await Swal.fire({
                title: 'Batalkan Antrean ' + num + '?',
                text: "Antrean ini akan ditandai sebagai 'Batal/Lewat' dan Anda dapat memanggil antrean berikutnya.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Batalkan!',
                cancelButtonText: 'Kembali'
            });

            if (result.isConfirmed) {
                try {
                    Swal.fire({
                        title: 'Memproses...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    const res = await axios.post("{{ route('operator.cancel-queue') }}", {
                        queue_number: num
                    });

                    if (res.data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Dibatalkan',
                            text: 'Antrean berhasil diskip.',
                            showConfirmButton: false,
                            timer: 1500
                        });

                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } catch (e) {
                    Swal.fire('Error', e.response?.data?.message || 'Gagal membatalkan antrean.', 'error');
                }
            }
        }
    </script>
@endpush