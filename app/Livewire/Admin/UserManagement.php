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

    public $allPermissions;
    public $selectedRole;
    public $selectedPermissions = [];
    public $selectAllPermissions = false;
    public $permissionsGrouped;

    protected $listeners = ['deleteUser'];
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->roles = Role::all();
        $this->units = UnitKerja::all();
        $this->deputies = Deputy::all();
        $this->allPermissions = Permission::all();

        $this->permissionsGrouped = [
            'Laporan Pengaduan' => $this->allPermissions->filter(fn($p) => str_contains($p->name, 'reports')),
            'Manajemen User' => $this->allPermissions->filter(fn($p) => str_contains($p->name, 'users')),
            'Lain-lain' => $this->allPermissions->filter(fn($p) => str_contains($p->name, 'worksheet') || str_contains($p->name, 'some_other_permission')),
        ];
    }

    public function render()
    {
        $users = User::with('unitKerja')
                    ->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orderBy($this->sortField, $this->sortDirection)
                    ->paginate($this->perPage);

        return view('livewire.admin.user-management', [
            'users' => $users,
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

        if ($this->role === 'deputi') {
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
            'unit_kerja_id' => ($this->role !== 'deputi') ? $this->unit_kerja_id : null,
            'deputy_id' => ($this->role === 'deputi') ? $this->deputy_id : null,
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
            'role' => 'required|string|exists:roles,name',
            'nip' => ['nullable', 'string', Rule::unique('users')->ignore($user->id)],
            'jabatan' => 'nullable|string',
            'is_active' => 'required|boolean',
        ];

        if ($this->role === 'deputi') {
            $rules['deputy_id'] = 'required|exists:deputies,id';
            $rules['unit_kerja_id'] = 'nullable';
        } else {
            $rules['deputy_id'] = 'nullable';
            $rules['unit_kerja_id'] = 'nullable|exists:unit_kerjas,id';
        }

        $this->validate($rules);
        
        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'unit_kerja_id' => ($this->role !== 'deputi') ? $this->unit_kerja_id : null,
            'deputy_id' => ($this->role === 'deputi') ? $this->deputy_id : null,
            'nip' => $this->nip,
            'jabatan' => $this->jabatan,
            'is_active' => $this->is_active,
        ]);
        $user->syncRoles($this->role);

        $this->resetForm();
        $this->dispatch('modal:hide', id: 'modal-edit-user');
        $this->dispatch('swal:toast', message: 'Pengguna berhasil diperbarui.', icon: 'success');
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
            'userId', 'name', 'email', 'password', 'password_confirmation',
            'role', 'unit_kerja_id', 'deputy_id', 'nip', 'jabatan', 'is_active',
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

        $this->dispatch('swal:toast', message: 'Permissions untuk role ' . $this->selectedRole->name . ' berhasil diperbarui.', icon: 'success');

        $this->closeRolesModal();
    }
}
