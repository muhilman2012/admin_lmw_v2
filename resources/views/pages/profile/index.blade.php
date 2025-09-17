@extends('layouts.app')

@section('title', 'Pengaturan Akun')
@section('page_pretitle', 'Pengaturan')
@section('page_title', 'Pengaturan Akun')

@section('content')
<div class="card">
    <div class="row g-0">
        <div class="col-12 col-md-3 border-end">
            <div class="card-body">
                <div class="list-group list-group-transparent mx-0" id="settings-tab" role="tablist">
                    <div class="subheader my-2">Detail Profil</div>
                    <a class="list-group-item list-group-item-action active" data-bs-toggle="list" data-bs-target="#pane-account" id="tab-account" role="button">Akun Saya</a>
                    <a class="list-group-item list-group-item-action" data-bs-toggle="list" data-bs-target="#pane-notifications" id="tab-notifications" role="button">Notifikasi</a>
                    <a class="list-group-item list-group-item-action" data-bs-toggle="list" data-bs-target="#pane-log" role="button" id="tab-log">Log Login</a>
                    
                    <div class="subheader my-2 mt-4">Pengaturan Profil</div>
                    <a class="list-group-item list-group-item-action" data-bs-toggle="list" data-bs-target="#pane-reset-password" role="button" id="tab-reset-password">Ubah Kata Sandi</a>

                    <div class="subheader my-2 mt-4">Pengaturan APP API</div>
                    <a class="list-group-item list-group-item-action" data-bs-toggle="list" data-bs-target="#pane-api-app-setting" role="button" id="tab-api-app-setting">Pengaturan API</a>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-9 d-flex flex-column">
            <div class="card-body">
                <div class="tab-content" id="settings-tabContent">
                    <div class="tab-pane fade show active" id="pane-account" role="tabpanel" aria-labelledby="tab-account">
                        <h2 class="mb-4">Akun Saya</h2>
                        <div class="row justify-content-between">
                            <div class="col">
                                <h3 class="card-title">Detail Profil</h3>
                            </div>
                            <div class="col-auto">
                                <label class="form-check form-switch form-switch-lg">
                                    <input id="edit-toggle-profile" class="form-check-input" type="checkbox" />
                                    <span class="form-check-label form-check-label-on">Edit</span>
                                    <span class="form-check-label form-check-label-off">Edit</span>
                                </label>
                            </div>
                        </div>
                        <div class="col align-items-start">
                            <form id="profile-form" class="row" action="{{ route('users.profile.update') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                {{-- Bagian Avatar --}}
                                <div id="avatar-section" class="row-auto">
                                    <div id="avatar-display-section">
                                        <x-user-avatar :user="$user" size="xl" />
                                    </div>
                                    <div id="avatar-edit-section" class="d-none">
                                        <div class="me-3">
                                            <x-user-avatar :user="$user" size="xl" id="avatar-preview" />
                                        </div>
                                        <div>
                                            <div class="form-label">Ganti Foto Profil</div>
                                            <input type="file" name="avatar" class="form-control" id="avatar-input" />
                                            @error('avatar') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>

                                {{-- Bagian Formulir --}}
                                <div class="row g-3 mt-4">
                                    <div class="col-md-6">
                                        <div class="form-label">Nama Lengkap</div>
                                        <input type="text" name="name" class="form-control" value="{{ $user->name }}" disabled />
                                        @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-label">Email</div>
                                        <input type="text" name="email" class="form-control" value="{{ $user->email }}" disabled />
                                        @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-label">NIP</div>
                                        <input type="text" name="nip" class="form-control" value="{{ $user->nip ?? '' }}" disabled />
                                        @error('nip') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-label">Jabatan</div>
                                        <input type="text" name="jabatan" class="form-control" value="{{ $user->jabatan ?? '' }}" disabled />
                                        @error('jabatan') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    @if (!$user->hasRole('deputy'))
                                    <div class="col-md-6">
                                        <div class="form-label">Unit</div>
                                        <select name="unit_kerja_id" id="unit-select" class="form-select" disabled>
                                            <option value="">Pilih Unit</option>
                                            @foreach ($units as $unit)
                                                <option value="{{ $unit->id }}" data-deputy="{{ $unit->deputy->name ?? '' }}" @selected($user->unit_kerja_id == $unit->id)>{{ $unit->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('unit_kerja_id') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    @endif
                                    
                                    <div class="col-md-6">
                                        <div class="form-label">Deputi</div>
                                        <input type="text" name="deputy" id="deputy-input" class="form-control" value="{{ $user->unitKerja->deputy->name ?? '' }}" @if (!$user->hasRole('deputi')) disabled @endif readonly />
                                        @error('deputy') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                
                                <div class="card-footer bg-transparent mt-4 d-none ps-0" id="profile-footer">
                                    <div class="btn-list justify-content-start">
                                        <button type="button" class="btn btn-1" id="cancel-profile">Batal</button>
                                        <button type="submit" form="profile-form" class="btn btn-primary btn-2">Kirim</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pane-reset-password" role="tabpanel" aria-labelledby="tab-reset-password" >
                        <h3 class="card-title">Kata Sandi</h3>
                            <p class="card-subtitle">Anda bisa merubah Kata Sandi disini.</p>
                        <div>
                            <a data-bs-toggle="modal" data-bs-target="#modal-reset-password" role="button" class="btn btn-1">Perbarui Kata Sandi</a>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="pane-notifications" role="tabpanel" aria-labelledby="tab-notifications">
                        <div class="list-group list-group-flush list-group-hoverable">
                            </div>
                    </div>
                    
                    <div class="tab-pane fade" id="pane-log" role="tabpanel" aria-labelledby="tab-log">
                        <div id="log-container" class="list-group list-group-flush overflow-auto" style="max-height: 35rem">
                            @forelse ($user->loginLogs as $log)
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="row align-items-center">
                                        <div class="col text-truncate">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="text-body d-block">IP Address: {{ $log->ip_address }}</div>
                                                <div class="text-secondary small">{{ $log->created_at->diffForHumans() }}</div>
                                            </div>
                                            <div class="d-block text-secondary text-truncate mt-n1">
                                                User Agent: {{ $log->user_agent }}
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="p-3 text-center text-secondary">Tidak ada log login terbaru.</div>
                            @endforelse
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="pane-reset-password" role="tabpanel" aria-labelledby="tab-reset-password">
                        <h3 class="card-title">Kata Sandi</h3>
                        <p class="card-subtitle">Ubah Kata Sandi Anda disini.</p>
                        <div>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-reset-password">Perbarui Kata Sandi</button>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="pane-api-app-setting" role="tabpanel" aria-labelledby="tab-api-app-setting">
                        <div class="row justify-content-between">
                            <div class="col">
                                <h3 class="card-title">Pengaturan API</h3>
                            </div>
                            <div class="col-auto">
                                <label class="form-check form-switch form-switch-lg">
                                    <input id="edit-toggle-api" class="form-check-input" type="checkbox" />
                                    <span class="form-check-label form-check-label-on">Edit</span>
                                    <span class="form-check-label form-check-label-off">Edit</span>
                                </label>
                            </div>
                        </div>
                        <p class="card-subtitle">Manajemen token API Anda.</p>
                        <div class="mt-4">
                            <label class="form-label">X-LMW-API-KEY</label>
                            <div class="input-group">
                                <input id="api-key-input" type="text" class="form-control" value="{{ env('LMW_API_TOKEN') }}" readonly />
                                <button id="refresh-api-key-btn" class="btn btn-primary" type="button">
                                    <i class="ti ti-refresh me-1"></i> Refresh Token
                                </button>
                            </div>
                            <small class="form-hint">Salin token ini untuk digunakan dalam permintaan API Anda.</small>
                        </div>
                        <form id="api-settings-form" action="{{ route('users.profile.api.update') }}" method="POST">
                            @csrf
                            <div class="mt-4">
                                <h3 class="card-title">Pengaturan API Eksternal</h3>
                                <p class="card-subtitle">Manajemen endpoint API eksternal.</p>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Base URL</label>
                                <input type="text" name="base_url" class="form-control" value="{{ $apiSettings['base_url'] ?? '' }}" disabled />
                                @error('base_url') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Authorization</label>
                                <input type="text" name="authorization" class="form-control" value="{{ $apiSettings['authorization'] ?? '' }}" disabled />
                                @error('authorization') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Token</label>
                                <input type="text" name="token" class="form-control" value="{{ $apiSettings['token'] ?? '' }}" disabled />
                                @error('token') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </form>
                        <div class="card-footer bg-transparent mt-4 d-none ps-0" id="api-footer">
                            <div class="btn-list justify-content-start">
                                <a href="#" class="btn btn-1">Batal</a>
                                <button type="submit" form="api-settings-form" class="btn btn-primary btn-2">Simpan</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-reset-password" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('users.profile.update-password') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Kata Sandi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kata Sandi Lama</label>
                        <input type="password" name="current_password" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kata Sandi Baru</label>
                        <input type="password" name="new_password" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" name="new_password_confirmation" class="form-control" required />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Objek untuk memetakan unit_kerja_id ke nama deputi
        const unitData = {};
        @foreach($units as $unit)
            unitData[{{ $unit->id }}] = "{{ $unit->deputy->name ?? '' }}";
        @endforeach

        const unitSelect = document.getElementById('unit-select');
        const deputyInput = document.getElementById('deputy-input');

        function updateDeputy() {
            const selectedUnitId = unitSelect.value;
            deputyInput.value = unitData[selectedUnitId] || '';
        }

        if (unitSelect) {
            unitSelect.addEventListener('change', updateDeputy);
        }
        
        // Logika untuk form Profile
        const toggleProfile = document.getElementById("edit-toggle-profile");
        const footerProfile = document.getElementById("profile-footer");
        const formProfile = document.getElementById("profile-form");
        const cancelProfileBtn = document.getElementById("cancel-profile");
        const avatarInput = document.getElementById("avatar-input");
        const avatarPreview = document.getElementById("avatar-preview");
        const avatarDisplay = document.getElementById("avatar-display-section");
        const avatarEdit = document.getElementById("avatar-edit-section");

        function applyEditStateProfile(editing) {
            if (footerProfile) {
                footerProfile.classList.toggle("d-none", !editing);
            }
            
            // Logika baru: Ambil semua kontrol form yang ada
            const controlsToDisable = formProfile.querySelectorAll('input, select');
            controlsToDisable.forEach((el) => {
                // Jangan nonaktifkan input Deputi jika toggle aktif dan user adalah deputi
                if (el.name === 'deputy' && "{{ $user->hasRole('deputy') }}" === "1") {
                    el.disabled = !editing;
                } else if (el.name !== 'deputy') {
                    el.disabled = !editing;
                }
            });
            
            // Logika untuk menampilkan/menyembunyikan bagian avatar
            avatarDisplay.classList.toggle("d-none", editing);
            avatarEdit.classList.toggle("d-none", !editing);
            
            // Juga aktifkan/nonaktifkan input file secara manual
            avatarInput.disabled = !editing;
        }
        
        if (toggleProfile) {
            applyEditStateProfile(toggleProfile.checked);
            toggleProfile.addEventListener("change", () => applyEditStateProfile(toggleProfile.checked));
            
            cancelProfileBtn.addEventListener("click", (e) => {
                e.preventDefault();
                toggleProfile.checked = false;
                applyEditStateProfile(false);
            });
        }
        
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Logika untuk form API Settings
        const toggleApi = document.getElementById("edit-toggle-api");
        const footerApi = document.getElementById("api-footer");
        const formApi = document.getElementById("api-settings-form");
        const controlsApi = formApi.querySelectorAll("input.form-control, textarea.form-control, select.form-select");
        const cancelApiBtn = document.getElementById("cancel-api");

        function applyEditStateApi(editing) {
            if (footerApi) {
                footerApi.classList.toggle("d-none", !editing);
            }
            controlsApi.forEach((el) => {
                el.disabled = !editing;
            });
        }
        
        if (toggleApi) {
            applyEditStateApi(toggleApi.checked);
            toggleApi.addEventListener("change", () => applyEditStateApi(toggleApi.checked));
            
            cancelApiBtn.addEventListener("click", (e) => {
                e.preventDefault();
                toggleApi.checked = false;
                applyEditStateApi(false);
            });
        }

        // JavaScript untuk merefresh API Key
        const refreshBtn = document.getElementById('refresh-api-key-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: 'Tindakan ini akan mengganti token API yang ada dan token lama tidak akan berfungsi lagi.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, refresh!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('{{ route('users.profile.regenerate-token') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.new_token) {
                                document.getElementById('api-key-input').value = data.new_token;
                                Swal.fire('Berhasil!', 'Token API berhasil diperbarui.', 'success');
                            } else {
                                Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('Gagal!', 'Terjadi kesalahan pada server.', 'error');
                        });
                    }
                });
            });
        }
    });
</script>
@endpush