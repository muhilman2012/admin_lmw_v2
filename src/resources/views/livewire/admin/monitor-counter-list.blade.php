<div wire:poll.3s class="container-fluid px-0">
    @forelse($counters as $c)
        <div class="queue-item d-flex justify-content-between align-items-center bg-white border border-2 border-light py-3 px-3 mb-3 rounded-3 shadow-sm animate__animated animate__fadeIn" style="min-height: 90px;">
            <div>
                <div class="fw-black text-dark mb-1" style="font-size: 2.2rem; line-height: 1; font-weight: 900; letter-spacing: -1px;">
                    LOKET {{ $c->counter_number }}
                </div>
                <span class="badge bg-success text-success-fg px-2 py-1 fw-bold rounded-pill" style="font-size: 1rem;">
                    <span class="badge-blink me-1" style="display: inline-block; width: 8px; height: 8px; background-color: #fff; border-radius: 50%;"></span>
                    AKTIF
                </span>
            </div>
            <div class="text-end">
                <div class="fw-black text-primary mb-0" style="font-size: 3.2rem; line-height: 1; font-weight: 900;">
                    {{ $c->total }}
                </div>
                <div class="text-muted text-uppercase fw-bold small" style="font-size: 0.9rem; letter-spacing: 1px;">Terlayani</div>
            </div>
        </div>
    @empty
        <div class="text-center py-5 text-muted">
            <i class="ti ti-desk d-block mb-2 text-secondary" style="font-size: 3rem;"></i>
            <div class="fw-bold" style="font-size: 1.4rem;">Belum Ada Loket Aktif</div>
        </div>
    @endforelse
</div>