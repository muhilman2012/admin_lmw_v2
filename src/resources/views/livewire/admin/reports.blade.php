<div>
    <div class="card-header">
        <div class="row w-full">
            <div class="col-11">
                <div class="ms-auto d-flex flex-wrap btn-list">
                    <div class="input-group input-group-flat w-full">
                        <span class="input-group-text">
                            <i class="ti ti-search"></i>
                        </span>
                        <input wire:model.live.debounce.500ms="search" type="text" class="form-control" autocomplete="off" placeholder="Cari berdasarkan Pelapor, Nomor Tiket, Judul, dll." />
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
    @if (count($selectedReports) > 0)
    <div class="card-header border-bottom-0 pt-0 pb-3" style="z-index: 5;">
        <div class="d-flex align-items-center">
            <span class="me-3 text-secondary">Dipilih: {{ count($selectedReports) }} Laporan</span>
            
            <div class="btn-list flex-nowrap">
                {{-- Tombol Ubah Kategori Massal --}}
                <button class="btn btn-outline-warning" 
                        data-bs-toggle="modal" 
                        data-bs-target="#modal-mass-category" 
                        title="Ubah Kategori & Batalkan Tugas">
                    <i class="ti ti-tag me-1"></i> Ubah Kategori Massal
                </button>

                {{-- Tombol Disposisi Massal --}}
                @can('assign reports')
                <button class="btn btn-outline-info" 
                        wire:click="massOpenDispositionModal" 
                        title="Disposisi ke Analis">
                    <i class="ti ti-share-3 me-1"></i> Disposisi Massal
                </button>
                @endcan
                
                {{-- Tombol Batalkan Seleksi --}}
                <button class="btn btn-outline-secondary" wire:click="$set('selectedReports', [])">
                    <i class="ti ti-x me-1"></i> Batalkan Seleksi
                </button>
            </div>
        </div>
    </div>
    @endif
    
    <div id="advanced-table-laporan">
        <div class="table-responsive" style="min-height: 50vh; max-height: 58vh; overflow-y: auto">
            <table class="table table-selectable card-table table-vcenter text-nowrap datatable">
                <colgroup>
                    <col style="width: 2%" />
                    <col style="width: 4%" />
                    <col style="width: 8%" />
                    <col style="width: 14%" />
                    <col style="width: 18%" />
                    <col style="width: 12%" />
                    <col style="width: 8%" />
                    <col style="width: 9%" />
                    <col style="width: 5%" />
                    <col style="width: 10%" />
                    <col style="width: 8%" />
                    <col style="width: 2%" />
                </colgroup>

                <thead class="sticky-top bg-white" style="z-index:10;">
                    <tr>
                        <th class="w-1">
                            <input 
                                class="form-check-input m-0 align-middle" 
                                type="checkbox" 
                                aria-label="Pilih semua laporan" 
                                wire:model.live="selectAll" 
                            />
                        </th>
                        <th class="w-1">#</th>
                        <th class="w-1">No Tiket</th>
                        <th>
                            <button class="table-sort d-flex justify-content-between" wire:click="sortBy('reporter_name')">
                                Nama Lengkap
                            </button>
                        </th>
                        <th class="w-1">Judul Pengaduan</th>
                        <th>
                            <button class="table-sort d-flex justify-content-between" wire:click="sortBy('category')">
                                Kategori
                            </button>
                        </th>
                        <th class="w-1">Distribusi</th>
                        <th style="width: 8%;">
                            @php $user = Auth::user(); @endphp
                            @if ($user->hasRole('analyst'))
                                <button class="table-sort d-flex justify-content-between">
                                    Disposisi Dari
                                </button>
                            @else
                                <button class="table-sort d-flex justify-content-between">
                                    Disposisi Ke
                                </button>
                            @endif
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
                                <input 
                                    class="form-check-input m-0 align-middle" 
                                    type="checkbox" 
                                    aria-label="Select aduan" 
                                    wire:model.live="selectedReports" 
                                    value="{{ $report->id }}"
                                />
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
                            <td class="text-start" style="max-width: 120px;">
                                <div class="multiline-clamp-2">
                                    @php
                                        // Ambil assignment terbaru
                                        $latestAssignment = $report->assignments->sortByDesc('created_at')->first();
                                        $user = Auth::user();
                                    @endphp

                                    @if ($latestAssignment)
                                        @if ($user->hasRole('analyst'))
                                            {{-- TAMPILAN ANALYST: Disposisi DARI (assigned_by_id) --}}
                                            @php $assigner = $latestAssignment->assignedBy ?? null; @endphp
                                            @if ($assigner)
                                                <span 
                                                    class="btn-link p-0 m-0 border-0 text-start text-dark fw-medium d-inline-block"
                                                    title="Ditugaskan oleh {{ $assigner->name }}"
                                                >
                                                    <span class="fs-5">{{ $assigner->name }}</span>
                                                </span>
                                            @else
                                                <span class="fs-5 text-muted">Disposisi ada</span>
                                            @endif
                                        @else
                                            {{-- TAMPILAN SELAIN ANALYST (Admin/Deputy/etc.): Disposisi KE (assigned_to_id) --}}
                                            @php $analyst = $latestAssignment->assignedTo ?? null; @endphp
                                            <button 
                                                type="button"
                                                class="btn btn-link p-0 m-0 border-0 text-start text-dark fw-medium"
                                                wire:click="openEditDispositionModal({{ $latestAssignment->id }})"
                                                title="Ubah Analis: {{ $analyst->name ?? 'Telah Ditugaskan' }}"
                                            >
                                                <span class="fs-5">{{ $analyst->name ?? 'Telah Disposisi' }}</span>
                                            </button>
                                        @endif
                                    @else
                                        {{-- Status: Belum Ditugaskan (Sama untuk semua role, kecuali Analis tidak perlu tombol ini) --}}
                                        <span class="fs-5 text-danger">Belum terdisposisi</span>
                                        
                                        {{-- Tombol Disposisi Cepat (hanya jika punya izin assign) --}}
                                        @can('assign reports')
                                            @if (!$user->hasRole('analyst'))
                                                <button 
                                                    class="btn btn-sm btn-outline-info mt-1" 
                                                    wire:click="openDispositionModal({{ $report->id }})" 
                                                    title="Tugaskan Sekarang">
                                                    Tugaskan
                                                </button>
                                            @endif
                                        @endcan
                                    @endif
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
                            <td style="max-width: 8%;">
                                <div class="multiline-clamp-2">
                                    <span class="fs-5 d-block">{{ $report->created_at->format('d/m/Y') }}</span>
                                    
                                    @if ($report->badge_text)
                                    <span 
                                        class="badge {{ $report->badge_class }} mt-1" 
                                        style="font-size: 0.6rem;"
                                        title="{{ $report->badge_tooltip }}" 
                                        data-bs-toggle="tooltip" {{-- Tambahkan ini jika menggunakan Tooltip Tabler/Bootstrap --}}
                                    >
                                        {{ $report->badge_text }}
                                    </span>
                                    @endif
                                    
                                </div>
                            </td>
                            
                            {{-- Aksi --}}
                            <td class="text-end">
                                <div class="btn-list flex-nowrap">
                                    <a class="btn btn-icon btn-outline-secondary btn-view" href="{{ route('reports.show', $report->uuid) }}" title="Lihat">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    @if (Auth::user()->hasAnyRole(['superadmin', 'admin', 'deputy', 'asdep_karo']))
                                        <button 
                                            class="btn btn-icon btn-outline-info btn-disposition" 
                                            wire:click="openDispositionModal({{ $report->id }})" 
                                            title="Disposisi ke Analis">
                                            <i class="ti ti-share-3"></i>
                                        </button>
                                    @endif
                                    {{-- <a class="btn btn-icon btn-outline-secondary btn-edit" href="{{ route('reports.edit', $report->uuid) }}" title="Edit">
                                        <i class="ti ti-pencil"></i>
                                    </a> --}}
                                    @can('delete reports')
                                    <button class="btn btn-icon btn-outline-danger btn-delete" wire:click="deleteReportConfirm({{ $report->id }})" title="Hapus">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                    @endcan
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
                    <span>data</span>
                </a>
                <div class="dropdown-menu">
                    <a class="dropdown-item" wire:click="setPerPage(10)">10 data</a>
                    <a class="dropdown-item" wire:click="setPerPage(20)">20 data</a>
                    <a class="dropdown-item" wire:click="setPerPage(50)">50 data</a>
                    <a class="dropdown-item" wire:click="setPerPage(100)">100 data</a>
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
                                <option value="unassigned">Belum Terdistribusi</option>
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
                            <label class="form-label">Disposisi</label>
                            <select class="form-select" wire:model.live="filterDisposisi" id="filter-disposisi">
                                <option value="">Semua Disposisi</option>
                                <option value="Belum terdisposisi">Belum Terdisposisi</option>
                                
                                {{-- Opsi Analis yang Dikelompokkan --}}
                                @foreach ($availableAnalysts as $deputyName => $unitGroups)
                                    <optgroup label="{{ $deputyName }}">
                                        @foreach ($unitGroups as $unitName => $analysts)
                                            <optgroup label="&nbsp;&nbsp;&nbsp;&nbsp;{{ $unitName }}">
                                                @foreach ($analysts as $analyst)
                                                    {{-- PENTING: Value adalah Nama Analis --}}
                                                    <option value="{{ $analyst->name }}">{{ $analyst->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                                
                                <option value="Sudah terdisposisi">Sudah Terdisposisi</option>
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
                    <button class="btn btn-link text-danger" wire:click="resetFilters">Reset</button>
                    <button class="btn btn-primary" id="apply-filter-button">Terapkan</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-disposition" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-md modal-dialog-centered">
            <form wire:submit="submitDisposition">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Disposisi Laporan</h5>
                        <button 
                            type="button" 
                            class="btn-close" 
                            data-bs-dismiss="modal" 
                            aria-label="Close"
                            wire:click.prevent="resetDispositionData"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-secondary">Anda akan mendisposisikan laporan #{{ $dispatchReportTicketNumber ?? $dispatchReportId }} ke Analis.</p>
                        <div class="mb-3">
                            <label class="form-label required">Tugaskan Kepada Analis:</label>
                            <div wire:ignore>
                                <select 
                                    id="analyst-select"
                                    wire:model="analystId" 
                                    class="form-select @error('analystId') is-invalid @enderror"
                                >
                                    <option value="">-- Pilih Analis --</option>
                                    @foreach ($availableAnalysts as $deputyName => $unitGroups)
                                        <optgroup label="{{ $deputyName }}">
                                            @foreach ($unitGroups as $unitName => $analysts)
                                                <optgroup label="&nbsp;&nbsp;&nbsp;&nbsp;{{ $unitName }}">
                                                    @foreach ($analysts as $analyst)
                                                        <option value="{{ $analyst->id }}">
                                                            {{ $analyst->name }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            @error('analystId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        
                        {{-- Catatan Disposisi --}}
                        <div class="mb-3">
                            <label class="form-label">Catatan Disposisi (Opsional)</label>
                            <textarea wire:model="dispositionNotes" class="form-control @error('dispositionNotes') is-invalid @enderror" rows="3"></textarea>
                            @error('dispositionNotes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    @php
                    $isEditMode = (bool)$currentAssignmentId;
                    @endphp
                    <div class="modal-footer">
                        @if ($isEditMode)
                            {{-- Mode EDIT/UBAH --}}
                            <button 
                                type="button" 
                                class="btn btn-danger me-auto"
                                wire:click.prevent="deleteAssignmentConfirm" 
                            >
                                <i class="ti ti-x me-2"></i> Hapus Tugas
                            </button>
                            
                            <button type="button" class="btn btn-secondary" wire:click.prevent="resetDispositionData">Batal</button>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-2"></i> Simpan Perubahan
                            </button>
                        @else
                            {{-- Mode BARU/TUGASKAN --}}
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click.prevent="resetDispositionData">Batal</button>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-2"></i> Tugaskan
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modal-mass-category" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-md modal-dialog-centered">
            <form id="mass-category-form-livewire"> 
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ubah Kategori Massal</h5>
                        <button type="button" class="btn-close" aria-label="Close" data-bs-dismiss="modal" onclick="hideModalPureJS(document.getElementById('modal-mass-category'), 'resetMassActionProperties')"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-danger">Anda akan mengubah kategori untuk {{ count($selectedReports) }} laporan. Tindakan ini akan <span>menghapus semua disposisi/tugas analis lama</span> dari laporan yang dipilih.</p>
                        
                        <div class="mb-3">
                            <label class="form-label required">Pilih Kategori Baru:</label>
                            <div wire:ignore>
                                <select 
                                    id="mass-category-select" 
                                    wire:model="newCategoryId" 
                                    class="form-select @error('newCategoryId') is-invalid @enderror"
                                >
                                    <option value="">-- Pilih Kategori --</option>
                                    
                                    {{-- Ulangi Logika dari View Buat Pengaduan --}}
                                    @foreach($categoriesForMassSelect as $category) {{-- Asumsi nama variabel baru --}}
                                        
                                        {{-- Opsi Kategori Utama (Parent dapat dipilih) --}}
                                        <option value="{{ $category->id }}" data-type="parent">
                                            {{ $category->name }} (Utama)
                                        </option>
                                        
                                        {{-- Opsi Sub-Kategori (Children) --}}
                                        @if ($category->children->count() > 0)
                                            <optgroup label="↳ Sub-Kategori {{ $category->name }}">
                                                @foreach($category->children as $childCategory)
                                                    <option value="{{ $childCategory->id }}" data-parent-id="{{ $category->id }}">
                                                        &nbsp;&nbsp;{{ $childCategory->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                        
                                    @endforeach
                                </select>
                                @error('newCategoryId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @error('newCategoryId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="hideModalPureJS(document.getElementById('modal-mass-category'), 'resetMassActionProperties')">Batal</button>
                        <button type="button" class="btn btn-warning" onclick="showMassCategoryConfirmation()">
                            <i class="ti ti-refresh me-2"></i> Update & Reset Tugas
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modal-confirm-category-mass" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="ti ti-alert-triangle me-2"></i>Konfirmasi Ubah Kategori Massal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.hideConfirmationMassCategory()"></button>
                </div>
                <div class="modal-body text-start">
                    <p>Anda akan mengubah kategori untuk <strong id="mass-count-reports-conf">X</strong> laporan.</p>
                    
                    <ul class="list-unstyled p-0 mb-3 border-top pt-2">
                        <li class="mb-2">
                            <small class="text-muted d-block">Kategori Baru:</small>
                            <strong id="konfirmasi-new-category" class="text-primary fs-5">Memuat...</strong>
                        </li>
                        <li class="mb-2">
                            <small class="text-muted d-block">Tujuan Unit Kerja:</small>
                            <strong id="konfirmasi-new-deputy" class="text-info">Memuat...</strong>
                        </li>
                    </ul>

                    <p class="text-danger fw-bold border-top pt-2">Semua penugasan Analis lama akan dihapus.</p>
                    <p class="fw-bold text-center mt-3">Lanjutkan proses ini?</p>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="window.hideConfirmationMassCategory()">Batal</button>
                    <button type="button" class="btn btn-danger" onclick="window.submitMassCategoryUpdate()">
                        Ya, Update Massal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-mass-disposition" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Disposisi Massal</h5>
                        <button type="button" class="btn-close" aria-label="Close" data-bs-dismiss="modal" wire:click.prevent="resetMassActionProperties"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-secondary">Disposisikan <span>{{ count($selectedReports) }}</span> laporan ke Analis berikut.</p>
                        
                        {{-- Dropdown Analis yang Difilter dan Dikelompokkan --}}
                        <div class="mb-3">
                            <label class="form-label required">Tugaskan Kepada Analis:</label>
                            <div wire:ignore>
                                <select 
                                    id="mass-analyst-select" 
                                    wire:model="newAnalystId" 
                                    class="form-select @error('newAnalystId') is-invalid @enderror"
                                >
                                    <option value="">-- Pilih Analis --</option>
                                    @foreach ($availableAnalysts as $deputyName => $unitGroups)
                                        <optgroup label="{{ $deputyName }}">
                                            @foreach ($unitGroups as $unitName => $analysts)
                                                <optgroup label="&nbsp;&nbsp;&nbsp;&nbsp;{{ $unitName }}">
                                                    @foreach ($analysts as $analyst)
                                                        <option value="{{ $analyst->id }}">
                                                            {{ $analyst->name }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            @error('newAnalystId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        
                        {{-- Catatan Disposisi --}}
                        <div class="mb-3">
                            <label class="form-label">Catatan Disposisi (Opsional)</label>
                            <textarea wire:model="dispositionNotes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click.prevent="resetMassActionProperties">Batal</button>
                        <button type="button" class="btn btn-primary" onclick="submitMassDispositionSafe()">
                            <i class="ti ti-check me-2"></i> Tugaskan {{ count($selectedReports) }} Laporan
                        </button>
                    </button>
                    </div>
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
                    <button 
                        type="button" 
                        class="btn-close" 
                        aria-label="Close" 
                        wire:click.prevent="closeDeleteModal"
                    ></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-alert-circle text-danger" style="font-size: 2rem"></i>
                    <div class="mt-2">Yakin ingin menghapus tiket <strong id="confirm-ticket">{{ $confirmDeleteTicket ?? '—' }}</strong>?</div>
                    <div class="text-secondary small" id="confirm-name">({{ $confirmDeleteName ?? '' }})</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" wire:click.prevent="closeDeleteModal">Batal</button>
                    
                    <button 
                        type="button" 
                        class="btn btn-danger" 
                        wire:click="deleteReport" 
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading wire:target="deleteReport" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Ya, hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-confirm-delete-assignment" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-status bg-danger"></div>
                <div class="modal-header">
                    <h5 class="modal-title">Batalkan Tugas Analis?</h5>
                    <button 
                        type="button" 
                        class="btn-close" 
                        aria-label="Close" 
                        wire:click.prevent="reset(['confirmDeleteAssignmentId', 'confirmDeleteAssignmentTicket'])"
                    ></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-alert-circle text-danger" style="font-size: 2rem"></i>
                    <div class="mt-2">Yakin ingin membatalkan tugas untuk Analis <strong class="text-dark">{{ $confirmDeleteAssignmentAnalyst ?? '—' }}</strong> pada laporan #{{ $confirmDeleteAssignmentTicket ?? '—' }}?</div>
                    <div class="text-secondary small">Tugas akan dihapus dan status laporan direset.</div>
                </div>
                <div class="modal-footer">
                    <button 
                        type="button" 
                        class="btn btn-link link-secondary" 
                        wire:click.prevent="reset(['confirmDeleteAssignmentId', 'confirmDeleteAssignmentTicket'])"
                    >Batal</button>
                    
                    <button 
                        type="button" 
                        class="btn btn-danger" 
                        wire:click="deleteAssignment"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading wire:target="deleteAssignment" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Ya, Batalkan Tugas
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Gunakan IIFE (Immediately Invoked Function Expression) untuk mengisolasi variabel
            (function () {
                // --- VARIABEL DAN ELEMEN DOM ---
                let reportsComponent = null; 
                let litepickerInstance = null;
                
                // Elemen DOM statis
                const dateRangeInput = document.getElementById('tanggal-range-filter');
                const previewModalElement = document.getElementById('modal-report-preview');
                const dispositionModalElement = document.getElementById('modal-disposition');
                const filterModal = document.getElementById('modal-filter-laporan');
                const massCategoryModalElement = document.getElementById('modal-mass-category');
                const massCategoryConfirmationModalElement = document.getElementById('modal-confirm-category-mass');
                const confirmDeleteModalElement = document.getElementById('modal-confirm-delete');

                // --- FUNGSI UTILITY INTI ---
                const getReportsComponent = () => {
                    if (!reportsComponent && typeof Livewire !== 'undefined') {
                        const componentRoot = document.querySelector('[wire\\:id]');
                        if (componentRoot) { reportsComponent = Livewire.find(componentRoot.getAttribute('wire:id')); }
                    }
                    return reportsComponent;
                };

                const updateFilterBadge = () => {
                    const component = getReportsComponent(); 
                    if (!component) return;

                    let count = 0;
                    const filters = [
                        'filterKategori', 'filterStatus', 'filterKlasifikasi',
                        'filterDistribusi', 'filterStatusAnalisis', 'filterDateRange',
                        'filterSumber', 'search'
                    ];

                    filters.forEach(filter => {
                        const value = component.$wire.get(filter); 
                        
                        if (value && value !== '' && value !== 'desc' && value !== 'asc') {
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
                
                // FUNGSI REUSABLE UNTUK MENUTUP MODAL (Pure JS) - GLOBAL
                window.hideModalPureJS = (modalElement, resetDataMethod = null) => {
                    if (modalElement) {
                        
                        const component = getReportsComponent(); 
                        if (component && resetDataMethod) {
                            component.$wire.call(resetDataMethod);
                        }
                        
                        modalElement.classList.remove('show');
                        modalElement.setAttribute('aria-hidden', 'true');
                        
                        const visibleModals = document.querySelectorAll('.modal.show');
                        if (visibleModals.length <= 1) { 
                            document.body.classList.remove('modal-open');
                            const backdrop = document.querySelector('.modal-backdrop');
                            if (backdrop) { backdrop.remove(); }
                        }

                        setTimeout(() => {
                            modalElement.style.display = 'none';
                        }, 150);
                    }
                };

                // FUNGSI REUSABLE UNTUK MENAMPILKAN MODAL (Pure JS) 
                const showModalPureJS = (modalElement) => {
                    if (!modalElement) return;

                    let backdrop = document.querySelector('.modal-backdrop');
                    if (!backdrop) {
                        backdrop = document.createElement('div');
                        backdrop.classList.add('modal-backdrop', 'fade', 'show'); 
                        document.body.appendChild(backdrop);
                        
                        backdrop.onclick = () => {
                            const topModal = document.querySelector('.modal.show:last-child'); 
                            if (topModal) {
                                if (topModal.id === 'modal-confirm-category-mass') {
                                    window.hideConfirmationMassCategory();
                                } else {
                                    window.hideModalPureJS(topModal); 
                                }
                            }
                        };
                    }

                    modalElement.style.display = 'block';
                    modalElement.removeAttribute('aria-hidden');
                    document.body.classList.add('modal-open');
                    
                    setTimeout(() => {
                        modalElement.classList.add('show');
                    }, 1);
                };

                // --- FUNGSI INITIATOR TOMSELECT (ANTI-LAG FIX) ---
                const initializeTomSelect = (selector, livewireProperty, config = {}) => {
                    const selectElement = document.getElementById(selector);
                    const component = getReportsComponent(); 

                    if (window.TomSelect && selectElement && component) {
                        
                        // JIKA SUDAH ADA, JANGAN BUAT ULANG, HANYA UPDATE NILAI DAN KELUAR
                        if (selectElement.tomselect) {
                            const livewireValue = component.$wire.get(livewireProperty);
                            if (livewireValue) { selectElement.tomselect.setValue(livewireValue, true); }
                            return; // SANGAT PENTING: MENGHENTIKAN RE-INIT BERULANG
                        }

                        const defaultConfig = {
                            plugins: { dropdown_input: {} },
                            create: false,
                            allowEmptyOption: true,
                            sortField: { field: "text", direction: "asc" },
                            // Pastikan Livewire state diperbarui saat perubahan terjadi di TomSelect
                            onChange: (value) => { component.$wire.set(livewireProperty, value); },
                            onItemAdd: (value) => { component.$wire.set(livewireProperty, value); updateFilterBadge(); }, 
                            onItemRemove: () => { component.$wire.set(livewireProperty, ''); updateFilterBadge(); },
                        };
                                    
                        const finalConfig = { ...defaultConfig, ...config };
                        const tomSelectInstance = new TomSelect(`#${selector}`, finalConfig);
                                    
                        const livewireValue = component.$wire.get(livewireProperty);
                        if (livewireValue) {
                            tomSelectInstance.setValue(livewireValue, true);
                        }
                    }
                };
                
                // FUNGSI INITIATOR SPESIFIK (Menggunakan initializeTomSelect yang sudah aman)
                const initializeAnalystTomSelect = () => { initializeTomSelect('analyst-select', 'analystId', { onChange: (value) => { getReportsComponent().$wire.set('analystId', value); }, onInitialize: function() { const initialValue = getReportsComponent().$wire.get('analystId'); if (initialValue) { this.setValue(initialValue); } } }); };
                const initializeMassAnalystTomSelect = () => { initializeTomSelect('mass-analyst-select', 'newAnalystId', { onChange: (value) => { getReportsComponent().$wire.set('newAnalystId', value); }, onInitialize: function() { const initialValue = getReportsComponent().$wire.get('newAnalystId'); if (initialValue) { this.setValue(initialValue); } } }); };
                const initializeMassCategoryTomSelect = () => { initializeTomSelect('mass-category-select', 'newCategoryId', { allowEmptyOption: false, onChange: (value) => { getReportsComponent().$wire.set('newCategoryId', value); } }); };
                const initializeLitepicker = () => { 
                    const component = getReportsComponent();
                    if (litepickerInstance) { litepickerInstance.destroy(); } 
                    if (window.Litepicker && dateRangeInput && component) {
                        litepickerInstance = new Litepicker({ element: dateRangeInput, singleMode: false, format: 'DD/MM/YYYY', autoApply: true, lang: 'id', });
                        litepickerInstance.on('selected', (start, end) => { const dateRange = `${start.format('DD/MM/YYYY')} - ${end.format('DD/MM/YYYY')}`; component.$wire.set('filterDateRange', dateRange); updateFilterBadge(); });
                        litepickerInstance.on('clear', () => { component.$wire.set('filterDateRange', ''); updateFilterBadge(); });
                    }
                };

                // -----------------------------------------------------------
                // --- LOGIKA UTAMA KONFIRMASI UBAH KATEGORI MASSAL (GLOBAL) ---
                // -----------------------------------------------------------

                window.showMassCategoryConfirmation = function () {
                    const component = getReportsComponent();
                    if (!component) { console.error("Livewire component not found."); return; }
                    
                    const newCategoryId = component.$wire.get('newCategoryId');
                    if (!newCategoryId) { component.$wire.call('validate', 'newCategoryId'); return; }
                    
                    const selectedReportsCount = component.$wire.get('selectedReports').length;
                    const categorySelect = document.getElementById('mass-category-select');
                    const selectedOption = categorySelect.querySelector(`option[value="${newCategoryId}"]`);
                    const newCategoryName = selectedOption ? selectedOption.textContent.trim() : 'N/A';
                    
                    // Ambil Nama Deputi/Unit Kerja dari properti Livewire (dihitung di PHP)
                    const deputyName = component.$wire.get('newCategoryDestinationDeputy');
                    const unitName = component.$wire.get('newCategoryDestinationName');
                    let newDeputyUnitName = `${deputyName} / ${unitName}`;

                    // 2. Update konten modal konfirmasi
                    document.getElementById('mass-count-reports-conf').textContent = selectedReportsCount;
                    document.getElementById('konfirmasi-new-category').textContent = newCategoryName;
                    document.getElementById('konfirmasi-new-deputy').textContent = newDeputyUnitName;

                    // 3. Sembunyikan modal Ubah Kategori
                    window.hideModalPureJS(massCategoryModalElement); 
                    
                    // 4. Tampilkan modal konfirmasi setelah jeda singkat
                    setTimeout(() => {
                        showModalPureJS(massCategoryConfirmationModalElement);
                    }, 150);
                };

                // Fungsi untuk menyembunyikan modal konfirmasi dan kembali ke modal aksi cepat (jika batal)
                window.hideConfirmationMassCategory = () => {
                    window.hideModalPureJS(massCategoryConfirmationModalElement); 
                    
                    // Buka kembali modal Ubah Kategori Massal
                    setTimeout(() => {
                        showModalPureJS(massCategoryModalElement);
                    }, 150);
                }

                // Fungsi untuk melanjutkan submit form mass update category
                window.submitMassCategoryUpdate = function () {
                    window.hideModalPureJS(massCategoryConfirmationModalElement); 
                    const component = getReportsComponent();
                    if (component) {
                        component.$wire.call('massUpdateCategory');
                    }
                };

                window.submitMassDispositionSafe = function() {
                    const component = getReportsComponent();
                    
                    if (!component) {
                        console.error("Livewire component not found.");
                        return;
                    }
                    
                    const analystSelectEl = document.getElementById('mass-analyst-select');
                    let newAnalystIdValue = '';
                    
                    // Ambil nilai TomSelect
                    if (analystSelectEl && analystSelectEl.tomselect) {
                        newAnalystIdValue = analystSelectEl.tomselect.getValue();
                    } else {
                        newAnalystIdValue = analystSelectEl ? analystSelectEl.value : null;
                    }
                    
                    component.$wire.set('newAnalystId', newAnalystIdValue, true);
                    
                    component.$wire.call('massSubmitDisposition');
                };
                
                // --- LIVEWIRE HOOKS & LISTENER SETUP ---
                
                document.addEventListener('DOMContentLoaded', () => {
                    if (typeof Livewire !== 'undefined') {
                        
                        // Simpan instance komponen saat terinisialisasi
                        Livewire.hook('element.init', ({ component }) => {
                            if (component.name === 'admin.reports') {
                                reportsComponent = component;
                            }
                        });

                        // Hook untuk TomSelect Initialization
                        Livewire.hook('element.initialized', ({ el }) => {
                            if (el.id === 'analyst-select') { initializeAnalystTomSelect(); }
                            if (el.id === 'mass-analyst-select') { initializeMassAnalystTomSelect(); }
                            // Filter TomSelects filter agar diinisialisasi hanya saat modal filter dibuka
                        });
                        
                        // LISTENER UNTUK MODAL DISPOSISI (PASTIKAN TOMSELECT DIINISIALISASI SETELAH SHOW)
                        Livewire.on('show-disposition-modal', () => { 
                            showModalPureJS(dispositionModalElement); 
                            initializeAnalystTomSelect(); // Inisialisasi cepat TomSelect satuan
                        });
                        
                        Livewire.on('show-mass-disposition-modal', () => { 
                            const modalElement = document.getElementById('modal-mass-disposition');
                            
                            if (modalElement) {
                                showModalPureJS(modalElement);
                                initializeMassAnalystTomSelect();
                            } else {
                                console.error("Modal element #modal-mass-disposition not found.");
                            }
                        });

                        Livewire.on('show-report-preview-modal', () => { 
                            showModalPureJS(previewModalElement); 
                        });

                        Livewire.on('show-delete-confirm-modal', () => { 
                            showModalPureJS(confirmDeleteModalElement); 
                        });

                        Livewire.on('close-confirm-delete-modal', () => {
                            window.hideModalPureJS(confirmDeleteModalElement); 
                        });

                        Livewire.on('close-disposition-modal', () => {
                            const modalElement = document.getElementById('modal-disposition');
                            
                            if (modalElement) {
                                window.hideModalPureJS(modalElement, 'resetDispositionData'); 
                            }
                        });

                        Livewire.on('close-mass-disposition-modal', () => { 
                            const modalElement = document.getElementById('modal-mass-disposition');
                            if (modalElement) {
                                window.hideModalPureJS(modalElement); 
                            }
                        });

                        // Listener untuk modal Kategori Massal (TomSelect diinisialisasi saat modal dibuka)
                        if (massCategoryModalElement) {
                            massCategoryModalElement.addEventListener('shown.bs.modal', initializeMassCategoryTomSelect);
                        }
                        
                        // --- SETUP LISTENER PADA MODAL FILTER ---
                        if (filterModal) {
                            filterModal.addEventListener('shown.bs.modal', () => {
                                // Inisialisasi semua filter di sini
                                initializeLitepicker();
                                initializeTomSelect('filter-kategori', 'filterKategori', { allowEmptyOption: false });
                                initializeTomSelect('filter-status', 'filterStatus');
                                initializeTomSelect('filter-klasifikasi', 'filterKlasifikasi');
                                initializeTomSelect('filter-distribusi', 'filterDistribusi');
                                initializeTomSelect('filter-status-analisis', 'filterStatusAnalisis');
                                initializeTomSelect('filter-sumber', 'filterSumber');
                                initializeTomSelect('filter-urutkan', 'sortDirection');
                                initializeTomSelect('filter-disposisi', 'filterDisposisi');
                                updateFilterBadge(); 
                            });
                            
                            // [Handler tombol APPLY/RESET Filter tetap sama]
                            const applyButton = document.getElementById('apply-filter-button');
                            if (applyButton) {
                                applyButton.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    updateFilterBadge();
                                    window.hideModalPureJS(filterModal);
                                });
                            }
                            
                            const resetButton = filterModal.querySelector('.btn-link.text-danger[wire\\:click="resetFilters"]');
                            if (resetButton) {
                                resetButton.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    const component = getReportsComponent();
                                    if (component) { component.call('resetFilters'); }
                                    window.hideModalPureJS(filterModal);
                                });
                            }
                            
                            const closeButtonHeader = filterModal.querySelector('.btn-close');
                            if (closeButtonHeader) {
                                closeButtonHeader.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    window.hideModalPureJS(filterModal);
                                });
                            }
                        }
                        
                        // --- SETUP GLOBAL CLOSE LISTENERS (UNTUK TOMBOL DI DALAM MODAL) ---
                        document.body.addEventListener('click', (e) => {
                            const dismissBtn = e.target.closest('[data-bs-dismiss="modal"]');
                            if (dismissBtn) {
                                const modal = dismissBtn.closest('.modal');
                                if (modal && modal.id !== 'modal-filter-laporan') { 
                                    e.preventDefault();
                                    
                                    let resetMethod = null;
                                    if (modal.id === 'modal-report-preview') { resetMethod = 'resetPreviewData'; } 
                                    else if (modal.id === 'modal-disposition') { resetMethod = 'resetDispositionData'; } 
                                    else if (modal.id.includes('confirm-delete') || modal.id.includes('mass') || modal.id === 'modal-confirm-category-mass') { resetMethod = 'resetMassActionProperties'; }
                                    
                                    window.hideModalPureJS(modal, resetMethod);
                                }
                            }
                        });
                    }
                });

            })(); // Akhir IIFE
        </script>
    @endpush
</div>