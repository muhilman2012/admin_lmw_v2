@extends('layouts.app')

@section('title', 'Laporan Diteruskan')
@section('page_pretitle', 'Laporan & Pengaduan')
@section('page_title', 'Laporan Diteruskan')


@section('page_header')
    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="d-flex g-2 align-items-center justify-content-between">
                <h2 class="page-title">Daftar Pengaduan Diteruskan</h2>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @livewire('admin.forwarded-reports')
@endsection