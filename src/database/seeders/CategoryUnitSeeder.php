<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\UnitKerja;

class CategoryUnitSeeder extends Seeder
{
    protected array $mapping = [
        'Ekonomi dan Keuangan' => 'Asisten Deputi Ekonomi, Keuangan, dan Transformasi Digital',
        'Teknologi Informasi dan Komunikasi' => 'Asisten Deputi Ekonomi, Keuangan, dan Transformasi Digital',
        'Pemulihan Ekonomi Nasional' => 'Asisten Deputi Ekonomi, Keuangan, dan Transformasi Digital',
        'Perpajakan' => 'Asisten Deputi Ekonomi, Keuangan, dan Transformasi Digital',

        'Pariwisata dan Ekonomi Kreatif' => 'Asisten Deputi Industri, Perdagangan, Pariwisata, dan Ekonomi Kreatif',
        'Perlindungan Konsumen' => 'Asisten Deputi Industri, Perdagangan, Pariwisata, dan Ekonomi Kreatif',
        'Industri dan Perdagangan' => 'Asisten Deputi Industri, Perdagangan, Pariwisata, dan Ekonomi Kreatif',
        
        'Lingkungan Hidup dan Kehutanan' => 'Asisten Deputi Infrastruktur, Sumber Daya Alam, dan Pembangunan Kewilayahan',
        'Pekerjaan Umum dan Penataan Ruang' => 'Asisten Deputi Infrastruktur, Sumber Daya Alam, dan Pembangunan Kewilayahan',
        'Pertanian dan Peternakan' => 'Asisten Deputi Infrastruktur, Sumber Daya Alam, dan Pembangunan Kewilayahan',
        'Energi dan Sumber Daya Alam' => 'Asisten Deputi Infrastruktur, Sumber Daya Alam, dan Pembangunan Kewilayahan',
        'Mudik' => 'Asisten Deputi Infrastruktur, Sumber Daya Alam, dan Pembangunan Kewilayahan',
        'Perairan' => 'Asisten Deputi Infrastruktur, Sumber Daya Alam, dan Pembangunan Kewilayahan',
        'Perhubungan' => 'Asisten Deputi Infrastruktur, Sumber Daya Alam, dan Pembangunan Kewilayahan',
        'Perumahan' => 'Asisten Deputi Infrastruktur, Sumber Daya Alam, dan Pembangunan Kewilayahan',
        'Pembangunan Kewilayahan' => 'Asisten Deputi Infrastruktur, Sumber Daya Alam, dan Pembangunan Kewilayahan',
        'Infrastruktur' => 'Asisten Deputi Infrastruktur, Sumber Daya Alam, dan Pembangunan Kewilayahan',
        'Sumber Daya Alam dan Energi' => 'Asisten Deputi Infrastruktur, Sumber Daya Alam, dan Pembangunan Kewilayahan',

        'Agama' => 'Asisten Deputi Pendidikan, Agama, Kebudayaan, Pemuda, dan Olahraga',
        'Pendidikan dan Kebudayaan' => 'Asisten Deputi Pendidikan, Agama, Kebudayaan, Pemuda, dan Olahraga',
        'Kekerasan di Satuan Pendidikan (Sekolah, Kampus, Lembaga Khusus)' => 'Asisten Deputi Pendidikan, Agama, Kebudayaan, Pemuda, dan Olahraga',
        'Kepemudaan dan Olahraga' => 'Asisten Deputi Pendidikan, Agama, Kebudayaan, Pemuda, dan Olahraga',
        
        'Corona Virus' => 'Asisten Deputi Kesehatan, Gizi, dan Pembangunan Keluarga',
        'Keluarga Berencana' => 'Asisten Deputi Kesehatan, Gizi, dan Pembangunan Keluarga',
        'Kesehatan' => 'Asisten Deputi Kesehatan, Gizi, dan Pembangunan Keluarga',
        'Kesetaraan Gender dan Sosial Inklusif' => 'Asisten Deputi Kesehatan, Gizi, dan Pembangunan Keluarga',
        'Pembangunan Keluarga' => 'Asisten Deputi Kesehatan, Gizi, dan Pembangunan Keluarga',

        'Pemberdayaan Masyarakat, Koperasi, dan UMKM' => 'Asisten Deputi Pemberdayaan Masyarakat dan Penanggulangan Bencana',
        'Penanggulangan Bencana' => 'Asisten Deputi Pemberdayaan Masyarakat dan Penanggulangan Bencana',
        'Ketenagakerjaan' => 'Asisten Deputi Pemberdayaan Masyarakat dan Penanggulangan Bencana',
        
        'Pembangunan Desa, Daerah Tertinggal, dan Transmigrasi' => 'Asisten Deputi Pengentasan Kemiskinan dan Pembangunan Desa',
        'Sosial dan Kesejahteraan' => 'Asisten Deputi Pengentasan Kemiskinan dan Pembangunan Desa',

        'Ketentraman, Ketertiban Umum, dan Perlindungan Masyarakat' => 'Asisten Deputi Politik, Keamanan, Hukum, dan Hak Asasi Manusia',
        'Politik dan Hukum' => 'Asisten Deputi Politik, Keamanan, Hukum, dan Hak Asasi Manusia',
        'Pencegahan dan Pemberantasan Penyalahgunaan dan Peredaran Gelap Narkotika dan Prekursor Narkotika (P4GN)' => 'Asisten Deputi Politik, Keamanan, Hukum, dan Hak Asasi Manusia',
        'TNI' => 'Asisten Deputi Politik, Keamanan, Hukum, dan Hak Asasi Manusia',
        'Polri' => 'Asisten Deputi Politik, Keamanan, Hukum, dan Hak Asasi Manusia',
        'Pertanahan' => 'Asisten Deputi Politik, Keamanan, Hukum, dan Hak Asasi Manusia',

        'Luar Negeri' => 'Asisten Deputi Hubungan Luar Negeri dan Pertahanan',

        'Politisasi ASN' => 'Asisten Deputi Tata Kelola Pemerintahan',
        'Netralitas ASN' => 'Asisten Deputi Tata Kelola Pemerintahan',
        'Manajemen ASN' => 'Asisten Deputi Tata Kelola Pemerintahan',
        'Kependudukan' => 'Asisten Deputi Tata Kelola Pemerintahan',
        'SP4N Lapor' => 'Asisten Deputi Tata Kelola Pemerintahan',
        'Pelayanan Publik' => 'Asisten Deputi Tata Kelola Pemerintahan',
        'Daerah Perbatasan' => 'Asisten Deputi Tata Kelola Pemerintahan',

        'Bantuan Masyarakat' => 'Biro Perencanaan dan Keuangan',

        'Topik Khusus' => 'Biro Umum',
        'Topik Lainnya' => 'Biro Umum',
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
