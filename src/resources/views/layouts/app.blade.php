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
    
    {{-- @include('layouts.partials.theme-settings') --}}
    @include('layouts.partials.scripts')
    @stack('scripts')
    @push('scripts')
        <script>
            const TIMEOUT_IN_MS = 50 * 1000;

            let timeoutTimer;

            function resetTimer() {
                clearTimeout(timeoutTimer);
                timeoutTimer = setTimeout(autoLogout, TIMEOUT_IN_MS);
            }

            function autoLogout() {
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

                const logoutForm = document.getElementById('logout-form');
                
                setTimeout(() => {
                    if (logoutForm) {
                        logoutForm.submit();
                    } else {
                        window.location.href = '{{ route('login') }}'; 
                    }
                }, 4500);
            }

            resetTimer();

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