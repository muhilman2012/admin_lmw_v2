<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Pages\DashboardController;
use App\Http\Controllers\Pages\UsersController;
use App\Http\Controllers\Pages\ProfileController;
use App\Http\Controllers\Pages\ReportersController;
use App\Http\Controllers\Pages\ReportsController;
use App\Http\Controllers\Pages\CategoriesController;
use App\Http\Controllers\Pages\DeputiesController;
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
        Route::get('/create/from-reporter/{reporter_id}', [ReportsController::class, 'create'])->name('create.from_reporter');
        Route::get('/create', [ReportsController::class, 'create'])->name('create');
        Route::post('/store', [ReportsController::class, 'store'])->name('store');
        Route::post('/attachments', [ReportsController::class, 'storeAttachment'])->name('attachments.store');
    });

    // --- Pengelolaan Kategori ---
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoriesController::class, 'index'])->name('index');
        Route::post('/', [CategoriesController::class, 'store'])->name('store');
        Route::put('/{category}', [CategoriesController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoriesController::class, 'destroy'])->name('destroy');
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