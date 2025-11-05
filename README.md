<<<<<<< HEAD
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
=======
# LMW



## Getting started

To make it easy for you to get started with GitLab, here's a list of recommended next steps.

Already a pro? Just edit this README.md and make it your own. Want to make it easy? [Use the template at the bottom](#editing-this-readme)!

## Add your files

- [ ] [Create](https://docs.gitlab.com/ee/user/project/repository/web_editor.html#create-a-file) or [upload](https://docs.gitlab.com/ee/user/project/repository/web_editor.html#upload-a-file) files
- [ ] [Add files using the command line](https://docs.gitlab.com/ee/gitlab-basics/add-file.html#add-a-file-using-the-command-line) or push an existing Git repository with the following command:

```
cd existing_repo
git remote add origin https://gigit.setneg.go.id/setwapres/lmw.git
git branch -M main
git push -uf origin main
```

## Integrate with your tools

- [ ] [Set up project integrations](https://gigit.setneg.go.id/setwapres/lmw/-/settings/integrations)

## Collaborate with your team

- [ ] [Invite team members and collaborators](https://docs.gitlab.com/ee/user/project/members/)
- [ ] [Create a new merge request](https://docs.gitlab.com/ee/user/project/merge_requests/creating_merge_requests.html)
- [ ] [Automatically close issues from merge requests](https://docs.gitlab.com/ee/user/project/issues/managing_issues.html#closing-issues-automatically)
- [ ] [Enable merge request approvals](https://docs.gitlab.com/ee/user/project/merge_requests/approvals/)
- [ ] [Automatically merge when pipeline succeeds](https://docs.gitlab.com/ee/user/project/merge_requests/merge_when_pipeline_succeeds.html)

## Test and Deploy

Use the built-in continuous integration in GitLab.

- [ ] [Get started with GitLab CI/CD](https://docs.gitlab.com/ee/ci/quick_start/index.html)
- [ ] [Analyze your code for known vulnerabilities with Static Application Security Testing(SAST)](https://docs.gitlab.com/ee/user/application_security/sast/)
- [ ] [Deploy to Kubernetes, Amazon EC2, or Amazon ECS using Auto Deploy](https://docs.gitlab.com/ee/topics/autodevops/requirements.html)
- [ ] [Use pull-based deployments for improved Kubernetes management](https://docs.gitlab.com/ee/user/clusters/agent/)
- [ ] [Set up protected environments](https://docs.gitlab.com/ee/ci/environments/protected_environments.html)

***

# Editing this README

When you're ready to make this README your own, just edit this file and use the handy template below (or feel free to structure it however you want - this is just a starting point!). Thank you to [makeareadme.com](https://www.makeareadme.com/) for this template.

## Suggestions for a good README
Every project is different, so consider which of these sections apply to yours. The sections used in the template are suggestions for most open source projects. Also keep in mind that while a README can be too long and detailed, too long is better than too short. If you think your README is too long, consider utilizing another form of documentation rather than cutting out information.

## Name
Choose a self-explaining name for your project.

## Description
Let people know what your project can do specifically. Provide context and add a link to any reference visitors might be unfamiliar with. A list of Features or a Background subsection can also be added here. If there are alternatives to your project, this is a good place to list differentiating factors.

## Badges
On some READMEs, you may see small images that convey metadata, such as whether or not all the tests are passing for the project. You can use Shields to add some to your README. Many services also have instructions for adding a badge.

## Visuals
Depending on what you are making, it can be a good idea to include screenshots or even a video (you'll frequently see GIFs rather than actual videos). Tools like ttygif can help, but check out Asciinema for a more sophisticated method.

## Installation
Within a particular ecosystem, there may be a common way of installing things, such as using Yarn, NuGet, or Homebrew. However, consider the possibility that whoever is reading your README is a novice and would like more guidance. Listing specific steps helps remove ambiguity and gets people to using your project as quickly as possible. If it only runs in a specific context like a particular programming language version or operating system or has dependencies that have to be installed manually, also add a Requirements subsection.

## Usage
Use examples liberally, and show the expected output if you can. It's helpful to have inline the smallest example of usage that you can demonstrate, while providing links to more sophisticated examples if they are too long to reasonably include in the README.

## Support
Tell people where they can go to for help. It can be any combination of an issue tracker, a chat room, an email address, etc.

## Roadmap
If you have ideas for releases in the future, it is a good idea to list them in the README.

## Contributing
State if you are open to contributions and what your requirements are for accepting them.

For people who want to make changes to your project, it's helpful to have some documentation on how to get started. Perhaps there is a script that they should run or some environment variables that they need to set. Make these steps explicit. These instructions could also be useful to your future self.

You can also document commands to lint the code or run tests. These steps help to ensure high code quality and reduce the likelihood that the changes inadvertently break something. Having instructions for running tests is especially helpful if it requires external setup, such as starting a Selenium server for testing in a browser.

## Authors and acknowledgment
Show your appreciation to those who have contributed to the project.

## License
For open source projects, say how it is licensed.

## Project status
If you have run out of energy or time for your project, put a note at the top of the README saying that development has slowed down or stopped completely. Someone may choose to fork your project or volunteer to step in as a maintainer or owner, allowing your project to keep going. You can also make an explicit request for maintainers.
>>>>>>> 8e2a70c82390feb0bbd6ac25f1902ffa08677aa3
