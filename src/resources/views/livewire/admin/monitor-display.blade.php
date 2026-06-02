<div class="h-100 d-flex flex-column" wire:poll.3s>
    <div class="card sidebar-card border-0 shadow-lg flex-grow-1 d-flex flex-column" style="background: #003366; border-radius: 12px; overflow: hidden;">
        
        <div class="card-header bg-transparent border-bottom border-white-10 py-3 px-3">
            <h3 class="card-title fw-bold text-white mb-0" style="font-size: 1.6rem;">
                <i class="ti ti-device-desktop-analytics me-2" style="font-size: 1.8rem;"></i>Status Loket
            </h3>
        </div>

        <div class="card-body p-0 flex-grow-1" style="overflow-y: auto;">
            <div class="table-responsive">
                <table class="table table-vcenter table-transparent text-white mb-0">
                    <thead style="background: rgba(0, 0, 0, 0.25);">
                        <tr class="text-white-50">
                            <th class="py-3 ps-3" style="font-size: 1.1rem; font-weight: 800; letter-spacing: 1px; color: #a0c4ff !important;">MEJA / LOKET</th>
                            <th class="text-center py-3" style="font-size: 1.1rem; font-weight: 800; letter-spacing: 1px; color: #a0c4ff !important;">TERLAYANI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activeCounters as $c)
                        <tr class="border-bottom border-white-10 animate__animated animate__fadeIn">
                            <td class="py-3 ps-3">
                                <div class="fw-black mb-1 text-white" style="font-size: 2rem; font-weight: 900; letter-spacing: -1px;">
                                    LOKET {{ $c->counter_number }}
                                </div>
                                <span class="badge bg-info-lt" style="font-size: 0.9rem; padding: 4px 12px; background: rgba(0, 210, 255, 0.15); color: #00d2ff; font-weight: 700;">
                                    <span class="badge-blink-dot me-1"></span>Online
                                </span>
                            </td>
                            <td class="text-center py-3">
                                <div class="fw-black text-white mb-0" style="font-size: 2.8rem; font-weight: 900; line-height: 1;">
                                    {{ $c->total }}
                                </div>
                                <div style="font-size: 0.9rem; letter-spacing: 0.5px;" class="text-white-50 text-uppercase fw-bold mt-1">Antrean</div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center py-5 text-white-50">
                                <i class="ti ti-info-circle d-block mb-2 text-white-50" style="font-size: 3rem;"></i>
                                <div class="fw-bold" style="font-size: 1.4rem;">Belum ada loket aktif</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer border-0 text-white text-center py-3" style="background: rgba(0, 0, 0, 0.35);">
            <div style="font-size: 1.1rem; letter-spacing: 1px;" class="text-white-50 fw-bold text-uppercase mb-1">Total Terlayani Hari Ini</div>
            <div class="fw-black mb-0 text-white" style="font-size: 3.2rem; font-weight: 900; line-height: 1;">
                {{ $activeCounters->sum('total') }}
            </div>
        </div>
    </div>

    <style>
        .fw-black { font-weight: 900 !important; }
        
        .sidebar-card .border-white-10 {
            border-color: rgba(255, 255, 255, 0.12) !important;
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

        .badge-blink-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #00d2ff;
            border-radius: 50%;
            animation: dot-blink 1.5s infinite;
        }
        @keyframes dot-blink { 
            0% { opacity: 0.4; } 
            50% { opacity: 1; } 
            100% { opacity: 0.4; } 
        }
    </style>
</div>