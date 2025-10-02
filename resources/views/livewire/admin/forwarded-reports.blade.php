<div>
    <div class="card">
        <div class="card-table">
            <div class="card-header">
                <div class="row w-100">
                    <div class="col-sm-12">
                        <div class="ms-auto d-flex flex-wrap btn-list">
                            <div class="input-group input-group-flat w-full">
                                <span class="input-group-text">
                                    <i class="ti ti-search"></i>
                                </span>
                                <input
                                    wire:model.live.debounce.500ms="search"
                                    type="text"
                                    class="form-control"
                                    autocomplete="off"
                                    placeholder="Cari berdasarkan Nama, Nomor Tiket, atau Instansi Tujuan"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="advanced-table-pengadu">
                <div class="table-responsive" style="min-height: 50vh; max-height: 58vh; overflow-y: auto">
                    <table class="table table-selectable card-table table-vcenter text-nowrap datatable">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th class="w-1">#</th>
                                <th class="w-1">
                                    <button class="table-sort d-flex justify-content-between" wire:click="sortBy('laporan_id')">
                                        No. Tiket
                                    </button>
                                </th>
                                <th>
                                    <button class="table-sort d-flex justify-content-between" wire:click="sortBy('laporan_id')">
                                        Nama Pelapor
                                    </button>
                                </th>
                                <th>
                                    <button class="table-sort d-flex justify-content-between" wire:click="sortBy('institution.name')">
                                        Instansi Tujuan
                                    </button>
                                </th>
                                <th>Status LAPOR!</th>
                                <th>Terakhir Pembaruan</th> 
                                <th>
                                    <button class="table-sort d-flex justify-content-between" wire:click="sortBy('sent_at')">
                                        Tanggal Diteruskan
                                    </button>
                                </th>
                                <th>Status Kirim</th>
                                <th class="w-1">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="table-tbody">
                            @forelse ($forwardings as $index => $forward)
                                <tr wire:key="{{ $forward->id }}">
                                    <td>{{ $forwardings->firstItem() + $index }}</td>
                                    <td>
                                        <a href="{{ route('reports.show', $forward->laporan->uuid) }}" class="text-blue">
                                            {{ $forward->laporan->ticket_number ?? '-' }}
                                        </a>
                                    </td>
                                    <td>{{ $forward->laporan->reporter->name ?? '-' }}</td>
                                    <td>{{ $forward->institution->name ?? '-' }}</td>
                                    
                                    {{-- DATA BARU 1: Status LAPOR! --}}
                                    <td>
                                        @if ($forward->lapor_status_code)
                                            <span class="badge bg-purple-lt">{{ $forward->lapor_status_name ?? $forward->lapor_status_code }}</span>
                                        @else
                                            <span class="badge bg-secondary-lt">Menunggu Cek</span>
                                        @endif
                                    </td>
                                    
                                    {{-- DATA BARU 2: Terakhir Dicek --}}
                                    <td>
                                        {{ $forward->next_check_at?->diffForHumans() ?? 'Belum pernah' }}
                                    </td>
                                    
                                    <td>{{ $forward->sent_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td>
                                        @if ($forward->status === 'terkirim')
                                            <span class="badge bg-green-lt">Terkirim</span>
                                        @elseif ($forward->status === 'gagal_forward')
                                            <span class="badge bg-red-lt">Gagal Forward</span>
                                        @else
                                            <span class="badge bg-yellow-lt">{{ Str::title($forward->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            <a
                                                href="{{ route('forwarding.detail', ['uuid' => $forward->laporan->uuid, 'complaintId' => $forward->complaint_id]) }}"
                                                class="btn btn-sm btn-outline-primary"
                                            ><i class="ti ti-eye me-2"></i>Detail TL</a>
                                            
                                            @if ($forward->status === 'gagal_forward')
                                                <button class="btn btn-sm btn-outline-warning" 
                                                    wire:click="retryForwarding({{ $forward->id }})">
                                                    <i class="ti ti-reload me-1"></i>Ulangi
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted p-4">
                                        <div class="d-flex flex-column align-items-center gap-1">
                                            <div style="font-size: 2rem; line-height: 1">😕</div>
                                            <div><strong>Tidak ada data</strong></div>
                                            <div class="small">Coba ubah filter atau kata kunci pencarian.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="card-footer d-flex align-items-center">
                    <div class="dropdown">
                        <a class="btn dropdown-toggle" data-bs-toggle="dropdown">
                            <span class="me-1">{{ $perPage }}</span>
                            <span>records</span>
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" wire:click="setPerPage(10)">10 records</a>
                            <a class="dropdown-item" wire:click="setPerPage(20)">20 records</a>
                            <a class="dropdown-item" wire:click="setPerPage(50)">50 records</a>
                            <a class="dropdown-item" wire:click="setPerPage(100)">100 records</a>
                        </div>
                    </div>
                    <p class="m-0 text-secondary ms-2">
                        Menampilkan <span>{{ $forwardings->count() }}</span> dari <span>{{ $forwardings->total() }}</span> entri
                    </p>
                    <ul class="pagination m-0 ms-auto">
                        @if ($forwardings->hasPages())
                            {{ $forwardings->links() }}
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>