<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\UnitKerja;
use App\Models\Deputy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function index()
    {
        // Muat hanya kategori utama, eager load sub-kategori, dan relasi unitKerjas
        $categories = Category::mainCategories()
            ->with(['children', 'unitKerjas'])
            ->orderBy('name')
            ->get();

        // Ambil semua kategori untuk modal dropdown
        $allCategories = Category::all(['id', 'name']);

        // Ambil semua Deputi untuk filter dropdown di tab Penugasan
        $deputies = Deputy::orderBy('name')->get(); 

        // Variabel kosong untuk mencegah error Undefined saat view dimuat pertama kali
        $units = collect([]); 
        $unitsByDeputy = collect([]);

        // Ambil semua Deputi dan Unit Kerja dibawahnya
        $groupedUnits = $this->getGroupedUnits();

        // Cek session untuk ID Deputi yang aktif terakhir
        $lastActiveDeputyId = session('active_deputy_id'); 
        
        // Mengirimkan variabel yang dibutuhkan oleh view
        return view('pages.settings.index', compact('groupedUnits', 'categories', 'allCategories', 'deputies', 'units', 'unitsByDeputy'))
            ->with('lastActiveDeputyId', $lastActiveDeputyId);
    }

    /**
     * Tampilkan form untuk membuat kategori baru.
     */
    public function create()
    {
        $mainCategories = Category::mainCategories()->orderBy('name')->get();
        $allCategories = Category::all(['id', 'name']);
        return view('pages.settings.create', compact('mainCategories', 'allCategories'));
    }

    /**
     * Simpan kategori baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'parent_id' => 'nullable|exists:categories,id',
            'active_tab_hash' => 'nullable|string',
        ]);

        Category::create($validated);
        $redirectTarget = $request->input('active_tab_hash') ?? '#tab-assignments';

        return redirect()->route('settings.index')
            ->with('success', 'Kategori/Sub-Kategori berhasil ditambahkan.');
    }

    /**
     * Hapus kategori dari database.
     */
    public function destroy(Category $category)
    {
        // Perhatian: Sebelum menghapus kategori, Anda harus memindahkan
        // laporan yang terkait ke kategori lain atau menghapusnya (sesuai logika bisnis Anda).
        // Untuk contoh ini, kita hanya akan membatasi penghapusan jika ada sub-kategori terkait.

        if ($category->children()->exists()) {
            return redirect()->route('settings.index')
                ->with('error', 'Gagal menghapus. Kategori ini memiliki Sub-Kategori.');
        }

        // Opsional: Cek apakah kategori ini digunakan di model Report sebelum menghapus

        $category->delete();

        return redirect()->route('settings.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * Toggle status is_active kategori melalui AJAX.
     */
    public function toggleCategoryActive(Request $request, Category $category)
    {
        // Pastikan permintaan adalah AJAX dan memiliki permission (opsional)
        if (!$request->ajax()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        // Toggle status
        $category->is_active = !$category->is_active;
        $category->save();

        // Tentukan status teks untuk feedback
        $statusText = $category->is_active ? 'Aktif' : 'Non-Aktif';

        return response()->json([
            'success' => true,
            'is_active' => $category->is_active,
            'message' => "Kategori '{$category->name}' berhasil diubah menjadi {$statusText}."
        ]);
    }

    public function assignUnits(Request $request)
    {
        // dd($request->all());

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'unit_assignments' => 'nullable|array', 
            'unit_assignments.*' => 'exists:unit_kerjas,id',
            'active_deputy_id' => 'nullable|exists:deputies,id', 
        ]);
        
        // dd($validated);

        $category = Category::findOrFail($validated['category_id']);
        $assignedUnitIds = array_values($validated['unit_assignments'] ?? []); 

        // dd($assignedUnitIds);

        $category->unitKerjas()->sync($assignedUnitIds);

        if (!empty($validated['active_deputy_id'])) {
        session()->flash('active_deputy_id', $validated['active_deputy_id']);
        }

        // Arahkan ke tab assignments
        return redirect()->to(route('settings.index') . '#tab-assignments')
            ->with('success', 'Penugasan Unit Kerja berhasil diperbarui.');
    }

    public function getAssignmentsByDeputy(Request $request)
    {
        $deputyId = $request->input('deputy_id');

        if (!$deputyId) {
            return response()->json([
                'html' => '<tr><td colspan="100" class="text-center text-muted">Silakan pilih Deputi terlebih dahulu.</td></tr>'
            ]);
        }
        
        // Ambil Unit Kerja berdasarkan Deputi yang dipilih
        $units = UnitKerja::where('deputy_id', $deputyId)
                          ->orderBy('name')
                          ->get();

        // Ambil semua Kategori Utama dengan relasi unitKerjas
        $categories = Category::mainCategories()
            ->with('unitKerjas')
            ->orderBy('name')
            ->get();
        
        // Render view fragment untuk tabel penugasan
        $html = view('pages.settings.partials.assignment_matrix', compact('units', 'categories'))->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Helper: Mengelompokkan Unit Kerja di bawah Deputi.
     */
    private function getGroupedUnits()
    {
        $deputies = Deputy::with('unitKerjas')
            ->orderBy('id', 'asc')
            ->get();
        $grouped = [];

        foreach ($deputies as $deputy) {
            $grouped[$deputy->name] = [
                'id' => $deputy->id,
                'units' => $deputy->unitKerjas->sortBy('name'),
            ];
        }
        
        return $grouped;
    }

    /**
     * Method untuk menampilkan data Deputi dan Unit Kerja ke view.
     */
    public function editMatrix()
    {
        // Eager load UnitKerja dengan relasi Deputi
        $deputies = Deputy::with('unitKerjas')->orderBy('name')->get();
        $units = UnitKerja::with('deputy')->orderBy('name')->get(); // Daftar semua unit

        // Data ini akan dikirim ke view untuk mengisi dropdown/tabel.
        return view('pages.settings.matrix', compact('deputies', 'units'));
    }

    /**
     * Method untuk menyimpan penugasan Deputi untuk setiap Unit Kerja.
     */
    public function updateMatrix(Request $request)
    {
        // 1. Validasi
        $validatedData = $request->validate([
            // Memastikan input adalah array mapping unit_id => deputy_id
            'unit_deputy_map' => 'required|array',
            'unit_deputy_map.*' => 'required|exists:deputies,id',
            'active_tab_hash' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $count = 0;
            
            // 2. Iterasi dan Update
            foreach ($validatedData['unit_deputy_map'] as $unitId => $deputyId) {
                $unit = UnitKerja::find($unitId);
                if ($unit && $unit->deputy_id != $deputyId) {
                    $unit->deputy_id = $deputyId;
                    $unit->save();
                    $count++;
                }
            }
            
            DB::commit();
            $redirectTarget = $request->input('active_tab_hash') ?? '#tab-matrix';

            return redirect()
                ->to(route('settings.index') . $redirectTarget)
                ->with('success', "Berhasil memperbarui {$count} penugasan Unit Kerja ke Kedeputian.");
                
        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan Matrix Distribusi: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyimpan Matrix Distribusi karena kesalahan sistem.');
        }
    }

    /**
     * Method untuk menjalankan command maintenance dari interface web.
     * Termasuk logika untuk menjalankan migrate:v1 secara bertahap (single-step).
     */
    public function runSystemMaintenance(Request $request)
    {
        // === BATAS WAKTU EKSEKUSI DITINGKATKAN ===
        // Mengatur batas waktu eksekusi ke 120 detik (2 menit) untuk memberi ruang bagi migrasi.
        set_time_limit(120); 
        
        if (!Auth::user()->hasRole('superadmin')) {
            return Redirect::back()->with('error', 'Akses ditolak. Hanya Superadmin yang diizinkan.');
        }
        
        $command = $request->input('command');
        $output = '';
        $redirectHash = '#tab-maintenance';

        try {
            switch ($command) {
                case 'lapor:check-status':
                    Artisan::call('lapor:check-status', ['--force' => true]); 
                    $output = Artisan::output();
                    Session::flash('success', "Perintah cek status LAPOR! (Manual) berhasil dijalankan. Hasil: " . Str::limit($output, 100));
                    break;

                case 'lapor:retry-forwarding':
                    Artisan::call('lapor:retry-forwarding');
                    $output = Artisan::output();
                    Session::flash('success', "Perintah pengiriman ulang berhasil dipanggil. Cek log server untuk detail. Hasil: " . Str::limit($output, 100));
                    break;

                case 'migrate:v1':
                    $startPage = (int) $request->input('start_page', 1);
                    $limit = (int) $request->input('limit', 500);
                    Artisan::call('migrate:v1', [
                        '--start-page' => $startPage,
                        '--limit' => $limit,
                    ]); 
                    $output = Artisan::output();
                    
                    // Cari hasil dari output CLI
                    $regex = '/Halaman (\d+)\/(\d+) berhasil diproses\. Total records: (\d+)/s'; 
                    preg_match($regex, $output, $matches);
                    
                    if (isset($matches[1])) {
                        $processedPage = (int) $matches[1];
                        $lastPage = (int) $matches[2];
                        $totalRecords = (int) $matches[3];

                        $message = "Migrasi Halaman {$processedPage}/{$lastPage} berhasil diproses ({$totalRecords} records).";
                        Session::flash('success', $message);
                    } else {
                        Session::flash('success', "Migrasi tahap ini selesai. Cek log server.");
                    }
                    
                    break;

                case 'sync:institutions':
                    Artisan::call('sync:institutions');
                    $output = Artisan::output();
                    Session::flash('success', "Command sync:institutions berhasil dijalankan. Hasil: " . Str::limit($output, 100));
                    break;

                case 'migrate:documents':
                    Artisan::call('migrate:documents', ['--force' => true]);
                    $output = Artisan::output();
                    Session::flash('success', "Command migrate:documents berhasil dijalankan. Hasil: " . Str::limit($output, 100));
                    break;
                    
                default:
                    Session::flash('error', "Command tidak valid.");
                    break;
            }
        } catch (\Exception $e) {
            Log::error("Gagal menjalankan Artisan Command ({$command}): " . $e->getMessage());
            Session::flash('error', "Gagal sistem saat menjalankan command: " . Str::limit($e->getMessage(), 100));
        }

        // Redirect kembali ke tab maintenance
        return Redirect::back()->withInput(['command' => $command, 'active_tab_hash' => $redirectHash]);
    }

    /**
     * Helper untuk mengambil konfigurasi API Gemini dari database.
     */
    protected function getGeminiConfig()
    {
        // Menggunakan Cache agar tidak query DB setiap kali check
        return Cache::remember('gemini_api_config', 300, function () {
            // Ambil semua key/value dari tabel api_settings yang name-nya 'gemini_api'
            $settings = DB::table('api_settings')
                            ->where('name', 'gemini_api')
                            ->pluck('value', 'key');
            
            return [
                'endpoint' => $settings->get('endpoint'),
                'model' => $settings->get('model'),
                'api_key' => $settings->get('api_key_primary'),
            ];
        });
    }

    /**
     * Melakukan health check ke Gemini API dan mengembalikan statusnya.
     */
    public function getGeminiStatus()
    {
        // Gunakan cache untuk menghindari ping API berlebihan (misalnya 5 menit)
        $statusData = Cache::remember('gemini_api_status', 300, function () {
            
            // --- PERBAIKAN: Ambil konfigurasi dari Database ---
            $config = $this->getGeminiConfig();
            
            $apiKey = $config['api_key'];
            $endpoint = $config['endpoint'];
            $model = $config['model'];
            
            // Siapkan respons default
            $response = [
                'status' => 'ERROR',
                'message' => 'Konfigurasi tidak lengkap (Endpoint/API Key/Model tidak ditemukan di DB).',
                'details' => null,
                'code' => 500,
            ];

            if (!$apiKey || !$endpoint || !$model) {
                return $response;
            }
            
            // Konstruksi URL (Pastikan endpoint memiliki format yang benar)
            $testUrl = "{$endpoint}/models/{$model}:generateContent?key={$apiKey}";
            
            try {
                // Melakukan ping sederhana (Test Connectivity)
                $httpResponse = Http::timeout(10)->post($testUrl, [
                    'contents' => [
                        ['parts' => [['text' => 'ping']]]
                    ]
                ]);

                $response['code'] = $httpResponse->status();

                if ($httpResponse->successful()) {
                     $response['status'] = 'UP';
                     $response['message'] = 'Koneksi API berhasil. Model merespons.';
                } else {
                     // Menangkap error 4xx (Unauthorized, Quota Exceeded) dan 5xx (Server Error)
                     $response['status'] = 'DOWN';
                     $response['message'] = 'Koneksi terjalin, namun API mengembalikan Error.';
                     $response['details'] = Str::limit($httpResponse->body(), 200);

                     if ($response['code'] == 400 || $response['code'] == 403) {
                         $response['message'] = 'Error Klien (Periksa API Key, Batas Kuota, atau Konfigurasi Endpoint).';
                     }
                }

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // Koneksi gagal total (DNS, jaringan, API mati)
                $response['status'] = 'DOWN';
                $response['message'] = 'Koneksi ke server Gemini gagal (Timeout/Jaringan).';
                $response['details'] = $e->getMessage();
            } catch (\Exception $e) {
                $response['status'] = 'ERROR';
                $response['message'] = 'Terjadi kesalahan internal saat mencoba ping.';
                $response['details'] = $e->getMessage();
            }
            
            $response['timestamp'] = now()->format('H:i:s');
            
            return $response;
        });

        return response()->json($statusData);
    }

    public function retryLaporForwarding()
    {
        try {
            // Panggil Artisan Command
            $result = Artisan::call('lapor:retry-forwarding');

            // Cek hasil command (biasanya 0 = sukses)
            if ($result === 0) {
                $output = Artisan::output(); // Ambil output dari command
                
                // Tambahkan pesan yang lebih spesifik berdasarkan output command (opsional)
                $successMessage = 'Perintah Retry Lapor Forwarding berhasil dijalankan.';
                
                return redirect()->back()->with('success', $successMessage . ' Output: ' . trim($output));
            } else {
                return redirect()->back()->with('error', 'Perintah gagal dijalankan. Cek log server.');
            }
        } catch (\Exception $e) {
            \Log::error('Error executing Lapor Retry Command via Web: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat menjalankan perintah.');
        }
    }
}
