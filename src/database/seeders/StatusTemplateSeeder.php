<?php

namespace Database\Seeders;

use App\Models\StatusTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatusTemplateSeeder extends Seeder
{
    public function run()
    {
        StatusTemplate::truncate();

        StatusTemplate::create([
            'name' => 'Proses verifikasi dan telaah',
            'status_code' => 'verification_in_progress',
            'response_template' => 'Laporan pengaduan Saudara dalam proses verifikasi & penelaahan.',
        ]);

        StatusTemplate::create([
            'name' => 'Menunggu kelengkapan data dukung dari Pelapor',
            'status_code' => 'additional_data_required',
            'response_template' => 'Berdasarkan hasil verifikasi dan telaahan atas laporan/pengaduan Saudara, dokumen aduan masih belum memadai. Mohon melengkapi kembali dengan data pendukung berupa [dokumen_yang_dibutuhkan] dengan dikirimkan melalui nomor WA: 0811-1704-2204 pada menu Kirim Data Dukung. Apabila dalam 10 hari Saudara tidak mengirim data dukung maka laporan akan otomatis ditutup.',
        ]);

        StatusTemplate::create([
            'name' => 'Diteruskan kepada instansi yang berwenang untuk penanganan lebih lanjut',
            'status_code' => 'reports_forwarded',
            'response_template' => 'Laporan/pengaduan Saudara telah diteruskan ke instansi yang berwenang {{instansi}} melalui surat nomor {{nomor_surat}} untuk penanganan lebih lanjut sesuai dengan ketentuan perundang-undangan',
        ]);

        StatusTemplate::create([
            'name' => 'Penanganan Selesai',
            'status_code' => 'reports_completed',
            'response_template' => 'Laporan/pengaduan Saudara telah selesai ditindaklanjuti, dengan laporan tindaklanjut/tanggapan dari {{instansi_berwenang}} melalui surat nomor {{nomor_surat}}, yang intinya {{isi_surat}}. Terima kasih atas kerja sama yang baik.',
        ]);
    }
}
