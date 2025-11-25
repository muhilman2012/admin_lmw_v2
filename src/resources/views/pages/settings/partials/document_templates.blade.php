<div class="d-flex justify-content-end mb-3">
    <button type="button" class="btn btn-primary create-document-template-btn" data-mode="create">
        <i class="ti ti-plus me-2"></i> Tambah Dokumen Template
    </button>
</div>

<div class="table-responsive">
    <table class="table table-vcenter card-table table-striped">
        <thead>
            <tr>
                <th style="width: 5%;">ID</th>
                <th>Nama Dokumen</th>
                <th>Terakhir Diperbarui</th>
                <th class="w-1">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($documentTemplates as $template)
                <tr>
                    <td>{{ $template->id }}</td>
                    <td class="fw-bold">{{ $template->name }}</td>
                    <td>{{ $template->updated_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <button type="button" 
                                class="btn btn-sm btn-icon btn-outline-secondary edit-document-template-btn"
                                data-mode="edit"
                                data-id="{{ $template->id }}"
                                data-name="{{ $template->name }}"
                                title="Edit Template">
                            <i class="ti ti-pencil"></i>
                        </button>
                        <button type="button" 
                                class="btn btn-sm btn-icon btn-outline-danger delete-template-btn"
                                data-type="document"
                                data-id="{{ $template->id }}"
                                data-name="{{ $template->name }}"
                                title="Hapus Template">
                            <i class="ti ti-trash"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">Belum ada Document Template yang terdaftar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Memuat Modal CRUD Document Template --}}
@include('pages.settings.modals.document_template_modal')