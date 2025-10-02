<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\UnitKerja;

class CategoryUnitSeeder extends Seeder
{
    protected array $mapping = [
        'Ekonomi dan Keuangan' => 'Asisten Deputi Ekonomi, Keuangan, dan Transformasi Digital',
        'Ketenagakerjaan' => 'Asisten Deputi Ekonomi, Keuangan, dan Transformasi Digital',
        'Perlindungan Konsumen' => 'Asisten Deputi Ekonomi, Keuangan, dan Transformasi Digital',
        'Pemulihan Ekonomi Nasional' => 'Asisten Deputi Ekonomi, Keuangan, dan Transformasi Digital',
        
        'Pembangunan Kewilayahan' => 'Asisten Deputi Infrastruktur, Sumber Daya Alam, dan Pembangunan Kewilayahan',
        'Infrastruktur' => 'Asisten Deputi Infrastruktur, Sumber Daya Alam, dan Pembangunan Kewilayahan',
        'Sumber Daya Alam dan Energi' => 'Asisten Deputi Infrastruktur, Sumber Daya Alam, dan Pembangunan Kewilayahan',

        'Agama' => 'Asisten Deputi Pendidikan, Agama, Kebudayaan, Pemuda, dan Olahraga',
        'Pendidikan dan Kebudayaan' => 'Asisten Deputi Pendidikan, Agama, Kebudayaan, Pemuda, dan Olahraga',
        'Kekerasan di Satuan Pendidikan (Sekolah, Kampus, Lembaga Khusus)' => 'Asisten Deputi Pendidikan, Agama, Kebudayaan, Pemuda, dan Olahraga',
        
        'Kesehatan' => 'Asisten Deputi Pemberdayaan Masyarakat dan Penanggulangan Bencana',
        'Kesetaraan Gender dan Sosial Inklusif' => 'Asisten Deputi Pemberdayaan Masyarakat dan Penanggulangan Bencana',
        'Sosial dan Kesejahteraan' => 'Asisten Deputi Pemberdayaan Masyarakat dan Penanggulangan Bencana',
        'Pembangunan Keluarga' => 'Asisten Deputi Pemberdayaan Masyarakat dan Penanggulangan Bencana',
        'Pemberdayaan Masyarakat, Koperasi, dan UMKM' => 'Asisten Deputi Pemberdayaan Masyarakat dan Penanggulangan Bencana',
        'Penanggulangan Bencana' => 'Asisten Deputi Pemberdayaan Masyarakat dan Penanggulangan Bencana',
        'Bantuan Masyarakat' => 'Asisten Deputi Pemberdayaan Masyarakat dan Penanggulangan Bencana',
        
        'Pembangunan Desa, Daerah Tertinggal, dan Transmigrasi' => 'Asisten Deputi Pengentasan Kemiskinan dan Pembangunan Desa',

        'Ketentraman, Ketertiban Umum, dan Perlindungan Masyarakat' => 'Asisten Deputi Politik, Keamanan, Hukum, dan Hak Asasi Manusia',
        'Politik dan Hukum' => 'Asisten Deputi Politik, Keamanan, Hukum, dan Hak Asasi Manusia',
        'Pencegahan dan Pemberantasan Penyalahgunaan dan Peredaran Gelap Narkotika dan Prekursor Narkotika (P4GN)' => 'Asisten Deputi Politik, Keamanan, Hukum, dan Hak Asasi Manusia',
        'TNI' => 'Asisten Deputi Politik, Keamanan, Hukum, dan Hak Asasi Manusia',

        'Luar Negeri' => 'Asisten Deputi Hubungan Luar Negeri dan Pertahanan',

        'Politisasi ASN' => 'Asisten Deputi Tata Kelola Pemerintahan',
        'Netralitas ASN' => 'Asisten Deputi Tata Kelola Pemerintahan',
        'Kependudukan' => 'Asisten Deputi Tata Kelola Pemerintahan',

        // Administrasi Umum (Biro Umum, IT, SP4N)
        'Teknologi Informasi dan Komunikasi' => 'Biro Umum',
        'SP4N Lapor' => 'Biro Umum',
        'Topik Khusus' => 'Biro Umum',
        'Topik Lainnya' => 'Biro Umum',
        'Lainnya' => 'Biro Umum',
    ];

    public function run(): void
    {
        // Ambil ID Kategori Utama dan Unit Kerja dari database
        $categories = Category::mainCategories()->pluck('id', 'name');
        $unitKerjas = UnitKerja::pluck('id', 'name');
        
        foreach ($this->mapping as $categoryName => $unitName) {
            $categoryId = $categories[$categoryName] ?? null;
            $unitId = $unitKerjas[$unitName] ?? null;

            if ($categoryId && $unitId) {
                $category = Category::find($categoryId);
                
                // PERBAIKAN: Memanggil method relasi yang benar (unitKerjas)
                $category->unitKerjas()->syncWithoutDetaching([$unitId]); 
            }
        }
    }
}
