<div class="h-100 d-flex flex-column">
    <div class="card sidebar-card border-0 shadow-lg flex-grow-1 d-flex flex-column" style="background: #003366; border-radius: 12px; overflow: hidden;">
        
        <div class="card-header bg-transparent border-bottom border-white-10 py-2 px-3">
            <h3 class="card-title fw-bold text-white mb-0" style="font-size: 1.1rem;">
                <i class="ti ti-device-desktop-analytics me-2"></i>Status Loket
            </h3>
        </div>

        <div class="card-body p-0 flex-grow-1" style="overflow-y: auto;">
            <div class="table-responsive">
                <table class="table table-vcenter table-transparent text-white mb-0">
                    <thead style="background: rgba(0, 0, 0, 0.15);">
                        <tr class="text-white-50">
                            <th class="py-2 ps-3" style="font-size: 0.75rem; letter-spacing: 1px; color: #a0c4ff !important;">MEJA / LOKET</th>
                            <th class="text-center py-2" style="font-size: 0.75rem; letter-spacing: 1px; color: #a0c4ff !important;">TERLAYANI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activeCounters as $c)
                        <tr class="border-bottom border-white-10">
                            <td class="py-2 ps-3">
                                <div class="fw-bold mb-0 text-white" style="font-size: 1.2rem;">LOKET {{ $c->counter_number }}</div>
                                <span class="badge bg-info-lt" style="font-size: 0.65rem; padding: 2px 8px; background: rgba(0, 210, 255, 0.1); color: #00d2ff;">Online</span>
                            </td>
                            <td class="text-center py-2">
                                <div class="h1 fw-bold text-white mb-0" style="line-height: 1.1;">{{ $c->total }}</div>
                                <div style="font-size: 0.65rem;" class="text-white-50 text-uppercase fw-bold">Antrean</div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center py-5 text-white-50">
                                <i class="ti ti-info-circle h2 d-block mb-1"></i>
                                <small>Belum ada loket aktif</small>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer border-0 text-white text-center py-2" style="background: rgba(0, 0, 0, 0.25);">
            <div style="font-size: 0.7rem;" class="text-white-50 fw-bold text-uppercase mb-1">Total Terlayani Hari Ini</div>
            <div class="h2 fw-bold mb-0 text-white" style="line-height: 1;">{{ $activeCounters->sum('total') }}</div>
        </div>
    </div>

    <style>
        /* Menyesuaikan border agar tetap senada dengan biru tua */
        .sidebar-card .border-white-10 {
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        
        .card-body::-webkit-scrollbar {
            width: 4px;
        }
        .card-body::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }
        .card-body::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        /* Warna teks info/biru muda untuk label header tabel */
        .text-info-light {
            color: #a0c4ff !important;
        }
    </style>
</div>