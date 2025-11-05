@if ($units->count() > 0 && $categories->where('parent_id', null)->count() > 0)
    {{-- Ambil nama Deputi dari unit pertama (asumsi semua unit di sini milik satu Deputi) --}}
    <p class="text-muted">Matriks penugasan untuk <strong>{{ $units->first()->deputy->name ?? 'Unit Kerja' }}</strong>.</p>
    
    {{-- STYLE UNTUK HEADER VERTIKAL WRAPPING --}}
    <style>
        /* Mengatur alignment dan padding umum untuk header tabel */
        .assignment-table th {
            vertical-align: middle; 
            text-align: center;
            height: 120px; 
            padding: 0.25rem 0.5rem !important; 
        }
        
        /* Gaya untuk header unit yang dipaksa wrap secara vertikal */
        .vertical-wrap-header {
            white-space: normal !important; 
            overflow-wrap: break-word;
            word-break: break-word; 
            width: 100px;  
            min-width: 80px; 
            max-width: 100px; 
            font-size: 0.75rem;
            line-height: 1.2;
        }
        
        /* Gaya untuk sel checkbox */
        .assignment-table td {
            text-align: center;
        }
        
        /* Opsional: Terapkan layout fixed agar width diprioritaskan */
        .table-fixed {
            table-layout: fixed;
        }
    </style>

    <div class="table-responsive">
        <table class="table table-vcenter table-striped table-hover assignment-table table-fixed">
            <thead>
                <tr>
                    <th style="width: 25%;">Kategori Utama</th>
                    @foreach ($units as $unit)
                        <th class="vertical-wrap-header" title="{{ $unit->name }}">
                            {{ $unit->name }}
                        </th>
                    @endforeach
                    <th style="width: 80px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($categories->where('parent_id', null) as $category)
                    {{-- TR sekarang tidak dibungkus FORM --}}
                    <tr data-category-id="{{ $category->id }}">
                        <td>
                            <strong>{{ $category->name }}</strong>
                        </td>
                        
                        {{-- SEL UNTUK CHECKBOX VISUAL --}}
                        @foreach ($units as $unit)
                            <td>
                                @php
                                    // Cek apakah Unit ini sudah ditugaskan ke Kategori saat ini
                                    $isAssigned = $category->unitKerjas->contains($unit->id);
                                @endphp
                                <label class="form-check form-check-single form-switch">
                                    <input 
                                        class="form-check-input visual-toggle" 
                                        type="checkbox"
                                        data-unit-id="{{ $unit->id }}" {{-- Gunakan data-* untuk JS --}}
                                        {{ $isAssigned ? 'checked' : '' }}
                                    >
                                </label>
                            </td>
                        @endforeach
                        
                        <td>
                            {{-- Tombol sekarang memanggil fungsi JS --}}
                            <button type="button" 
                                class="btn btn-sm btn-primary" 
                                data-category-id="{{ $category->id }}"
                                onclick="submitAssignment(this.getAttribute('data-category-id'))"
                                title="Simpan Penugasan"
                            >
                                Simpan
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- FORMULIR TERSEMBUNYI GLOBAL (VALID HTML) -->
    <form id="global-assignment-form" action="{{ route('settings.assign.units') }}" method="POST" style="display: none;">
        @csrf
        <input type="hidden" name="category_id" id="hidden-category-id">
        {{-- Kontainer ini akan diisi input unit_assignments[] secara dinamis oleh JavaScript --}}
        <div id="hidden-unit-inputs-container"></div>
    </form>
@else
    <div class="alert alert-info m-0">Tidak ditemukan Kategori Utama atau Unit Kerja untuk Deputi ini.</div>
@endif