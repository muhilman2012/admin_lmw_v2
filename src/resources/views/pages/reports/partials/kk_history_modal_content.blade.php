<h5 class="modal-title mb-3">Riwayat Laporan untuk KK #{{ $kkNumber }}</h5>
@php
    $reportsWithBenefit = $reports->filter(fn($r) => $r->is_benefit_provided);
@endphp

@if ($reportsWithBenefit->isNotEmpty())
    <div class="alert alert-danger mb-4 shadow-sm">
        <i class="ti ti-checkup-list me-2"></i>
        <strong>{{ $reportsWithBenefit->count() }} Laporan Sudah Menerima Bantuan/Manfaat!</strong>
        <span>Perhatikan data ini untuk menghindari duplikasi bantuan.</span>
    </div>
@endif

@if ($reports->isEmpty())
    <div class="alert alert-info">Tidak ada laporan lain yang ditemukan untuk Nomor KK ini.</div>
@else
    <p class="text-muted small">Total {{ $reports->count() }} laporan terkait ditemukan. Periksa kolom status untuk mengetahui penerimaan bantuan.</p>
    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th># Tiket</th>
                    <th>Kategori</th>
                    <th>Status Laporan</th>
                    <th>Status Bantuan</th>
                    <th>Tanggal Dibuat</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reports as $report)
                    <tr>
                        <td>
                            <a href="{{ route('reports.show', $report->uuid) }}" target="_blank" class="text-primary" title="Lihat Detail">{{ $report->ticket_number }}</a>
                        </td>
                        <td>{{ $report->category->name ?? 'N/A' }}</td> 
                        <td>
                            <span class="badge bg-{{ $report->status == 'Penanganan Selesai' ? 'success' : 'warning' }}-lt">{{ $report->status }}</span>
                        </td>
                        <td>
                            @if ($report->is_benefit_provided)
                                <span class="badge bg-success-lt fw-bold" title="Laporan ini ditandai sebagai telah memberikan manfaat/solusi.">
                                    <i class="ti ti-star me-1"></i> Telah Dibantu
                                </span>
                            @else
                                <span class="badge bg-secondary-lt">Belum Dikonfirmasi</span>
                            @endif
                        </td>
                        <td>{{ $report->created_at->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif