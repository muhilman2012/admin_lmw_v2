<div  wire:poll.30s>
    <div class="card-header">
        <div class="row w-full">
            <div class="col-sm-12">
                <div class="ms-auto d-flex flex-wrap btn-list">
                    <div class="input-group input-group-flat w-full">
                        <span class="input-group-text">
                            <i class="ti ti-search"></i>
                        </span>
                        <input wire:model.live.debounce.500ms="search" type="text" class="form-control" autocomplete="off" placeholder="Cari Pengadu berdasarkan Nama, NIK, atau Nomor HP" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive" style="min-height: 50vh; max-height: 58vh; overflow-y: auto">
        <table class="table table-selectable card-table table-vcenter text-nowrap datatable">
            <thead class="sticky-top bg-white">
                <tr>
                    <th class="w-1">No.</th>
                    <th>Nama Lengkap</th>
                    <th>NIK</th>
                    <th>Nomor HP</th>
                    <th>Email</th>
                    <th>Alamat</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody class="table-tbody">
                @forelse ($reporters as $index => $reporter)
                    <tr>
                        <td>{{ $reporters->firstItem() + $index }}</td>
                        <td>{{ $reporter->name }}</td>
                        <td>{{ $reporter->nik }}</td>
                        <td>{{ $reporter->phone_number }}</td>
                        <td>{{ $reporter->email }}</td>
                        <td>{{ $reporter->address }}</td>
                        <td>
                            <div class="btn-list flex-nowrap">
                                <a href="#" class="btn btn-primary" >
                                    <i class="ti ti-pencil me-2"></i>Buat Laporan
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted p-4">
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
            <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="me-1">{{ $perPage }}</span> <span>records</span>
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item" wire:click="setPerPage(10)">10 records</a>
                <a class="dropdown-item" wire:click="setPerPage(20)">20 records</a>
                <a class="dropdown-item" wire:click="setPerPage(50)">50 records</a>
                <a class="dropdown-item" wire:click="setPerPage(100)">100 records</a>
            </div>
        </div>
        <p class="m-0 text-secondary ms-2">
            Menampilkan <span>{{ $reporters->count() }}</span> dari <span>{{ $reporters->total() }}</span> entri
        </p>
        {{ $reporters->links() }}
    </div>
</div>