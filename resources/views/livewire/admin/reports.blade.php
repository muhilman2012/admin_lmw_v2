<div>
    <div class="card-header">
        <div class="row w-full">
            <div class="col-11">
                <div class="ms-auto d-flex flex-wrap btn-list">
                    <div class="input-group input-group-flat w-full">
                        <span class="input-group-text">
                            <i class="ti ti-search"></i>
                        </span>
                        <input wire:model.live.debounce.500ms="search" type="text" class="form-control" autocomplete="off" placeholder="Cari berdasarkan Nama, Nomor Tiket, Judul, dll." />
                    </div>
                </div>
            </div>
            <div class="col-1">
                <button class="btn btn-outline-secondary position-relative" data-bs-toggle="modal" data-bs-target="#modal-filter-laporan" >
                    <i class="ti ti-filter me-1"></i> Filter
                    {{-- <span id="filter-active-count" class="badge bg-blue-lt bg-primary ms-2 d-none">0</span> --}}
                </button>
            </div>
        </div>
    </div>
    
    <div id="advanced-table-laporan">
        <div class="table-responsive" style="min-height: 50vh; max-height: 58vh; overflow-y: auto">
            <table class="table table-selectable card-table table-vcenter text-nowrap datatable">
                <thead class="sticky-top bg-white">
                    <tr>
                        <th class="w-1">
                            <button class="table-sort d-flex justify-content-between" wire:click="sortBy('id')">#</button>
                        </th>
                        <th>
                            <button class="table-sort d-flex justify-content-between" wire:click="sortBy('ticket_number')">
                                No Tiket
                            </button>
                        </th>
                        <th>
                            <button class="table-sort d-flex justify-content-between" wire:click="sortBy('reporter_name')">
                                Nama Lengkap
                            </button>
                        </th>
                        <th>
                            <button class="table-sort d-flex justify-content-between" wire:click="sortBy('title')">
                                Judul
                            </button>
                        </th>
                        <th>
                            <button class="table-sort d-flex justify-content-between" wire:click="sortBy('category')">
                                Kategori
                            </button>
                        </th>
                        <th>
                            <button class="table-sort d-flex justify-content-between" wire:click="sortBy('distribution')">
                                Distribusi
                            </button>
                        </th>
                        <th>
                            <button class="table-sort d-flex justify-content-between" wire:click="sortBy('disposition')">
                                Disposisi
                            </button>
                        </th>
                        <th>
                            <button class="table-sort d-flex justify-content-between" wire:click="sortBy('source')">
                                Sumber
                            </button>
                        </th>
                        <th>
                            <button class="table-sort d-flex justify-content-between" wire:click="sortBy('status')">
                                Status
                            </button>
                        </th>
                        <th>
                            <button class="table-sort d-flex justify-content-between" wire:click="sortBy('created_at')">
                                Dikirim
                            </button>
                        </th>
                        <th class="w-1">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-tbody">
                    @forelse ($reports as $index => $report)
                        <tr>
                            <td>{{ $reports->firstItem() + $index }}</td>
                            <td><a href="{{ route('reports.show', $report) }}" class="text-blue">{{ $report->ticket_number }}</a></td>
                            <td style="max-width: 100px; white-space: normal;">{{ \Illuminate\Support\Str::words($report->reporter?->name ?? '-', 2) }}</td>
                            <td style="max-width: 150px; white-space: normal; word-break: break-word;">
                                <div style="
                                    display: -webkit-box;
                                    -webkit-line-clamp: 2;
                                    -webkit-box-orient: vertical;
                                    overflow: hidden;
                                ">
                                    {{ $report->subject }}
                                </div>
                            </td>
                            <td style="max-width: 100px; white-space: normal; word-break: break-word;">{{ $report->category?->name ?? '-' }}</td>
                            <td style="max-width: 100px; white-space: normal; word-break: break-word;">{{ $report->unitKerja?->name ?? $report->deputy?->name ?? '-' }}</td>
                            <td class="{{ $report->disposition ? '' : 'text-danger' }}" style="max-width: 120px; white-space: normal; word-break: break-word;">{{ $report->disposition ?? 'Belum terdisposisi' }}</td>
                            <td style="max-width: 80px; white-space: normal; word-break: break-word;"><span class="badge bg-primary-lt">{{ $report->source }}</span></td>
                            <td style="max-width: 120px; white-space: normal; word-break: break-word;">
                                <div style="
                                    display: -webkit-box;
                                    -webkit-line-clamp: 2;
                                    -webkit-box-orient: vertical;
                                    overflow: hidden;
                                ">
                                    {{ $report->status }}
                                </div>
                            </td>
                            <td style="max-width: 80px; white-space: normal;">{{ $report->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a class="btn btn-icon btn-outline-secondary btn-view" href="{{ route('reports.show', $report->uuid) }}" title="Lihat">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    <a class="btn btn-icon btn-outline-secondary btn-edit" href="#" title="Edit">
                                        <i class="ti ti-pencil"></i>
                                    </a>
                                    <button class="btn btn-icon btn-outline-danger btn-delete" wire:click="deleteReportConfirm({{ $report->id }})" title="Hapus">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty  
                        <tr>
                            <td colspan="11" class="text-center text-muted p-4">
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
                    <span class="me-1" wire:model="perPage">{{ $perPage }}</span>
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
                Menampilkan <span>{{ $reports->count() }}</span> dari <span>{{ $reports->total() }}</span> entri
            </p>
            <ul class="pagination m-0 ms-auto">
                @if ($reports->hasPages())
                    {{ $reports->links() }}
                @endif
            </ul>
        </div>
    </div>
    
    <div class="modal fade" id="modal-filter-laporan" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header sticky-top bg-body z-3 border-bottom">
                    <h5 class="modal-title">Filter Data Pengaduan</h5>
                    <button class="btn-close" data-bs-dismiss="modal" wire:click="updatingFilters"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" wire:model.live="filterKategori">
                                <option value="">Semua Kategori</option>
                                @foreach ($categories as $category)
                                    <option>{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" wire:model.live="filterStatus">
                                <option value="">Semua Status</option>
                                @foreach ($statuses as $status)
                                    <option>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Distribusi</label>
                            <select class="form-select" wire:model.live="filterDistribusi">
                                <option value="">Semua Distribusi</option>
                                @foreach ($distributions as $distribution)
                                    <option>{{ $distribution }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Disposisi</label>
                            <select class="form-select" wire:model.live="filterDisposisi">
                                <option value="">Semua</option>
                                <option>Belum terdisposisi</option>
                                <option>Sudah terdisposisi</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sumber</label>
                            <select class="form-select" wire:model.live="filterSumber">
                                <option value="">Semua Sumber</option>
                                @foreach ($sources as $source)
                                    <option>{{ $source }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Urutkan</label>
                            <select class="form-select" wire:model="sortDirection">
                                <option value="desc">Terbaru</option>
                                <option value="asc">Terlama</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer sticky-bottom bg-body z-3 border-top">
                    <button class="btn btn-link text-danger" wire:click="resetFilters" data-bs-dismiss="modal">Reset</button>
                    <button class="btn btn-primary" data-bs-dismiss="modal">Terapkan</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-confirm-delete" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-status bg-danger"></div>
                <div class="modal-header">
                    <h5 class="modal-title">Hapus data?</h5>
                    <button class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="closeDeleteModal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-alert-circle text-danger" style="font-size: 2rem"></i>
                    <div class="mt-2">Yakin ingin menghapus tiket <strong id="confirm-ticket">—</strong>?</div>
                    <div class="text-secondary small" id="confirm-name"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-link link-secondary" wire:click="closeDeleteModal" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-danger" wire:click="deleteReport" data-bs-dismiss="modal">Ya, hapus</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('swal:confirm', (event) => {
                    const { title, text, confirmButtonText, onConfirmed, onConfirmedParams } = event[0];
                    Swal.fire({
                        title: title,
                        text: text,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: confirmButtonText,
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Livewire.dispatch(onConfirmed, { reportId: onConfirmedParams[0] });
                        }
                    });
                });

                Livewire.on('session:success', (event) => {
                    const message = event[0].message;
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: message,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer);
                            toast.addEventListener('mouseleave', Swal.resumeTimer);
                        }
                    });
                });
            });
        </script>
    @endpush
</div>