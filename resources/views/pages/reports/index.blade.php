@extends('layouts.app')

@section('title', 'Kelola Pengaduan')
@section('page_title', 'Kelola Pengaduan')

@section('page_header')
    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="d-flex g-2 align-items-center justify-content-between">
                <h2 class="page-title">Kelola Pengaduan</h2>
                <a href="{{ route('reports.create') }}" class="btn btn-primary">
                    <i class="ti ti-pencil me-2"></i>Buat Laporan
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
<div class="card">
    <div class="card-table">
        @livewire('admin.reports')
    </div>
</div>
@endsection