@extends('layouts.error')

@section('title', 'Page 419')

@section('content')
<div class="empty">
    <div class="empty-header">419</div>
    <p class="empty-title">Oops… You just found an error page</p>
    <p class="empty-subtitle text-secondary">
        We are sorry but the page you are looking for was not found
    </p>
    <div class="empty-action">
        <a href="{{ url('/') }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0" /><path d="M5 12l6 6" /><path d="M5 12l6 -6" /></svg>
            Take me home
        </a>
    </div>
</div>
@endsection