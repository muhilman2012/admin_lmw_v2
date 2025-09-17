<?php

use App\Http\Middleware\VerifyLmwApiToken;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SuperadminController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReporterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rute untuk API yang digunakan oleh bot WhatsApp dan aplikasi lain.
|
*/


Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', VerifyLmwApiToken::class])->group(function () {
    Route::post('/superadmin/regenerate-token', [SuperadminController::class, 'regenerateToken'])->name('api.superadmin.regenerate-token');
    Route::post('/documents', [DocumentController::class, 'upload']);
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/reporters/check-eligibility/{nik}', [ReporterController::class, 'checkEligibility']);
    Route::post('/reporters', [ReporterController::class, 'checkOrStore']);

    // API untuk alur verifikasi dua langkah
    Route::get('/reports/{ticketNumber}/check', [ReportController::class, 'checkIfReportExists']);
    Route::get('/reports/{ticketNumber}/verify', [ReportController::class, 'verifyReporter']);

    // API cek status yang disederhanakan (dipanggil setelah verifikasi berhasil)
    Route::get('/reports/{ticketNumber}/status', [ReportController::class, 'checkStatus']);

    // API untuk mengecek eligibilitas dokumen tambahan
    Route::get('/reports/{ticketNumber}/document-eligibility', [ReportController::class, 'checkDocumentEligibility']);
});