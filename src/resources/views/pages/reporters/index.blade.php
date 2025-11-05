@extends('layouts.app')

@section('title', 'Daftar Pengadu')
@section('page_pretitle', 'Pengaduan')
@section('page_title', 'Daftar Pengadu')

@section('page_header')
<div class="page-header d-print-none" aria-label="Page header">
    <div class="container-xl">
        <div class="d-flex g-2 align-items-center justify-content-between">
            <h2 class="page-title">Daftar Pengadu</h2>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-table">
        @livewire('admin.reporters')
    </div>
</div>
@endsection