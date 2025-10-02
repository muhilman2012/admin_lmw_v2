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
                    <span id="filter-active-count" class="badge bg-blue-lt bg-primary ms-2 d-none">0</span>
                </button>
            </div>
        </div>
    </div>
    
    <div id="advanced-table-laporan">
        <div class="table-responsive" style="min-height: 50vh; max-height: 58vh; overflow-y: auto">
            <table class="table table-selectable card-table table-vcenter text-nowrap datatable">
                <colgroup>
                    <col style="width: 2%" />
                    <col style="width: 4%" />
                    <col style="width: 8%" />
                    <col style="width: 16%" />
                    <col style="width: 20%" />
                    <col style="width: 12%" />
                    <col style="width: 8%" />
                    <col style="width: 9%" />
                    <col style="width: 5%" />
                    <col style="width: 12%" />
                    <col style="width: 4%" />
                    <col style="width: 2%" />
                </colgroup>

                <thead class="sticky-top bg-white" style="z-index:10;">
                    <tr>
                        <th class="w-1">
                            <input class="form-check-input m-0 align-middle" type="checkbox" aria-label="Pilih semua laporan" />
                        </th>
                        <th>
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
                            <button class="table-sort d-flex justify-content-between" wire:click="sortBy('subject')">
                                Judul Pengaduan
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
                        <tr wire:key="{{ $report->id }}">
                            <td>
                                <input class="form-check-input m-0 align-middle" type="checkbox" aria-label="Select aduan" />
                            </td>
                            <td class="fs-5">{{ $reports->firstItem() + $index }}</td>
                            <td class="fs-5">
                                <button 
                                    type="button"
                                    wire:click="dispatchReportPreview('{{ $report->uuid }}')"
                                    class="text-blue fw-bold btn btn-link p-0 m-0 border-0 text-start"
                                    title="Pratinjau Laporan"
                                >
                                    {{ $report->ticket_number }}
                                </button>
                            </td>
                            
                            {{-- Nama Lengkap --}}
                            <td style="max-width: 100px;">
                                <div class="multiline-clamp-2">
                                    <span class="fs-5">{{ \Illuminate\Support\Str::words($report->reporter?->name ?? '-', 4, '...') }}</span>
                                </div>
                            </td>
                            
                            {{-- Judul Pengaduan (Sudah ada clamping) --}}
                            <td style="max-width: 150px;">
                                <div class="multiline-clamp-2">
                                    <span class="fs-5">{{ $report->subject }}</span>
                                </div>
                            </td>
                            
                            {{-- Kategori --}}
                            <td style="max-width: 100px;">
                                <div class="multiline-clamp-2">
                                    <span class="fs-5">{{ $report->category?->name ?? '-' }}</span>
                                </div>
                            </td>
                            
                            {{-- Distribusi (Unit/Deputi) --}}
                            <td style="max-width: 100px;">
                                <div class="multiline-clamp-2">
                                    <span class="fs-5">{{ $report->unitKerja?->name ?? $report->deputy?->name ?? '-' }}</span>
                                </div>
                            </td>
                            
                            {{-- Disposisi --}}
                            <td class="{{ $report->disposition ? '' : 'text-danger' }}" style="max-width: 120px;">
                                <div class="multiline-clamp-2">
                                    <span class="fs-5">{{ $report->disposition ?? 'Belum terdisposisi' }}</span>
                                </div>
                            </td>
                            
                            {{-- Sumber --}}
                            <td style="max-width: 80px;">
                                <div class="multiline-clamp-2">
                                    <span class="badge bg-primary-lt fs-5 text-wrap w-100">{{ $report->source }}</span>
                                </div>
                            </td>
                            
                            {{-- Status --}}
                            <td style="max-width: 120px;">
                                <div class="multiline-clamp-2">
                                    <span class="fs-5">{{ $report->status }}</span>
                                </div>
                            </td>
                            
                            {{-- Dikirim --}}
                            <td style="max-width: 80px;">
                                <div class="multiline-clamp-2">
                                    <span class="fs-5">{{ $report->created_at->format('d/m/Y') }}</span>
                                </div>
                            </td>
                            
                            {{-- Aksi --}}
                            <td class="text-end">
                                <div class="btn-list flex-nowrap">
                                    <a class="btn btn-icon btn-outline-secondary btn-view" href="{{ route('reports.show', $report->uuid) }}" title="Lihat">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    <a class="btn btn-icon btn-outline-secondary btn-edit" href="{{ route('reports.edit', $report->uuid) }}" title="Edit">
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
                            <td colspan="12" class="text-center text-muted p-4">
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

        <style>
            /* Gaya untuk membatasi teks hingga 2 baris */
            .multiline-clamp-2 {
                display: -webkit-box !important;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: normal; /* Pastikan teks pindah baris */
                max-width: 100%;
            }
        </style>
    
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
                    <button class="btn-close" data-bs-dismiss="modal" wire:click="resetPaginasi"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" wire:model.live="filterKategori" id="filter-kategori">
                                <option value="">Semua Kategori</option>
                                @foreach ($categories as $category)
                                    <option>{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" wire:model.live="filterStatus" id="filter-status">
                                <option value="">Semua Status</option>
                                @foreach ($statuses as $status)
                                    <option>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Klasifikasi</label>
                            <select class="form-select" wire:model.live="filterKlasifikasi" id="filter-klasifikasi">
                                <option value="">Semua Klasifikasi</option>
                                <option value="Pengaduan berkadar pengawasan">Pengaduan berkadar pengawasan</option>
                                <option value="Pengaduan tidak berkadar pengawasan">Pengaduan tidak berkadar pengawasan</option>
                                <option value="Aspirasi">Aspirasi</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Distribusi</label>
                            <select class="form-select" wire:model.live="filterDistribusi" id="filter-distribusi">
                                <option value="">Semua Distribusi</option>
                                <optgroup label="Deputi">
                                    @foreach ($deputies as $deputy)
                                        <option value="deputy_{{ $deputy->id }}">{{ $deputy->name }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Unit Kerja">
                                    @foreach ($unitKerjas as $unit)
                                        <option value="unit_{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status Analisis</label>
                            <select class="form-select" wire:model.live="filterStatusAnalisis" id="filter-status-analisis">
                                <option value="">Semua Status Analisis</option>
                                @foreach ($analysisStatuses as $statusAnalisis)
                                    <option>{{ $statusAnalisis }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Pengaduan</label>
                            <input type="text" class="form-control" id="tanggal-range-filter" wire:model.live="filterDateRange" placeholder="dd/mm/yyyy - dd/mm/yyyy" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sumber</label>
                            <select class="form-select" wire:model.live="filterSumber" id="filter-sumber">
                                <option value="">Semua Sumber</option>
                                @foreach ($sources as $source)
                                    <option>{{ $source }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Urutkan</label>
                            <select class="form-select" wire:model.live="sortDirection" id="filter-urutkan">
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
    
    <div 
        class="modal fade" 
        id="modal-report-preview" 
        tabindex="-1" 
        aria-hidden="true" 
        wire:ignore.self
    >
        {{-- PERUBAHAN: Ubah modal-md menjadi modal-lg agar lebih lebar --}}
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pratinjau Laporan #{{ $previewReportData['ticket_number'] ?? '' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item"><strong>Pengadu:</strong> {{ $previewReportData['reporter_name'] ?? '-' }}</div>
                        <div class="list-group-item"><strong>Judul:</strong> {{ $previewReportData['subject'] ?? '-' }}</div>
                        
                        <div class="list-group-item"><strong>Status:</strong> {{ $previewReportData['status'] ?? '-' }}</div>
                        <div class="list-group-item"><strong>Status Disposisi:</strong> {{ $previewReportData['disposition'] ?? '-' }}</div>
                        
                        <div class="list-group-item">
                            <strong>Unit:</strong> 
                            {{ $previewReportData['unit_tujuan'] ?? '-' }}
                        </div>
                        <div class="list-group-item">
                            <strong>Deputi:</strong> 
                            {{ $previewReportData['deputi_tujuan'] ?? '-' }}
                        </div>
                        
                        <div class="list-group-item"><strong>Kategori:</strong> {{ $previewReportData['category'] ?? '-' }}</div>
                        <div class="list-group-item"><strong>Sumber:</strong> {{ $previewReportData['source'] ?? '-' }}</div>
                        <div class="list-group-item"><strong>Dibuat:</strong> {{ $previewReportData['created_at'] ?? '-' }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    {{-- Gunakan logika yang aman dari error route --}}
                    @php
                        $detailUuid = $previewReportData['uuid'] ?? false;
                    @endphp
                    <a 
                        href="{{ $detailUuid ? route('reports.show', ['uuid' => $detailUuid]) : '#' }}" 
                        class="btn btn-primary"
                        {{ $detailUuid ? '' : 'disabled' }}
                    >
                        Lihat Detail Lengkap
                    </a>
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
                // Deklarasi variabel global untuk instance library dan komponen
                let litepickerInstance = null;
                let reportsComponent = null; // Instance komponen Livewire
                
                // Elemen DOM
                const dateRangeInput = document.getElementById('tanggal-range-filter');
                const filterModal = document.getElementById('modal-filter-laporan');
                const previewModalElement = document.getElementById('modal-report-preview');
                
                // --- 1. Fungsi Utilitas (Shared Helpers) ---

                const updateFilterBadge = () => {
                    if (!reportsComponent) return;
                    let count = 0;
                    const filters = [
                        'filterKategori', 'filterStatus', 'filterKlasifikasi',
                        'filterDistribusi', 'filterStatusAnalisis', 'filterDateRange',
                        'filterSumber', 'search'
                    ];

                    filters.forEach(filter => {
                        const value = reportsComponent.$wire.get(filter);
                        if (value && value !== 'desc' && value !== '') {
                            count++;
                        }
                    });

                    const badge = document.getElementById('filter-active-count');
                    if (badge) {
                        if (count > 0) {
                            badge.classList.remove('d-none');
                            badge.textContent = count;
                        } else {
                            badge.classList.add('d-none');
                            badge.textContent = '0';
                        }
                    }
                };

                const initializeLitepicker = () => {
                    if (litepickerInstance) { litepickerInstance.destroy(); }
                    
                    if (window.Litepicker && dateRangeInput && reportsComponent) {
                        litepickerInstance = new Litepicker({
                            element: dateRangeInput,
                            singleMode: false,
                            format: 'DD/MM/YYYY',
                            autoApply: true,
                            lang: 'id',
                        });
                        
                        litepickerInstance.on('selected', (start, end) => {
                            const dateRange = `${start.format('DD/MM/YYYY')} - ${end.format('DD/MM/YYYY')}`;
                            reportsComponent.$wire.set('filterDateRange', dateRange);
                            updateFilterBadge();
                        });
                        litepickerInstance.on('clear', () => {
                            reportsComponent.$wire.set('filterDateRange', '');
                            updateFilterBadge();
                        });
                    }
                };

                const initializeTomSelect = (selector, livewireProperty, config = {}) => {
                    const selectElement = document.getElementById(selector);
                    
                    if (window.TomSelect && selectElement && reportsComponent) {
                        if (selectElement.tomselect) { selectElement.tomselect.destroy(); }

                        const defaultConfig = {
                            plugins: { dropdown_input: {} },
                            create: false,
                            allowEmptyOption: true,
                            sortField: { field: "text", direction: "asc" },
                            onItemAdd: (value) => {
                                reportsComponent.$wire.set(livewireProperty, value);
                                updateFilterBadge();
                            },
                            onItemRemove: () => {
                                reportsComponent.$wire.set(livewireProperty, '');
                                updateFilterBadge();
                            },
                        };
                        
                        const finalConfig = { ...defaultConfig, ...config };
                        const tomSelectInstance = new TomSelect(`#${selector}`, finalConfig);
                        
                        const livewireValue = reportsComponent.$wire.get(livewireProperty);
                        if (livewireValue) {
                            tomSelectInstance.setValue(livewireValue);
                        }
                    }
                };
                
                // --- 2. Logika Modal Preview (Pure JS Show/Hide) ---
                
                // Fungsi untuk menyembunyikan modal secara Pure JS (dipanggil saat tombol close diklik)
                const hidePreviewModalPureJS = () => {
                    if (previewModalElement) {
                        previewModalElement.classList.remove('show');
                        previewModalElement.setAttribute('aria-hidden', 'true');
                        document.body.classList.remove('modal-open');
                        
                        setTimeout(() => {
                            previewModalElement.style.display = 'none';
                            const backdrop = document.querySelector('.modal-backdrop');
                            if (backdrop) {
                                backdrop.remove();
                            }
                            
                            // Panggil Livewire untuk mereset data (sama seperti hidden.bs.modal)
                            if (reportsComponent) {
                                reportsComponent.$wire.call('resetPreviewData');
                            }
                        }, 150); 
                    }
                };
                
                // Fungsi untuk menampilkan modal secara Pure JS (dipanggil dari Livewire Dispatch)
                const showPreviewModalPureJS = () => {
                    if (!previewModalElement) return;

                    // Tambahkan backdrop
                    let backdrop = document.querySelector('.modal-backdrop');
                    if (!backdrop) {
                        backdrop = document.createElement('div');
                        // Menggunakan class Bootstrap CSS murni
                        backdrop.classList.add('modal-backdrop', 'fade', 'show'); 
                        document.body.appendChild(backdrop);
                        
                        // Tambahkan listener untuk menutup modal saat klik backdrop
                        backdrop.onclick = hidePreviewModalPureJS;
                    }

                    // Tampilkan modal
                    previewModalElement.style.display = 'block';
                    previewModalElement.removeAttribute('aria-hidden');
                    document.body.classList.add('modal-open');
                    
                    // Tambahkan class 'show' (dengan jeda untuk transisi CSS)
                    setTimeout(() => {
                        previewModalElement.classList.add('show');
                    }, 1);
                };
                
                // --- 3. Livewire Hooks dan Event Listeners ---
                
                // Simpan instance komponen saat terinisialisasi
                Livewire.hook('element.init', ({ component }) => {
                    if (component.name === 'admin.reports') {
                        reportsComponent = component;
                        updateFilterBadge();
                    }
                });
                
                // LISTENER BARU: Dipicu dari Controller setelah data diisi
                // Ganti event show-bootstrap-modal dengan nama event Anda
                Livewire.on('show-bootstrap-modal', () => {
                    showPreviewModalPureJS();
                });

                // Event listener saat modal filter dibuka (untuk inisialisasi TomSelect & Litepicker)
                if (filterModal) {
                    filterModal.addEventListener('shown.bs.modal', () => {
                        initializeLitepicker();
                        initializeTomSelect('filter-kategori', 'filterKategori', { allowEmptyOption: false });
                        initializeTomSelect('filter-status', 'filterStatus');
                        initializeTomSelect('filter-klasifikasi', 'filterKlasifikasi');
                        initializeTomSelect('filter-distribusi', 'filterDistribusi');
                        initializeTomSelect('filter-status-analisis', 'filterStatusAnalisis');
                        initializeTomSelect('filter-sumber', 'filterSumber');
                        initializeTomSelect('filter-urutkan', 'sortDirection');
                        updateFilterBadge();
                    });
                }
                
                // Listener update filter (dipicu setelah reset atau perubahan)
                Livewire.on('filtersUpdated', () => {
                    updateFilterBadge();
                });
                
                // DAFTARKAN LISTENER CLOSE MANUAL (Pure JS)
                // Kita harus mendaftarkan listener untuk tombol close (data-bs-dismiss="modal")
                if (previewModalElement) {
                    const closeButton = previewModalElement.querySelector('[data-bs-dismiss="modal"]');
                    if (closeButton) {
                        closeButton.addEventListener('click', hidePreviewModalPureJS);
                    }
                }
                
                // Hapus kode Bootstrap Modal yang tidak digunakan (previewModalInstance dan logikanya)
                // Hapus blok kode di bawah ini:
                /*
                // Inisialisasi Bootstrap Modal
                if (typeof bootstrap !== 'undefined' && previewModalElement) {
                    previewModalInstance = new bootstrap.Modal(previewModalElement);
                }
                // ...
                // Hapus semua event listener yang menggunakan 'hidden.bs.modal'
                */
                
            });
        </script>
    @endpush
</div>