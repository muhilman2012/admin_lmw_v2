@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('page_pretitle', 'Pengaturan')
@section('page_title', 'Manajemen Pengguna')

@section('page_header')
<div class="page-header d-print-none" aria-label="Page header">
    <div class="container-xl">
        <div class="d-flex g-2 align-items-center justify-content-between">
            <h2 class="page-title">Manajemen Pengguna</h2>
        </div>
    </div>
</div>
@endsection

@section('content')
    @livewire('admin.user-management')
@endsection