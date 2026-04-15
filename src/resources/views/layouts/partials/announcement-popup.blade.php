@if($announcement)
    <div class="modal modal-blur fade" id="modal-announcement" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $announcement->title }}</h5>
                    <button type="button" class="btn-close" id="btn-close-announcement-x"></button>
                </div>
                <div class="modal-body text-center">
                    @if($announcement->image_path)
                        @php
                            $key = ltrim($announcement->image_path, '/');
                            $imageUrl = signMinioUrlSmart(env('AWS_UPLOADS_BUCKET'), $key, 60); 
                        @endphp
                        
                        <img src="{{ $imageUrl }}" class="img-fluid mb-3 rounded shadow-sm" alt="Pengumuman">
                    @endif
                    
                    @if($announcement->content)
                        <div class="text-muted text-start">
                            {!! $announcement->content !!}
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <div class="d-flex justify-content-between w-100 align-items-center">
                        <label class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="dont-show-again">
                            <span class="form-check-label text-muted small">Jangan tampilkan lagi hari ini</span>
                        </label>
                        <button type="button" class="btn btn-primary" id="btn-close-announcement">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function () {
                const annoId = "{{ $announcement->id }}";
                const storageKey = `hide_announcement_${annoId}`;
                const hiddenUntil = localStorage.getItem(storageKey);
                const now = new Date().getTime();

                if (!hiddenUntil || now > hiddenUntil) {
                    const modal = document.getElementById('modal-announcement');
                    const btnClose = document.getElementById('btn-close-announcement');
                    const btnCloseX = document.getElementById('btn-close-announcement-x');
                    const checkbox = document.getElementById('dont-show-again');

                    // Fungsi Tampilkan (JS Murni)
                    function showModal() {
                        modal.style.display = 'block';
                        document.body.classList.add('modal-open');
                        
                        // Buat backdrop secara manual
                        const backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        backdrop.id = 'announcement-backdrop';
                        document.body.appendChild(backdrop);

                        // Trigger animasi fade in
                        setTimeout(() => { modal.classList.add('show'); }, 10);
                    }

                    // Fungsi Sembunyikan (JS Murni)
                    function closeModal() {
                        if (checkbox.checked) {
                            // Expire dalam 24 jam
                            const expiry = new Date().getTime() + (24 * 60 * 60 * 1000); 
                            localStorage.setItem(storageKey, expiry);
                        }

                        modal.classList.remove('show');
                        const backdrop = document.getElementById('announcement-backdrop');
                        if (backdrop) backdrop.classList.remove('show');

                        setTimeout(() => {
                            modal.style.display = 'none';
                            document.body.classList.remove('modal-open');
                            if (backdrop) backdrop.remove();
                        }, 300);
                    }

                    // Jalankan tampilkan
                    showModal();

                    // Event Listeners
                    btnClose.addEventListener('click', closeModal);
                    btnCloseX.addEventListener('click', closeModal);
                    
                    // Close jika klik di luar area modal (backdrop)
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) closeModal();
                    });
                }
            });
        })();
    </script>
@endif