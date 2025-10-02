<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Imports\ReportsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ReportImportController extends Controller
{
    protected array $middleware = ['auth'];

    /**
     * Menangani unggahan file Excel dan dispatch job import.
     */
    public function importReports(Request $request)
    {
        // 1. Validasi file
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ], [
            'file.required' => 'Pilih file Excel migrasi.',
            'file.mimes' => 'Format file harus XLSX, XLS, atau CSV.',
        ]);

        try {
            // Dapatkan user ID yang melakukan import (untuk logging di job)
            $userId = Auth::id();

            // 2. Dispatch job ke queue
            // Gunakan Excel::queue untuk memastikan import berjalan di background
            Excel::queue(new ReportsImport($userId), $request->file('file'));

            Log::info('Migrasi data laporan dimulai.', ['user_id' => $userId, 'filename' => $request->file('file')->getClientOriginalName()]);

            // 3. Respon sukses
            return redirect()->back()->with('success', 'Migrasi data laporan dimulai! Proses akan berjalan di latar belakang.');
            
        } catch (\Exception $e) {
            Log::error('Gagal memulai proses import/migrasi: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Gagal memulai migrasi data. Pastikan format header file sudah benar.');
        }
    }
}
