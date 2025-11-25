<div class="d-flex justify-content-end mb-3">
    <button type="button" class="btn btn-primary create-status-template-btn" data-mode="create">
        <i class="ti ti-plus me-2"></i> Tambah Status Template
    </button>
</div>

<div class="table-responsive">
    <table class="table table-vcenter card-table table-striped">
        <thead>
            <tr>
                <th style="width: 5%;">ID</th>
                <th>Nama Status</th>
                <th>Status Code</th>
                <th>Template Respon</th>
                <th class="w-1">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($statusTemplates as $template)
                <tr>
                    <td>{{ $template->id }}</td>
                    <td>{{ $template->name }}</td>
                    <td class="fw-bold">{{ $template->status_code }}</td>
                    <td>{{ Str::limit($template->response_template, 80) }}</td>
                    <td>
                        <button type="button" 
                                class="btn btn-sm btn-icon btn-outline-secondary edit-status-template-btn"
                                data-mode="edit"
                                data-id="{{ $template->id }}"
                                data-name="{{ $template->name }}"
                                data-code="{{ $template->status_code }}"
                                data-template="{{ $template->response_template }}"
                                title="Edit Template">
                            <i class="ti ti-pencil"></i>
                        </button>
                        <button type="button" 
                                class="btn btn-sm btn-icon btn-outline-danger delete-template-btn"
                                data-type="status"
                                data-id="{{ $template->id }}"
                                data-name="{{ $template->name }}"
                                title="Hapus Template">
                            <i class="ti ti-trash"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">Belum ada Status Template yang terdaftar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Memuat Modal CRUD Status Template --}}
@include('pages.settings.modals.status_template_modal')