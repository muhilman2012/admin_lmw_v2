<div class="modal modal-blur fade" id="modal-manage-status-template" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="status-template-modal-title">Tambah Status Template Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="status-template-form" action="{{ route('settings.templates.status.store') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="status-template-id">
                <input type="hidden" name="active_tab_hash" value="#tab-templates"> {{-- Untuk persistensi tab --}}

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Nama Status (Tampilan)</label>
                        <input type="text" name="name" id="status-template-name" class="form-control" placeholder="Contoh: Menunggu kelengkapan data dukung dari Pelapor" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Status Code (Untuk JS Logic)</label>
                        <input type="text" name="status_code" id="status-template-code" class="form-control" placeholder="Contoh: additional_data_required" required>
                        <small class="form-hint text-danger">Gunakan code yang konsisten. Contoh: `verifikasi_proses`, `additional_data_required`.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Template Respon</label>
                        <textarea name="response_template" id="status-template-response" class="form-control" rows="5" placeholder="Contoh: Saudara diminta melengkapi [dokumen_yang_dibutuhkan] dalam waktu 3 hari." required></textarea>
                        <small class="form-hint">Gunakan placeholder <code>[dokumen_yang_dibutuhkan]</code> jika status membutuhkan data tambahan.</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="status-template-submit-btn">Simpan Template</button>
                </div>
            </form>
        </div>
    </div>
</div>