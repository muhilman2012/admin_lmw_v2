<header class="navbar navbar-expand-md d-print-none">
    <div class="container-xl">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
            <a href="{{ route('dashboard') }}" aria-label="LaporMasWapres">
                <img src="{{ asset('tabler/img/logo/LaporMasWapres.png') }}" alt="Logo LaporMasWapres" class="navbar-brand-image" style="height: 32px;" />
            </a>
        </div>
        
        <div class="navbar-nav flex-row order-md-last">
            <div class="d-none d-md-flex">
                <div class="nav-item">
                    <a href="?theme=dark" class="nav-link px-0 hide-theme-dark" title="Enable dark mode" data-bs-toggle="tooltip" data-bs-placement="bottom">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                            <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" />
                        </svg>
                    </a>
                    <a href="?theme=light" class="nav-link px-0 hide-theme-light" title="Enable light mode" data-bs-toggle="tooltip" data-bs-placement="bottom">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                            <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                            <path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7" />
                        </svg>
                    </a>
                </div>
                <div class="nav-item dropdown d-none d-md-flex">
                    <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1" aria-label="Show notifications">
                        <i class="ti ti-bell-ringing"></i>
                        
                        @php
                            $unreadCount = auth()->user()->unreadNotifications->count();
                        @endphp

                        @if ($unreadCount > 0)
                            <span class="badge bg-red badge-unread badge-blink">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                        <div class="card">
                            <div class="card-header d-flex">
                                {{-- Menghapus hitungan dari judul di sini, fokus pada badge di luar --}}
                                <h3 class="card-title">Notifications</h3>
                                <div class="btn-close ms-auto" data-bs-dismiss="dropdown"></div>
                            </div>
                            
                            <div class="list-group list-group-flush list-group-hoverable" id="notification-dropdown-list">
                                @forelse (auth()->user()->unreadNotifications->take(5) as $notification)
                                    @php
                                        $data = $notification->data;
                                        $icon = $data['icon'] ?? 'ti ti-bell-ringing';
                                        $color = $data['color'] ?? 'primary';
                                        $url = $data['url'] . '?read=' . $notification->id; // Tambahkan query untuk mark as read
                                    @endphp
                                    <div class="list-group-item">
                                        <a href="{{ $url }}" class="d-flex text-decoration-none">
                                            <div>
                                                <span class="avatar avatar-sm rounded bg-{{ $color }}-lt me-3">
                                                    <i class="{{ $icon }}"></i>
                                                </span>
                                            </div>
                                            <div class="d-flex flex-column flex-grow-1">
                                                <div class="font-weight-medium">{{ $data['title'] ?? 'Notifikasi Baru' }}</div>
                                                <div class="text-secondary" style="font-size: 0.85rem;">
                                                    {!! Str::limit($data['message'] ?? 'Lihat detail laporan.', 50) !!}
                                                </div>
                                                <div class="text-secondary mt-1" style="font-size: 0.75rem;">
                                                    {{ $notification->created_at->diffForHumans() }}
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                @empty
                                    <div class="p-3 text-center text-secondary">Tidak ada notifikasi baru.</div>
                                @endforelse
                            </div>

                            @if (auth()->user()->unreadNotifications->isNotEmpty())
                                <div class="card-body border-top">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('notifications.markAllRead') }}" class="btn btn-2 btn-secondary w-100"> 
                                            Tandai semua sudah dibaca 
                                        </a>
                                        <a href="{{ route('users.profile.index', ['#pane-notifications']) }}" class="btn btn-2 btn-primary w-100"> 
                                            Lihat Semua Notifikasi 
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="card-body border-top">
                                    <a href="{{ route('users.profile.index', ['#pane-notifications']) }}" class="btn btn-2 btn-primary w-100"> 
                                        Lihat Semua Notifikasi 
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 p-0 px-2" data-bs-toggle="dropdown" aria-label="Open user menu">
                    <x-user-avatar :user="Auth::user()" size="sm" />
                    <div class="d-none d-xl-block ps-2">
                        <div>
                            {{ Str::limit(Auth::user()->name, 15) }}
                        </div>
                        <div class="mt-1 small text-secondary">{{ Auth::user()->role }}</div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow z-3">
                    <a href="{{ route('users.profile.index') }}" class="dropdown-item">Profil</a>
                    <a href="{{ route('kms.index') }}" target="_blank" class="dropdown-item">KMS</a>
                    @can('manage structure')
                        <a href="{{ route('settings.index') }}" class="dropdown-item">Pengaturan</a>
                    @endcan
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        Logout
                    </a>
                </div>
            </div>
        </div>
        
        <div class="collapse navbar-collapse" id="navbar-menu">
            <ul class="navbar-nav">
                <li class="nav-item {{ Request::is('admin/dashboard') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('dashboard') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-home"></i>
                        </span>
                        <span class="nav-link-title">Beranda</span>
                    </a>
                </li>
                <li class="nav-item {{ Request::is('admin/users/profile') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('users.profile.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-user-circle"></i>
                        </span>
                        <span class="nav-link-title">Profil</span>
                    </a>
                </li>
                <li class="nav-item {{ Request::is('admin/reporters*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('reporters.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-list"></i>
                        </span>
                        <span class="nav-link-title">Daftar Pengadu</span>
                    </a>
                </li>
                <li class="nav-item {{ Request::is('admin/reports*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('reports.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-file-text"></i>
                        </span>
                        <span class="nav-link-title">Kelola Pengaduan</span>
                    </a>
                </li>
                <li class="nav-item {{ Request::is('admin/forwarding*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('forwarding.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-share"></i>
                        </span>
                        <span class="nav-link-title">Laporan Diteruskan</span>
                    </a>
                </li>
                <li class="nav-item {{ Request::is('admin/search*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('search.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-search"></i>
                        </span>
                        <span class="nav-link-title">Pencarian</span>
                    </a>
                </li>
                @can('export data')
                <li class="nav-item {{ Request::is('admin/export*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('export.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-download"></i>
                        </span>
                        <span class="nav-link-title">Export</span>
                    </a>
                </li>
                @endcan
                @can('view users')
                    <li class="nav-item {{ Request::is('admin/users/management*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('users.management.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-users"></i>
                            </span>
                            <span class="nav-link-title">Manajemen Pengguna</span>
                        </a>
                    </li>
                @endcan
            </ul>
        </div>
    </div>
</header>