@extends('layouts.app')
@inject('Str', 'Illuminate\Support\Str')

@section('title', 'Detail Artikel KMS: ' . $article->title)
@section('page_pretitle', 'KMS')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-auto">
                <a href="{{ route('kms.index') }}" class="btn btn-outline-secondary mb-2 mb-md-0">
                    <i class="ti ti-chevron-left me-2"></i> Kembali ke Pencarian
                </a>
                
                @hasanyrole(['superadmin', 'admin'])
                <a href="{{ route('kms.edit', $article) }}" class="btn btn-primary mb-2 mb-md-0">
                    <i class="ti ti-pencil me-2"></i> Edit Artikel
                </a>
                
                {{-- Tombol Hapus (Menggunakan Form) --}}
                <button type="button" class="btn btn-danger mb-2 mb-md-0" data-bs-toggle="modal" data-bs-target="#modal-delete-article">
                    <i class="ti ti-trash me-2"></i> Hapus
                </button>
                @endhasanyrole
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <h1 class="h2 mb-0">{{ $article->title }}</h1>
                    <span class="badge bg-blue-lt">{{ $article->category }}</span>
                </div>
                
                {{-- Status Artikel (Hanya terlihat jika tidak aktif dan user adalah admin) --}}
                @if (!$article->is_active)
                    <div class="alert alert-warning mb-4">
                        Artikel ini berstatus **Draft (Tidak Aktif)** dan tidak terlihat oleh pengguna umum.
                    </div>
                @endif
                
                {{-- Konten Artikel (Render HTML) --}}
                <div class="article-content mb-4">
                    {!! $article->content !!}
                </div>

                {{-- Footer Metadata --}}
                <div class="mt-5 pt-3 border-top text-muted small">
                    <p class="mb-1">
                        Dibuat oleh: {{ $article->user->name ?? 'Pengguna Dihapus' }} | 
                        Terakhir diperbarui: {{ $article->updated_at->format('d M Y H:i') }}
                    </p>
                    <p class="mb-0">
                        Tags: 
                        @foreach (explode(',', $article->tags ?? '') as $tag)
                            <a href="{{ route('kms.index', ['tag' => trim($tag)]) }}" class="badge bg-secondary-lt me-1">{{ trim($tag) }}</a>
                        @endforeach
                    </p>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Hapus --}}
<div class="modal modal-blur fade" id="modal-delete-article" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle icon mb-2 text-danger icon-lg"></i>
                <h3>Hapus Artikel?</h3>
                <div class="text-muted">Anda yakin ingin menghapus artikel "{{ $article->title }}"? Tindakan ini tidak dapat dibatalkan.</div>
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
@endsection