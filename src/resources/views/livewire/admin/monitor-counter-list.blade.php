<div>
    @forelse($counters as $c)
        <div class="queue-item d-flex justify-content-between align-items-center">
            <div>
                <div class="h2 fw-bold mb-0 text-dark">LOKET {{ $c->counter_number }}</div>
                <span class="text-muted small">Aktif</span>
            </div>
            <div class="text-end">
                <div class="display-6 fw-bold text-primary">{{ $c->total }}</div>
                <small class="text-muted text-uppercase">Terlayani</small>
            </div>
        </div>
    @empty
        <div class="text-center py-5 text-muted">
            <i class="ti ti-desk h1 d-block mb-2"></i>
            Belum ada loket aktif.
        </div>
    @endforelse
</div>