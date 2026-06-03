<div>
    <div class="modal-header">
        <h5 class="modal-title">Daftar Pengadu - {{ date('d M Y') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div class="mb-3">
            <input type="text" class="form-control" placeholder="Cari Nama, No. Antrean..." wire:model.live="search">
        </div>

        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Pengadu</th>
                        <th>Status Layanan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($queues as $q)
                        <tr>
                            <td class="fw-bold text-blue">{{ $q->queue_number }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $q->name }}</div>
                                
                                <div class="text-muted small text-truncate" style="max-width: 300px;" title="{{ $q->subject }}">
                                    <i class="ti ti-file-text me-1"></i>{{ $q->subject }}
                                </div>
                            </td>
                            <td>
                                @if($q->status == 'checked_in')
                                    <span class="badge bg-yellow-lt">Menunggu</span>
                                @elseif($q->status == 'calling')
                                    <span class="badge bg-blue-lt">Dipanggil</span>
                                @elseif($q->status == 'serving')
                                    <span class="badge bg-purple-lt">Sedang Dilayani</span>
                                @elseif($q->status == 'served')
                                    <span class="badge bg-green-lt">Selesai</span>
                                @elseif($q->status == 'skipped')
                                    <span class="badge bg-red-lt">Batal/Lewat</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">Tidak ada data pengadu hari ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" wire:click="$refresh">
            <i class="ti ti-refresh me-1"></i> Refresh Data
        </button>
    </div>
</div>