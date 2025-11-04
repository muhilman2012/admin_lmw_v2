@extends('layouts.app')
@inject('Str', 'Illuminate\Support\Str')

@section('title', $article->exists ? 'Edit Artikel KMS' : 'Buat Artikel KMS Baru')
@section('page_pretitle', 'KMS')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    {{ $article->exists ? 'Edit Artikel: ' . $article->title : 'Buat Artikel Knowledge Base' }}
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="{{ route('kms.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-chevron-left me-2"></i> Kembali ke Daftar
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        {{-- Pesan Sesi --}}
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        
        <form method="POST" action="{{ $article->exists ? route('kms.update', $article) : route('kms.store') }}">
            @csrf
            @if ($article->exists)
                @method('PUT')
            @endif

            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-9">
                            {{-- Judul --}}
                            <div class="mb-3">
                                <label class="form-label">Judul Artikel</label>
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $article->title) }}" required>
                                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Konten (Gunakan TinyMCE, Trix, atau input textarea biasa) --}}
                            <div class="mb-3">
                                <label class="form-label">Isi Konten</label>
                                <textarea name="content" id="kms-content-editor" class="form-control @error('content') is-invalid @enderror" rows="15" required>{{ old('content', $article->content) }}</textarea>
                                <small class="form-hint">Dapat memuat teks, gambar, atau kode.</small>
                                @error('content') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            {{-- Kategori --}}
                            <div class="mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat }}" {{ old('category', $article->category) == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                                @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Tags (Kata Kunci) --}}
                            <div class="mb-3">
                                <label class="form-label">Tags (Pisahkan dengan koma)</label>
                                <input type="text" name="tags" class="form-control @error('tags') is-invalid @enderror" value="{{ old('tags', $article->tags) }}" placeholder="cth: kebijakan, prosedur, faq">
                                <small class="form-hint">Digunakan untuk fitur pencarian cepat.</small>
                                @error('tags') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Status Aktif/Tidak Aktif --}}
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $article->is_active ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">{{ $article->is_active ? 'Aktif (Terlihat Publik)' : 'Nonaktif (Draft)' }}</label>
                                </div>
                            </div>
                            
                            {{-- Informasi Pembuat --}}
                            @if ($article->exists)
                            <div class="mt-4 pt-4 border-top">
                                <div class="text-muted small">Dibuat Oleh: {{ $article->user->name ?? 'N/A' }}</div>
                                <div class="text-muted small">Terakhir Diperbarui: {{ $article->updated_at->diffForHumans() }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-2"></i> Simpan Artikel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection