<script src="{{ asset('tabler/js/tabler.min.js') }}" defer></script>
<script src="{{ asset('tabler/js/tabler-theme.min.js') }}" defer></script>
<script src="{{ asset('tabler/libs/list.js/dist/list.min.js') }}" defer></script>
<script src="{{ asset('tabler/libs/dropzone/dist/dropzone-min.js') }}" defer></script>
<script src="{{ asset('tabler/js/toast-trial.js') }}" defer></script>
<script src="{{ asset('tabler/libs/litepicker/dist/litepicker.js') }}" defer></script>
<script src="{{ asset('tabler/libs/tom-select/dist/js/tom-select.complete.min.js') }}" defer></script>
<script src="{{ asset('assets/js/loader-util.js') }}"></script>

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
            const { title, text, confirmButtonText, onConfirmed, onConfirmedParams } = event;
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
                    Livewire.dispatch(onConfirmed, { userId: onConfirmedParams[0] });
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
</script>
<script>
    document.getElementById('reset-changes').addEventListener('click', function() {
        localStorage.clear();
        location.reload();
    });
</script>