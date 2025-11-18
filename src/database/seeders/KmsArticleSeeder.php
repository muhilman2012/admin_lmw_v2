<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KmsArticle;
use App\Models\User;
use Illuminate\Support\Str;

class KmsArticleSeeder extends Seeder
{
    /**
     * Jalankan seed database.
     */
    public function run(): void
    {
        // Pastikan tabel users tidak kosong
        $adminUser = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['superadmin', 'admin']);
        })->first();

        // Jika tidak ada user admin, gunakan user pertama (atau buat user default)
        if (!$adminUser) {
            $adminUser = User::first(); 
        }

        if (!$adminUser) {
            return;
        }

        // Hapus data lama (opsional, jika ingin selalu fresh)
        KmsArticle::truncate();

        // --- ARTIKEL 1: CARA UBAH KATA SANDI (DARI HALAMAN PROFILE) ---
        KmsArticle::create([
            'title' => 'Panduan Mengubah Kata Sandi Melalui Halaman Profil',
            'content' => '
                <p>Langkah-langkah berikut digunakan untuk mengubah kata sandi Anda setelah berhasil masuk ke sistem Lapor Mas Wapres (LMW).</p>
                <ol>
                    <li>Masuk ke <strong>Halaman Profil</strong> Anda (Biasanya melalui menu drop-down di kanan atas atau link di navigasi).</li>
                    <li>Pilih tab <strong>Pengaturan Profil</strong> (jika menggunakan sidebar) atau temukan tab dengan nama <strong>Ubah Kata Sandi</strong>.</li>
                    <li>Klik tombol <strong>Perbarui Kata Sandi</strong> atau sejenisnya.</li>
                    <li>Anda akan diminta mengisi tiga kolom wajib:
                        <ul>
                            <li>Kata Sandi Lama (Password Anda saat ini)</li>
                            <li>Kata Sandi Baru</li>
                            <li>Konfirmasi Kata Sandi Baru</li>
                        </ul>
                    </li>
                    <li>Pastikan kata sandi baru Anda memenuhi kriteria keamanan sistem.</li>
                    <li>Klik <strong>Submit</strong> atau <strong>Simpan</strong>. Anda akan mendapatkan notifikasi sukses dan sesi Anda akan tetap aktif.</li>
                </ol>
                <p class="alert alert-info">Jika Anda lupa kata sandi lama, gunakan prosedur "Lupa Kata Sandi" di halaman login.</p>
            ',
            'category' => 'FAQ',
            'tags' => 'password, ganti password, profil, keamanan',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 2: CARA LUPA KATA SANDI (DARI HALAMAN LOGIN) ---
        KmsArticle::create([
            'title' => 'Prosedur Pemulihan Akun: Lupa Kata Sandi',
            'content' => '
                <p>Jika Anda tidak dapat masuk karena lupa kata sandi, ikuti langkah-langkah pemulihan berikut:</p>
                <ol>
                    <li>Di <strong>Halaman Login LMW</strong>, klik link <strong>Lupa Password</strong>.</li>
                    <li>Sistem akan meminta Anda untuk memasukkan:
                        <ul>
                            <li><strong>Email Dinas</strong> Anda (yang terdaftar di LMW)</li>
                            <li><strong>NIP</strong> (Nomor Induk Pegawai) Anda.</li>
                        </ul>
                    </li>
                    <li>Klik tombol <strong>Verifikasi</strong>.</li>
                    <li>Sistem akan memproses permintaan Anda dan mengirimkan <strong>kata sandi sementara</strong>.</li>
                    <li>Gunakan kata sandi sementara tersebut untuk <strong>Login</strong> ke sistem LMW.</li>
                    <li>Setelah berhasil masuk, Anda akan <strong>otomatis diarahkan</strong> ke halaman untuk segera membuat kata sandi baru (wajib).</li>
                    <li>Buat kata sandi baru yang kuat dan simpan. Proses pemulihan selesai.</li>
                </ol>
            ',
            'category' => 'Prosedur',
            'tags' => 'lupa password, reset, login, password sementara',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 3: CARA BUAT LAPORAN BARU DARI TATAP MUKA ---
        KmsArticle::create([
            'title' => 'Panduan Membuat Laporan Tatap Muka (Petugas Dumas LMW)',
            'content' => '
                <p>Prosedur ini menjelaskan langkah-langkah membuat laporan pengaduan baru yang berasal dari jalur tatap muka (petugas Dumas LMW).</p>
                <ol>
                    <li><strong>Pastikan Chekin dan Panggilan Selesai:</strong> Pastikan pengadu sudah melakukan *check-in* dan Anda (petugas) telah memanggil pengadu tersebut untuk memulai sesi pengaduan.</li>
                    <li><strong>Cari Data Pengadu:</strong>
                        <ul>
                            <li>Buka halaman <strong>Daftar Pengadu</strong>.</li>
                            <li>Gunakan kolom pencarian untuk mencari nama pengadu yang sedang Anda layani (sesuai nama yang dipanggil).</li>
                            <li>Setelah menemukan nama pengadu yang benar, klik tombol <strong>Buat Laporan</strong>.</li>
                        </ul>
                    </li>
                    <li><strong>Arahkan ke Halaman Tambah Pengaduan:</strong> Anda akan otomatis dipindahkan ke halaman <strong>Tambah Pengaduan</strong>.</li>
                    <li><strong>Verifikasi dan Isi Data Pengadu:</strong> Data pengadu (*Reporter*) akan otomatis terisi dari data *check-in* sebelumnya. Periksa kembali keakuratannya.</li>
                    <li><strong>Isi Detail Laporan:</strong>
                        <ul>
                            <li>Isi semua kolom yang dibutuhkan berdasarkan wawancara Anda dengan pengadu.</li>
                            <li>Perhatikan kolom <strong>wajib</strong> yang ditandai dengan <strong>tanda bintang merah (`*`)</strong>.</li>
                            <li>Pastikan <strong>Judul Laporan</strong>, <strong>Detail Pengaduan</strong>, dan <strong>Kategori</strong> sudah sesuai dengan inti masalah yang disampaikan.</li>
                            <li>Lengkapi dokumen pendukung jika ada.</li>
                        </ul>
                    </li>
                    <li><strong>Fitur Aksi Cepat (Opsional):</strong> Fitur ini digunakan untuk mengubah Status dan Tanggapan secara langsung. Lihat Penjelasan Fitur Aksi Cepat di Thread</li>
                    <li><strong>Simpan Laporan:</strong> Setelah semua kolom terisi dan diverifikasi, klik tombol <strong>Simpan</strong>. Laporan baru akan dibuat dengan sumber `Tatap Muka` dan status awalnya.</li>
                </ol>
                <p class="alert alert-success">Petugas wajib memastikan semua kolom krusial terisi lengkap sebelum menyimpan laporan untuk mempercepat proses disposisi selanjutnya.</p>
            ',
            'category' => 'Prosedur',
            'tags' => 'tatap muka, laporan baru, petugas, check-in, buat laporan, aksi cepat',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 4: PENGGUNAAN FITUR AKSI CEPAT ---
        KmsArticle::create([
            'title' => 'Menggunakan Fitur Aksi Cepat untuk Mengubah Status Laporan',
            'content' => '
                <p>Fitur Aksi Cepat tersedia di bawah kolom *Drop File* pada halaman Tambah Pengaduan. Fitur ini dirancang untuk <strong>mempercepat penyesuaian status laporan</strong> di luar status *default* <strong>"Proses Verifikasi dan Telaah"</strong>.</p>
                <h4 class="h5 mt-4">Langkah-Langkah Penggunaan</h4>
                <ol>
                    <li><strong>Akses Fitur:</strong> Setelah mengisi detail laporan (Judul, Kategori, dll.), temukan tombol <strong>Aksi Cepat</strong> (berada di bawah area unggah dokumen).</li>
                    <li><strong>Pilih Status Baru:</strong> Klik tombol tersebut. Anda akan disajikan opsi untuk memilih status laporan secara langsung. Contohnya:
                        <ul>
                            <li>Pilih status <strong>"Menunggu kelengkapan data dukung dari Pelapor"</strong> jika pengadu menyatakan akan melengkapi dokumen.</li>
                            <li>Anda juga dapat memilih status akhir seperti <strong>"Penanganan Selesai"</strong> (jika pengaduan langsung diselesaikan saat itu juga).</li>
                        </ul>
                    </li>
                    <li><strong>Tentukan Dokumen yang Dibutuhkan (Opsional):</strong> Jika Anda memilih status "Menunggu kelengkapan data dukung...", Anda akan diminta untuk mencentang/memilih dokumen apa saja yang wajib dilengkapi oleh pengadu (misalnya: KTP, Surat Tanah, dll.).</li>
                    <li><strong>Tanggapan Otomatis:</strong> Setelah status dan dokumen dipilih, kolom <strong>Tanggapan</strong> akan secara otomatis terisi dengan *template* tanggapan yang relevan.
                        <p class="alert alert-warning"><strong>Pengecekan Kalimat Tanggapan:</strong> Mohon selalu tinjau dan pastikan kalimat tanggapan otomatis tersebut sudah informatif, profesional, dan sesuai dengan hasil kesepakatan dengan pengadu.</p>
                    </li>
                    <li><strong>Terapkan Perubahan:</strong> Klik tombol <strong>Terapkan</strong> di jendela Aksi Cepat. Status dan Tanggapan Anda akan diperbarui di halaman Tambah Pengaduan.</li>
                    <li><strong>Simpan Pengaduan:</strong> Lanjutkan dan klik <strong>Simpan</strong> Pengaduan untuk menyimpan laporan dan status baru secara permanen.</li>
                </ol>
            ',
            'category' => 'Prosedur',
            'tags' => 'aksi cepat, status, data dukung, template tanggapan, petugas',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 5: PENGGUNAAN FITUR UBAH KATEGORI MASSAL ---
        KmsArticle::create([
            'title' => 'Prosedur Mengubah Kategori Pengaduan Secara Massal',
            'content' => '
                <p>Fitur ini digunakan untuk memperbaiki klasifikasi kategori beberapa pengaduan sekaligus, yang secara otomatis akan memindahkan laporan ke unit kerja/deputi yang baru sesuai dengan kategori yang dipilih.</p>
                <h4 class="h5 mt-4">Langkah-Langkah Ubah Kategori Massal</h4>
                <ol>
                    <li><strong>Akses Menu:</strong> Masuk ke menu <strong>Kelola Pengaduan</strong>.</li>
                    <li><strong>Pilih Laporan:</strong> Ceklis pengaduan mana saja yang akan diubah kategorinya dengan mengklik kotak <strong>checkbox</strong> pada samping kiri penomoran laporan.
                        <ul>
                            <li>Anda juga dapat memilih semua laporan yang muncul di halaman saat ini dengan menggunakan fitur <strong>Select All</strong> (jika tersedia di <strong>datatable</strong> Anda).</li>
                        </ul>
                    </li>
                    <li><strong>Munculkan Tombol Aksi:</strong> Setelah minimal satu laporan diceklis, tombol <strong>Ubah Kategori Massal</strong> akan muncul di bawah <strong>search bar</strong> (atau di area <strong>bulk action</strong>). Klik tombol tersebut.</li>
                    <li><strong>Pilih Kategori Baru:</strong> Sebuah jendela atau <strong>pop-up</strong> akan muncul yang menampilkan daftar kategori. Pilih kategori baru yang dirasa paling sesuai dengan jenis pengaduan yang diceklis.</li>
                    <li><strong>Konfirmasi Perubahan:</strong> Setelah memilih kategori, sistem akan menampilkan <strong>konfirmasi perubahan</strong> yang menjelaskan bahwa:
                        <ul>
                            <li>Kategori semua laporan yang dipilih akan diperbarui.</li>
                            <li>Laporan-laporan tersebut <strong>akan secara otomatis berpindah</strong> ke Unit Kerja atau Deputi yang menjadi penanggung jawab kategori baru tersebut.</li>
                        </ul>
                    </li>
                    <li><strong>Terapkan dan Simpan:</strong> Jika Anda yakin, klik <strong>Terapkan Perubahan</strong> atau <strong>Simpan</strong>. Laporan akan diproses di <strong>background</strong> dan status kategorinya akan diperbarui.</li>
                </ol>
                <p class="alert alert-info">Perubahan kategori massal seringkali dilakukan oleh Superadmin atau Admin untuk tujuan <strong>re-routing</strong> atau <strong>disposisi</strong> awal yang lebih akurat.</p>
            ',
            'category' => 'Prosedur',
            'tags' => 'ubah kategori, massal, kategori, disposisi',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 6: PENGGUNAAN FITUR DISPOSISI MASSAL & SATU PER SATU ---
        KmsArticle::create([
            'title' => 'Panduan Disposisi Laporan (Massal dan Satuan)',
            'content' => '
                <p>Disposisi adalah proses penugasan laporan kepada Analis yang bertanggung jawab untuk melakukan tindak lanjut. Fitur ini hanya tersedia untuk peran <strong>Superadmin</strong>, <strong>Admin</strong>, <strong>Deputi</strong>, dan <strong>Asdep/Karo</strong>.</p>
                
                <h4 class="h5 mt-4">Prosedur Disposisi Massal</h4>
                <p>Disposisi massal memungkinkan penugasan beberapa laporan kepada Analis yang sama secara efisien.</p>
                <ol>
                    <li><strong>Akses Menu:</strong> Masuk ke menu <strong>Kelola Pengaduan</strong>.</li>
                    <li><strong>Pilih Laporan:</strong> Ceklis laporan mana saja yang akan ditugaskan dengan mengklik kotak <strong>checkbox</strong> di samping kiri penomoran.</li>
                    <li><strong>Akses Tombol Aksi:</strong> Setelah laporan dipilih, tombol <strong>Disposisi Massal</strong> akan muncul di bawah <strong>search bar</strong>. Klik tombol tersebut.</li>
                    <li><strong>Tentukan Penugasan:</strong> Sebuah jendela atau <strong>pop-up</strong> akan muncul yang menampilkan:
                        <ul>
                            <li><strong>List Nama Analis:</strong> Pilih nama Analis di bawah Unit Kerja yang bersangkutan.</li>
                            <li><strong>Catatan Disposisi:</strong> Isi catatan atau instruksi penting kepada Analis terkait penugasan ini.</li>
                        </ul>
                    </li>
                    <li><strong>Tugaskan Laporan:</strong> Klik tombol <strong>Tugaskan Laporan</strong>. Sistem akan segera memproses penugasan laporan kepada Analis yang dipilih tanpa memerlukan konfirmasi tambahan. Status laporan akan diperbarui.</li>
                </ol>

                <h4 class="h5 mt-4">Prosedur Disposisi Satu Per Satu</h4>
                <p>Fitur ini digunakan untuk memberikan penugasan khusus pada satu laporan saja.</p>
                <ol>
                    <li><strong>Temukan Baris:</strong> Cari laporan yang ingin Anda disposisi di daftar laporan.</li>
                    <li><strong>Akses Disposisi:</strong> Klik <strong>tombol arah panah</strong> (atau ikon Share) yang berada pada baris aksi laporan tersebut.</li>
                    <li><strong>Tentukan Penugasan:</strong> Sama seperti disposisi massal, sebuah <strong>pop-up</strong> akan muncul meminta Anda memilih <strong>Nama Analis</strong> dan mengisi <strong>Catatan Disposisi</strong>.</li>
                    <li><strong>Tugaskan:</strong> Klik tombol <strong>Tugaskan Laporan</strong> untuk menyelesaikan disposisi.</li>
                </ol>

                <p class="alert alert-danger"><strong>Penting:</strong> Fitur Disposisi (Massal maupun Satuan) <strong>tidak tersedia</strong> untuk pengguna dengan peran <strong>Analis</strong>, karena Analis bertindak sebagai penerima tugas, bukan pemberi tugas.</p>
            ',
            'category' => 'Prosedur',
            'tags' => 'disposisi massal, penugasan, tugaskan laporan, analis',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 7: PENGGUNAAN FITUR UBAH DISPOSISI & HAPUS PENUGASAN ---
        KmsArticle::create([
            'title' => 'Panduan Mengubah dan Menghapus Penugasan Analis (re-disposisi)',
            'content' => '
                <p>Fitur Ubah Disposisi digunakan untuk memindahkan penugasan laporan dari satu Analis ke Analis lain (misalnya, dari Petugas A ke Petugas B). Fitur ini menjamin tidak adanya duplikasi data (<strong>redundancy</strong>) dan memperbarui daftar tugas secara <strong>real-time</strong>.</p>
                
                <h4 class="h5 mt-4">Prosedur Mengubah Penugasan Analis</h4>
                <ol>
                    <li><strong>Akses Menu:</strong> Masuk ke menu <strong>Kelola Pengaduan</strong>.</li>
                    <li><strong>Temukan Laporan:</strong> Cari baris laporan yang Analis penugasannya ingin Anda ubah.</li>
                    <li><strong>Akses Ubah Disposisi:</strong> Klik pada <strong>nama Analis penugasan awal</strong> (yang saat ini bertugas) yang terletak di kolom Penugasan. Tindakan ini akan memunculkan <strong>pop-up</strong> Disposisi Ulang.</li>
                    <li><strong>Disposisi Ulang:</strong> Di dalam <strong>pop-up</strong>, Anda akan menemukan:
                        <ul>
                            <li><strong>List Nama Analis:</strong> Pilih nama Analis yang baru (Petugas B).</li>
                            <li><strong>Catatan Disposisi:</strong> Isi catatan atau instruksi baru terkait penugasan ini.</li>
                        </ul>
                    </li>
                    <li><strong>Simpan Tugas:</strong> Klik tombol <strong>Simpan Tugas</strong>. Secara otomatis:
                        <ul>
                            <li>Laporan tersebut akan <strong>hilang</strong> dari daftar tugas di akun Petugas A.</li>
                            <li>Laporan tersebut akan <strong>muncul</strong> di daftar tugas di akun Petugas B.</li>
                            <li>Tidak akan terjadi <strong>redundant data</strong> (data ganda).</li>
                        </ul>
                    </li>
                </ol>

                <h4 class="h5 mt-4">Prosedur Hapus Penugasan</h4>
                <p>Fitur ini digunakan jika Anda ingin membatalkan penugasan pada laporan yang salah atau jika laporan perlu dipertimbangkan kembali sebelum ditugaskan ke Analis tertentu.</p>
                <ol>
                    <li>Lakukan langkah 1 hingga 3 seperti di atas (klik pada nama Analis penugasan awal).</li>
                    <li>Di dalam <strong>pop-up</strong> Disposisi Ulang, temukan tombol <strong>Hapus Tugas</strong> (atau Batalkan Penugasan).</li>
                    <li>Klik tombol tersebut untuk melepaskan laporan dari Analis yang bertugas. Laporan akan kembali ke status menunggu penugasan (jika sistem Anda memiliki status tersebut).</li>
                </ol>

                <p class="alert alert-danger"><strong>Penting:</strong> Sama seperti fitur Disposisi lainnya, fitur Ubah Disposisi dan Hapus Penugasan <strong>tidak tersedia</strong> untuk pengguna dengan peran <strong>Analis</strong>.</p>
            ',
            'category' => 'Prosedur',
            'tags' => 'ubah disposisi, redisposisi, re-assign, hapus penugasan, analis',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 8: ALUR PROSES PENGADUAN DARI LOKET HINGGA SELESAI ---
        KmsArticle::create([
            'title' => 'Alur Lengkap Pengaduan: Dari Input Loket Hingga Selesai Analisis',
            'content' => '
                <p>Artikel ini menjelaskan alur kerja (workflow) pengaduan dari saat diterima dan diinput oleh Petugas Loket hingga selesai diproses oleh Analis terkait.</p>

                <h4 class="h5 mt-4">1. Input Pengaduan di Loket (Petugas LMW)</h4>
                <ol>
                    <li><strong>Penggunaan Akun Personal:</strong> Setiap Petugas Loket (LMW) wajib menggunakan <strong>akun login pribadi</strong> saat menginput pengaduan. Hal ini krusial untuk memastikan setiap pengaduan memiliki <strong>jejak audit (track record)</strong> yang jelas, memudahkan identifikasi petugas yang bertanggung jawab atas input data jika diperlukan dalam investigasi atau keperluan internal lainnya.</li>
                    <li><strong>Proses Input:</strong> Petugas mengisi detail pengaduan (Nomor Tiket, Pelapor, Judul, Kategori, dll.) melalui modul yang tersedia.</li>
                    <li><strong>Pemberian Bukti:</strong> Setelah pengaduan selesai diinput dan disimpan, sistem akan menghasilkan bukti pengaduan. Petugas Loket harus memberikan bukti ini kepada Pengadu.</li>
                    <li><strong>Edukasi Pengadu:</strong> Petugas harus menjelaskan kepada Pengadu mengenai <strong>cara cek status laporan</strong> secara mandiri melalui <strong>fitur Cek Status di WhatsApp</strong> (atau platform notifikasi yang ditentukan), menggunakan Nomor Tiket yang tertera pada bukti pengaduan.</li>
                </ol>

                <h4 class="h5 mt-4">2. Otomatisasi Distribusi</h4>
                <ol start="5">
                    <li><strong>Mapping Otomatis:</strong> Setelah pengaduan disimpan, sistem akan secara otomatis memetakan dan mendistribusikan laporan tersebut ke Deputi dan/atau Asdep/Karo terkait, berdasarkan <strong>Kategori</strong> yang dipilih oleh petugas saat input.</li>
                    <li><strong>Notifikasi Akses:</strong> Laporan akan langsung muncul di dashboard Deputi dan Asdep/Karo yang memiliki cakupan wewenang atas Kategori tersebut, siap untuk ditindaklanjuti.</li>
                </ol>

                <h4 class="h5 mt-4">3. Disposisi dan Penugasan Analis</h4>
                <ol start="7">
                    <li><strong>Tugas Asdep/Karo:</strong> Asdep/Karo terkait akan memeriksa laporan yang masuk ke dashboard mereka dan bertanggung jawab untuk melakukan <strong>Disposisi</strong> (penugasan) laporan tersebut kepada <strong>Analis</strong> yang sesuai di bawah unit kerja mereka.</li>
                    <li><strong>Notifikasi Analis:</strong> Ketika laporan didisposisikan, Analis yang ditugaskan akan menerima notifikasi otomatis, dan laporan tersebut akan muncul di daftar tugas pribadi mereka.</li>
                </ol>
                
                <h4 class="h5 mt-4">4. Proses Telaah dan Penyelesaian</h4>
                <ol start="9">
                    <li><strong>Proses Telaah:</strong> Analis yang bertugas akan mengambil alih laporan dan memulai proses <strong>telaah/analisis</strong> sesuai prosedur internal.</li>
                    <li><strong>Pengaduan Selesai:</strong> Setelah telaah selesai dan hasil laporan disetujui (misalnya, status diubah menjadi <strong>Selesai</strong> atau <strong>Ditutup</strong>), laporan akan beralih ke tahap penyelesaian. Analis bertanggung jawab memastikan semua proses dokumentasi telah lengkap.</li>
                </ol>
                
                <p class="alert alert-info">Dengan alur ini, tanggung jawab dan jejak digital pengaduan terjaga mulai dari input awal hingga penyelesaian, menjamin efisiensi dan akuntabilitas (<strong>accountability</strong>) proses.</p>
            ',
            'category' => 'Prosedur',
            'tags' => 'alur pengaduan, loket, petugas loket, disposisi otomatis, analis, track record',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 9: PROSEDUR MENERUSKAN LAPORAN KE K/L/D MELALUI FITUR TERUSKAN ---
        KmsArticle::create([
            'title' => 'Panduan Meneruskan Laporan ke K/L/D Melalui Fitur Teruskan (Sistem Lapor)',
            'content' => '
                <p>Fitur <strong>Teruskan</strong> digunakan untuk mengirim laporan pengaduan yang sudah selesai ditelaah, namun membutuhkan tindak lanjut lebih lanjut dari Kementerian, Lembaga, atau Pemerintah Daerah (K/L/D) terkait melalui sistem Lapor.</p>
                
                <h4 class="h5 mt-4">Prosedur Penerusan Laporan</h4>
                <ol>
                    <li><strong>Prasyarat:</strong> Pastikan laporan pengaduan telah selesai ditelaah oleh Analis dan diputuskan untuk diteruskan ke pihak eksternal.</li>
                    <li><strong>Akses Fitur:</strong> Pada halaman detail pengaduan, klik tombol <strong>Teruskan</strong>.</li>
                    <li><strong>Pilih Institusi Tujuan:</strong> Anda akan diminta untuk memilih <strong>Institusi (K/L/D)</strong> yang dituju untuk menerima dan menindaklanjuti laporan ini.</li>
                    <li><strong>Input Catatan dan Data Pelapor:</strong>
                        <ul>
                            <li>Anda dapat menambahkan <strong>Catatan</strong> (jika ada) yang berisi instruksi atau konteks tambahan untuk institusi tujuan.</li>
                            <li>Lakukan <strong>Ceklis</strong> jika Anda ingin <strong>menyertakan data pelapor</strong> (identitas pengadu) dalam laporan yang diteruskan. Jika tidak diceklis, laporan akan dikirim secara anonim.</li>
                        </ul>
                    </li>
                    <li><strong>Kirim Laporan:</strong> Klik tombol <strong>Teruskan Laporan</strong> dan tunggu hingga proses pengiriman selesai.</li>
                </ol>

                <h4 class="h5 mt-4">Monitoring dan Tindak Lanjut (TL) Laporan Diteruskan</h4>
                <ol start="6">
                    <li><strong>Halaman Monitoring:</strong> Laporan yang telah berhasil dikirim akan muncul di menu <strong>Laporan Diteruskan</strong>. Menu ini berfungsi sebagai alat monitoring untuk memantau status laporan di pihak K/L/D tujuan.</li>
                    <li><strong>Perubahan Status Otomatis:</strong> Status laporan pada tabel monitoring akan otomatis berubah sesuai tindak lanjut dari institusi terkait:
                        <ul>
                            <li><strong>Belum Terverifikasi:</strong> Laporan telah diterima oleh sistem Lapor, tetapi institusi tujuan belum mengambil tindakan apa pun (belum dikerjakan).</li>
                            <li><strong>Sudah Terverifikasi:</strong> Institusi tujuan telah memverifikasi laporan dan sudah <strong>menurunkan tugas ke satuan kerja</strong> mereka untuk memulai proses penelaahan.</li>
                            <li><strong>Berubah Otomatis:</strong> Status laporan akan terus diperbarui secara otomatis sesuai perkembangan yang dicatat di sistem Lapor.</li>
                        </ul>
                    </li>
                    <li><strong>Melihat Jejak (Track) Laporan:</strong> Terdapat tombol <strong>Detail TL</strong> (Tindak Lanjut). Aksi ini akan memunculkan semua <strong>track record</strong> atau riwayat laporan pengaduan, menunjukkan sudah sampai mana proses laporan tersebut di sistem Lapor.</li>
                    <li><strong>Jawaban Final dan Notifikasi Pengadu:</strong>
                        <ul>
                            <li>Jika sudah terdapat <strong>jawaban final</strong> dari K/L/D melalui sistem Lapor, petugas akan diminta untuk <strong>merubah status laporan</strong> dan mengisikan tanggapan akhir.</li>
                            <li>Tanggapan akhir ini yang kemudian dapat <strong>dilihat oleh pengadu</strong> melalui fitur Cek Status di <strong>WhatsApp Lapor Mas Wapres</strong> pada nomor: <strong>0811 - 1704 - 2204</strong>.</li>
                        </ul>
                    </li>
                </ol>

                <p class="alert alert-warning">Fitur ini memastikan interoperabilitas dan akuntabilitas tindak lanjut laporan pengaduan yang melibatkan entitas K/L/D eksternal.</p>
            ',
            'category' => 'Prosedur',
            'tags' => 'teruskan laporan, Lapor, K/L/D, tindak lanjut, monitoring, cek status whatsapp',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 10: PANDUAN PENANGANAN ADUAN BANTUAN UMKM ---
        \App\Models\KmsArticle::create([
            'title' => 'Panduan Penanganan Aduan Bantuan UMKM (D2/D4)',
            'content' => '
                <p>Bantuan UMKM diperuntukkan bagi pemohon yang sudah memiliki usaha dan berkelanjutan (kategori D2/D4).</p>
                
                <h4 class="h5 mt-4">Prosedur Penanganan di Counter LMW</h4>
                <ol>
                    <li><strong>Mencocokkan persyaratan pemohon</strong> dengan ketentuan di masing-masing dinas UMKM di daerah. Kantor dinas dipilih berdasarkan kesesuaian tempat tinggal KTP pengadu.</li>
                    <li><strong>Menjelaskan kewajiban registrasi UMKM</strong> melalui pendaftaran NIB. Salah satu persyaratan NIB adalah pengusaha sudah memiliki NPWP dan akta pendirian perusahaan.</li>
                    <li>Petugas harus mencatat <strong>Kronologis Peristiwa</strong> dan <strong>Kategori usaha</strong> pengadu.</li>
                    <li>Verifikasi kesediaan pengadu untuk mengikuti <strong>pelatihan dan pendampingan</strong> dari Dinas setempat.</li>
                </ol>
                
                <h4 class="h5 mt-4">Langkah Akhir (Jika Dokumen Tidak Lengkap)</h4>
                <p>Apabila pengadu tidak membawa dokumen lengkap, pelaporan dikategorikan <strong>Banmas (D4)</strong>. Petugas harus:</p>
                <ul>
                    <li>Menginformasikan bahwa bantuan usaha <strong>bukan prioritas</strong> bantuan Presiden/Wakil Presiden.</li>
                    <li>Menuliskan keterangan bahwa sudah diinfokan ke pengadu, bukan prioritas banpres.</li>
                    <li>Laporan pengadu tetap diproses, di-print slip khusus pengadu, dan diinformasikan ke pengadu.</li>
                </ul>
            ',
            'category' => 'Penanganan Aduan Sektor Ekonomi',
            'tags' => 'UMKM, bantuan usaha, NIB, NPWP, D2, D4',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 11: DTKS & BANTUAN LANSIA ---
        \App\Models\KmsArticle::create([
            'title' => 'Prosedur Pendaftaran Data Terpadu Kesejahteraan Sosial (DTKS) dan Bantuan Lansia',
            'content' => '
                <p>Pelaporan permohonan pendaftaran DTKS tetap dicatat melalui dashboard LMW, namun pengadu disarankan untuk aktif mendaftar ke Kelurahan domisili.</p>
                
                <h4 class="h5 mt-4">Syarat Umum Pendaftaran DTKS</h4>
                <ul>
                    <li>Merupakan warga miskin atau rentan miskin.</li>
                    <li>Bukan pensiunan atau <strong>ASN, TNI/POLRI</strong>, maupun karyawan aktif BUMN/BUMD.</li>
                    <li>Tidak memiliki anggota keluarga yang menjadi anggota DPR/DPRD.</li>
                    <li>Memiliki kartu identitas, yaitu <strong>KTP dan Kartu Keluarga (KK)</strong>.</li>
                </ul>

                <h4 class="h5 mt-4">Proses Pendaftaran Mandiri</h4>
                <ol>
                    <li>Datang ke kantor kelurahan atau desa setempat.</li>
                    <li>Membawa <strong>KTP dan KK asli</strong> serta salinannya.</li>
                    <li>Mengisi formulir pendaftaran dibantu oleh petugas Kelurahan.</li>
                    <li>Formulir akan dilanjutkan ke musyawarah kelurahan atau desa untuk persetujuan.</li>
                    <li>Data pemohon DTKS diverifikasi dan divalidasi oleh petugas dinas sosial, kemudian diusulkan ke Kementerian Sosial.</li>
                </ol>
                <p class="mt-3">Pemohon DTKS dapat mengecek status pendaftaran secara berkala melalui situs <strong>cekbansos.kemensos.go.id</strong>.</p>
                
                <h4 class="h5 mt-4">Penanganan Bantuan Lansia (D2)</h4>
                <p>Petugas tetap mencatatkan pelaporan tetapi berupaya menawarkan agar pelapor melapor juga ke Kelurahan sesuai domisili, dengan syarat:</p>
                <ul>
                    <li>Berusia <strong>60 tahun ke atas</strong>.</li>
                    <li>Tidak memiliki penghasilan tetap.</li>
                    <li>Terdaftar dalam <strong>DTKS</strong> atau DTSEN.</li>
                </ul>
            ',
            'category' => 'Penanganan Aduan Sektor Sosial',
            'tags' => 'DTKS, Bansos, Bantuan Lansia, KTP, KK',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 12: PERMOHONAN MENCARI KERJA ---
        \App\Models\KmsArticle::create([
            'title' => 'Panduan Pengaduan Permohonan Mencari Kerja (D2)',
            'content' => '
                <p>Pengaduan permohonan mencari kerja dicatat oleh petugas, tetapi fokus utama adalah mengarahkan pengadu untuk aktif menggunakan platform yang disediakan pemerintah.</p>
                
                <h4 class="h5 mt-4">Prosedur dan Pengarahan</h4>
                <ol>
                    <li>Petugas counter mencatat keluhan pengadu dan mengarahkan pengadu untuk menggunakan platform bernama <strong>SIAPkerja</strong>.</li>
                    <li>Platform <strong>SIAPkerja</strong> ($\text{https://siapkerja.kemnaker.go.id/}$) disediakan oleh Kementerian Ketenagakerjaan (Kemenaker). Salah satu portal di dalamnya adalah **Karirhub** (portal lowongan pekerjaan).</li>
                    <li><strong>Catatan Kritis:</strong> Apabila pengadu kurang dapat bekerja sama, pengaduan cukup dilaporkan, dicetak slip, tetapi <strong>tidak dijanjikan</strong> dapat ditindaklanjuti karena kurangnya data dukung.</li>
                </ol>
                
                <h4 class="h5 mt-4">Kewajiban Pengadu</h4>
                <p>Untuk permohonan mencari pekerjaan, yang terpenting adalah pengadu mau mencoba mendaftar mandiri ke <strong>SIAPkerja</strong>.</p>
            ',
            'category' => 'Penanganan Aduan Sektor Ketenagakerjaan',
            'tags' => 'SIAPkerja, Karirhub, lowongan kerja, Kemenaker, D2',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 13: KJP PLUS & KIP ---
        \App\Models\KmsArticle::create([
            'title' => 'Syarat dan Prosedur Permohonan Kartu Pelajar (KJP Plus & KIP)',
            'content' => '
                <p>Pelaporan permohonan Kartu Jakarta Pintar (KJP) Plus dan Kartu Indonesia Pintar (KIP) tetap dicatat, tetapi pengadu harus secara aktif berkoordinasi dengan pihak sekolah/madrasah dan mendaftar mandiri.</p>
                
                <h4 class="h5 mt-4">A. Kartu Jakarta Pintar (KJP) Plus</h4>
                <p>Target Penerima: Warga DKI Jakarta usia sekolah (6-21 tahun) dari keluarga tidak mampu.</p>
                <ul>
                    <li><strong>Syarat Utama:</strong> Berdomisili dan memiliki <strong>KTP serta KK DKI Jakarta</strong>. Terdaftar dalam <strong>DTKS</strong> dan/atau DTKS Daerah.</li>
                    <li><strong>Proses Pendaftaran:</strong>
                        <ol>
                            <li>Calon penerima harus terdaftar dalam DTKS.</li>
                            <li>Pemerintah Provinsi DKI Jakarta akan memadankan data DTKS dengan <strong>DAPODIK (sekolah)</strong> dan <strong>EMIS (madrasah)</strong>.</li>
                            <li>Pihak sekolah/madrasah akan mengumumkan siswa yang lolos verifikasi. Orang tua murid perlu berkoordinasi dengan pihak sekolah untuk menginput data siswa ke dalam sistem KJP.</li>
                        </ol>
                    </li>
                </ul>
                
                <h4 class="h5 mt-4">B. Kartu Indonesia Pintar (KIP) - Kuliah</h4>
                <p>KIP ditujukan untuk siswa dari jenjang SD hingga SMA/SMK/Kuliah yang berasal dari keluarga kurang mampu.</p>
                <ul>
                    <li><strong>Kriteria:</strong> Siswa atau orang tuanya harus terdaftar sebagai penerima salah satu program bantuan sosial (KKS, PKH) atau terdaftar dalam <strong>DTKS</strong> Kementerian Sosial.</li>
                    <li><strong>Surat Keterangan:</strong> Jika tidak memiliki kartu bantuan sosial, lampirkan <strong>Surat Keterangan Tidak Mampu (SKTM)</strong> dari kelurahan.</li>
                    <li><strong>Proses Pendaftaran:</strong> Pendaftaran KIP Kuliah dilakukan secara online melalui situs resmi <strong>kip-kuliah.kemdikbud.go.id</strong>.</li>
                </ul>
            ',
            'category' => 'Penanganan Aduan Sektor Pendidikan',
            'tags' => 'KJP Plus, KIP, DTKS, Sekolah, Kuliah, D2',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 14: PENYELESAIAN MASALAH KJMU ---
        \App\Models\KmsArticle::create([
            'title' => 'Penyelesaian Masalah KJMU dan Prosedur Pengaduan Alternatif (D2)',
            'content' => '
                <p>Pengaduan terkait pemutusan atau masalah KJMU dicatat, namun petugas harus menginformasikan bahwa penetapan dan masa sanggah KJMU/KJP memiliki periode waktu yang ketat (misalnya, April-Mei 2025).</p>
                
                <h4 class="h5 mt-4">Dokumen yang Perlu Diperiksa Petugas</h4>
                <ul>
                    <li><strong>KTP dan Kartu Keluarga (KK).</strong></li>
                    <li>Surat Keterangan Tidak Mampu (SKTM) dari kelurahan.</li>
                    <li>Surat Keterangan Mahasiswa Aktif dari perguruan tinggi.</li>
                    <li>Bukti-bukti lain yang relevan, misalnya jika ada kesalahan data terkait kepemilikan aset.</li>
                </ul>
                
                <h4 class="h5 mt-4">Alternatif dan Kanal Pengaduan Resmi</h4>
                <p>Pengadu disarankan melapor secara mandiri melalui kanal pengaduan resmi untuk menyampaikan keberatan secara formal.</p>
                <ul>
                    <li><strong>Situs Web Resmi:</strong> Kunjungi $\text{kjp.jakarta.go.id}$ atau $\text{p4op.jakarta.go.id/kjmu}$ untuk formulir pengaduan.</li>
                    <li><strong>Media Sosial:</strong> Cek akun resmi Instagram <strong>@upt.p4op</strong>.</li>
                    <li><strong>Telepon/WhatsApp:</strong> Hubungi nomor yang disediakan Disdik DKI Jakarta (Contoh: <strong>081585958706</strong>).</li>
                </ul>
            ',
            'category' => 'Penanganan Aduan Sektor Pendidikan',
            'tags' => 'KJMU, KJP, Sanggah, Disdik DKI, D2',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 15: KERINGANAN HUTANG BANK/BPD ---
        \App\Models\KmsArticle::create([
            'title' => 'Penanganan Aduan Permohonan Keringanan Hutang Bank/BPD (D1)',
            'content' => '
                <p>Petugas LMW mencatat laporan ini, namun perlu diinformasikan bahwa <strong>saat ini tidak ada pemutihan hutang dari Bank</strong>. Debitur hanya bisa mengajukan keringanan bunga, penundaan pembayaran tenor, atau keringanan perhitungan angsuran.</p>
                
                <h4 class="h5 mt-4">Dokumen yang Harus Dilengkapi Pemohon</h4>
                <p>Dokumen yang harus dilengkapi pemohon sama dengan jika menghadap ke Bank/BPD langsung:</p>
                <ol>
                    <li><strong>Surat Permohonan:</strong> Berisi alasan yang jelas mengapa keringanan dibutuhkan.</li>
                    <li><strong>Dokumen Identitas:</strong> KTP, KK, dan dokumen identitas lainnya.</li>
                    <li><strong>Dokumen Bukti Keuangan:</strong> Slip gaji, laporan keuangan usaha, atau surat keterangan kehilangan/penurunan pendapatan.</li>
                    <li><strong>Dokumen Kredit:</strong> Perjanjian (akad) kredit, bukti pembayaran cicilan sebelumnya, dan rekening koran angsuran.</li>
                </ol>

                <p class="mt-3"><strong>Catatan:</strong> Sebagai alternatif, pengadu disarankan untuk melapor/mengajukan permohonan <strong>langsung ke Bank atau BPD</strong> agar pihak bank dapat melakukan penilaian atau *assessment* terhadap permohonan keringanan kredit.</p>
            ',
            'category' => 'Penanganan Aduan Sektor Keuangan',
            'tags' => 'Keringanan Cicilan, Restrukturisasi, Bank, BPD, Debitur, D1',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 16: PEMUTIHAN BI CHECKING/SLIK ---
        \App\Models\KmsArticle::create([
            'title' => 'Prosedur Permohonan Pemutihan BI Checking / SLIK OJK (D1)',
            'content' => '
                <p>Petugas mencatat pengaduan dan mencetak slip, tetapi tetap menyarankan pengadu melapor secara mandiri melalui kanal resmi OJK.</p>
                
                <h4 class="h5 mt-4">Langkah Pendaftaran Mandiri di iDebKu OJK</h4>
                <ol>
                    <li><strong>Siapkan Dokumen:</strong> Untuk perorangan (KTP/Paspor) atau Badan Usaha (KTP/Paspor pengurus, NPWP, Akta Pendirian).</li>
                    <li><strong>Mendaftar:</strong> Akses situs web resmi iDebKu OJK di $\text{https://idebku.ojk.go.id}$.</li>
                    <li><strong>Unggah dan Swafoto:</strong> Unggah dokumen persyaratan dan lakukan swafoto (*selfie*) dengan memegang KTP.</li>
                    <li><strong>Ajukan Permohonan:</strong> Setelah mengisi data, klik tombol "Ajukan Permohonan".</li>
                    <li><strong>Cek Status:</strong> Gunakan nomor pendaftaran untuk mengecek status permohonan Anda di menu "Status Layanan". OJK akan memproses dan mengirim hasilnya ke email.</li>
                </ol>
                
                <h4 class="h5 mt-4">Kategori Kolektibilitas (Kol) pada Laporan SLIK</h4>
                <p>Petugas perlu memahami status SLIK untuk memberikan informasi yang akurat:</p>
                <ul>
                    <li><strong>Kol 1 (Lancar):</strong> Memenuhi kewajiban membayar cicilan tepat waktu.</li>
                    <li><strong>Kol 5 (Macet):</strong> Terlambat membayar cicilan lebih dari 180 hari (Status ini menyulitkan pengajuan pinjaman di masa depan).</li>
                </ul>
            ',
            'category' => 'Penanganan Aduan Sektor Keuangan',
            'tags' => 'BI Checking, SLIK, OJK, Kredit Macet, Kolektibilitas, D1',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 17: PENIPUAN ONLINE & PENDAMPINGAN OSS ---
        \App\Models\KmsArticle::create([
            'title' => 'Prosedur Pelaporan Penipuan Online dan Pendampingan OSS (D1)',
            'content' => '
                <h4 class="h5 mt-4">A. Penanganan Aduan Penipuan Online (Perlindungan Konsumen)</h4>
                <p>Pelaporan penipuan online merupakan sektor jasa keuangan yang ditangani oleh OJK. Pelaporan aktif oleh pengadu ke kanal resmi sangat diperlukan.</p>
                <ul>
                    <li><strong>Dokumen Wajib:</strong> Buku Kepemilikan Rekening, Bukti Transfer, Nomor Telepon/Rekening Penipu, Kronologis Peristiwa, Jumlah Kerugian.</li>
                    <li><strong>Tindakan Petugas:</strong> Dapat membantu pelaporan melalui *counter* selama pelapor memiliki email yang sama dengan yang didaftar pada saat membuka akun bank.</li>
                    <li><strong>Kanal Wajib:</strong> Bukti lapor ke <strong>iasc.ojk.go.id</strong> dan bukti lapor ke Bank penerbit (pemilik rekening-pelapor).</li>
                </ul>

                <h4 class="h5 mt-4">B. Bantuan Pendampingan Online Single Submission (OSS)</h4>
                <ul>
                    <li><strong>Pengarahan:</strong> Pengadu diarahkan agar <strong>langsung mendatangi Dinas Perdagangan atau Kantor PTSP di daerahnya</strong> dengan membawa dokumen (KTP, Akta pendirian, NPWP, Izin Usaha).</li>
                    <li><strong>Keterbatasan LMW:</strong> Kategori ini tidak bisa ditindaklanjuti bagi pemohon yang belum pernah berkonsultasi ke PTSP atau Dinas Perdagangan.</li>
                    <li><strong>Tindak Lanjut:</strong> Petugas mencatat laporan sekaligus saran agar pengadu menghubungi PTSP terlebih dahulu.</li>
                </ul>
            ',
            'category' => 'Penanganan Aduan Sektor Hukum',
            'tags' => 'Penipuan Online, OJK, OSS, PTSP, Hukum, D1',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);

        // --- ARTIKEL 18: KREDIT PERUMAHAN, SENGKETA TANAH, DAN PERUMAHAN SOSIAL ---
        \App\Models\KmsArticle::create([
            'title' => 'Checklist Dokumen Sengketa Tanah, Kredit Perumahan, dan Perbaikan Rusun (D1/D3)',
            'content' => '
                <h4 class="h5 mt-4">A. Pengaduan Sengketa/Aset Tanah (D3)</h4>
                <p>Berkaitan dengan hukum dan status aset. Kelengkapan minimal yang perlu petugas *crosscheck* adalah:</p>
                <ul>
                    <li><strong>Dokumen Tanah:</strong> Sertifikat tanah, girik, atau bukti kepemilikan lain yang relevan (surat jual beli, akta waris).</li>
                    <li><strong>Bukti Pendukung:</strong> Surat pernyataan saksi, foto, atau dokumen lain yang relevan dengan sengketa.</li>
                    <li><strong>Bukti Lain:</strong> Laporan ke kepolisian, hasil pengadilan, atau bukti mediasi.</li>
                </ul>

                <h4 class="h5 mt-4">B. Perbaikan WC, Sewa Rusun, atau Perumahan (D1)</h4>
                <p>Laporan dicatat, namun pengadu disarankan untuk melapor secara mandiri ke kanal resmi.</p>
                <ul>
                    <li><strong>Data Pendukung:</strong> Lokasi rumah/lingkungan, perincian tunggakan sewa Rusun, Surat Keterangan Tidak Mampu (SKTM).</li>
                    <li><strong>Saran Mandiri:</strong> Pengadu disarankan melapor melalui Program BENAR PKP pada WhatsApp nomor <strong>0812-88888-911</strong> atau ke Dinas Perumahan dan Pemukiman setempat.</li>
                    <li><strong>Program Pemerintah:</strong> Kementerian PUPR memiliki program seperti <strong>BSPS</strong> (Bantuan Stimulan Perumahan Swadaya) dan <strong>SANIMAS</strong> (Sanitasi Berbasis Masyarakat).</li>
                </ul>
            ',
            'category' => 'Penanganan Aduan Sektor Infrastruktur',
            'tags' => 'Sengketa Tanah, Rusun, Perbaikan WC, SKTM, D1, D3, BSPS',
            'is_active' => true,
            'user_id' => $adminUser->id,
        ]);
    }
}
