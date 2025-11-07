@extends('layouts.app')
@inject('Str', 'Illuminate\Support\Str')

@section('title', 'Sistem Manajemen Pengetahuan (KMS)')
@section('page_pretitle', 'Sistem Manajemen Pengetahuan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title">Sistem Manajemen Pengetahuan</h2>
    
    {{-- @can('create kms article')  --}}
    @hasanyrole(['superadmin', 'admin'])
    <a href="{{ route('kms.create') }}" class="btn btn-primary">
        <i class="ti ti-plus me-2"></i> Tambah Artikel KMS
    </a>
    @endhasanyrole
    {{-- @endcan --}}
</div>
        
<div class="row row-cards">
    <div class="col-lg-9">
        <form method="GET" action="{{ route('kms.index') }}" class="card mb-4">
            <div class="card-body">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Cari Judul, Isi, atau Kata Kunci..." value="{{ $search ?? '' }}">
                    <button class="btn btn-primary" type="submit">
                        <i class="ti ti-search me-1"></i> Cari
                    </button>
                    @if ($search || $tag)
                    <a href="{{ route('kms.index') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
                @if ($tag)
                <small class="text-muted mt-2 d-block">Filter Aktif: Tag <strong>{{ $tag }}</strong></small>
                @endif
            </div>
        </form>

        {{-- HASIL PENCARIAN --}}
        @forelse ($articles as $article)
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 class="card-title mb-1">
                            <a href="{{ route('kms.show', $article) }}" class="text-body">{{ $article->title }}</a>
                        </h3>
                        <p class="text-muted mb-2">
                            {{ Str::limit(strip_tags($article->content), 200) }}
                        </p>
                    </div>
                    <div class="dropdown">
                        @hasanyrole(['superadmin', 'admin'])
                        <button class="btn btn-icon btn-sm" data-bs-toggle="dropdown">
                            <i class="ti ti-dots-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="{{ route('kms.edit', $article) }}">Edit</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#modal-delete-{{ $article->id }}">
                                <i class="ti ti-trash me-2"></i> Hapus
                            </a> 
                        </div>
                        @endhasanyrole
                    </div>
                </div>
                
                <div class="text-secondary small">
                    Kategori: <span class="badge bg-blue-lt me-2">{{ $article->category }}</span>
                    @if ($article->tags)
                        | Tags:
                        @foreach (explode(',', $article->tags) as $tagItem)
                        <a href="{{ route('kms.index', ['tag' => trim($tagItem)]) }}" class="badge bg-secondary-lt">{{ trim($tagItem) }}</a>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        {{-- MODAL DELETE ARTICLE (Disisipkan dalam loop untuk setiap artikel) --}}
        @hasanyrole(['superadmin', 'admin'])
        <div class="modal modal-blur fade" id="modal-delete-{{ $article->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-status bg-danger"></div>
                    <div class="modal-body text-center py-4">
                        <i class="ti ti-alert-triangle icon mb-2 text-danger icon-lg"></i>
                        <h3>Hapus Artikel?</h3>
                        <div class="text-muted">Anda yakin ingin menghapus artikel "{{ Str::limit($article->title, 50) }}"? Tindakan ini tidak dapat dibatalkan.</div>
                    </div>
                    <div class="modal-footer">
                        <div class="w-100">
                            <div class="row">
                                <div class="col"><a href="#" class="btn w-100" data-bs-dismiss="modal">Batal</a></div>
                                <div class="col">
                                    <form action="{{ route('kms.destroy', $article) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger w-100">Ya, Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endhasanyrole

        @empty
        <div class="alert alert-info text-center p-5">
            Tidak ada artikel Knowledge Base yang ditemukan.
        </div>
        @endforelse

        {{ $articles->links() }}
    </div>
            
    {{-- KOLOM KANAN --}}
    <div class="col-lg-3">
        {{-- TAGS POPULER --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Tags Populer</h3>
            </div>
            <div class="card-body">
                @forelse ($popularTags as $tagItem)
                    <a href="{{ route('kms.index', ['tag' => $tagItem]) }}" class="btn btn-outline-secondary btn-sm mb-2 me-1">{{ $tagItem }}</a>
                @empty
                    <p class="text-muted">Tidak ada tag populer saat ini.</p>
                @endforelse
            </div>
        </div>

        {{-- DAFTAR KATEGORI --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pilih Berdasarkan Kategori</h3>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($categories as $categoryItem)
                <a 
                    href="{{ route('kms.index', ['category' => $categoryItem->name]) }}" 
                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center 
                        @if (isset($category) && $category === $categoryItem->name) active @endif"
                >
                    {{ $categoryItem->name }}
                    <span class="badge bg-white badge-pill">{{ $categoryItem->article_count }}</span> {{-- Asumsi ada properti article_count di objek kategori --}}
                </a>
                @empty
                    <div class="list-group-item">Tidak ada kategori tersedia.</div>
                @endforelse
                {{-- Opsi untuk melihat semua/reset kategori jika filter aktif --}}
                @if (isset($category))
                <a href="{{ route('kms.index') }}" class="list-group-item list-group-item-action text-danger">
                    Lihat Semua Artikel
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection