<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\UnitKerja;

class UsersController extends Controller
{
    protected array $middleware = ['role:superadmin|admin'];

    public function index()
    {
        $users = User::all();
        $roles = Role::all();
        $units = UnitKerja::all();
        return view('pages.users.index', compact('users', 'roles', 'units'));
    }

    public function create()
    {
        $roles = Role::all();
        $units = UnitKerja::all();
        return view('pages.users.create', compact('roles', 'units'));
    }

    /**
     * Simpan pengguna baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
            'unit_kerja_id' => 'nullable|exists:unit_kerjas,id',
            'is_active' => 'required|boolean',
            'nip' => 'nullable|string|max:255|unique:users,nip',
            'jabatan' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'unit_kerja_id' => $request->unit_kerja_id,
            'is_active' => $request->is_active,
            'nip' => $request->nip,
            'jabatan' => $request->jabatan,
            'phone' => $request->phone,
        ]);
        
        $user->assignRole($request->role);

        return redirect()->route('users.management.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function editApi(User $user)
    {
        return response()->json($user->load('unitKerja'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $units = UnitKerja::all();
        return view('pages.users.edit', compact('user', 'roles', 'units'));
    }

    /**
     * Perbarui data pengguna di database.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|exists:roles,name',
            'unit_kerja_id' => 'nullable|exists:unit_kerjas,id',
            'is_active' => 'required|boolean',
            'nip' => 'nullable|string|max:255|unique:users,nip,' . $user->id,
            'jabatan' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        $user->update($request->only('name', 'email', 'role', 'unit_kerja_id', 'is_active', 'nip', 'jabatan', 'phone'));
        $user->syncRoles($request->role);
        
        return redirect()->route('users.management.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    /**
     * Hapus pengguna dari database.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.management.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
