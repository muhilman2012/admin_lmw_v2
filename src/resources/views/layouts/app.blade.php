<!DOCTYPE html>
<html lang="en">
@include('layouts.partials.head')

<body class="layout-fluid">
    @include('layouts.partials.page-loader')
    <div class="page">
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
        <div class="sticky-top">
            @include('layouts.partials.navbar')
        </div>
        <div class="page-wrapper">
            @yield('page_header')
            
            <div class="page-body">
                <div class="container-xl">
                    @yield('content')
                </div>
            </div>

            @include('layouts.partials.footer')
        </div>
    </div>
    
    @include('layouts.partials.theme-settings')
    @include('layouts.partials.scripts')
    @stack('scripts')
    @push('scripts')
        <script>
            // Konfigurasi Timeout (HARUS SAMA dengan SESSION_LIFETIME di .env)
            // 60 menit * 60 detik * 1000 milidetik = 3,600,000 milidetik
            const TIMEOUT_IN_MS = 30 * 1000; // 1 Jam

            let timeoutTimer;

            // Fungsi untuk me-reset timer setiap kali ada aksi
            function resetTimer() {
                clearTimeout(timeoutTimer);
                timeoutTimer = setTimeout(autoLogout, TIMEOUT_IN_MS);
            }

            // Fungsi yang dipanggil saat timer habis
            function autoLogout() {
                // Tampilkan pesan Toaster (asumsi Anda menggunakan Swal/Toast)
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sesi Habis',
                        text: 'Sesi Anda telah berakhir karena tidak aktif. Harap login kembali.',
                        showConfirmButton: false,
                        timer: 4000
                    });
                } else {
                    alert('Sesi Anda telah berakhir karena tidak aktif. Harap login kembali.');
                }
                
                // PERBAIKAN: Gunakan form yang sudah ada di HTML
                const logoutForm = document.getElementById('logout-form');
                
                // Tambahkan delay agar user sempat melihat pesan Swal
                setTimeout(() => {
                    if (logoutForm) {
                        logoutForm.submit();
                    } else {
                        // Fallback jika form tidak ditemukan
                        window.location.href = '{{ route('login') }}'; 
                    }
                }, 4500); // 4.5 detik (sedikit lebih lama dari timer Swal)
            }

            // 1. Inisialisasi timer saat halaman dimuat
            resetTimer();

            // 2. Pasang listener untuk event aktivitas: mouse/keyboard
            document.addEventListener('mousemove', resetTimer, true);
            document.addEventListener('keypress', resetTimer, true);
            document.addEventListener('scroll', resetTimer, true); 
            document.addEventListener('click', resetTimer, true); 
            
        </script>
        @endpush
    @livewireScripts
    @stack('modals')
    </body>
</html>