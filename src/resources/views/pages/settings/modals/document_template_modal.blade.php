<div class="modal modal-blur fade" id="modal-manage-document-template" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="document-template-modal-title">Tambah Dokumen Template Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="document-template-form" action="{{ route('settings.templates.document.store') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="document-template-id">
                <input type="hidden" name="active_tab_hash" value="#tab-templates"> {{-- Untuk persistensi tab --}}

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Nama Dokumen</label>
                        <input type="text" name="name" id="document-template-name" class="form-control" placeholder="Contoh: Surat Keterangan Tidak Mampu (SKTM)" required>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="document-template-submit-btn">Simpan Dokumen</button>
                </div>
            </form>
        </div>
    </div>
</div>