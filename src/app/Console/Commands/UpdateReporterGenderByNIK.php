<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reporter;
use Illuminate\Support\Facades\DB;

class UpdateReporterGenderByNIK extends Command
{
    /**
     * Nama dan tanda tangan dari command.
     */
    protected $signature = 'reporter:fix-gender';

    /**
     * Deskripsi command.
     */
    protected $description = 'Memperbarui data gender reporter yang kosong berdasarkan NIK';

    /**
     * Eksekusi command.
     */
    public function handle()
    {
        // Ambil reporter yang gendernya masih null atau kosong, dan memiliki NIK
        $reporters = Reporter::where(function($query) {
                $query->whereNull('gender')
                      ->orWhere('gender', '');
            })
            ->whereNotNull('nik')
            ->whereRaw('LENGTH(nik) >= 8') // Pastikan NIK cukup panjang
            ->get();

        if ($reporters->isEmpty()) {
            $this->info('Tidak ada data reporter yang perlu diperbarui.');
            return 0;
        }

        $updatedCount = 0;
        $this->output->progressStart($reporters->count());

        foreach ($reporters as $reporter) {
            // Digit ke-7 dan ke-8 (index 6 dan 7 dalam string)
            $birthDateCode = (int) substr($reporter->nik, 6, 2);

            // Logika: Jika > 40 berarti Perempuan (P), jika <= 40 berarti Laki-laki (L)
            $gender = ($birthDateCode > 40) ? 'P' : 'L';

            $reporter->gender = $gender;
            
            // Gunakan save quiet agar tidak menempelkan timestamps updated_at jika tidak diperlukan
            if ($reporter->save()) {
                $updatedCount++;
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->info("Berhasil memperbarui {$updatedCount} data gender reporter.");
        
        return 0;
    }
}