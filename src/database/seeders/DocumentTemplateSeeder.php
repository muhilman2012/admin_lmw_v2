<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $documents = [
            'KTP',
            'KK',
            'Surat Kuasa',
            'Akta Kelahiran',
            'SKTM dari Kelurahan',
            'Rincian Biaya Tagihan dari Sekolah',
            'Bukti kepemilikan tanah',
            'Sertifikat Tanah',
            'Lainnya'
        ];
        
        foreach ($documents as $doc) {
            DocumentTemplate::create(['name' => $doc]);
        }
    }
}
