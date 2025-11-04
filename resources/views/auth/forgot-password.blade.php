@extends('layouts.auth')

@section('title', 'Lupa Kata Sandi')

@section('content')
<div class="container container-tight py-4">
    <div class="text-center mb-4">
        <a href="{{ url('/') }}" class="navbar-brand navbar-brand-autodark">
            <img src="{{ asset('tabler/img/logo/LaporMasWapres.png') }}" width="110" height="32" alt="Logo" class="navbar-brand-image">
        </a>
    </div>
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">Lupa Kata Sandi</h2>
            <p class="text-muted mb-4">Masukkan alamat email dan NIP Anda untuk mendapatkan kata sandi sementara.</p>
            <form action="{{ route('password.reset.internal') }}" method="post" novalidate>
                @csrf
                
                @if ($errors->any())
                    <div class="alert alert-danger mb-3">
                        <ul class="list-unstyled mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="email@set.wapresri.go.id" value="{{ old('email') }}" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="mb-3">
                    <label class="form-label">NIP (Nomor Induk Pegawai)</label>
                    <input type="text" name="nip" class="form-control @error('nip') is-invalid @enderror" placeholder="Masukkan NIP Anda" value="{{ old('nip') }}" required>
                    @error('nip') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">Verifikasi & Buat Kata Sandi Sementara</button>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center text-muted mt-3">
        Ingat kata sandi Anda? <a href="{{ route('login') }}" tabindex="-1">Kembali ke halaman masuk</a>
    </div>
</div>
@endsection