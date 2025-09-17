## Proyek Admin LaporMasWapres
Proyek ini adalah sistem manajemen untuk mengelola laporan dan pengaduan. Dibangun dengan Laravel 12, Livewire 3, dan Tabler. Lingkungan pengembangan diatur menggunakan Laravel Sail (Docker), dan penyimpanan file diurus oleh MinIO.

## Daftar Isi
Prasyarat
Instalasi
Struktur Aplikasi
Alur Kerja Git
Catatan Penting

## Prasyarat
Pastikan Anda telah menginstal Docker dan Docker Compose di sistem Anda.

## Instalasi
Ikuti langkah-langkah berikut untuk menginstal dan menjalankan proyek:

## Clone Repositori
Bash
git clone https://github.com/muhilman2012/admin_lmw_v2.git
cd admin_lmw_v2

## Konfigurasi Environment
Salin file .env.example ke .env.
Bash
cp .env.example .env

## Sesuaikan variabel berikut di file .env Anda:
Database:
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=db_admin_lmw
DB_USERNAME=sail
DB_PASSWORD=password

MinIO:
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=sail
AWS_SECRET_ACCESS_KEY=password
AWS_DEFAULT_REGION=us-east-1
AWS_ENDPOINT=http://minio:9000
AWS_URL=http://localhost:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_COMPLAINT_BUCKET=complaint-documents

## Jalankan Laravel Sail
Bash
./vendor/bin/sail up -d

## Instal Dependensi & Migrasi Database
Bash
./vendor/bin/sail composer install
./vendor/bin/sail artisan migrate --seed

## Akses Aplikasi
Buka http://localhost/admin/login di browser Anda.

## Struktur Aplikasi
Teknologi
Backend: Laravel 12
Frontend: Livewire 3 & Tabler
Database: MySQL
Container: Docker & Laravel Sail
Penyimpanan File: MinIO

## Fitur Utama
Manajemen Pengguna: Mengelola pengguna, role (berbasis Spatie), dan permission.
Manajemen Laporan: Datatable interaktif untuk melihat, menyaring, dan mengelola semua laporan pengaduan.
Detail Laporan: Halaman detail untuk setiap laporan, menampilkan informasi pengadu, log aktivitas, dan dokumen pendukung.
Pembuatan Laporan: Form khusus untuk membuat laporan baru, dengan opsi untuk mengunggah multiple file menggunakan Dropzone.js.
Daftar Pengadu: Daftar pengadu yang antre untuk membuat laporan, dengan fungsionalitas pencarian.

## Kredensial MinIO:
Dashboard: http://localhost:9000
Access Key: sail
Secret Key: password
