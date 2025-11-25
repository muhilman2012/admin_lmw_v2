<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Report;
use App\Models\User;
use App\Models\Deputy;
use App\Models\Category;
use App\Models\UnitKerja;
use App\Models\Assignment;
use App\Models\ProvinceMapId;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class DashboardController extends Controller
{
    /**
     * Helper: Menentukan scope data berdasarkan peran pengguna.
     */
    private function applyUserScope(Builder $query, User $user)
    {
        // --- LEVEL 1: SUPER ADMIN & ADMIN (Akses Penuh) ---
        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return $query;
        }
        
        // --- LEVEL 2: ANALIS ---
        if ($user->hasRole('analyst')) {
            return $query->whereHas('assignments', function (Builder $q) use ($user) {
                $q->where('assigned_to_id', $user->id);
            });
        }

        // --- LEVEL 3: DEPUTI ---
        if ($user->hasRole('deputy') && $user->deputy_id) {
            $unitIds = UnitKerja::where('deputy_id', $user->deputy_id)->pluck('id');

            return $query->whereHas('category', function (Builder $q) use ($unitIds) {
                $q->whereHas('unitKerjas', function ($qUnit) use ($unitIds) {
                    $qUnit->whereIn('unit_kerjas.id', $unitIds);
                });
            });
        }
        
        // --- LEVEL 4: ASDEP/KARO (Unit Kerja) ---
        if ($user->unit_kerja_id) {
            return $query->whereHas('category', function (Builder $q) use ($user) {
                $q->whereHas('unitKerjas', function ($qUnit) use ($user) {
                    $qUnit->where('unit_kerjas.id', $user->unit_kerja_id);
                });
            });
        }
        
        // --- LEVEL 5: DEFAULT/FALLBACK ---
        return $query->where('reports.user_id', $user->id);
    }


    /**
     * Memuat statistik laporan berdasarkan kategori aktif (Parent/Sub-Kategori).
     * Mengaplikasikan scope laporan berdasarkan peran user.
     */
    protected function loadCategoryStatistics(User $user, string $filterKey)
    {
        // Ambil base query Reports yang sudah difilter waktu
        $baseQuery = Report::query();
        if ($filterKey !== 'total') {
            $range = $this->getTimeRange($filterKey);
            $baseQuery->whereBetween('reports.created_at', [$range['startDate'], $range['endDate']]);
        }
        
        // Terapkan scope akses (RBAC)
        $scopedQuery = $this->applyUserScope($baseQuery, $user);

        // 1. Ambil semua kategori aktif (termasuk Parent dan Child)
        $activeCategories = Category::active()->with('children')->get();
        
        // 2. Hitung jumlah laporan per category_id
        $reportCounts = $scopedQuery->clone()
            ->whereNotNull('category_id')
            ->select('category_id', DB::raw('count(*) as total'))
            ->groupBy('category_id')
            ->pluck('total', 'category_id')
            ->toArray();

        $categoryStats = [];

        // 3. Agregasi: Hitung total Child ke Parent dan buat struktur Tree
        foreach ($activeCategories->whereNull('parent_id') as $parent) {
            $parentTotal = $reportCounts[$parent->id] ?? 0;
            $childrenStats = [];

            foreach ($parent->children->where('is_active', true) as $child) {
                $childTotal = $reportCounts[$child->id] ?? 0;
                
                if ($childTotal > 0) {
                    $childrenStats[] = [
                        'id' => $child->id,
                        'name' => $child->name,
                        'total' => $childTotal
                    ];
                    $parentTotal += $childTotal; // Agregasi ke Parent
                }
            }
            
            if ($parentTotal > 0) {
                $categoryStats[] = [
                    'id' => $parent->id,
                    'name' => $parent->name,
                    'total' => $parentTotal,
                    'children' => $childrenStats, // Sub-Kategori di dalam Parent
                ];
            }
        }
        
        // 4. Urutkan berdasarkan total laporan (tertinggi ke terendah)
        return collect($categoryStats)->sortByDesc('total')->values()->all();
    }

    /**
     * Helper: Menentukan range waktu untuk filter
     */
    private function getTimeRange(string $filter = 'total')
    {
        $endDate = Carbon::now();
        switch ($filter) {
            case 'total':
                $startDate = Carbon::createFromTimestamp(0); 
                $label = 'Total Semua Data';
                break;
            case '1_years':
                $startDate = Carbon::now()->subDays(365);
                $label = 'Last 1 Years';
                break;
            case '30_days':
                $startDate = Carbon::now()->subDays(30);
                $label = 'Last 30 Days';
                break;
            case '3_months':
                $startDate = Carbon::now()->subMonths(3);
                $label = 'Last 3 Months';
                break;
            case '7_days':
            default:
                $startDate = Carbon::now()->subDays(6);
                $label = 'Last 7 Days';
                $filter = '7_days'; // Pastikan filter key-nya benar
                break;
        }
        return ['startDate' => $startDate->startOfDay(), 'endDate' => $endDate->endOfDay(), 'label' => $label, 'key' => $filter];
    }
    
    public function index(Request $request)
    {
        $user = Auth::user();
        $filterKey = $request->get('range', 'total');
        $range = $this->getTimeRange($filterKey);

        $deputyStats = collect();
        $deputyPieChartData = [];
        $userDeputyPieChartData = [];
        $totalComplaintStatusPieData = [];
        $categoryStats = $this->loadCategoryStatistics($user, $filterKey);

        $totalStatusSourceRaw = collect();
        $sourceDetailsGlobal = [];

        // Inisialisasi Base Query (untuk Card Statistik & Map)
        $baseQuery = Report::query(); 
        // Filter Waktu KONDISIONAL untuk Card & Map
        if ($filterKey !== 'total') {
            $baseQuery->whereBetween('reports.created_at', [$range['startDate'], $range['endDate']]);
        }
        
        $scopedQuery = $this->applyUserScope($baseQuery->clone(), $user);

        // --- Logika Source Counts, Map Stats, dan Report Stats ---
        $sourceCounts = $scopedQuery->clone()
            ->select('source', DB::raw('count(*) as total'))
            ->groupBy('source')
            ->pluck('total', 'source')
            ->toArray();

        $mapStats = $this->loadMapStatistics($scopedQuery->clone(), $user);
        $mapNames = ProvinceMapId::query()
            ->select(['map_id', 'name'])
            ->get()
            ->mapWithKeys(function ($row) {
                return [(string) $row->map_id => $row->name ?? (string) $row->map_id];
            })
            ->toArray();

        $mapDataJson  = json_encode($mapStats, JSON_UNESCAPED_UNICODE);
        $mapNamesJson = json_encode($mapNames, JSON_UNESCAPED_UNICODE);

        $undisposedCount = 0;
        $disposedCount = 0;
        $undistributedCount = 0;
        $distributedCount = 0;

        $pendingAssignmentCount = 0;
        $submittedCount = 0;
        $pendingAssignments = collect();
        $submittedReports = collect();

        $otherCategory = Category::where('name', 'Lainnya')->first();
        $otherCategoryId = $otherCategory ? $otherCategory->id : null;
        $baseQueryClone = $baseQuery->clone();

        $analystMonitoringData = [];
        if ($user->hasAnyRole(['deputy', 'asdep_karo'])) {
            $analystMonitoringData = $this->loadAnalystMonitoring($user, $filterKey);
        }
        
        // Logika Berdasarkan Role
        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            // ... (Logika Distribusi/Disposisi untuk Admin/Superadmin) ...
            if ($otherCategoryId) {
                $undistributedCount = $baseQueryClone->clone()
                    ->where('category_id', $otherCategoryId)
                    ->count();

                $distributedCount = $baseQueryClone->clone()
                    ->where('category_id', '!=', $otherCategoryId)
                    ->whereNotNull('category_id') 
                    ->count();
            } else {
                $undistributedCount = 0;
                $distributedCount = $baseQueryClone->count(); 
            }

            $undisposedCount = $baseQueryClone->clone()
                ->whereDoesntHave('assignments')
                ->count();

            $disposedCount = $baseQueryClone->clone()
                ->whereHas('assignments')
                ->count();
            
        } elseif ($user->hasAnyRole(['deputy', 'asdep_karo'])) {
            // ... (Logika Disposisi untuk Deputi/Asdep/Karo) ...
            $targetQuery = $baseQueryClone;
            
            if ($user->hasRole('deputy') && $user->deputy_id) {
                $targetQuery->where('reports.deputy_id', $user->deputy_id);
            } elseif ($user->hasRole('asdep_karo') && $user->unit_kerja_id)  {
                $targetQuery->where('reports.unit_kerja_id', $user->unit_kerja_id);
            }
            
            $disposedCount = $targetQuery->clone()
                ->whereHas('assignments')
                ->count();
            
            $undisposedCount = $targetQuery->clone()
                ->whereDoesntHave('assignments')
                ->count();

            // >>> LOGIKA HITUNG SUBMITTED UNTUK DEPUTY/ASDEP <<<
            $submittedQuery = $targetQuery->clone();

            $submittedCount = $submittedQuery
                ->whereHas('assignments', function (Builder $query) {
                    // Hanya hitung laporan yang status assignment-nya 'submitted'
                    $query->where('status', 'submitted'); 
                })
                ->count();

            $submittedReports = $submittedQuery->clone()
                ->whereHas('assignments', function (Builder $query) {
                    $query->where('status', 'submitted');
                })
                ->with('category')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $userDeputyPieChartData = $this->loadDeputyStatusPieChartData($user, $filterKey);
        }

        if ($user->hasRole('analyst')) {
            // 🔥 Status Report yang diizinkan untuk Assignment 'approved'
            $targetReportStatus = 'Proses verifikasi dan telaah';
            
            // Status Assignment yang SELALU memerlukan tindakan Analis
            $actionRequiredAssignmentStatuses = ['pending', 'rejected', 'Perlu Perbaikan'];
            
            // 1. Buat Base Query dengan kondisi OR yang kompleks
            $analystReportsQuery = Report::query();
            
            $analystReportsQuery->where(function ($query) use ($user, $targetReportStatus, $actionRequiredAssignmentStatuses) {
                
                // A. Kondisi OR 1: Assignment status yang memerlukan tindakan (pending, rejected, Perlu Perbaikan)
                // Laporan ini selalu muncul jika assignment-nya memiliki salah satu status ini.
                $query->whereHas('assignments', function (Builder $q) use ($user, $actionRequiredAssignmentStatuses) {
                    $q->where('assigned_to_id', $user->id)
                    ->whereIn('status', $actionRequiredAssignmentStatuses);
                });

                // B. Kondisi OR 2: Assignment status 'approved' HANYA JIKA Report status masih target/default
                $query->orWhere(function ($q) use ($user, $targetReportStatus) {
                    $q->where('status', $targetReportStatus) // Report status HARUS sesuai
                    ->whereHas('assignments', function (Builder $qq) use ($user) {
                        $qq->where('assigned_to_id', $user->id)
                            ->where('status', 'approved'); // Assignment status HARUS 'approved'
                    });
                });
            });
            
            // 2. Ambil data hitungan Report
            $pendingAssignmentCount = (clone $analystReportsQuery)->count();

            // 3. Ambil data Report (Mini-Datatable)
            $pendingAssignments = (clone $analystReportsQuery)
                ->with('category')
                ->orderBy('created_at', 'desc')
                ->limit(7)
                ->get();
        }

        $reportStats = [
            'whatsapp' => $sourceCounts['whatsapp'] ?? 0,
            'tatap muka' => $sourceCounts['tatap muka'] ?? 0,
            'surat fisik' => $sourceCounts['surat fisik'] ?? 0,
            'undistributed_count' => $undistributedCount, 
            'distributed_count' => $distributedCount,
            'undisposed_count' => $undisposedCount, 
            'disposed_count' => $disposedCount,
            'submitted' => $submittedCount,
            'pending_assignment' => $pendingAssignmentCount,
        ];
        $reportStats['total'] = $reportStats['whatsapp'] + $reportStats['tatap muka'] + $reportStats['surat fisik'];

        // --- Logika Daily Trend Data ---
        if ($filterKey === 'total') {
            $trendStartDate = Carbon::now()->subDays(29)->startOfDay();
            $trendEndDate = Carbon::now()->endOfDay();
            $trendQuery = Report::query()->whereBetween('reports.created_at', [$trendStartDate, $trendEndDate]);
            $trendScopedQuery = $this->applyUserScope($trendQuery, $user);
            $chartRange = ['startDate' => $trendStartDate, 'endDate' => $trendEndDate, 'key' => '30_days']; 
        } else {
            $trendScopedQuery = $scopedQuery->clone();
            $chartRange = $range;
        }

        $trendData = $trendScopedQuery
            ->select(
                DB::raw('DATE(reports.created_at) as date'),
                'source',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date', 'source')
            ->orderBy('date', 'asc')
            ->get();
        
        $chartData = $this->prepareChartData($trendData, $chartRange);
        // --- End Logika Daily Trend Data ---


        // 4. Ambil Data Agregat Per Deputi (Hanya untuk Superadmin/Admin)
        $totalComplaintStatusPieData = [];
        // INISIALISASI WAJIB untuk menghindari error "Undefined variable $deputies" jika Role check gagal
        $deputies = new Collection(); 

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            // ISI variabel $deputies HANYA JIKA ROLE CHECK BERHASIL
            $deputies = Deputy::orderBy('id')->get();
            
            $statusMapping = [
                'Proses verifikasi dan telaah' => 'Verifikasi & Telaah',
                'Menunggu kelengkapan data dukung dari Pelapor' => 'Menunggu Data Dukung',
                'Diteruskan kepada instansi yang berwenang untuk penanganan lebih lanjut' => 'Diteruskan ke Instansi',
                'Penanganan Selesai' => 'Penanganan Selesai',
            ];
            
            $statusToTrackCleaned = array_map('trim', array_keys($statusMapping));
            $statusMappingCleaned = array_combine($statusToTrackCleaned, $statusMapping);
            $sourceKeysDB = ['whatsapp', 'tatap muka', 'surat fisik'];
            
            // --- 5. AGREGASI PIE CHART STATUS PENGADUAN TOTAL (SEMUA DEPUTI) ---
            
            // 1. Query untuk mengambil COUNT status pengaduan dari SEMUA laporan yang sudah terdisposisi ke deputi
            $allDeputiesBaseQuery = Report::query()
                ->whereNotNull('reports.deputy_id') 
                ->whereIn(DB::raw('TRIM(reports.status)'), $statusToTrackCleaned);
            
            // Ambil data agregat STATUS PENGADUAN TOTAL
            $totalStatusSourceRaw = $allDeputiesBaseQuery->clone()
                ->select(DB::raw('TRIM(reports.status) as status_raw'), 'reports.source', DB::raw('COUNT(*) as total'))
                ->groupBy('status_raw', 'reports.source')
                ->get();

            // Ambil data agregat STATUS PENGADUAN TOTAL (Total Counts)
            $totalStatusCountsRaw = $totalStatusSourceRaw->groupBy('status_raw')->map->sum('total')->toArray();

            $labels = [];
            $series = [];
            $sourceDetailsGlobal = [];

            // Loop melalui status yang ingin ditampilkan
            foreach ($statusMappingCleaned as $statusCodeCleaned => $mappedStatus) {
                $count = $totalStatusCountsRaw[$statusCodeCleaned] ?? 0;
                
                if ($count > 0) {
                    $labels[] = $mappedStatus;
                    $series[] = $count;

                    $sourceDetailEntry = [];
                    foreach ($sourceKeysDB as $source) {
                        $sourceDetailEntry[$source] = $totalStatusSourceRaw
                            ->where('status_raw', $statusCodeCleaned)
                            ->where('source', $source)
                            ->sum('total');
                    }
                    $sourceDetailsGlobal[$mappedStatus] = $sourceDetailEntry;
                }
            }
            
            $totalComplaintStatusPieData = [
                'title' => 'Total Status Pengaduan Semua Deputi',
                'labels' => $labels,
                'series' => $series,
                'source_details' => $sourceDetailsGlobal,
            ];
            // --- END PIE CHART STATUS PENGADUAN TOTAL ---


            // --- Logika Pie Chart Per Deputi (5.2) ---
            foreach ($deputies as $deputy) { 
                // --- 1. Ambil Unit Kerjas di bawah Deputi yang sedang di-loop ---
                $unitIds = UnitKerja::where('deputy_id', $deputy->id)->pluck('id');
                
                // --- 2. Perluas Query Dasar Deputi (Scope Penuh) ---
                $deputyQueryBase = Report::query()->where(function (Builder $query) use ($deputy, $unitIds) {
                    $query->where('reports.deputy_id', $deputy->id)
                        // TAMBAHKAN FILTER BERDASARKAN KATEGORI/UNIT KERJA
                        ->orWhereHas('category', function (Builder $qCat) use ($unitIds) {
                            $qCat->whereHas('unitKerjas', function ($qUnit) use ($unitIds) {
                                $qUnit->whereIn('unit_kerjas.id', $unitIds);
                            });
                        });
                });
                
                // --- Card Statistik Per Deputi ---
                $deputySourceCounts = $deputyQueryBase->clone()
                    ->select('source', DB::raw('count(*) as total'))
                    ->groupBy('source')->pluck('total', 'source')->toArray();
                
                $deputyStats->push([
                    'id' => $deputy->id,
                    'name' => $deputy->name,
                    'counts' => [
                        'whatsapp' => $deputySourceCounts['whatsapp'] ?? 0,
                        'tatap muka' => $deputySourceCounts['tatap muka'] ?? 0,
                        'surat fisik' => $deputySourceCounts['surat fisik'] ?? 0,
                    ]
                ]);

                // --- Agregasi Pie Chart Per Deputi (Data Source dan Status) ---
                $statusSourceCountsRaw = $deputyQueryBase->clone()
                    ->whereIn(DB::raw('TRIM(reports.status)'), $statusToTrackCleaned) 
                    ->select(DB::raw('TRIM(reports.status) as status_raw'), 'reports.source', DB::raw('count(*) as total'))
                    ->groupBy('status_raw', 'reports.source')
                    ->get();

                $statusSourceAggregated = $statusSourceCountsRaw
                    ->groupBy('status_raw')
                    ->map(function ($group) {
                        return $group->pluck('total', 'source');
                    })
                    ->toArray();

                $seriesData = [];
                $labelsData = [];
                $sourceDetails = [];

                foreach ($statusMappingCleaned as $statusCodeCleaned => $mappedStatus) {
                    $currentSourceData = $statusSourceAggregated[$statusCodeCleaned] ?? [];
                    $totalCount = array_sum($currentSourceData); 
                    
                    if ($totalCount > 0) {
                        $seriesData[] = $totalCount;
                        $labelsData[] = $mappedStatus;
                        
                        $sourceDetailEntry = [];
                        foreach ($sourceKeysDB as $source) {
                            $sourceDetailEntry[$source] = $currentSourceData[$source] ?? 0;
                        }
                        $sourceDetails[$mappedStatus] = $sourceDetailEntry;
                    }
                }
                $deputyPieChartData["deputi_{$deputy->id}"] = [
                    'title' => $deputy->name,
                    'labels' => $labelsData,
                    'series' => $seriesData,
                    'source_details' => $sourceDetails,
                ];
            }
        }

        return view('pages.dashboard', [
            'reportStats' => $reportStats, 
            'chartDataJson' => json_encode($chartData),
            'currentRange' => $range['label'],
            'currentRangeKey' => $range['key'],
            'deputyStats' => $deputyStats,
            'deputyPieChartDataJson' => json_encode($deputyPieChartData),
            'userDeputyPieChartDataJson' => json_encode($userDeputyPieChartData),
            'totalStatusPieDataJson' => json_encode($totalComplaintStatusPieData),
            'categoryStats' => $categoryStats,
            'currentSubjectRangeLabel' => $range['label'],
            'mapDataJson' => $mapDataJson,
            'mapNamesJson' => $mapNamesJson,
            'undisposedCount' => $reportStats['undisposed_count'],
            'disposedCount' => $reportStats['disposed_count'],
            'submittedReports' => $submittedReports,
            'pendingAssignments' => $pendingAssignments,
            'analystMonitoringData' => $analystMonitoringData,
        ]);
    }
    
    /**
     * Helper: Memformat data agregasi menjadi struktur yang dibutuhkan ApexCharts (Multi-series)
     */
    private function prepareChartData($trendData, $range)
    {
        $sources = ['whatsapp', 'tatap muka', 'surat fisik'];
        $seriesData = [];
        $dataMap = [];
        
        foreach ($trendData as $item) {
            $dateKey = Carbon::parse($item->date)->format('Y-m-d');
            $dataMap[$dateKey][$item->source] = (int)$item->count;
        }

        foreach ($sources as $source) {
            $data = [];
            $currentDate = $range['startDate']->copy();
            
            while ($currentDate->lte($range['endDate'])) {
                $dateKey = $currentDate->format('Y-m-d');
                $data[] = $dataMap[$dateKey][$source] ?? 0;
                $currentDate->addDay();
            }
            
            $seriesData[] = [
                'name' => ucwords(str_replace('_', ' ', $source)),
                'data' => $data,
                'color' => $this->getChartColor($source)
            ];
        }

        $chartDates = [];
        $currentDate = $range['startDate']->copy();
        while ($currentDate->lte($range['endDate'])) {
            $chartDates[] = $currentDate->format('d/M'); 
            $currentDate->addDay();
        }

        return [
            'labels' => $chartDates,
            'series' => $seriesData,
        ];
    }
    
    private function getChartColor(string $source): string
    {
        return match ($source) {
            'whatsapp' => '#20c997', // green
            'tatap muka' => '#4676fe', // primary
            'surat fisik' => '#ffc330', // yellow
            default => '#8898a9', // muted
        };
    }

    /**
     * Helper: Mengagregasi jumlah laporan berdasarkan Map ID (NIK 6 digit awal).
     */
    private function loadMapStatistics(Builder $query, User $user)
    {
        $mappings = \App\Models\ProvinceMapId::pluck('map_id', 'bps_code')->toArray();
        
        if (empty($mappings)) {
            return [];
        }

        $rawResults = $query->clone()
            ->join('reporters', 'reports.reporter_id', '=', 'reporters.id')
            ->whereRaw('LENGTH(reporters.nik) >= 2')
            ->select(DB::raw("SUBSTRING(reporters.nik, 1, 2) AS nik_prefix"), DB::raw('COUNT(*) as total'))
            ->groupBy('nik_prefix')
            ->get();
        
        $mapData = [];

        foreach ($rawResults as $result) {
            $nikPrefix = $result->nik_prefix;
            $total = (int)$result->total;
            
            if (isset($mappings[$nikPrefix])) {
                $mapId = $mappings[$nikPrefix];
                
                $mapIdString = (string)$mapId;
                
                if (isset($mapData[$mapIdString])) {
                    $mapData[$mapIdString] += $total;
                } else {
                    $mapData[$mapIdString] = $total;
                }
            }
        }

        return $mapData;
    }

    /**
     * Memuat statistik laporan yang menunggu approval/validasi Deputi.
     */
    private function loadPendingApprovalCount(User $user, string $filterKey = 'total'): int
    {
        // 1. Cek Role
        if (!$user->hasAnyRole(['deputy', 'asdep_karo'])) {
            return 0;
        }

        $query = Report::query();

        // 2. Filter Waktu
        if ($filterKey !== 'total') {
            $range = $this->getTimeRange($filterKey);
            $query->whereBetween('reports.created_at', [$range['startDate'], $range['endDate']]);
        }
        
        // 3. Filter Scope berdasarkan Reports table (Laporan yang menjadi tanggung jawab Deputi/Asdep)
        if ($user->hasRole('deputy') && $user->deputy_id) {
            $query->where('reports.deputy_id', $user->deputy_id);
            
        } elseif ($user->hasRole('asdep_karo') && $user->unit_kerja_id) {
            $query->where('reports.unit_kerja_id', $user->unit_kerja_id);
        } else {
            return 0; // Tidak ada ID Deputi/Unit Kerja, tidak ada yang perlu di-approve
        }
        
        // 4. Filter Assignments (HANYA Laporan yang sudah selesai dikerjakan Analis)
        $query->whereHas('assignments', function (Builder $q) {
            $q->where('status', 'submitted');
        });

        // 5. Filter Laporan yang BELUM di-approve/diproses lebih lanjut (optional, tapi disarankan)
        $query->where('reports.status', '!=', 'Selesai Tuntas'); 
        
        // Karena Anda tidak ingin menggunakan filter status, kita gunakan kueri minimal:
        return $query->count();
    }

    /**
     * Helper: Memuat data pie chart status laporan untuk Deputi atau Unit Kerja yang sedang login.
     */
    private function loadDeputyStatusPieChartData(User $user, string $filterKey)
    {
        // 1. Dapatkan base query yang terfilter waktu
        $baseQuery = Report::query();
        if ($filterKey !== 'total') {
            $range = $this->getTimeRange($filterKey);
            $baseQuery->whereBetween('reports.created_at', [$range['startDate'], $range['endDate']]);
        }
        
        $targetQuery = $baseQuery->clone();
        
        // 2. Filter Laporan sesuai Deputi/Unit Kerja User yang sedang login
        if ($user->hasRole('deputy') && $user->deputy_id) {
            // Scope DEPUTI: Laporan yang didisposisikan ke Deputi ini
            $targetQuery->where('reports.deputy_id', $user->deputy_id);
        } elseif ($user->hasAnyRole(['asdep_karo']) && $user->unit_kerja_id) {
            // Scope ASDEP/KARO: Laporan yang didisposisikan ke Unit Kerja ini
            $targetQuery->where('reports.unit_kerja_id', $user->unit_kerja_id);
        } else {
            return []; // Tidak ada scope, kembalikan kosong
        }
        
        $statusMapping = [
            'Proses verifikasi dan telaah' => 'Verifikasi & Telaah',
            'Menunggu kelengkapan data dukung dari Pelapor' => 'Menunggu Data Dukung',
            'Diteruskan kepada instansi yang berwenang untuk penanganan lebih lanjut' => 'Diteruskan ke Instansi',
            'Penanganan Selesai' => 'Penanganan Selesai',
        ];
        $statusToTrackCleaned = array_map('trim', array_keys($statusMapping));
        $statusMappingCleaned = array_combine($statusToTrackCleaned, $statusMapping);
        $sourceKeysDB = ['whatsapp', 'tatap muka', 'surat fisik'];

        // 3. Ambil data agregat STATUS + SUMBER
        $statusSourceRaw = $targetQuery->clone()
            ->whereIn(DB::raw('TRIM(reports.status)'), $statusToTrackCleaned)
            ->select(DB::raw('TRIM(reports.status) as status_raw'), 'reports.source', DB::raw('COUNT(*) as total'))
            ->groupBy('status_raw', 'reports.source')
            ->get();

        // 4. Transformasi data untuk Pie Chart
        $labels = [];
        $series = [];
        $sourceDetails = [];

        foreach ($statusMappingCleaned as $statusCodeCleaned => $mappedStatus) {
            $currentSourceData = $statusSourceRaw->where('status_raw', $statusCodeCleaned);
            $totalCount = $currentSourceData->sum('total'); 
            
            if ($totalCount > 0) {
                $labels[] = $mappedStatus;
                $series[] = $totalCount;

                $sourceDetailEntry = [];
                foreach ($sourceKeysDB as $source) {
                    $sourceDetailEntry[$source] = $currentSourceData
                        ->where('source', $source)
                        ->sum('total');
                }
                $sourceDetails[$mappedStatus] = $sourceDetailEntry;
            }
        }
        
        // Catatan: Nama Deputi/Unit Kerja tidak diikutkan di sini, karena chart ini hanya untuk 1 unit.
        return [
            'labels' => $labels,
            'series' => $series,
            'source_details' => $sourceDetails,
        ];
    }

    /**
     * Helper: Memuat daftar Analis di bawah cakupan user (Deputy/Asdep)
     * dan menghitung laporan yang ditugaskan kepada mereka per reports.status.
     */
    protected function loadAnalystMonitoring(\App\Models\User $user, string $filterKey)
    {
        // 1. Tentukan Scope Analis yang akan dimonitor
        $analystQuery = \App\Models\User::role('analyst')->with('unitKerja');
        $isDeputy = $user->hasRole('deputy') && $user->deputy_id;
        $isAsdep = $user->hasRole('asdep_karo') && $user->unit_kerja_id;
        
        if ($isDeputy) {
            $analystQuery->whereHas('unitKerja', function($q) use ($user) {
                $q->where('deputy_id', $user->deputy_id);
            });
        } elseif ($isAsdep) {
            $analystQuery->where('unit_kerja_id', $user->unit_kerja_id);
        } elseif (!($user->hasRole('superadmin') || $user->hasRole('admin'))) {
            return [];
        }

        $analysts = $analystQuery->orderBy('unit_kerja_id')->orderBy('name')->get();
        $monitoredData = [];
        
        // Status yang ingin dilacak (Berasal dari kolom reports.status)
        $statusToTrack = [
            'Proses verifikasi dan telaah', 
            'Menunggu kelengkapan data dukung dari Pelapor', 
            'Diteruskan kepada instansi yang berwenang untuk penanganan lebih lanjut',
            'Penanganan Selesai',
        ];

        // 2. Tentukan Filter Waktu
        $range = $this->getTimeRange($filterKey);
        $startDate = $range['startDate'];
        $endDate = $range['endDate'];

        // 3. Loop Analis dan Agregasi Data
        foreach ($analysts as $analyst) {
            
            // Step A: Dapatkan report_id yang ditugaskan ke analis ini
            $assignedReportIds = \App\Models\Assignment::where('assigned_to_id', $analyst->id)
                                                       ->pluck('report_id');
            
            if ($assignedReportIds->isEmpty()) continue;

            // Step B: Query Reports berdasarkan ID yang ditugaskan DAN filter WAKTU
            $reportsAssignedQuery = \App\Models\Report::query()
                ->whereIn('id', $assignedReportIds);
            
            // Apply Filter Waktu
            if ($filterKey !== 'total') {
                 $reportsAssignedQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            
            $totalAssigned = $reportsAssignedQuery->count();
            
            if ($totalAssigned > 0) {
                 
                 // Step C: Agregasi Status berdasarkan reports.status
                 $statusCounts = $reportsAssignedQuery->select('status', DB::raw('count(*) as total'))
                                                     ->whereIn('status', $statusToTrack)
                                                     ->groupBy('status')
                                                     ->pluck('total', 'status')
                                                     ->toArray();

                 // Format data per Analis
                 $unitName = $analyst->unitKerja->name ?? 'TANPA UNIT';
                 
                 $monitoredData[$unitName][] = [
                     'id' => $analyst->id,
                     'name' => $analyst->name,
                     'total' => $totalAssigned,
                     'statuses' => $statusCounts 
                 ];
            }
        }
        
        return $monitoredData;
    }
}