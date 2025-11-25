@extends('layouts.app')

@section('title', 'Pengaturan Akun')


@section('page_header')
<div class="page-header d-print-none" aria-label="Page header">
    <div class="container-xl">
        <div class="d-flex g-2 align-items-center justify-content-between">
            <h2 class="page-title">Pengaturan Akun</h2>
        </div>
    </div>
</div>
@endsection

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

                    @can('view api settings')
                        <div class="subheader my-2 mt-4">Pengaturan API</div>
                        <a class="list-group-item list-group-item-action" data-bs-toggle="list" data-bs-target="#pane-api-lmw" role="button">API LMW</a>
                        <a class="list-group-item list-group-item-action" data-bs-toggle="list" data-bs-target="#pane-api-v1-migration" role="button">API MIGRASI V1</a>
                        <a class="list-group-item list-group-item-action" data-bs-toggle="list" data-bs-target="#pane-api-lapor" role="button">API LAPOR!</a>
                        <a class="list-group-item list-group-item-action" data-bs-toggle="list" data-bs-target="#pane-api-dukcapil" role="button">API DUKCAPIL</a>
                        <a class="list-group-item list-group-item-action" data-bs-toggle="list" data-bs-target="#pane-api-gemini" role="button">API GEMINI</a>
                    @endcan
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

                                    @php
                                        if ($user->hasRole('deputy')) {
                                            $deputyName = $user->deputy->name ?? 'N/A';
                                        } elseif ($user->unitKerja) {
                                            $deputyName = $user->unitKerja->deputy->name ?? 'N/A';
                                        } else {
                                            $deputyName = 'N/A';
                                        }
                                    @endphp
                                    <div class="col-md-6">
                                        <div class="form-label">Deputi</div>
                                        <input type="text" 
                                            name="deputy" 
                                            id="deputy-input" 
                                            class="form-control" 
                                            value="{{ $deputyName }}"
                                            @if (!$user->hasRole('deputy')) disabled @endif readonly 
                                        />
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
                        <div id="notifications-container" class="list-group list-group-flush list-group-hoverable overflow-auto" style="max-height: 40rem;">
                            @forelse (auth()->user()->notifications()->latest()->limit(50)->get() as $notification) 
                                @php
                                    $data = $notification->data;
                                    $icon = $data['icon'] ?? 'ti ti-bell-ringing';
                                    $color = $data['color'] ?? 'primary';
                                    $url = $data['url'] . '?read=' . $notification->id;
                                @endphp
                                <div class="list-group-item @if (!$notification->read_at) list-group-item-unread @endif">
                                    <a href="{{ $url }}" class="d-flex text-decoration-none">
                                        <div>
                                            <span class="avatar avatar-sm rounded bg-{{ $color }}-lt me-3">
                                                <i class="{{ $icon }}"></i>
                                            </span>
                                        </div>
                                        <div class="d-flex flex-column flex-grow-1">
                                            <div class="font-weight-medium @if ($notification->read_at) text-secondary @endif">{{ $data['title'] ?? 'Notifikasi' }}</div>
                                            <div class="text-secondary" style="font-size: 0.9rem;">
                                                {!! $data['message'] ?? 'Detail notifikasi.' !!}
                                            </div>
                                            <div class="text-muted mt-1" style="font-size: 0.75rem;">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            @if (!$notification->read_at)
                                                <span class="badge bg-primary-lt">Baru</span>
                                            @endif
                                        </div>
                                    </a>
                                </div>
                            @empty
                                <div class="p-4 text-center text-muted">Anda tidak memiliki riwayat notifikasi.</div>
                            @endforelse
                        </div>
                        
                        {{-- @if (auth()->user()->notifications()->count() > 15)
                            <div class="card-footer bg-transparent text-center">
                                <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-secondary">
                                    Lihat Semua Notifikasi ({{ auth()->user()->notifications()->count() }})
                                </a>
                            </div>
                        @endif --}}
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
                    
                    @can('view api settings')
                    {{-- Tab Pane: API LMW --}}
                    <div class="tab-pane fade" id="pane-api-lmw" role="tabpanel">
                        <div class="row justify-content-between">
                            <div class="col">
                                <h3 class="card-title">API LMW</h3>
                            </div>
                            @can('edit api settings')
                            <div class="col-auto">
                                <label class="form-check form-switch form-switch-lg">
                                    <input id="edit-toggle-lmw-api" class="form-check-input" type="checkbox" />
                                    <span class="form-check-label form-check-label-on">Edit</span>
                                    <span class="form-check-label form-check-label-off">Edit</span>
                                </label>
                            </div>
                            @endcan
                        </div>
                        <p class="card-subtitle">Manajemen endpoint API internal LMW</p>
                        <form id="lmw-api-settings-form" action="{{ route('users.profile.api.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="api_name" value="lmw_api">
                            <div class="mt-4">
                                <label class="form-label">Base URL</label>
                                <input id="lmw-base-url-input" type="text" name="base_url" class="form-control" 
                                    value="{{ $apiSettings['lmw_api']['base_url'] ?? config('app.url') }}" disabled />
                                <small class="form-hint">Jika kosong, menggunakan URL aplikasi: {{ config('app.url') }}</small>
                            </div>
                            
                            <div class="mt-4">
                                <label class="form-label">X-LMW-API-KEY</label>
                                <div class="input-group">
                                    {{-- 1. UBAH TYPE MENJADI PASSWORD UNTUK MENYEMBUNYIKAN --}}
                                    <input 
                                        id="lmw-api-key-input" 
                                        type="password" 
                                        name="api_token" 
                                        class="form-control"
                                        value="{{ $apiSettings['lmw_api']['api_token'] ?? 'Token belum diatur' }}" 
                                        disabled 
                                    />
                                    
                                    {{-- 2. TOMBOL SALIN --}}
                                    <button id="copy-lmw-api-key-btn" class="btn btn-outline-secondary" type="button" title="Salin API Key">
                                        <i class="ti ti-copy me-1"></i> Salin
                                    </button>
                                    
                                    @can('regenerate api key')
                                    {{-- 3. TOMBOL REFRESH (Tetap ada) --}}
                                    <button id="refresh-lmw-api-key-btn" class="btn btn-primary" type="button" title="Ganti Token Baru">
                                        <i class="ti ti-refresh me-1"></i> Refresh Token
                                    </button>
                                    @endcan
                                </div>
                                <small class="form-hint">Salin token ini untuk digunakan dalam permintaan API internal.</small>
                            </div>
                            <div class="card-footer bg-transparent mt-4 d-none ps-0" id="lmw-api-footer">
                                <div class="btn-list justify-content-start">
                                    <button type="button" class="btn btn-1" id="cancel-lmw-api">Batal</button>
                                    <button type="submit" form="lmw-api-settings-form" class="btn btn-primary btn-2">Simpan</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Tab Pane: API MIGRASI V1 --}}
                    <div class="tab-pane fade" id="pane-api-v1-migration" role="tabpanel">
                        <div class="row justify-content-between">
                            <div class="col">
                                <h3 class="card-title">API MIGRASI V1</h3>
                            </div>
                            @can('edit api settings')
                            <div class="col-auto">
                                <label class="form-check form-switch form-switch-lg">
                                    <input id="edit-toggle-v1-api" class="form-check-input" type="checkbox" />
                                    <span class="form-check-label form-check-label-on">Edit</span>
                                    <span class="form-check-label form-check-label-off">Edit</span>
                                </label>
                            </div>
                            @endcan
                        </div>
                        <p class="card-subtitle">Kredensial API untuk mengambil data dari sistem lama (V1).</p>
                        <form id="v1-api-settings-form" action="{{ route('users.profile.api.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="api_name" value="v1_migration_api">
                            <div class="mt-4">
                                <label class="form-label">Base URL API V1</label>
                                <input id="v1-base-url-input" type="text" name="base_url" class="form-control" 
                                    value="{{ $apiSettings['v1_migration_api']['base_url'] ?? '' }}" autocomplete="off" disabled />
                                <small class="form-hint">Contoh: http://apibot.lapormaswapres.id/api</small>
                            </div>
                            
                            <div class="mt-4">
                                <label class="form-label">Bearer Token V1 (Auth Sanctum)</label>
                                <input id="v1-api-token-input" type="password" name="authorization" class="form-control" 
                                    value="{{ $apiSettings['v1_migration_api']['authorization'] ?? '' }}" autocomplete="off" disabled />
                                <small class="form-hint">Token yang didapat dari login user migrasi V1.</small>
                            </div>
                            
                            <div class="card-footer bg-transparent mt-4 d-none ps-0" id="v1-api-footer">
                                <div class="btn-list justify-content-start">
                                    <button type="button" class="btn btn-1" id="cancel-v1-api">Batal</button>
                                    <button type="submit" form="v1-api-settings-form" class="btn btn-primary btn-2">Simpan</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Tab Pane: API LAPOR! --}}
                    <div class="tab-pane fade" id="pane-api-lapor" role="tabpanel">
                        <div class="row justify-content-between">
                            <div class="col">
                                <h3 class="card-title">API LAPOR!</h3>
                            </div>
                            @can('edit api settings')
                            <div class="col-auto">
                                <label class="form-check form-switch form-switch-lg">
                                    <input id="edit-toggle-lapor-api" class="form-check-input" type="checkbox" />
                                    <span class="form-check-label form-check-label-on">Edit</span>
                                    <span class="form-check-label form-check-label-off">Edit</span>
                                </label>
                            </div>
                            @endcan
                        </div>
                        <p class="card-subtitle">Manajemen endpoint API eksternal LAPOR!.</p>
                        <form id="lapor-api-settings-form" action="{{ route('users.profile.api.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="api_name" value="lapor_api">
                            <div class="mt-3">
                                <label class="form-label">Base URL</label>
                                <input type="text" name="base_url" class="form-control" value="{{ $apiSettings['lapor_api']['base_url'] ?? '' }}" autocomplete="off" disabled />
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Header Auth Key</label>
                                @php
                                    $currentAuthKey = $apiSettings['lapor_api']['auth_key'] ?? 'Authorization';
                                @endphp
                                <select name="auth_key" class="form-select" disabled>
                                    <option value="Authorization" {{ $currentAuthKey == 'Authorization' ? 'selected' : '' }}>Development (Authorization)</option>
                                    <option value="auth" {{ $currentAuthKey == 'auth' ? 'selected' : '' }}>Production (auth)</option>
                                </select>
                                <small class="form-hint">Pilih nama header untuk Bearer Token (Authorization/auth).</small>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Bearer Token</label>
                                <input type="password" name="auth_value" class="form-control" value="{{ $apiSettings['lapor_api']['auth_value'] ?? '' }}" autocomplete="off" disabled />
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Token Tambahan (Key Lain)</label>
                                <input type="password" name="token" class="form-control" value="{{ $apiSettings['lapor_api']['token'] ?? '' }}" autocomplete="off" disabled />
                            </div>
                        </form>
                        <div class="card-footer bg-transparent mt-4 d-none ps-0" id="lapor-api-footer">
                            <div class="btn-list justify-content-start">
                                <button type="button" class="btn btn-1" id="cancel-lapor-api">Batal</button>
                                <button type="submit" form="lapor-api-settings-form" class="btn btn-primary btn-2">Simpan</button>
                            </div>
                        </div>
                    </div>

                    {{-- Tab Pane: API DUKCAPIL --}}
                    <div class="tab-pane fade" id="pane-api-dukcapil" role="tabpanel">
                        <div class="row justify-content-between">
                            <div class="col">
                                <h3 class="card-title">API DUKCAPIL</h3>
                            </div>
                            @can('edit api settings')
                            <div class="col-auto">
                                <label class="form-check form-switch form-switch-lg">
                                    <input id="edit-toggle-dukcapil-api" class="form-check-input" type="checkbox" />
                                    <span class="form-check-label form-check-label-on">Edit</span>
                                    <span class="form-check-label form-check-label-off">Edit</span>
                                </label>
                            </div>
                            @endcan
                        </div>
                        <p class="card-subtitle">Manajemen endpoint API eksternal DUKCAPIL.</p>
                        <form id="dukcapil-api-settings-form" action="{{ route('users.profile.api.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="api_name" value="dukcapil_api">
                            <div class="mt-3">
                                <label class="form-label">Base URL</label>
                                <input type="text" name="base_url" class="form-control" value="{{ $apiSettings['dukcapil_api']['base_url'] ?? '' }}" autocomplete="off" disabled />
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Authorization</label>
                                <input type="password" name="authorization" class="form-control" value="{{ $apiSettings['dukcapil_api']['authorization'] ?? '' }}" autocomplete="off" disabled />
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Token</label>
                                <input type="password" name="token" class="form-control" value="{{ $apiSettings['dukcapil_api']['token'] ?? '' }}" autocomplete="off" disabled />
                            </div>
                        </form>
                        <div class="card-footer bg-transparent mt-4 d-none ps-0" id="dukcapil-api-footer">
                            <div class="btn-list justify-content-start">
                                <button type="button" class="btn btn-1" id="cancel-dukcapil-api">Batal</button>
                                <button type="submit" form="dukcapil-api-settings-form" class="btn btn-primary btn-2">Simpan</button>
                            </div>
                        </div>
                    </div>

                    {{-- Tab Pane: API GEMINI --}}
                    <div class="tab-pane fade" id="pane-api-gemini" role="tabpanel">
                        <div class="row justify-content-between">
                            <div class="col">
                                <h3 class="card-title">API GEMINI</h3>
                            </div>
                            @can('edit api settings')
                            <div class="col-auto">
                                <label class="form-check form-switch form-switch-lg">
                                    <input id="edit-toggle-gemini-api" class="form-check-input" type="checkbox" />
                                    <span class="form-check-label form-check-label-on">Edit</span>
                                    <span class="form-check-label form-check-label-off">Edit</span>
                                </label>
                            </div>
                            @endcan
                        </div>
                        <p class="card-subtitle">Manajemen endpoint dan kunci untuk Gemini AI.</p>
                        <form id="gemini-api-settings-form" action="{{ route('users.profile.api.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="api_name" value="gemini_api">
                            
                            <div class="mt-3">
                                <label class="form-label">API Endpoint (Versi)</label>
                                <input type="text" name="endpoint" class="form-control" value="{{ $apiSettings['gemini_api']['endpoint'] ?? 'https://generativelanguage.googleapis.com/v1beta' }}" autocomplete="off" disabled />
                            </div>

                            <div class="mt-3">
                                <label class="form-label">Model Digunakan</label>
                                <select name="model" class="form-select" disabled>
                                    <option value="gemini-2.5-flash" @selected(($apiSettings['gemini_api']['model'] ?? '') === 'gemini-2.5-flash')>gemini-2.5-flash (Direkomendasikan)</option>
                                    <option value="gemini-2.5-pro" @selected(($apiSettings['gemini_api']['model'] ?? '') === 'gemini-2.5-pro')>gemini-2.5-pro</option>
                                    <option value="gemini-pro" @selected(($apiSettings['gemini_api']['model'] ?? '') === 'gemini-pro')>gemini-pro (Legacy)</option>
                                    <option value="gemini-1.5-flash" @selected(($apiSettings['gemini_api']['model'] ?? '') === 'gemini-1.5-flash')>gemini-1.5-flash (Legacy)</option>
                                </select>
                            </div>

                            <div class="mt-3">
                                <label class="form-label">API Key Utama (GEMINI_API_KEY)</label>
                                <input type="password" name="api_key_primary" class="form-control" value="{{ $apiSettings['gemini_api']['api_key_primary'] ?? '' }}" autocomplete="off" disabled />
                                <small class="form-hint">Kunci utama untuk klasifikasi.</small>
                            </div>

                            <div class="mt-3">
                                <label class="form-label">API Key Cadangan (Fallback)</label>
                                <input type="password" name="api_key_fallback" class="form-control" value="{{ $apiSettings['gemini_api']['api_key_fallback'] ?? '' }}" autocomplete="off" disabled />
                                <small class="form-hint">Kunci cadangan yang digunakan saat kunci utama gagal (untuk mekanisme retry/fallback).</small>
                            </div>

                        </form>
                        <div class="card-footer bg-transparent mt-4 d-none ps-0" id="gemini-api-footer">
                            <div class="btn-list justify-content-start">
                                <button type="button" class="btn btn-1" id="cancel-gemini-api">Batal</button>
                                <button type="submit" form="gemini-api-settings-form" class="btn btn-primary btn-2">Simpan</button>
                            </div>
                        </div>
                    </div>
                    @endcan
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
                    @if ($errors->any())
                        <div class="alert alert-danger mb-3">
                            Kata sandi baru tidak cocok atau tidak memenuhi syarat.
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Kata Sandi Lama</label>
                        <input type="password" name="current_password" 
                               class="form-control @error('current_password') is-invalid @enderror" required />
                        @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kata Sandi Baru</label>
                        <input type="password" name="new_password" 
                               class="form-control @error('new_password') is-invalid @enderror" required />
                        @error('new_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" name="new_password_confirmation" 
                               class="form-control @error('new_password_confirmation') is-invalid @enderror" required />
                        @error('new_password_confirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
        // --- LOGIKA UTAMA: MENGAKTIFKAN TAB SETELAH REDIRECT ---
        const activeTab = new URL(location.href).hash;
        if (activeTab) {
            const tabElement = document.querySelector(`a[data-bs-target="${activeTab}"]`);
            if (tabElement) {
                // Nonaktifkan tab yang aktif secara default
                const defaultActiveTab = document.querySelector('#settings-tab a.active');
                if (defaultActiveTab) {
                    defaultActiveTab.classList.remove('active');
                }
                const defaultActivePane = document.querySelector('#settings-tabContent .tab-pane.show.active');
                if (defaultActivePane) {
                    defaultActivePane.classList.remove('show', 'active');
                }

                // Aktifkan tab dan pane yang sesuai dengan URL
                tabElement.classList.add('active');
                const paneElement = document.querySelector(activeTab);
                if (paneElement) {
                    paneElement.classList.add('show', 'active');
                }
            }
        }

        // --- LOGIKA MENAMPILKAN SWAL FIRE DARI SESSION FLASH ---
        const swalWarning = '{{ session('swal:warning') }}';

        if (swalWarning) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: swalWarning,
                showConfirmButton: false,
                timer: 5000
            });
        }

        const hasSessionError = '{{ session('error') }}';
        const modalResetPassword = document.getElementById('modal-reset-password');

        if (hasSessionError && modalResetPassword) {
            const modalInstance = new bootstrap.Modal(modalResetPassword);
            modalInstance.show();
            
             Swal.fire({
                 toast: true,
                 position: 'top-end',
                 icon: 'error',
                 title: hasSessionError,
                 showConfirmButton: false,
                 timer: 5000
             });
        }
        
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

        // --- Logika API Settings untuk setiap tab ---
        function setupApiForm(apiName) {
            const toggle = document.getElementById(`edit-toggle-${apiName}-api`);
            const footer = document.getElementById(`${apiName}-api-footer`);
            const form = document.getElementById(`${apiName}-api-settings-form`);
            const controls = form.querySelectorAll("input.form-control, textarea.form-control, select.form-select");
            const cancelBtn = document.getElementById(`cancel-${apiName}-api`);
            
            function applyEditState(editing) {
                if (footer) {
                    footer.classList.toggle("d-none", !editing);
                }
                controls.forEach((el) => {
                    el.disabled = !editing;
                });

                if (apiName === 'lmw') {
                    const refreshBtn = document.getElementById('refresh-lmw-api-key-btn');
                    const keyInput = document.getElementById('lmw-api-key-input');
                    if (refreshBtn) {
                        refreshBtn.disabled = !editing;
                    }
                    if (keyInput) {
                        keyInput.disabled = !editing;
                    }
                }
            }

            if (toggle) {
                applyEditState(toggle.checked);
                toggle.addEventListener("change", () => applyEditState(toggle.checked));
                
                if (cancelBtn) {
                    cancelBtn.addEventListener("click", (e) => {
                        e.preventDefault();
                        toggle.checked = false;
                        applyEditState(false);
                    });
                }
            }
        }

        setupApiForm('lmw');
        setupApiForm('v1');
        setupApiForm('lapor');
        setupApiForm('dukcapil');
        setupApiForm('gemini');

        // JavaScript untuk merefresh API Key
        const refreshBtn = document.getElementById('refresh-lmw-api-key-btn');
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
                                document.getElementById('lmw-api-key-input').value = data.new_token;
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
<script>
    document.getElementById('copy-lmw-api-key-btn').addEventListener('click', function() {
        var inputElement = document.getElementById('lmw-api-key-input');
        
        var originalType = inputElement.type;
        var originalDisabled = inputElement.disabled;
        
        inputElement.type = 'text';
        inputElement.disabled = false;
        
        inputElement.select(); 
        inputElement.setSelectionRange(0, 99999);
        
        try {
            document.execCommand('copy');
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Token API berhasil disalin!',
                showConfirmButton: false,
                timer: 3000
            });
        } catch (err) {
            console.error('Gagal menyalin token:', err);
             Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Gagal menyalin token. Coba salin manual.',
                showConfirmButton: false,
                timer: 4000
            });
        }
        
    
        setTimeout(() => {
            inputElement.type = originalType; 
            inputElement.disabled = originalDisabled;
        }, 0);
        
    });
</script>
@endpush