<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\UnitKerja;
use App\Models\Deputy;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class UserManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 20;
    public $sortField = 'name';
    public $sortDirection = 'asc';

    public $userId;
    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $role;
    public $unit_kerja_id;
    public $deputy_id;
    public $nip;
    public $phone;
    public $jabatan;
    public $is_active = true;

    public $roles;
    public $units;
    public $deputies;

    public $newRoleName = '';

    // Roles & Permissions Properties
    public Collection $allPermissions; // Menggunakan Collection untuk type hinting
    public $selectedRole;
    public $selectedPermissions = [];
    public $selectAllPermissions = false;

    protected $listeners = ['deleteUser'];
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->roles = Role::all();
        $this->units = UnitKerja::all();
        $this->deputies = Deputy::all();

        $this->allPermissions = Permission::all();
    }

    public function getPermissionsGroupedProperty()
    {
        $grouped = [];
        // Menggunakan properti allPermissions yang sudah dimuat di mount
        $permissions = $this->allPermissions ?? collect([]);

        foreach ($permissions as $permission) {
            $name = $permission->name;
            $groupName = 'F. UNCATEGORIZED (PERLU DIATUR)';

            // A. REPORT CORE (CRUD)
            if (str_contains($name, 'reports') && !str_contains($name, 'forwarded') && !str_contains($name, 'assigned')) {
                $groupName = 'A. REPORT CORE (CRUD)';
            } 
            // B. USER & STRUKTUR
            elseif (str_contains($name, 'users') || str_contains($name, 'structure') || str_contains($name, 'roles permissions')) {
                $groupName = 'B. USER & STRUKTUR';
            } 
            // C. FORWARDING & EKSPOR
            elseif (str_contains($name, 'forward') || str_contains($name, 'export') || str_contains($name, 'import')) {
                $groupName = 'C. FORWARDING & EKSPOR';
            } 
            // D. API SETTINGS
            elseif (str_contains($name, 'api') || str_contains($name, 'regenerate api key')) {
                $groupName = 'D. API SETTINGS';
            } 
            // E. ASSIGNMENT & ANALISIS
            elseif (str_contains($name, 'assign') || str_contains($name, 'analysis') || str_contains($name, 'worksheet') || str_contains($name, 'response')) {
                $groupName = 'E. ASSIGNMENT & ANALISIS';
            }

            $grouped[$groupName][] = $permission;
        }

        // Urutkan berdasarkan kunci (A, B, C, D, E, F)
        ksort($grouped);
        return $grouped;
    }

    public function render()
    {
        $users = User::with('unitKerja')
                    ->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orderBy($this->sortField, $this->sortDirection)
                    ->paginate($this->perPage);

        $permissionsGrouped = $this->permissionsGrouped; 

        return view('livewire.admin.user-management', [
            'users' => $users,
            'permissionsGrouped' => $permissionsGrouped,
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    public function openAddModal()
    {
        $this->resetValidation();
        $this->resetForm();
        $this->role = 'user';
        $this->dispatch('modal:show', id: 'modal-add-user');
    }

    public function closeAddModal()
    {
        $this->resetValidation();
        $this->resetForm();
        $this->role = 'user';
        $this->dispatch('modal:hide', id: 'modal-add-user');
    }

    public function openEditModal(User $user)
    {
        $this->resetValidation();
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->unit_kerja_id = $user->unit_kerja_id;
        $this->deputy_id = $user->deputy_id; 
        $this->deputy_id = $user->deputy_id;
        $this->nip = $user->nip;
        $this->phone = $user->phone;
        $this->jabatan = $user->jabatan;
        $this->is_active = $user->is_active;

        $this->dispatch('modal:show', id: 'modal-edit-user');
    }

    public function closeEditModal()
    {
        $this->dispatch('modal:hide', id: 'modal-edit-user');
    }

    public function createRole()
    {
        // 1. Validasi
        $this->validate([
            'newRoleName' => [
                'required', 
                'string', 
                'min:3', 
                // Cek apakah nama role sudah ada
                Rule::unique('roles', 'name')->where(fn ($query) => $query->where('guard_name', 'web'))
            ],
        ], [
            'newRoleName.required' => 'Nama role wajib diisi.',
            'newRoleName.unique' => 'Role dengan nama ini sudah terdaftar.',
        ]);

        // 2. Buat Role
        Role::create([
            'name' => strtolower($this->newRoleName), // Disarankan role selalu lowercase
            'guard_name' => 'web', // Sesuai permintaan
        ]);

        // 3. Reset dan Reload
        $createdRoleName = $this->newRoleName;
        
        $this->reset(['newRoleName']); // Reset input
        $this->roles = Role::all(); // Reload daftar roles
        
        // Pilih role yang baru dibuat secara otomatis
        $newRole = Role::where('name', strtolower($createdRoleName))->first();
        if ($newRole) {
            $this->selectRole($newRole); 
        }

        $this->dispatch('session:success', message: 'Role "' . $createdRoleName . '" berhasil ditambahkan.');
    }

    public function store()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
            'nip' => 'nullable|string|max:255|unique:users,nip',
            'jabatan' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ];

        if ($this->role === 'deputy') {
            $rules['deputy_id'] = 'required|exists:deputies,id';
            $rules['unit_kerja_id'] = 'nullable';
        } else {
            $rules['deputy_id'] = 'nullable';
            $rules['unit_kerja_id'] = 'nullable|exists:unit_kerjas,id';
        }

        $this->validate($rules);
        
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => $this->role,
            'unit_kerja_id' => ($this->role !== 'deputy') ? $this->unit_kerja_id : null,
            'deputy_id' => ($this->role === 'deputy') ? $this->deputy_id : null,
            'nip' => $this->nip,
            'jabatan' => $this->jabatan,
            'is_active' => $this->is_active,
        ]);
        $user->assignRole($this->role);

        $this->resetForm();
        $this->dispatch('modal:hide', id: 'modal-add-user');
        $this->dispatch('session:success', message: 'Pengguna berhasil ditambahkan.');
    }

    public function update()
    {
        $user = User::findOrFail($this->userId);
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|exists:roles,name',
            'nip' => 'nullable|string',
            'jabatan' => 'nullable|string',
            'is_active' => 'required|boolean',
        ];

        if ($this->role === 'deputy') {
            $rules['deputy_id'] = 'required|exists:deputies,id';
            $rules['unit_kerja_id'] = 'nullable';
        } else {
            $rules['deputy_id'] = 'nullable';
            $rules['unit_kerja_id'] = 'nullable|exists:unit_kerjas,id';
        }

        $this->validate($rules);
        $sanitizedNip = empty($this->nip) ? null : $this->nip;
        
        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'unit_kerja_id' => ($this->role !== 'deputy') ? $this->unit_kerja_id : null,
            'deputy_id' => ($this->role === 'deputy') ? $this->deputy_id : null,
            'nip' => $sanitizedNip,
            'jabatan' => $this->jabatan,
            'is_active' => $this->is_active,
        ]);
        $user->syncRoles($this->role);

        $this->resetForm();
        $this->dispatch('modal:hide', id: 'modal-edit-user');
        $this->dispatch('session:success', message: 'Pengguna berhasil diperbarui.');
        $this->dispatch('refresh');
    }

    public function deleteUserConfirm($userId)
    {
        $this->dispatch(
            'swal:confirm',
            title: 'Apakah Anda yakin?',
            text: 'Data pengguna akan dihapus secara permanen!',
            confirmButtonText: 'Ya, hapus!',
            onConfirmed: 'deleteUser',
            onConfirmedParams: [$userId]
        );
    }
    public function deleteUser($userId)
    {
        if ($u = User::find($userId)) $u->delete();
        $this->dispatch('swal:toast', message: 'Pengguna berhasil dihapus.', icon: 'success');
    }
    
    private function resetForm()
    {
        $this->reset([
            'userId', 
            'name', 
            'email', 
            'password', 
            'password_confirmation',
            'role', 
            'unit_kerja_id', 
            'deputy_id', 
            'nip', 
            'phone',
            'jabatan', 
            'is_active',
        ]);
        $this->is_active = true;
    }

    public function setPageSize($perPage)
    {
        $this->perPage = $perPage;
        $this->resetPage();
    }

    public function openRolesModal()
    {
        $this->reset(['selectedRole', 'selectedPermissions', 'selectAllPermissions']);
        $this->dispatch('modal:show', id: 'modal-roles-permissions');
    }

    public function closeRolesModal()
    {
        $this->dispatch('modal:hide', id: 'modal-roles-permissions');
        $this->reset(['selectedRole', 'selectedPermissions', 'selectAllPermissions']);
    }

    public function selectRole(Role $role)
    {
        $this->selectedRole = $role;
        $this->selectedPermissions = $role->permissions()->pluck('name')->toArray();
        $this->selectAllPermissions = false;
    }

    public function updatedSelectAllPermissions($value)
    {
        if ($value) {
            $this->selectedPermissions = $this->allPermissions->pluck('name')->toArray();
        } else {
            $this->selectedPermissions = [];
        }
    }

    public function updateRolePermissions()
    {
        if (!$this->selectedRole) {
            return;
        }

        $this->selectedRole->syncPermissions($this->selectedPermissions);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->dispatch('swal:toast', message: 'Permissions untuk role ' . $this->selectedRole->name . ' berhasil diperbarui.', icon: 'success');
        $this->closeRolesModal();
    }
}
