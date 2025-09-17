<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Agama' => [
                'Haji dan Umroh',
                'Lainnya terkait Agama',
                'Pendidikan Agama',
                'Penyediaan fasilitas ibadah',
            ],
            'Ekonomi dan Keuangan' => [
                'Anggaran dan Perbendaharaan',
                'Bea dan Cukai',
                'Jasa Keuangan',
                'Kawasan Ekonomi Khusus',
                'Kekayaan Negara dan Lelang',
                'Kepuasan Konsumen',
                'Pajak',
                'Pariwisata dan Ekonomi Kreatif',
                'Penanaman Modal / Investasi',
                'Pengadaan Barang / Jasa',
                'Pengiriman Barang / Logistik',
                'Perdagangan',
                'Perimbangan Keuangan Pusat dan Daerah',
                'Perindustrian',
            ],
            'Pembangunan Keluarga' => [
                'Layanan KB dan Kesehatan Reproduksi',
                'Layanan Pelatihan Keluarga Berencana',
                'Akses Pemberdayaan Keluarga',
                'Ketahanan Keluarga Lansia dan Rentan',
                'Ketahanan Keluarga Remaja dan Remaja',
                'Ketahanan Keluarga Balita dan Balita',
                'Lainnya terkait Pembangunan Keluarga',
            ],
            'Kesehatan' => [
                'Biaya Bantuan Pengobatan (Non BPJS Kesehatan)',
                'BPJS Kesehatan',
                'Masalah Gizi / Stunting',
                'Fasilitas Layanan Kesehatan (Fasyankes)',
                'Infrastruktur Kesehatan',
                'Pendidikan Kesehatan',
                'SDM Non Tenaga Kesehatan pada Institusi Layanan Kesehatan',
                'SDM Tenaga Kesehatan (Dokter dan Perawat)',
                'Penyakit Menular',
                'Lainnya terkait Kesehatan',
                'Makan Bergizi Gratis (MBG)',
                'Vaksinasi',
                'Pelayanan Obat',
            ],
            'Kesetaraan Gender dan Sosial Inklusif' => [
                'Akses Masyarakat Berkebutuhan Khusus / Disabilitas',
                'Perlindungan Hak Perempuan Dalam Rumah Tangga',
                'Perlindungan Hak Anak Dalam Rumah Tangga',
                'Kekerasan dalam Rumah Tangga',
                'Kekerasan terhadap Anak',
                'Kesetaraan Gender',
                'Lainnya terkait Kesetaraan Gender dan Sosial Inklusif',
            ],
            'Ketentraman, Ketertiban Umum, dan Perlindungan Masyarakat' => [
                'Kepolisian',
                'Ketertiban Umum',
                'Lainnya terkait Ketentraman, Ketertiban Umum, dan Perlindungan Masyarakat',
                'Pemadaman Kebakaran',
                'Radikalisme',
                'SARA',
            ],
            'Pembangunan Kewilayahan' => [
                'Aglomerasi',
                'Metropolitan',
                'Minapolitan',
            ],
            'Infrastruktur' => [
                'Infrastruktur Permukiman',
                'Infrastruktur Transportasi',
                'Infrastruktur Air dan Sanitasi',
                'Infrastruktur Konektivitas',
            ],
            'Pembangunan Desa, Daerah Tertinggal, dan Transmigrasi' => [
                'Lainnya terkait Pembangunan Desa, Daerah Tertinggal, dan Transmigrasi',
                'Pembangunan Desa dan Daerah Tertinggal',
                'Transmigrasi',
            ],
            'Pendidikan dan Kebudayaan' => [
                'Kartu Indonesia Pintar/KIP',
                'Kebudayaan',
                'Lainnya terkait Pendidikan dan Kebudayaan',
                'Pendidikan Dasar dan Menengah',
                'Pendidikan Tinggi',
                'Politeknik',
                'Balai Diklat',
                'Pelestarian Budaya',
            ],
            'Politik dan Hukum' => [
                'Administrasi Hukum Umum (AHU) Online',
                'BPHN',
                'Hak Asasi Manusia (HAM)',
                'Hukum',
                'Imigrasi',
                'Kekayaan Intelektual',
                'Kewaspadaan Nasional',
                'Korupsi, Kolusi, dan Nepotisme',
                'Lainnya terkait Politik dan Hukum',
                'Pemilihan Kepala Desa / Pilkades',
                'Pemilihan Umum',
                'Pengadilan Negeri dan Pengadilan Agama',
                'Peraturan Perundang-undangan',
                'Permasyarakatan',
            ],
            'Politisasi ASN' => [
                'Ajakan dan Himbauan kepada ASN untuk kepentingan politik',
                'Lainnya terkait Politisasi ASN',
                'Pelaksanaan pertemuan ASN untuk kepentingan politik',
                'Pemanfaatan fasilitas negara untuk kepentingan politik',
                'Pemanfaatan jabatan untuk kepentingan politik',
                'Pemberian barang kepada ASN untuk kepentingan politik',
            ],
            'Sosial dan Kesejahteraan' => [
                'Bantuan Sosial',
                'Lainnya terkait Sosial dan Kesejahteraan',
                'Pangan',
            ],
            'SP4N Lapor' => [
                'Gangguan Aplikasi SP4N-LAPOR!',
                'Kritik dan Saran untuk SP4N-LAPOR!',
                'Pengembangan SP4N-LAPOR!',
                'Penghapusan Akun SP4N-LAPOR!',
                'Penghapusan Laporan SP4N-LAPOR!',
            ],
            'Sumber Daya Alam dan Energi' => [
                'Pertanian',
                'Kehutanan',
                'Ketenagalistrikan',
                'Minyak dan Gas',
                'Pertambangan',
                'Perkebunan',
                'Peternakan',
                'Kelautan dan Perikanan',
                'Lingkungan Hidup',
            ],
            'Kekerasan di Satuan Pendidikan (Sekolah, Kampus, Lembaga Khusus)' => [
                'Gabungan atau Lainnya',
                'Intoleransi',
                'Kekerasan Seksual',
                'Perundungan (Bullying)',
            ],
            'Kependudukan' => [
                'Kerjasama Bilateral',
                'Kerjasama Multilateral',
            ],
            'Ketenagakerjaan' => [
                'Hak Pekerja',
                'Jaminan Sosial Ketenagakerjaan',
                'Kepegawaian',
                'Keselamatan Pekerja',
                'Perselisihan Hubungan Industrial',
                'Perlakuan Tidak Adil / Diskriminasi ',
                'Rekrutmen Tenagakerja',
                'Tenaga Kerja Asing (TKA)',
                'Tenaga Kerja Indonesia / Pekerja Migran Indonesia (TKI/PMI)',
                'Lainnya terkait Ketenagakerjaan',
            ],
            'Netralitas ASN' => [
                'ASN Kampanye Politik di Media Sosial',
                'ASN Turut Serta dalam Kampanye Offline',
                'Foto bersama peserta pemilu',
                'Ikut serta dalam kegiatan politik',
                'Lainnya terkait Netralitas ASN',
                'Penggunaan Atribut peserta pemilu',
                'Provokasi atas peserta pemilu tertentu',
            ],
            'Pemulihan Ekonomi Nasional' => [
                'Insentif Usaha',
                'Sektoral dan Pemda',
            ],
            'Pencegahan dan Pemberantasan Penyalahgunaan dan Peredaran Gelap Narkotika dan Prekursor Narkotika (P4GN)' => [
                'Penyalahgunaan Narkotika',
                'Transaksi Narkotika',
            ],
            'Perlindungan Konsumen' => [
                'Perlindungan Konsumen terkait Barang Elektronik, Telematika, dan Kendaraan Bermotor',
                'Perlindungan Konsumen terkait Jasa Keuangan',
                'Perlindungan Konsumen terkait Jasa Layanan Kesehatan',
                'Perlindungan Konsumen terkait Jasa Logistik',
                'Perlindungan Konsumen terkait Jasa Pariwisata',
                'Perlindungan Konsumen terkait Jasa Telekomunikasi',
                'Perlindungan Konsumen terkait Jasa Transportasi',
                'Perlindungan Konsumen terkait Listrik dan Gas Rumah Tangga',
                'Perlindungan Konsumen terkait Obat dan Makanan',
                'Perlindungan Konsumen terkait Perumahan',
                'Perlindungan Konsumen terkait Transaksi Perdagangan melalui Sistem Elektronik (E-commerce)',
            ],
            'Teknologi Informasi dan Komunikasi' => [
                'Blokir Nomor Telepon',
                'Blokir Website',
                'CSIRT',
                'Informasi Terkait Aplikasi Pemerintah',
                'Jaringan Internet',
                'Kriptografi',
                'Lainnya terkait Teknologi Informasi dan Komunikasi',
                'Layanan Online / Aplikasi Pemerintah',
                'Tanda Tangan Elektronik / E-Sign',
            ],
            'Topik Khusus' => [
                'Bansos Digital 2021',
            ],
            'Topik Lainnya' => [],
            'Pemberdayaan Masyarakat, Koperasi, dan UMKM' => [
                'Pemberdayaan Masyarakat',
                'Koperasi',
                'Permohonan Bantuan Modal Usaha',
                'Kredit Macet',
                'Kebijakan UMKM',
                'Pelatihan UMKM',
                'Lainnya Terkait UMKM',
            ],
            'Penanggulangan Bencana' => [
                'Laporan Kejadian Bencana',
                'Bantuan Pasca Bencana',
                'Pencegahan Bencana',
                'Lainnya Terkait Penanggulangan Bencana',
            ],
            'Luar Negeri' => [
                'Imigran',
                'Kekonsuleran',
                'Pengungsi asing',
                'Pekerja Migran Indonesia',
                'Deportan',
                'Tindak Pidana Perdagangan Orang (TPPO)',
                'WNA',
                'KITAS',
            ],
            'TNI' => [],
            'Lainnya' => [],
        ];

        // Looping untuk membuat parent dan child categories
        foreach ($categories as $parentName => $childNames) {
            // Cek apakah parent sudah ada untuk menghindari duplikasi
            $parent = Category::firstOrCreate(['name' => $parentName]);

            // Jika ada sub-kategori, buat child dan hubungkan ke parent
            foreach ($childNames as $childName) {
                Category::firstOrCreate([
                    'name' => $childName,
                    'parent_id' => $parent->id,
                ]);
            }
        }
    }
}
