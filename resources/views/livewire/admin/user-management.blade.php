<div>
    <div class="card">
        <div class="card-table">
            <div class="card-header">
                <div class="row w-100 align-items-center g-2">
                    <div class="col-12 col-md-auto me-md-auto">
                        <div class="d-flex flex-wrap btn-list gap-2">
                            @can('edit roles permissions')
                                <button class="btn btn-secondary" wire:click="openRolesModal">
                                    <i class="ti ti-lock me-1"></i> Atur Roles & Permissions
                                </button>
                            @endcan
                            @can('create users')
                                <button class="btn btn-primary" wire:click="openAddModal">
                                    <i class="ti ti-user-plus me-1"></i> Tambah User
                                </button>
                            @endcan
                        </div>
                    </div>

                    <div class="col-12 col-md-4 col-lg-3">
                        <div class="input-group input-group-flat">
                            <span class="input-group-text"><i class="ti ti-search"></i></span>
                            <input
                                wire:model.live.debounce.500ms="search"
                                type="text"
                                class="form-control"
                                autocomplete="off"
                                placeholder="Cari user (nama, email, role, jabatan, unit)"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div id="advanced-table-user" class="list">
                <div class="table-responsive" style="min-height: 50vh; max-height: 58vh; overflow-y: auto">
                    <table class="table card-table table-vcenter datatable">
                        <thead>
                            <tr>
                                <th class="w-1">
                                    <button wire:click="sortBy('id')" class="table-sort d-flex justify-content-between">#</button>
                                </th>
                                <th>
                                    <button wire:click="sortBy('name')" class="table-sort d-flex justify-content-between">Nama</button>
                                </th>
                                <th>
                                    <button wire:click="sortBy('email')" class="table-sort d-flex justify-content-between">Email</button>
                                </th>
                                <th>
                                    <button wire:click="sortBy('role')" class="table-sort d-flex justify-content-between">Role</button>
                                </th>
                                <th>
                                    <button wire:click="sortBy('jabatan')" class="table-sort d-flex justify-content-between">Jabatan</button>
                                </th>
                                <th>
                                    <button wire:click="sortBy('unit_kerja_id')" class="table-sort d-flex justify-content-between">Unit</button>
                                </th>
                                <th class="w-1">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="list">
                            @foreach ($users as $index => $user)
                                <tr>
                                    <td class="text-secondary sort-no">{{ $users->firstItem() + $index }}</td>
                                    <td class="text-secondary sort-nama">{{ $user->name }}</td>
                                    <td class="text-secondary sort-email">{{ $user->email }}</td>
                                    <td class="text-secondary sort-role"><span class="badge bg-blue-lt">{{ $user->role }}</span></td>
                                    <td class="text-secondary sort-jabatan">{{ $user->jabatan ?? '-' }}</td>
                                    <td class="text-secondary sort-unit">
                                        @if ($user->role === 'deputy' && $user->deputy)
                                            {{ $user->deputy->name }}
                                        @elseif ($user->unitKerja)
                                            {{ $user->unitKerja->name }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-secondary text-nowrap">
                                        @can('edit users')
                                            <button wire:click="openEditModal({{ $user->id }})" class="btn btn-1 btn-outline-primary btn-edit">
                                                <i class="ti ti-edit"></i>
                                            </button>
                                        @endcan
                                        @can('delete users')
                                            <button wire:click="deleteUserConfirm({{ $user->id }})" class="btn btn-1 btn-outline-danger btn-del">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex align-items-center">
                    <div class="dropdown me-3" wire:ignore>
                        <a id="per-page-dropdown-toggle" class="btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="current-per-page-value" class="me-1">{{ $perPage }}</span> <span>data</span>
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" wire:click="setPageSize(10)">10 data</a>
                            <a class="dropdown-item" wire:click="setPageSize(20)">20 data</a>
                            <a class="dropdown-item" wire:click="setPageSize(50)">50 data</a>
                            <a class="dropdown-item" wire:click="setPageSize(100)">100 data</a>
                        </div>
                    </div>
                    <span class="text-muted small">
                        Menampilkan {{ $users->firstItem() }} hingga {{ $users->lastItem() }} dari {{ $users->total() }} entri
                    </span>
                    <ul class="pagination ms-auto">
                        @if ($users->hasPages())
                            {{ $users->links() }}
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="modal-add-user" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah User Baru</h5>
                    <button class="btn-close" data-bs-dismiss="modal" wire:click="closeAddModal"></button>
                </div>
                <form wire:submit="store">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama</label>
                                <input type="text" wire:model="name" class="form-control" required />
                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" wire:model="email" class="form-control" required />
                                @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" wire:model="password" class="form-control" autocomplete="new-password" required />
                                @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Konfirmasi Password</label>
                                <input type="password" wire:model="password_confirmation" class="form-control" autocomplete="new-password" required />
                                @error('password_confirmation') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <select wire:model="role" class="form-select">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                @error('role') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Unit</label>
                                <select wire:model="unit_kerja_id" class="form-select">
                                    <option value="">Tidak ada</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                                @error('unit_kerja_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIP</label>
                                <input type="text" wire:model="nip" class="form-control" />
                                @error('nip') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jabatan</label>
                                <input type="text" wire:model="jabatan" class="form-control" />
                                @error('jabatan') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status Akun</label>
                                <select wire:model="is_active" class="form-select">
                                    <option value="1">Aktif</option>
                                    <option value="0">Tidak Aktif</option>
                                </select>
                                @error('is_active') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn" type="button" data-bs-dismiss="modal" wire:click="closeAddModal">Batal</button>
                        <button class="btn btn-primary" type="submit">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="modal-edit-user" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User: {{ $name }}</h5>
                    <button class="btn-close" data-bs-dismiss="modal" wire:click="closeEditModal"></button>
                </div>
                <form wire:submit="update">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-3">
                            <input type="hidden" wire:model="userId" />
                            <div class="col-md-6">
                                <label class="form-label">Nama</label>
                                <input type="text" wire:model="name" class="form-control" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" wire:model="email" class="form-control" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <select wire:model="role" class="form-select">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6" x-data="{ userRole: @entangle('role').live }">
                                <label class="form-label">Unit</label>
                                <select wire:model="deputy_id" class="form-select" x-show="userRole === 'deputy'">
                                    <option value="">-- Pilih Deputi --</option>
                                    @foreach ($deputies as $deputy)
                                        <option value="{{ $deputy->id }}">{{ $deputy->name }}</option>
                                    @endforeach
                                </select>
                                <select wire:model="unit_kerja_id" class="form-select" x-show="userRole !== 'deputy'">
                                    <option value="">Tidak ada</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                                @error('deputy_id') <span class="text-danger">{{ $message }}</span> @enderror
                                @error('unit_kerja_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIP</label>
                                <input type="text" wire:model="nip" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor Telepon</label>
                                <input type="text" wire:model="phone" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jabatan</label>
                                <input type="text" wire:model="jabatan" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status Akun</label>
                                <select wire:model="is_active" class="form-select">
                                    <option value="1">Aktif</option>
                                    <option value="0">Tidak Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn" type="button" data-bs-dismiss="modal" wire:click="closeEditModal">Batal</button>
                        <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-roles-permissions" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Atur Roles & Permissions</h5>
                    <button class="btn-close" wire:click="closeRolesModal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <h4>Daftar Role</h4>
                            <form wire:submit="createRole" class="mb-3">
                                <div class="input-group">
                                    <input type="text" 
                                        wire:model.live.debounce.300ms="newRoleName" 
                                        class="form-control form-control-sm" 
                                        placeholder="Nama Role Baru (cth: manager)"
                                        required>
                                    <button class="btn btn-primary btn-sm" type="submit" 
                                            @if (empty($newRoleName)) disabled @endif>
                                        <i class="ti ti-plus"></i>
                                    </button>
                                </div>
                                @error('newRoleName') <span class="text-danger small">{{ $message }}</span> @enderror
                            </form>
                            <ul class="list-group" style="max-height: 400px; overflow-y: auto;">
                                @foreach ($roles as $role)
                                    <li class="list-group-item d-flex justify-content-between align-items-center cursor-pointer {{ $selectedRole && $selectedRole->id == $role->id ? 'active' : '' }}" wire:click="selectRole({{ $role->id }})">
                                        {{ $role->name }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-md-8">
                            @if ($selectedRole)
                                <h4>Permissions untuk Role: {{ $selectedRole->name }}</h4>
                                <form wire:submit="updateRolePermissions">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" wire:model.live="selectAllPermissions" id="selectAllPermissions">
                                        <label class="form-check-label" for="selectAllPermissions">Pilih Semua</label>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        @forelse ($permissionsGrouped as $groupName => $permissions)
                                            <div class="col-md-4">
                                                <h5 class="mb-2">{{ $groupName }}</h5>
                                                @foreach ($permissions as $permission)
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input" type="checkbox" value="{{ $permission->name }}" wire:model="selectedPermissions" id="permission-{{ $permission->id }}">
                                                        <label class="form-check-label" for="permission-{{ $permission->id }}">
                                                            {{ $permission->name }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @empty
                                            <div class="col-12 text-center text-muted">Data permission tidak ditemukan. Pastikan seeder sudah dijalankan.</div>
                                        @endforelse
                                    </div>
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                    </div>
                                </form>
                            @else
                                <div class="alert alert-info">Pilih sebuah role dari daftar di samping untuk melihat dan mengedit permission-nya.</div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn" wire:click="closeRolesModal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    
@push('scripts')
<script src="{{ asset('bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script>
    document.addEventListener('livewire:initialized', () => {
        // JavaScript untuk mengelola Modal Tambah User dan Edit User
        Livewire.on('modal:show', (id) => {
            const modalId = id.id;
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                const modalInstance = new bootstrap.Modal(modalElement);
                modalInstance.show();
            }
        });

        Livewire.on('modal:hide', (id) => {
            const modalId = id.id;
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
        });

        // Event listener untuk membersihkan layar setelah modal tertutup sepenuhnya
        const addModal = document.getElementById('modal-add-user');
        const editModal = document.getElementById('modal-edit-user');
        const rolesModal = document.getElementById('modal-roles-permissions');

        if (addModal) {
            addModal.addEventListener('hidden.bs.modal', function() {
                // Hapus kelas 'modal-open' dari body dan backdrop
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            });
        }
        
        if (editModal) {
            editModal.addEventListener('hidden.bs.modal', function() {
                // Hapus kelas 'modal-open' dari body dan backdrop
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            });
        }

        if (rolesModal) {
            rolesModal.addEventListener('hidden.bs.modal', function() {
                // Hapus kelas 'modal-open' dari body dan backdrop
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            });
        }

        // JavaScript untuk konfirmasi delete dan pesan sukses
        Livewire.on('swal:confirm', (event) => {
            const { title, text, confirmButtonText, onConfirmed, onConfirmedParams } = event;
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
                    Livewire.dispatch(onConfirmed, { userId: onConfirmedParams[0] });
                }
            });
        });

        Livewire.on('session:success', (event) => {
            const message = event.message;
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

            setTimeout(() => {
                // Hapus class yang membuat scrollbar menghilang
                document.body.classList.remove('modal-open'); 
                
                // Hapus backdrop abu-abu
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                
                // Pastikan tidak ada style inline pada body
                document.body.style.paddingRight = ''; 

            }, 150);

            // --- LOGIKA PERBAIKAN DROPDOWN PERPAGE ---
            const syncPerPageDropdown = (perPageValue) => {
                const currentSpan = document.getElementById('current-per-page-value');
                if (currentSpan) {
                    currentSpan.textContent = perPageValue;
                }
            };
            
            // 1. Sinkronisasi setelah Livewire selesai memproses aksi
            Livewire.hook('message.processed', (message, component) => {
                // Ambil nilai perPage yang baru dari state Livewire component
                const perPageValue = component.data.perPage || component.serverMemo.data.perPage; 
                if (perPageValue) {
                    syncPerPageDropdown(perPageValue);
                }
            });

            // 2. Sinkronisasi saat item dropdown diklik (Feedback visual instan)
            const perPageDropdownMenu = document.querySelector('.card-footer .dropdown-menu');
            if (perPageDropdownMenu) {
                perPageDropdownMenu.addEventListener('click', function(e) {
                    // Cek apakah yang diklik adalah link dengan wire:click="setPageSize(...)"
                    if (e.target.tagName === 'A' && e.target.getAttribute('wire:click')) {
                        const match = e.target.getAttribute('wire:click').match(/setPageSize\((\d+)\)/);
                        if (match) {
                            const selectedValue = match[1];
                            // Sinkronisasi tampilan segera
                            syncPerPageDropdown(selectedValue);
                            // Tutup dropdown setelah klik (Bootstrap JS functionality)
                            const toggleElement = document.getElementById('per-page-dropdown-toggle');
                            if (toggleElement) {
                                new bootstrap.Dropdown(toggleElement).hide();
                            }
                        }
                    }
                });
            }
        });
    });
</script>
@endpush
</div>