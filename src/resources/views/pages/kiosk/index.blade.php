<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kiosk LMW - LaporMasWapres!</title>

    <link rel="icon" href="{{ asset('tabler/img/logo/LaporMasWapres.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('tabler/img/logo/LaporMasWapres.png') }}">
    <link href="{{ asset('tabler/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/css/tabler-icons.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/css/sweetalert2.min.css') }}">

    <style>
        @import url("{{ asset('tabler/fonts/inter.css') }}");
        body { font-family: 'Inter', sans-serif; background-color: #f4f6fa; overflow-x: hidden; user-select: none; }
        
        .kiosk-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; padding: 20px; }
        .screen { display: none; width: 100%; max-width: 900px; animation: slideUp 0.4s ease-out; }
        .screen.active { display: block; }
        
        /* Tombol Utama */
        .btn-kiosk { padding: 3rem 2rem; border-radius: 24px; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); border: none; position: relative; overflow: hidden; }
        .btn-kiosk:hover { transform: scale(1.03); box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important; }
        .icon-lg { font-size: 5rem; margin-bottom: 1.5rem; display: block; }
        
        /* Camera & Scanner Layout */
        #reader { width: 100%; border-radius: 16px; overflow: hidden; border: none !important; background: #000; min-height: 300px; }
        #my_camera { background: #000; border-radius: 16px; margin: 0 auto; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        #photo-preview img { border-radius: 16px; border: 4px solid #fff; max-width: 100%; height: auto; }

        .form-control-xl { padding: 1.2rem; font-size: 1.25rem; border-radius: 12px; }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Styling khusus struk thermal */
        @media print {
            body * { visibility: hidden; }
            #print-area, #print-area * { visibility: visible; }
            #print-area {
                position: absolute;
                left: 0; top: 0;
                width: 80mm;
                font-family: 'Courier New', monospace;
                color: #000;
                padding: 2mm;
                display: block !important;
            }
            .text-center { text-align: center; }
            .hr-dashed { border-top: 1px dashed #000; margin: 5px 0; }
            .q-num { font-size: 45pt; font-weight: bold; margin: 10px 0; display: block; }
        }
        
        #print-area { display: none; }
        .kiosk-footer { position: absolute; bottom: 20px; width: 100%; text-align: center; color: #666; }
    </style>
</head>
<body>

<div class="kiosk-wrapper">
    
    <div class="screen active text-center" id="screen-home">
        <img src="{{ asset('tabler/img/logo/LaporMasWapres.png') }}" style="height: 100px;" class="mb-5">
        <h1 class="display-3 fw-bold mb-2">Selamat Datang</h1>
        <p class="h2 text-muted mb-5">Pilih metode kedatangan Anda</p>
        
        <div class="row g-4">
            <div class="col-6">
                <button class="card btn-kiosk bg-primary text-white w-100 shadow-lg" onclick="showScreen('screen-checkin')">
                    <i class="ti ti-qrcode icon-lg"></i>
                    <span class="h1 fw-bold mb-1">CHECK-IN</span>
                    <p class="h3 mb-0 opacity-75">Sudah Memiliki Reservasi</p>
                </button>
            </div>
            <div class="col-6">
                <button class="card btn-kiosk bg-success text-white w-100 shadow-lg" onclick="startOffline()">
                    <i class="ti ti-pencil-plus icon-lg"></i>
                    <span class="h1 fw-bold mb-1">DAFTAR BARU</span>
                    <p class="h3 mb-0 opacity-75">Registrasi di Tempat</p>
                </button>
            </div>
        </div>
    </div>

    <div class="screen" id="screen-checkin">
        <div class="card shadow-lg border-0" style="border-radius: 24px; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);">
            <div class="card-body p-5 text-center">
                <div class="mb-4">
                    <div class="avatar avatar-xl rounded-circle bg-primary-lt mb-3">
                        <i class="ti ti-barcode text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h1 class="fw-bold">Scan Kode Reservasi</h1>
                    <p class="text-muted h3">Silakan arahkan kode QR Anda ke alat pemindai <br> atau masukkan NIK secara manual di bawah ini.</p>
                </div>
                
                <div class="mb-5">
                    <input type="text" id="input_booking" 
                        class="form-control form-control-xl text-center fw-bold shadow-sm" 
                        placeholder="Ketik Kode / NIK di sini..." 
                        style="letter-spacing: 5px; font-size: 2.5rem; border: 3px solid #206bc4; height: 100px; border-radius: 20px;"
                        autofocus>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <button class="btn btn-lg btn-outline-secondary w-100 py-3" style="border-radius: 15px;" onclick="showScreen('screen-home')">
                            <i class="ti ti-arrow-left me-2"></i> Kembali
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-lg btn-primary w-100 py-3" style="border-radius: 15px;" onclick="processSearch()">
                            Cari Data <i class="ti ti-search ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="screen" id="screen-form">
        <div class="card shadow-lg border-0" style="border-radius: 24px;">
            <div class="card-header bg-light py-4 d-flex justify-content-between align-items-center">
                <h2 class="card-title h1 mb-0 fw-bold">Lengkapi Data</h2>
                <span class="badge bg-blue-lt h3 mb-0" id="badge-mode">Mode: Reservasi</span>
            </div>
            <div class="card-body p-5">
                <div class="row g-4">
                    <div class="col-md-6"><label class="form-label h3 fw-bold">NIK</label><input type="text" id="f_nik" class="form-control form-control-xl" maxlength="16"></div>
                    <div class="col-md-6"><label class="form-label h3 fw-bold">Nama Lengkap</label><input type="text" id="f_name" class="form-control form-control-xl"></div>
                    <div class="col-12"><label class="form-label h3 fw-bold">Topik Pengaduan</label><input type="text" id="f_subject" class="form-control form-control-xl"></div>
                    <div class="col-12"><label class="form-label h3 fw-bold">Alamat</label><textarea id="f_address" class="form-control form-control-xl" rows="2"></textarea></div>
                    <div class="col-md-6"><label class="form-label h3 fw-bold">WhatsApp</label><input type="text" id="f_phone" class="form-control form-control-xl"></div>
                    <div class="col-md-6"><label class="form-label h3 fw-bold">Email (Opsional)</label><input type="email" id="f_email" class="form-control form-control-xl"></div>
                </div>
                <div class="row g-3 mt-5">
                    <div class="col-6"><button class="btn btn-lg btn-outline-secondary w-100 py-3" onclick="handleBackForm()">Kembali</button></div>
                    <div class="col-6"><button class="btn btn-lg btn-primary w-100 py-3" onclick="validateAndNext()">Lanjut Foto KTP</button></div>
                </div>
            </div>
        </div>
    </div>

    <div class="screen text-center" id="screen-photo">
        <h1 class="display-5 fw-bold mb-4">Verifikasi Identitas</h1>
        <div id="my_camera" class="mb-4"></div>
        <div id="photo-preview" class="d-none mb-4"><img id="captured-image" src="" class="shadow-lg"></div>
        
        <div id="camera-controls">
            <button class="btn btn-success btn-xl px-5 py-4" onclick="capturePhoto()" style="border-radius: 50px;">AMBIL FOTO KTP</button>
            <div class="mt-4"><button class="btn btn-link h3" onclick="showScreen('screen-form')">Edit Data</button></div>
        </div>

        <div id="confirm-controls" class="d-none">
            <h2 class="text-warning fw-bold mb-4">Pastikan data KTP terbaca jelas!</h2>
            <button class="btn btn-outline-warning btn-lg px-5 me-2" onclick="retakePhoto()">Foto Ulang</button>
            <button class="btn btn-success btn-lg px-5" onclick="finalizeAll()">Selesai & Cetak</button>
        </div>
    </div>

    <div class="kiosk-footer">© 2026 LaporMasWapres!</div>
</div>

<div class="modal modal-blur fade" id="modal-success-print" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content text-center py-4">
            <div class="modal-body">
                <i class="ti ti-circle-check text-success" style="font-size: 4rem;"></i>
                <h1 class="mt-3 mb-1" id="display-queue-number" style="font-size: 5rem; font-weight: 800;">000</h1><br>
                <p class="h2">NOMOR ANTREAN ANDA</p>
                <div class="progress progress-sm mt-3"><div class="progress-bar progress-bar-indeterminate"></div></div>
            </div>
        </div>
    </div>
</div>

<div id="print-area"></div>

<script src="{{ asset('tabler/js/tabler.min.js') }}"></script>
<script src="{{ asset('assets/libs/html5-qrcode.min.js') }}"></script>
<script src="{{ asset('assets/libs/webcam.min.js') }}"></script>
<script src="{{ asset('assets/js/axios.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>

<script>
    // --- 1. Inisialisasi Axios & Security ---
    // Pastikan meta tag csrf-token sudah ada di head layout
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
    }
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    axios.defaults.headers.common['Accept'] = 'application/json';

    let qrScanner = null; 
    let base64Photo = null;
    let isOfflineMode = false;
    let currentData = null;

    // --- 2. Fungsi Finalize (Post Data) ---
    async function finalizeAll() {
        if (!base64Photo) {
            return Swal.fire('Error', 'Foto KTP wajib diambil.', 'error');
        }

        // Tampilkan Loading
        Swal.fire({ 
            title: 'Menyimpan Data...', 
            allowOutsideClick: false, 
            didOpen: () => Swal.showLoading() 
        });

        // Kumpulkan Payload
        const payload = {
            nik: document.getElementById('f_nik').value,
            name: document.getElementById('f_name').value,
            address: document.getElementById('f_address').value,
            phone: document.getElementById('f_phone').value,
            email: document.getElementById('f_email').value,
            subject: document.getElementById('f_subject').value,
            ktp_image: base64Photo,
            // Jika offline kirim 'OFFLINE', jika online kirim kode bookingnya
            registration_number: isOfflineMode ? 'OFFLINE' : document.getElementById('input_booking').value
        };

        try {
            // URL menggunakan route name laravel
            const res = await axios.post("{{ route('kiosk.finalize') }}", payload);
            
            if (res.data.status === 'success') {
                Swal.close();
                const p = res.data.print_data;

                // Tampilkan Modal Berhasil
                document.getElementById('display-queue-number').innerText = p.queue_number;
                const successModal = new bootstrap.Modal(document.getElementById('modal-success-print'));
                successModal.show();

                // Proses Cetak Struk
                const logoUrl = "{{ asset('tabler/img/logo/LaporMasWapres.png') }}";
                const content = `
                    <div class="text-center">
                        <img src="${logoUrl}" style="width: 120px; height: auto; margin-bottom: 5px;">
                        <div class="hr-dashed"></div>
                        <p style="margin: 5px 0; font-size: 10pt;">NOMOR ANTREAN</p>
                        <div class="q-num">${p.queue_number}</div>
                        <div class="hr-dashed"></div>
                        <div class="info">
                            Waktu : ${p.time}<br>
                            Nama  : ${p.name.substring(0, 25)}<br>
                        </div>
                        <div class="hr-dashed"></div>
                        <p style="font-size: 8pt; margin-top: 10px;">Harap tunggu panggilan petugas.</p>
                    </div>`;

                printThermal(content);

                // Auto Reload setelah 6 detik
                setTimeout(() => { location.reload(); }, 6000);
            }
        } catch (e) {
            console.error('Finalize Error Detail:', e.response);
            const errorMsg = e.response?.data?.message || 'Terjadi kesalahan sistem atau koneksi storage.';
            
            Swal.fire({
                icon: 'error',
                title: 'Gagal Simpan',
                text: errorMsg,
                footer: 'Saran: Cek koneksi server atau permission folder storage.'
            });
        }
    }

    // --- HELPER: FUNGSI PRINT AMPUH (HIDDEN IFRAME) ---
    function printThermal(htmlContent) {
        const oldFrame = document.getElementById('print-frame');
        if (oldFrame) oldFrame.remove();

        const iframe = document.createElement('iframe');
        iframe.id = 'print-frame';
        iframe.style.display = 'none';
        document.body.appendChild(iframe);

        const doc = iframe.contentWindow.document;
        doc.open();
        doc.write(`
            <html>
                <head>
                    <style>
                        @page { size: 80mm auto; margin: 0; }
                        body { 
                            font-family: 'Courier New', Courier, monospace; 
                            width: 72mm; 
                            padding: 4mm; 
                            text-align: center; 
                            color: #000;
                            margin: 0;
                        }
                        img { 
                            filter: grayscale(100%);
                            max-width: 100%;
                        }
                        .hr-dashed { border-top: 1px dashed #000; margin: 8px 0; }
                        .q-num { font-size: 45pt; font-weight: bold; }
                        .info { text-align: left; font-size: 9pt; }
                    </style>
                </head>
                <body>${htmlContent}</body>
            </html>
        `);
        doc.close();

        // Beri jeda sedikit lebih lama (1 detik) agar gambar logo sempat ter-load
        setTimeout(() => {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        }, 1000);
    }

    function initCamera() {
        if (typeof Webcam === 'undefined') {
            Swal.fire('Error', 'Library Kamera tidak ditemukan.', 'error');
            return;
        }
        Webcam.set({
            width: 640, height: 480,
            image_format: 'jpeg', jpeg_quality: 90,
            constraints: { facingMode: "user" }
        });
        Webcam.attach('#my_camera');
    }

    function initScanner() {
        const readerElement = document.getElementById("reader");
        if (!readerElement) return;

        if (qrScanner === null) {
            qrScanner = new Html5QrcodeScanner("reader", { 
                fps: 10, qrbox: {width: 250, height: 250}, aspectRatio: 1.0 
            });
            qrScanner.render((text) => {
                document.getElementById('input_booking').value = text;
                processSearch();
            });
        }
    }

    function showScreen(id) {
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
        document.getElementById(id).classList.add('active');

        if(id === 'screen-photo') { 
            initCamera(); 
        } else { 
            Webcam.reset(); 
        }

        if(id === 'screen-checkin') { 
            const inputField = document.getElementById('input_booking');
            inputField.value = '';
            setTimeout(() => inputField.focus(), 500);
        }
    }

    // LISTENER UNTUK SCANNER FISIK (AUTO ENTER)
    document.getElementById('input_booking').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            processSearch();
        }
    });

    async function processSearch() {
        const input = document.getElementById('input_booking').value;
        if(!input) return;
        
        Swal.fire({ title: 'Mencari data...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        
        try {
            const res = await axios.post("{{ route('kiosk.check-data') }}", { search: input });
            currentData = res.data.data;
            isOfflineMode = false;
            
            document.getElementById('badge-mode').innerText = "Mode: Reservasi";
            document.getElementById('badge-mode').className = "badge bg-blue-lt h3";
            
            document.getElementById('f_nik').value = currentData.nik || '';
            document.getElementById('f_name').value = currentData.name || '';
            document.getElementById('f_address').value = currentData.address || '';
            document.getElementById('f_phone').value = currentData.phone || currentData.phone_number || '';
            document.getElementById('f_subject').value = currentData.subject || '';
            document.getElementById('f_email').value = currentData.email || '';

            Swal.close();
            showScreen('screen-form');
        } catch (e) {
            Swal.fire('Gagal', e.response?.data?.message || 'Data tidak ditemukan.', 'error');
        }
    }

    function validateAndNext() {
        const nik = document.getElementById('f_nik').value;
        const name = document.getElementById('f_name').value;
        const subj = document.getElementById('f_subject').value;
        if (nik.length !== 16 || !name || !subj) {
            return Swal.fire('Peringatan', 'NIK, Nama, dan Topik wajib diisi.', 'warning');
        }
        showScreen('screen-photo');
    }

    function capturePhoto() {
        Webcam.snap(uri => {
            base64Photo = uri;
            document.getElementById('captured-image').src = uri;
            document.getElementById('my_camera').classList.add('d-none');
            document.getElementById('photo-preview').classList.remove('d-none');
            document.getElementById('camera-controls').classList.add('d-none');
            document.getElementById('confirm-controls').classList.remove('d-none');
        });
    }

    function retakePhoto() {
        document.getElementById('my_camera').classList.remove('d-none');
        document.getElementById('photo-preview').classList.add('d-none');
        document.getElementById('camera-controls').classList.remove('d-none');
        document.getElementById('confirm-controls').classList.add('d-none');
    }

    function startOffline() {
        isOfflineMode = true;
        base64Photo = null;
        currentData = null;
        
        // Reset visual form
        resetForm();
        
        document.getElementById('badge-mode').innerText = "Mode: Registrasi Baru";
        document.getElementById('badge-mode').className = "badge bg-success-lt h3";
        
        showScreen('screen-form');
    }

    function resetForm() {
        document.querySelectorAll('#screen-form input, #screen-form textarea').forEach(el => el.value = '');
    }

    // Listener tombol checkin awal
    document.querySelector('button[onclick*="screen-checkin"]')?.addEventListener('click', () => {
        setTimeout(initScanner, 500);
    });

    function handleBackForm() {
        // Jika user sedang di Mode Daftar Baru, kembalikan ke Home
        if (isOfflineMode) {
            showScreen('screen-home');
        } 
        // Jika user sedang di Mode Reservasi, kembalikan ke layar Scan/Input Kode
        else {
            showScreen('screen-checkin');
        }
        
        // Opsional: Bersihkan form saat kembali agar data tidak nyangkut jika orang lain yang pakai
        resetForm();
    }

    Echo.channel('kiosk-channel')
        .listen('AntreanDipanggil', (e) => {
            playVoice(e.queueNumber, e.counterNumber);
        });

    function playVoice(queueNumber, counter) {
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
            const spelledQueue = queueNumber.split('').join(' ');
            const text = `Nomor antrean, ${spelledQueue}, silakan menuju loket, ${counter}`;
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'id-ID';
            utterance.rate = 0.8;
            window.speechSynthesis.speak(utterance);
        }
    }
</script>
</body>
</html>