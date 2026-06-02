<div class="container-fluid px-0">
    @forelse($history as $index => $h)
        <div class="queue-item d-flex justify-content-between align-items-center p-3 mb-2 rounded-3 transition-all
            {{ $index === 0 
                ? 'bg-primary-lt border border-2 border-primary shadow-sm animate__animated animate__pulse animate__infinite' 
                : 'bg-white border border-bottom shadow-sm' 
            }}" style="min-height: 85px;">
            
            <div>
                <div class="{{ $index === 0 ? 'text-primary' : 'text-dark' }} fw-black mb-0" 
                     style="font-size: {{ $index === 0 ? '3rem' : '2.2rem' }}; line-height: 1; font-weight: 900;">
                    {{ $h->queue_number }}
                </div>
                <div class="{{ $index === 0 ? 'fw-bold text-primary' : 'text-muted' }} text-truncate" 
                     style="font-size: {{ $index === 0 ? '1.3rem' : '1.1rem' }}; max-width: 160px;">
                    {{ $h->name }}
                </div>
            </div>
            
            <div class="text-end">
                <div class="{{ $index === 0 ? 'text-primary' : 'text-dark' }} fw-black mb-0"
                     style="font-size: {{ $index === 0 ? '1.8rem' : '1.4rem' }}; font-weight: 800; line-height: 1;">
                    LOKET {{ $h->counter_number }}
                </div>
                <div class="{{ $index === 0 ? 'text-primary fw-bold' : 'text-muted' }} mt-1" 
                     style="font-size: 0.9rem;">
                    <i class="ti ti-clock me-1"></i>{{ $h->updated_at->format('H:i') }}
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5 text-muted">
            <i class="ti ti-history d-block mb-2 text-secondary" style="font-size: 3rem;"></i>
            <div class="fw-bold" style="font-size: 1.4rem;">Belum Ada Riwayat</div>
        </div>
    @endforelse
</div>