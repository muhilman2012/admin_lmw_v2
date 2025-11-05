<div id="page-loader-overlay" 
    class="d-none" 
    style="position: fixed; 
           top: 0; 
           left: 0; 
           width: 100%; 
           height: 100%; 
           background: rgba(255, 255, 255, 0.9); 
           z-index: 9999;">
    
    <div class="page page-center" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
        <div class="container container-slim py-4">
            <div class="text-center">
                <div class="mb-3">
                    <a href="#" class="navbar-brand navbar-brand-autodark">
                        <img src="{{ asset('tabler/img/logo/LaporMasWapres.png') }}" alt="Logo LaporMasWapres" class="navbar-brand-image" style="height: 38px;" />
                    </a>
                </div>
                {{-- ID untuk teks dinamis yang diisi JS --}}
                <div id="loader-text" class="text-secondary mb-3">Memproses data...</div> 
                <div class="progress progress-sm" style="max-width: 300px; margin: 0 auto;">
                    <div class="progress-bar progress-bar-indeterminate"></div>
                </div>
            </div>
        </div>
    </div>
</div>