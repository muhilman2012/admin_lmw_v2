<?php

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
use App\Http\Controllers\Pages\CategoriesController;
use App\Http\Controllers\Pages\DeputiesController;
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

// Rute yang hanya bisa diakses setelah login
Route::middleware(['auth'])->prefix('admin')->group(function () {
    // Rute Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // Halaman Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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
        Route::get('reports/{uuid}/edit', [ReportsController::class, 'edit'])->name('edit');
        Route::patch('reports/{uuid}', [ReportsController::class, 'update'])->name('update');
        Route::post('/attachments', [ReportsController::class, 'storeAttachment'])->name('attachments.store');
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
        Route::put('/{complaintId}/reply', [ForwardingController::class, 'submitReply'])->name('reply');
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
        Route::get('/template', [ReportExportController::class, 'downloadTemplate'])->name('template');
    });

    Route::prefix('import')->name('import.')->group(function () {
        Route::post('/reports', [ReportImportController::class, 'importReports'])->name('reports');
    });

    // --- Pengelolaan Kategori ---
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoriesController::class, 'index'])->name('index');
        Route::post('/', [CategoriesController::class, 'store'])->name('store');
        Route::put('/{category}', [CategoriesController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoriesController::class, 'destroy'])->name('destroy');
        Route::post('/assign-units', [CategoriesController::class, 'assignUnits'])->name('assign.units');
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
});