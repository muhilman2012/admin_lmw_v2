<?php

use App\Http\Middleware\CheckPasswordReset; 
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Pages\DashboardController;
use App\Http\Controllers\Pages\UsersController;
use App\Http\Controllers\Pages\ProfileController;
use App\Http\Controllers\Pages\ReportersController;
use App\Http\Controllers\Pages\ReportsController;
use App\Http\Controllers\Pages\ForwardingController;
use App\Http\Controllers\Pages\SearchController;
use App\Http\Controllers\Pages\ReportExportController;
use App\Http\Controllers\Pages\ReportImportController;
use App\Http\Controllers\Pages\UserImportController;
use App\Http\Controllers\Pages\SettingsController;
use App\Http\Controllers\Pages\UnitKerjaController;
use App\Http\Controllers\Pages\DeputiesController;
use App\Http\Controllers\Pages\NotificationController;
use App\Http\Controllers\Pages\KmsController;
use App\Http\Controllers\ReceiptPdfController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Rute untuk antarmuka web (admin dashboard).
|
*/

// Rute Autentikasi
Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/', [LoginController::class, 'login']);
Route::get('/forgot-password', [LoginController::class, 'showForgotPasswordForm'])->name('password.forgot');
Route::post('/forgot-password', [LoginController::class, 'handleForgotPassword'])->name('password.reset.internal');

