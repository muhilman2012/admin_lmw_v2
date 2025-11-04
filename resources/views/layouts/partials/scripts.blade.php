<script src="{{ asset('tabler/js/tabler.min.js') }}" defer></script>
<script src="{{ asset('tabler/js/tabler-theme.min.js') }}" defer></script>
<script src="{{ asset('tabler/libs/list.js/dist/list.min.js') }}" defer></script>
<script src="{{ asset('tabler/libs/dropzone/dist/dropzone-min.js') }}" defer></script>
<script src="{{ asset('tabler/js/toast-trial.js') }}" defer></script>
<script src="{{ asset('tabler/libs/litepicker/dist/litepicker.js') }}" defer></script>
<script src="{{ asset('tabler/libs/tom-select/dist/js/tom-select.complete.min.js') }}" defer></script>
<script src="{{ asset('assets/js/loader-util.js') }}"></script>
<script src="{{ asset('tabler/libs/apexcharts/dist/apexcharts.min.js') }}" defer></script>

<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const successMessage = "{{ session('success') }}";
        const errorMessage = "{{ session('error') }}";

        if (successMessage) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: successMessage,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        }

        if (errorMessage) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: errorMessage,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        }
    });
</script>

<div id="toast-placement" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080"></div>

@stack('scripts')

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('session:success', (data) => {
            const payload = data[0] || data; 
            const message = payload.message || 'Aksi berhasil!'; 

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        });

        Livewire.on('modal:show', (event) => {
            const modalElement = document.getElementById(event.id);
            if (modalElement) {
                const modalInstance = new bootstrap.Modal(modalElement);
                modalInstance.show();
            }
        });

        Livewire.on('modal:hide', (event) => {
            const modalInstance = bootstrap.Modal.getInstance(document.getElementById(event.id));
            if (modalInstance) {
                modalInstance.hide();
            }
        });
        
        document.querySelectorAll('.modal').forEach(modalEl => {
            modalEl.addEventListener('hidden.bs.modal', function () {
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            });
        });

        Livewire.on('swal:confirm', (event) => {
            const data = event.detail || {}; 
            
            const { title, text, confirmButtonText, onConfirmed, params } = data; 
            
            if (!title) {
                console.warn('SweetAlert dispatch failed: Title is missing.');
                return;
            }

            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: confirmButtonText,
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch(onConfirmed, params);
                }
            });
        });

        Livewire.on('swal:toast', (event) => {
            const message = event.message;
            const icon = event.icon;
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: icon,
                title: message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const settingsForm = document.getElementById('offcanvasSettings');
        const root = document.documentElement;
        
        if (!settingsForm) return;

        // Kunci yang digunakan Tabler (sesuaikan jika ada kustom)
        const themeKeys = [
            'data-bs-theme', 'theme-color', 'theme-font', 'theme-base', 'theme-radius'
        ];
        
        // --- 1. LISTENER UTAMA (APPLY & SAVE) ---
        // Tangkap perubahan radio/color input dalam offcanvas
        settingsForm.addEventListener('change', (e) => {
            const target = e.target;
            if (target.name && target.type === 'radio') {
                const key = target.name;
                const value = target.value;
                
                // 1. Simpan ke Local Storage
                localStorage.setItem(key, value);
                
                // 2. Terapkan (Memanggil Tabler's initTheme untuk membaca Local Storage dan menerapkan CSS Vars)
                if (typeof tabler !== 'undefined' && typeof tabler.initTheme === 'function') {
                    tabler.initTheme(); 
                } 
                // Catatan: Jika tabler.initTheme tidak ada, tema mungkin hanya berubah setelah refresh.
            }
        });

        // --- 2. LISTENER RESET ---
        document.getElementById('reset-changes').addEventListener('click', () => {
            if (confirm('Yakin ingin mereset semua pengaturan tema ke default?')) {
                
                // Hapus semua custom setting dari Local Storage
                themeKeys.forEach(key => {
                    localStorage.removeItem(key);
                });
                
                // Panggil initTheme() untuk memaksa Tabler JS menerapkan default settings
                if (typeof tabler !== 'undefined' && typeof tabler.initTheme === 'function') {
                    tabler.initTheme();
                } else {
                    window.location.reload(); 
                }
            }
        });

        // --- 3. SINKRONISASI RADIO BUTTONS SAAT OFFCANVAS DIBUKA ---
        settingsForm.addEventListener('show.bs.offcanvas', () => {
            themeKeys.forEach(key => {
                const savedValue = localStorage.getItem(key);
                if (savedValue) {
                    // Set input radio agar checked sesuai savedValue
                    const input = settingsForm.querySelector(`input[name="${key}"][value="${savedValue}"]`);
                    if (input) {
                        input.checked = true;
                    }
                }
            });
        });
    });
</script>