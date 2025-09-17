<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Deputy;
use App\Models\UnitKerja;

class UnitKerjaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $unitsByDeputy = [
            'Deputi Bidang Dukungan Kebijakan Perekonomian, Pariwisata, dan Transformasi Digital' => [
                'Asisten Deputi Industri, Perdagangan, Pariwisata, dan Ekonomi Kreatif',
                'Asisten Deputi Ekonomi, Keuangan, dan Transformasi Digital',
                'Asisten Deputi Infrastruktur, Sumber Daya Alam, dan Pembangunan Kewilayahan',
            ],
            'Deputi Bidang Dukungan Kebijakan Peningkatan Kesejahteraan dan Pembangunan Sumber Daya Manusia' => [
                'Asisten Deputi Pemberdayaan Masyarakat dan Penanggulangan Bencana',
                'Asisten Deputi Pengentasan Kemiskinan dan Pembangunan Desa',
                'Asisten Deputi Pendidikan, Agama, Kebudayaan, Pemuda, dan Olahraga',
            ],
            'Deputi Bidang Dukungan Kebijakan Pemerintahan dan Pemerataan Pembangunan' => [
                'Asisten Deputi Tata Kelola Pemerintahan',
                'Asisten Deputi Politik, Keamanan, Hukum, dan Hak Asasi Manusia',
                'Asisten Deputi Hubungan Luar Negeri dan Pertahanan',
            ],
            'Deputi Bidang Administrasi' => [
                'Biro Perencanaan dan Keuangan',
                'Biro Pers, Media, dan Informasi',
                'Biro Umum',
                'Biro Tata Usaha dan Sumber Daya Manusia',
                'Biro Protokol dan Kerumahtanggaan',
            ],
        ];

        foreach ($unitsByDeputy as $deputyName => $units) {
            // Temukan ID Deputi berdasarkan nama
            $deputy = Deputy::where('name', $deputyName)->first();

            // Jika Deputi ditemukan, buat unit kerjanya
            if ($deputy) {
                foreach ($units as $unitName) {
                    UnitKerja::firstOrCreate(
                        ['name' => $unitName, 'deputy_id' => $deputy->id]
                    );
                }
            }
        }
    }
}