// Rute yang hanya bisa diakses setelah login
Route::middleware(['auth', CheckPasswordReset::class])->prefix('admin')->group(function () {
    // Rute Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // Halaman Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Notification
    Route::get('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');

    // --- Pengelolaan Pengguna dan Profil ---
    Route::prefix('users')->name('users.')->group(function () {
        // Rute untuk Profil Pengguna
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/update-password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
        Route::post('/profile/regenerate-token', [ProfileController::class, 'regenerateApiToken'])->name('profile.regenerate-token');
        Route::post('/profile/api/update', [ProfileController::class, 'updateApiSettings'])->name('profile.api.update');

        // Rute untuk Manajemen Pengguna
        Route::prefix('management')->name('management.')->group(function () {
            Route::get('/', [UsersController::class, 'index'])->name('index');
            Route::get('/create', [UsersController::class, 'create'])->name('create');
            Route::post('/', [UsersController::class, 'store'])->name('store');
            Route::get('/{user}/edit-api', [UsersController::class, 'editApi'])->name('edit.api');
            Route::get('/{user}/edit', [UsersController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UsersController::class, 'update'])->name('update');
            Route::delete('/{user}', [UsersController::class, 'destroy'])->name('destroy');
        });
    });

    // --- Pengelolaan Daftar Pengadu ---
    Route::prefix('reporters')->name('reporters.')->group(function () {
        Route::get('/', [ReportersController::class, 'index'])->name('index');
    });

    // --- Pengelolaan Laporan Pengaduan ---
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('index');
        Route::get('/{uuid}/detail', [ReportsController::class, 'show'])->name('show');
        Route::get('/create/from-reporter/{reporter_uuid}', [ReportsController::class, 'create'])->name('create.from_reporter');
        Route::get('/create', [ReportsController::class, 'create'])->name('create');
        Route::post('/store', [ReportsController::class, 'store'])->name('store');
        Route::get('/by-kk', [ReportsController::class, 'getReportsByKK'])->name('by.kk');
        Route::get('{uuid}/edit', [ReportsController::class, 'edit'])->name('edit');
        Route::patch('{uuid}', [ReportsController::class, 'update'])->name('update');
        Route::post('/attachments', [ReportsController::class, 'storeAttachment'])->name('attachments.store');
        Route::post('/{uuid}/assign-quick', [ReportsController::class, 'assignQuick'])->name('assign-quick');
        Route::post('/{uuid}/submit-analysis', [ReportsController::class, 'submitAnalysis'])->name('submit-analysis');
        Route::patch('/{uuid}/update-response', [ReportsController::class, 'updateResponse'])->name('update-response');
        Route::post('/{uuid}/approve', [ReportsController::class, 'approveAnalysis'])->name('approve');
        Route::post('/{uuid}/forward', [ReportsController::class, 'forwardToLapor'])->name('forward');
        Route::get('/{uuid}/download/user', [ReceiptPdfController::class, 'downloadReceiptUser'])->name('download.user');
        Route::get('/{uuid}/download/government', [ReceiptPdfController::class, 'downloadReceiptGovernment'])->name('download.government');
    });

    Route::prefix('forwarding')->name('forwarding.')->group(function () {
        Route::get('/', [ForwardingController::class, 'index'])->name('index');
        Route::get('/{uuid}/detail/{complaintId}', [ForwardingController::class, 'showDetail'])->name('detail');
        Route::post('/{complaintId}/followup/add', [ForwardingController::class, 'sendFollowUp'])->name('followup.add');
        Route::get('/masters/templates', [ForwardingController::class, 'getTemplates'])->name('templates'); 
    });

    Route::prefix('search')->name('search.')->group(function () {
        Route::get('/', [SearchController::class, 'index'])->name('index');
        Route::post('/run', [SearchController::class, 'runSearch'])->name('run');
    });

    Route::prefix('export')->name('export.')->group(function () {
        Route::get('/', [ReportExportController::class, 'index'])->name('index');
        Route::post('/excel', [ReportExportController::class, 'exportExcel'])->name('excel');
        Route::post('/pdf', [ReportExportController::class, 'exportPdf'])->name('pdf');
        Route::get('/status', [ReportExportController::class, 'checkStatus'])->name('status');
        Route::get('/download', [ReportExportController::class, 'download'])->name('download');
        Route::get('/template', [ReportImportController::class, 'downloadTemplate'])->name('template');
    });

    Route::prefix('import')->name('import.')->group(function () {
        Route::post('/reports', [ReportImportController::class, 'importReports'])->name('reports');
        Route::post('/preview', [ReportImportController::class, 'previewReports'])->name('preview');
        Route::post('/users', [UserImportController::class, 'store'])->name('users.store');
        Route::get('/users/template', [UserImportController::class, 'downloadTemplate'])->name('users.template');
    });

    // --- Settings Aplikasi ---
    Route::prefix('settings')->name('settings.')->group(function () {
        
        // 1. ROUTE UTAMA (settings.index)
        // 🔥 PERBAIKAN: Mengarahkan /settings ke method index() yang sudah digabungkan
        Route::get('/', [SettingsController::class, 'index'])->name('index'); 

        // 2. KATEGORI (CREATE, STORE, DELETE, TOGGLE)
        // Catatan: Route::put('/{category}', [SettingsController::class, 'update'])->name('update'); masih hilang, tambahkan jika Anda memiliki method update kategori
        Route::post('/', [SettingsController::class, 'store'])->name('store');
        Route::delete('/{category}', [SettingsController::class, 'destroy'])->name('destroy');
        Route::patch('/{category}/toggle-active', [SettingsController::class, 'toggleCategoryActive'])->name('toggle-active');

        // 3. PENUGASAN/MATRIKS
        Route::post('/assign-units', [SettingsController::class, 'assignUnits'])->name('assign.units');
        Route::get('/assignments-by-deputy', [SettingsController::class, 'getAssignmentsByDeputy'])->name('assignments.by.deputy');
        Route::put('/matrix/update', [SettingsController::class, 'updateMatrix'])->name('matrix.update');

        // 4. MAINTENANCE (COMMANDS)
        Route::post('/run-maintenance', [SettingsController::class, 'runSystemMaintenance'])->name('run-maintenance');
        Route::post('/retry-lapor', [SettingsController::class, 'retryLaporForwarding'])->name('retry.lapor'); // Catatan: Route ini sudah diubah ke POST di Controller
        Route::get('/gemini-status', [SettingsController::class, 'getGeminiStatus'])->name('gemini.status');

        // 5. 🔥 TEMPLATES (CRUD)
        Route::post('/templates/status', [SettingsController::class, 'storeStatusTemplate'])->name('templates.status.store');
        Route::post('/templates/document', [SettingsController::class, 'storeDocumentTemplate'])->name('templates.document.store');
        Route::delete('/templates/destroy/{id}', [SettingsController::class, 'destroyTemplate'])->name('templates.destroy');

        // 6. UNIT KERJA (Prefix/Nested Grouping)
        Route::prefix('unit-kerjas')->name('unitkerjas.')->group(function () {
            Route::post('/', [UnitKerjaController::class, 'store'])->name('store');
            Route::put('/{unitKerja}', [UnitKerjaController::class, 'update'])->name('update');
            Route::delete('/{unitKerja}', [UnitKerjaController::class, 'destroy'])->name('destroy');
        });
    });

    // --- Pengelolaan Struktur Organisasi ---
    Route::prefix('deputies')->name('deputies.')->group(function () {
        Route::get('/', [DeputiesController::class, 'index'])->name('index');
        Route::post('/', [DeputiesController::class, 'store'])->name('store');
        Route::put('/{deputy}', [DeputiesController::class, 'update'])->name('update');
    });

    // --- Pengelompokan Rute Berdasarkan Peran (Spatie) ---
    Route::middleware('role:superadmin')->group(function () {
        Route::get('/logs/activity', [\App\Http\Controllers\Pages\ActivityLogController::class, 'index'])->name('logs.activity');
    });
    
    Route::middleware('role:asdep_karo|deputy')->group(function () {
        Route::post('/reports/{report}/assign', [ReportsController::class, 'assign'])->name('reports.assign');
    });

    Route::prefix('kms')->name('kms.')->group(function () {
        Route::middleware('role:superadmin|admin')->group(function () {
            Route::get('/create', [KmsController::class, 'create'])->name('create');
            Route::post('/', [KmsController::class, 'store'])->name('store');
            Route::get('/{article}/edit', [KmsController::class, 'edit'])->name('edit');
            Route::put('/{article}', [KmsController::class, 'update'])->name('update');
            Route::delete('/{article}', [KmsController::class, 'destroy'])->name('destroy');
        });
        
        Route::get('/', [KmsController::class, 'index'])->name('index'); 
        Route::get('/{article}', [KmsController::class, 'show'])->name('show');
    });
});

Route::get('/changelog/v2', function () {
    return view('changelogs.v2'); 
})->name('changelog.v2');