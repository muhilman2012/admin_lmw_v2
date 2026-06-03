<div wire:poll.3s class="mb-3">
    <div class="d-flex align-items-center justify-content-between p-3 bg-blue-lt rounded-3 border border-2 border-blue-200 shadow-sm animate__animated animate__fadeIn" style="min-height: 110px; overflow: hidden;">
        <div class="d-flex align-items-center">
            <div class="avatar bg-blue text-white me-3 shadow-sm" style="width: 3.2rem; height: 3.2rem; min-width: 3.2rem; border-radius: 8px;">
                <i class="ti ti-users" style="font-size: 2rem;"></i>
            </div>
            <div style="min-width: 0;">
                <div class="text-blue fw-black text-uppercase tracking-wider mb-1 text-truncate" style="font-size: 1.2rem; font-weight: 700; letter-spacing: 0.5px;">
                    Antrean Menunggu
                </div>
                <div class="fw-black text-dark mb-0" style="font-size: 3.2rem; line-height: 1; font-weight: 900;">
                    {{ $count }} <span class="text-muted fw-normal" style="font-size: 1.8rem;">Orang</span>
                </div>
            </div>
        </div>
        <div class="spinner-grow text-blue ms-2" role="status" style="width: 1.2rem; height: 1.2rem; min-width: 1.2rem;"></div>
    </div>
</div>