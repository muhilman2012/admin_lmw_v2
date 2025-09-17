# Proyek Admin Lapor Mas Wapres!
<p align="center">Sistem manajemen laporan dan pengaduan.</p>

<p align="center">
<a href="https://lapormaswapres.id" target="_blank"><img src="https://lapormaswapres.id/images/logo.png" width="200" alt="LMW Logo"></a>
</p>

## Daftar Isi
- [Prasyarat](#prasyarat)
- [Instalasi](#instalasi)
- [Struktur Aplikasi](#struktur-aplikasi)
- [Catatan Penting](#catatan-penting)

---

## Prasyarat
Pastikan Anda telah menginstal **Docker** dan **Docker Compose** di sistem Anda.

---

## Instalasi
Ikuti langkah-langkah berikut untuk menginstal dan menjalankan proyek:

1.  **Clone Repositori**
    ```bash
    git clone [https://github.com/muhilman2012/admin_lmw_v2.git](https://github.com/muhilman2012/admin_lmw_v2.git)
    cd admin_lmw_v2
    ```

2.  **Konfigurasi Environment**
    Salin file `.env.example` ke `.env`.
    ```bash
    cp .env.example .env
    ```
    Sesuaikan variabel berikut di file `.env` Anda:
    - **Database:**
        ```ini
        DB_CONNECTION=mysql
        DB_HOST=mysql
        DB_PORT=3306
        DB_DATABASE=db_admin_lmw
        DB_USERNAME=sail
        DB_PASSWORD=password

        FILESYSTEM_DISK=uploads
        ```
    - **MinIO:**
        ```ini
        AWS_ACCESS_KEY_ID=sail
        AWS_SECRET_ACCESS_KEY=password
        AWS_DEFAULT_REGION=us-east-1
        AWS_ENDPOINT=http://minio:9000
        AWS_USE_PATH_STYLE_ENDPOINT=true
        AWS_URL=http://localhost:9000
        AWS_URL_TEMPORARY=http://localhost:9000
        AWS_TEMPORARY_URL=http://localhost:9000
        AWS_BUCKET=lmw-uploads
        AWS_UPLOADS_BUCKET=lmw-uploads
        AWS_COMPLAINT_BUCKET=complaint-documents
        ```

3.  **Jalankan Laravel Sail**
    ```bash
    ./vendor/bin/sail up -d
    ```

4.  **Instal Dependensi & Migrasi Database**
    ```bash
    ./vendor/bin/sail composer install
    ./vendor/bin/sail artisan migrate --seed
    ```

5.  **Akses Aplikasi**
    Buka `http://localhost/admin/login` di browser Anda.

---

## Struktur Aplikasi
### Teknologi
- **Backend:** Laravel 12
- **Frontend:** Livewire 3 & Tabler
- **Database:** MySQL
- **Container:** Docker & Laravel Sail
- **Penyimpanan File:** MinIO

### Fitur Utama
- **Manajemen Pengguna:** Mengelola pengguna, role (berbasis Spatie), dan permission.
- **Manajemen Laporan:** Datatable interaktif untuk melihat, menyaring, dan mengelola semua laporan pengaduan.
- **Detail Laporan:** Halaman detail untuk setiap laporan, menampilkan informasi pengadu, log aktivitas, dan dokumen pendukung.
- **Pembuatan Laporan:** Form khusus untuk membuat laporan baru, dengan opsi untuk mengunggah multiple file menggunakan Dropzone.js.
- **Daftar Pengadu:** Daftar pengadu yang antre untuk membuat laporan, dengan fungsionalitas pencarian.

---

## Catatan Penting
- **MinIO Bucket Policy:** Untuk melihat dokumen yang diunggah, pastikan Anda telah mengatur **bucket policy** di MinIO dashboard untuk mengizinkan akses baca publik.
- **Perintah Sail:** Semua perintah Artisan (`migrate`, `seed`, dll.) harus diawali dengan `./vendor/bin/sail`.
- **URL Relatif:** Gunakan helper `Storage::disk('complaints')->url(...)` di view untuk mengakses file dari MinIO.
- **Kredensial MinIO:**
    - **Dashboard:** `http://localhost:9000`
    - **Access Key:** `sail`
    - **Secret Key:** `password`
