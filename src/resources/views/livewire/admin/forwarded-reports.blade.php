<div>
    <div class="card">
        <div class="card-table">
            <div class="card-header">
                <div class="row w-100">
                    <div class="col-11">
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
                                    placeholder="Cari berdasarkan No. Tiket, Pelapor, atau Instansi Tujuan"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="col-1">
                        <button class="btn btn-outline-secondary position-relative" data-bs-toggle="modal" data-bs-target="#modal-filter-forwarding">
                            <i class="ti ti-filter me-1"></i> Filter
                            <span id="filter-active-count-forward" class="badge bg-blue-lt bg-primary ms-2 d-none">0</span> 
                        </button>
                    </div>
                </div>
            </div>
            <div id="advanced-table-pengadu">
                <div class="table-responsive" style="min-height: 50vh; max-height: 58vh; overflow-y: auto">
                    <table class="table table-selectable card-table table-vcenter text-nowrap datatable">
                        <thead class="sticky-top bg-white" style="z-index:10;">
                            <tr>
                                <th class="w-1">#</th>
                                <th class="w-1">
                                    No. Tiket
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
                                <th>Disposisi</th>
                                <th>Terakhir Pembaruan</th>
                                <th>Status LAPOR!</th>
                                <th>
                                    <button class="table-sort d-flex justify-content-between" wire:click="sortBy('sent_at')">
                                        Waktu Diteruskan
                                    </button>
                                </th>
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
                                    <td>
                                        <span class="text-wrap" style="max-width: 100px;">
                                            {{ $forward->laporan->reporter->name ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-wrap" style="max-width: 100px;">
                                            {{ $forward->institution->name ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-wrap" style="max-width: 100px;">
                                            {{ $forward->disposisi->institution_name ?? 'Belum Ada Disposisi' }} 
                                        </span>
                                    </td>
                                    <td>
                                        {{ $forward->next_check_at?->diffForHumans() ?? 'Belum pernah' }}
                                    </td>
                                    <td>
                                        @php
                                            $statusName = $forward->lapor_status_name ?? 'Belum Terverifikasi';
                                            // Tentukan warna badge berdasarkan status (Optional, tapi bagus untuk UX)
                                            $badgeClass = match ($statusName) {
                                                'Selesai' => 'bg-success-lt',
                                                'Dalam Proses', 'Terverifikasi' => 'bg-info-lt',
                                                'Ditolak', 'API Error', 'gagal_forward' => 'bg-danger-lt',
                                                default => 'bg-secondary-lt',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }} text-wrap" style="max-width: 100px;">
                                            {{ $statusName }}
                                        </span>
                                    </td>
                                    <td>{{ $forward->sent_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            <a href="{{ route('forwarding.detail', ['uuid' => $forward->laporan->uuid, 'complaintId' => $forward->complaint_id]) }}"
                                                class="btn btn-sm btn-outline-primary load-detail-link" 
                                            ><i class="ti ti-eye me-2"></i>Detail TL</a>
                                            
                                            {{-- @if ($forward->status === 'gagal_forward')
                                                <button class="btn btn-sm btn-outline-warning" 
                                                    wire:click="retryForwarding({{ $forward->id }})">
                                                    <i class="ti ti-reload me-1"></i>Ulangi
                                                </button>
                                            @endif --}}

                                            @hasrole('superadmin')
                                                <button 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    wire:click="deleteForwardingConfirm({{ $forward->id }})">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            @endhasrole
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

    <div class="modal fade" id="modal-filter-forwarding" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header sticky-top bg-body z-3 border-bottom">
                    <h5 class="modal-title">Filter Laporan Diteruskan</h5>
                    <button class="btn-close" data-bs-dismiss="modal" wire:click="resetPage"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Instansi Tujuan</label>
                            <select class="form-select" wire:model.live="filterInstitution">
                                <option value="">Semua Instansi</option>
                                {{-- $institutions harus dimuat di component backend --}}
                                @foreach ($institutions as $inst) 
                                    <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status LAPOR!</label>
                            <select class="form-select" wire:model.live="filterLaporStatus">
                                <option value="">Semua Status LAPOR!</option>
                                {{-- $laporStatuses harus dimuat di component backend --}}
                                @foreach ($laporStatuses as $status) 
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status Penerusan Internal</label>
                            <select class="form-select" wire:model.live="filterInternalStatus">
                                <option value="">Semua Status Internal</option>
                                <option value="terkirim">Terkirim</option>
                                <option value="gagal_forward">Gagal Forward</option>
                                <option value="proses">Diproses (Internal)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Diteruskan</label>
                            <input type="text" class="form-control" id="tanggal-range-forward" wire:model.live="filterDateRange" placeholder="dd/mm/yyyy - dd/mm/yyyy" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer sticky-bottom bg-body z-3 border-top">
                    <button class="btn btn-link text-danger" wire:click="resetFilters">Reset</button>
                    <button class="btn btn-primary" data-bs-dismiss="modal">Terapkan</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            
            const pageLoader = document.getElementById('page-loader-overlay'); 
            const loaderText = document.getElementById('loader-text'); 
            
            // Cek apakah loader ada
            if (!pageLoader || !loaderText) {
                console.warn("Page loader elements not found. Skipping loader setup.");
                return;
            }
            
            // Helper untuk mengontrol Loader
            const toggleLoader = (show, message = "Memproses data...") => {
                pageLoader.classList.toggle('d-none', !show);
                loaderText.textContent = message;
            };

            // --- 1. LOGIKA LIVEWIRE (Untuk Pencarian, Sort, dan Pagination) ---
            
            // Livewire Hook: Menampilkan loader saat AJAX request dimulai
            Livewire.hook('message.sending', (message, component) => {
                toggleLoader(true, "Memperbarui daftar laporan...");
            });

            // Livewire Hook: Menyembunyikan loader saat AJAX request selesai
            Livewire.hook('message.processed', (message, component) => {
                toggleLoader(false);
            });


            // --- 2. LOGIKA PURE JS (Untuk Link Detail TL) ---
            
            const detailLinks = document.querySelectorAll('.load-detail-link');

            if (detailLinks.length > 0) {
                detailLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault(); 
                        
                        toggleLoader(true, "Memuat detail tindak lanjut dari LAPOR! (Mohon tunggu)...");
                        
                        const targetUrl = this.href;
                        
                        // Tunggu sedikit untuk memastikan loader tampil sebelum navigasi
                        setTimeout(() => {
                            window.location.href = targetUrl;
                        }, 100); 
                    });
                });
            }
            
            // --- 3. LOGIKA PENUTUP LOADER SETELAH REDIRECT (Sukses/Gagal) ---
            
            // Ini akan dieksekusi saat halaman dimuat ulang (setelah redirect)
            const successMessage = '{{ session('success') }}';
            const errorMessage = '{{ session('error') }}';
            
            if (successMessage || errorMessage) {
                // Sembunyikan loader jika halaman sudah dimuat ulang
                toggleLoader(false);
                
                // Tampilkan notifikasi
                if (successMessage) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: successMessage,
                        showConfirmButton: false,
                        timer: 5000
                    });
                } else if (errorMessage) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: errorMessage,
                        showConfirmButton: false,
                        timer: 5000
                    });
                }
            }
        });
    </script>
    @endpush
</div>