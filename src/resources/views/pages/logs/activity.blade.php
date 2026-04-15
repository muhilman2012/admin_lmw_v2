@extends('layouts.app')

@section('content')
<div class="container-xl">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">Monitoring Activity Logs</h2>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Riwayat Aktivitas Sistem</h3>
            </div>
            <div class="card-body border-bottom py-3">
                <form action="{{ route('logs.activity') }}" method="GET">
                    <div class="d-flex align-items-end gap-2">
                        <div class="flex-grow-1">
                            <label class="form-label">Cari Kata Kunci</label>
                            <input type="text" name="search" class="form-control" placeholder="Cari aksi atau deskripsi..." value="{{ request('search') }}">
                        </div>
                        <div style="width: 200px;">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                        <div style="width: 200px;">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                        <div class="btn-list">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-search me-1"></i> Filter
                            </button>
                            @if(request()->anyFilled(['search', 'start_date', 'end_date']))
                                <a href="{{ route('logs.activity') }}" class="btn btn-outline-secondary">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>User</th>
                            <th>Aksi</th>
                            <th>Deskripsi</th>
                            <th>Tautan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-xs me-2">{{ substr($log->user->name ?? 'S', 0, 1) }}</span>
                                    {{ $log->user->name ?? 'System' }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-blue-lt">{{ strtoupper($log->action) }}</span>
                            </td>
                            <td class="text-wrap" style="min-width: 300px;">
                                {{ $log->description }}
                            </td>
                            <td>
                                @if($log->loggable_type === 'App\Models\Report')
                                    <a href="{{ route('reports.show', $log->loggable->uuid ?? '#') }}" class="btn btn-sm btn-ghost-primary">
                                        Lihat Laporan
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">Belum ada log aktivitas.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex align-items-center">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection