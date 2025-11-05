/**
 * Fungsi utilitas untuk menampilkan dan menyembunyikan overlay loading.
 */
window.appLoader = {
    /**
     * Menampilkan overlay loading dengan pesan opsional.
     * @param {string} text - Pesan yang ditampilkan.
     */
    show: function(text = 'Memproses data, mohon tunggu sebentar...') {
        const overlay = document.getElementById('page-loader-overlay');
        const textElement = document.getElementById('loader-text');
        
        if (overlay) {
            if (textElement) {
                textElement.textContent = text;
            }
            // Hapus kelas d-none untuk menampilkan loader
            overlay.classList.remove('d-none');
            // Pastikan body tidak scrolling di belakang loader (Opsional)
            document.body.style.overflow = 'hidden'; 
        }
    },

    /**
     * Menyembunyikan overlay loading.
     */
    hide: function() {
        const overlay = document.getElementById('page-loader-overlay');
        if (overlay) {
            // Tambahkan kembali kelas d-none
            overlay.classList.add('d-none');
            // Kembalikan scrolling body
            document.body.style.overflow = '';
        }
    }
};