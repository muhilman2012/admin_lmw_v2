<div>
    @forelse($history as $index => $h)
        <div class="queue-item d-flex justify-content-between align-items-center {{ $index === 0 ? 'active' : '' }}">
            <div>
                <div class="h2 fw-bold mb-0 text-dark">{{ $h->queue_number }}</div>
                <div class="text-muted small text-truncate" style="max-width: 150px;">{{ $h->name }}</div>
            </div>
            <div class="text-end">
                <div class="h4 fw-bold mb-0 text-primary">LOKET {{ $h->counter_number }}</div>
                <small class="text-muted">{{ $h->updated_at->format('H:i') }}</small>
            </div>
        </div>
    @empty
        <div class="text-center py-5 text-muted">
            <i class="ti ti-history h1 d-block mb-2"></i>
            Belum ada riwayat.
        </div>
    @endforelse
</div>