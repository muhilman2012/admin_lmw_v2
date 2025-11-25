<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\UnitKerja;
use App\Models\Deputy;
use App\Models\StatusTemplate;
use App\Models\DocumentTemplate;
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
    // METHOD UTAMA: Menggabungkan index dan manageTemplates
    public function index()
    {
        // 1. Muat data Kategori (untuk Tab 1 dan Matriks)
        $categories = Category::mainCategories()
            ->with(['children', 'unitKerjas'])
            ->orderBy('name')
            ->get();

        $allCategories = Category::all(['id', 'name']); // Untuk dropdown modal create category
        $deputies = Deputy::orderBy('name')->get(); 
        
        // 2. Muat data Matrix Organisasi (untuk Tab Matriks dan Assignment)
        $groupedUnits = $this->getGroupedUnits();
        $lastActiveDeputyId = session('active_deputy_id'); 
        
        // 3. Muat data TEMPLATES (untuk Tab baru)
        $statusTemplates = StatusTemplate::orderBy('name')->get();
        $documentTemplates = DocumentTemplate::orderBy('name')->get();
        
        // Mengirimkan semua variabel yang dibutuhkan oleh SEMUA TAB
        return view('pages.settings.index', compact(
            'groupedUnits', 
            'categories', 
            'allCategories', 
            'deputies',
            'statusTemplates',
            'documentTemplates'
        ))
        ->with('lastActiveDeputyId', $lastActiveDeputyId);
    }
    
    // 🔥 METHOD manageTemplates LAMA DIHAPUS (diganti index())

    /**
     * Tampilkan form untuk membuat kategori baru. (Tidak digunakan di View)
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
        $redirectTarget = $request->input('active_tab_hash') ?? '#tab-categories';

        return redirect()->to(route('settings.index') . $redirectTarget)
            ->with('success', 'Kategori/Sub-Kategori berhasil ditambahkan.');
    }

    /**
     * Hapus kategori dari database.
     */
    public function destroy(Category $category)
    {
        if ($category->children()->exists()) {
            return redirect()->route('settings.index')
                ->with('error', 'Gagal menghapus. Kategori ini memiliki Sub-Kategori.');
        }

        $category->delete();

        return redirect()->route('settings.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * Toggle status is_active kategori melalui AJAX.
     */
    public function toggleCategoryActive(Request $request, Category $category)
    {
        if (!$request->ajax()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $category->is_active = !$category->is_active;
        $category->save();

        $statusText = $category->is_active ? 'Aktif' : 'Non-Aktif';

        return response()->json([
            'success' => true,
            'is_active' => $category->is_active,
            'message' => "Kategori '{$category->name}' berhasil diubah menjadi {$statusText}."
        ]);
    }
    
    /**
     * Simpan/Update Status Template (Digunakan oleh modal CRUD)
     */
    public function storeStatusTemplate(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:status_templates,id',
            'name' => 'required|string|max:255',
            'status_code' => ['required', 'string', 'max:50', Rule::unique('status_templates')->ignore($request->id)], 
            'response_template' => 'required|string',
            'active_tab_hash' => 'nullable|string',
        ]);
        
        StatusTemplate::updateOrCreate(['id' => $validated['id']], $validated);
        
        $message = $request->id ? 'Status Template berhasil diperbarui.' : 'Status Template berhasil ditambahkan.';
        $redirectTarget = $request->input('active_tab_hash') ?? '#tab-templates';

        return redirect()->to(route('settings.index') . $redirectTarget)->with('success', $message);
    }
    
    /**
     * Simpan/Update Document Template (Digunakan oleh modal CRUD)
     */
    public function storeDocumentTemplate(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:document_templates,id',
            'name' => ['required', 'string', 'max:255', Rule::unique('document_templates')->ignore($request->id)],
            'active_tab_hash' => 'nullable|string',
        ]);
        
        DocumentTemplate::updateOrCreate(['id' => $validated['id']], $validated);
        
        $message = $request->id ? 'Document Template berhasil diperbarui.' : 'Document Template berhasil ditambahkan.';
        $redirectTarget = $request->input('active_tab_hash') ?? '#tab-templates';

        return redirect()->to(route('settings.index') . $redirectTarget)->with('success', $message);
    }

    /**
     * Hapus Status/Document Template
     */
    public function destroyTemplate(Request $request, $id)
    {
        $validated = $request->validate([
            'type' => 'required|in:status,document',
            'active_tab_hash' => 'nullable|string',
        ]);
        
        $redirectTarget = $request->input('active_tab_hash') ?? '#tab-templates';
        $message = 'Template berhasil dihapus.';

        if ($validated['type'] === 'status') {
            StatusTemplate::findOrFail($id)->delete();
        } else {
            DocumentTemplate::findOrFail($id)->delete();
        }

        return redirect()->to(route('settings.index') . $redirectTarget)->with('success', $message);
    }


    public function assignUnits(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'unit_assignments' => 'nullable|array', 
            'unit_assignments.*' => 'exists:unit_kerjas,id',
            'active_deputy_id' => 'nullable|exists:deputies,id', 
        ]);
        
        $category = Category::findOrFail($validated['category_id']);
        $assignedUnitIds = array_values($validated['unit_assignments'] ?? []); 

        $category->unitKerjas()->sync($assignedUnitIds);

        if (!empty($validated['active_deputy_id'])) {
            session()->flash('active_deputy_id', $validated['active_deputy_id']);
        }

        return redirect()->to(route('settings.index') . '#tab-assignments')
            ->with('success', 'Penugasan Unit Kerja berhasil diperbarui.');
    }

    public function getAssignmentsByDeputy(Request $request)
    {
        $deputyId = $request->input('deputy_id');

        if (!$deputyId) {
            return response()->json([
                'html' => '<p class="text-center text-muted m-0 p-4">Silakan pilih Deputi terlebih dahulu.</p>'
            ]);
        }
        
        $units = UnitKerja::where('deputy_id', $deputyId)
                          ->orderBy('name')
                          ->get();

        $categories = Category::mainCategories()
            ->with('unitKerjas')
            ->orderBy('name')
            ->get();
        
        $html = view('pages.settings.partials.assignment_matrix', compact('units', 'categories'))->render();

        return response()->json(['html' => $html]);
    }

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

    public function editMatrix()
    {
        $deputies = Deputy::with('unitKerjas')->orderBy('name')->get();
        $units = UnitKerja::with('deputy')->orderBy('name')->get(); 

        return view('pages.settings.matrix', compact('deputies', 'units'));
    }

    public function updateMatrix(Request $request)
    {
        $validatedData = $request->validate([
            'unit_deputy_map' => 'required|array',
            'unit_deputy_map.*' => 'required|exists:deputies,id',
            'active_tab_hash' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $count = 0;
            
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

    public function runSystemMaintenance(Request $request)
    {
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

                case 'migrate:logs':
                    $startPage = (int) $request->input('start_page', 1);
                    $limit = (int) $request->input('limit', 500);
                    
                    Artisan::call($command, [
                        '--start-page' => $startPage,
                        '--limit' => $limit,
                    ]); 
                    $output = Artisan::output();
                    
                    $regex = '/Halaman (\d+)\/(\d+) berhasil diproses\. Total records: (\d+)/s'; 
                    preg_match($regex, $output, $matches);
                    
                    if (isset($matches[1])) {
                        $processedPage = (int) $matches[1];
                        $lastPage = (int) $matches[2];
                        $totalRecords = (int) $matches[3];

                        $message = "Migrasi {$command} Halaman {$processedPage}/{$lastPage} berhasil diproses ({$totalRecords} records).";
                        Session::flash('success', $message);
                    } else {
                        Session::flash('success', "Migrasi {$command} tahap ini selesai. Cek log server.");
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

        return Redirect::back()->withInput(['command' => $command, 'active_tab_hash' => $redirectHash]);
    }

    protected function getGeminiConfig()
    {
        return Cache::remember('gemini_api_config', 300, function () {
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

    public function getGeminiStatus()
    {
        $statusData = Cache::remember('gemini_api_status', 300, function () {
            $config = $this->getGeminiConfig();
            
            $apiKey = $config['api_key'];
            $endpoint = $config['endpoint'];
            $model = $config['model'];
            
            $response = [
                'status' => 'ERROR',
                'message' => 'Konfigurasi tidak lengkap (Endpoint/API Key/Model tidak ditemukan di DB).',
                'details' => null,
                'code' => 500,
            ];

            if (!$apiKey || !$endpoint || !$model) {
                return $response;
            }
            
            $testUrl = "{$endpoint}/models/{$model}:generateContent?key={$apiKey}";
            
            try {
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
                    $response['status'] = 'DOWN';
                    $response['message'] = 'Koneksi terjalin, namun API mengembalikan Error.';
                    $response['details'] = Str::limit($httpResponse->body(), 200);

                    if ($response['code'] == 400 || $response['code'] == 403) {
                        $response['message'] = 'Error Klien (Periksa API Key, Batas Kuota, atau Konfigurasi Endpoint).';
                    }
                }

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
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
            $result = Artisan::call('lapor:retry-forwarding');

            if ($result === 0) {
                $output = Artisan::output();
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
