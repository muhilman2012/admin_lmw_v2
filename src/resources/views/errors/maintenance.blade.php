@extends('layouts.error')

@section('title', 'Maintenance mode')

@section('content')
<div class="empty">
    <div class="empty-img"><img src="{{ asset('tabler/static/illustrations/undraw_quitting_time_dm8t.svg') }}" height="128" alt="Maintenance illustration">
    </div>
    <p class="empty-title">Temporarily down for maintenance</p>
    <p class="empty-subtitle text-secondary">
        Sorry for the inconvenience but we’re performing some maintenance at the moment. We’ll be back online shortly!
    </p>
    <div class="empty-action">
        <a href="{{ url('/admin/dashboard') }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0" /><path d="M5 12l6 6" /><path d="M5 12l6 -6" /></svg>
            Take me home
        </a>
    </div>
</div>
@endsection