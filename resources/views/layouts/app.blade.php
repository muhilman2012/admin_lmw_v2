<!DOCTYPE html>
<html lang="en">
@include('layouts.partials.head')

<body class="layout-fluid">
    <div class="page">
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
        <div class="sticky-top">
            @include('layouts.partials.navbar')
        </div>
        <div class="page-wrapper">
            @yield('page_header')

            <div class="page-body">
                <div class="container-xl">
                    @yield('content')
                </div>
            </div>

            @include('layouts.partials.footer')
        </div>
    </div>
    
    @include('layouts.partials.theme-settings')
    @include('layouts.partials.scripts')
    @stack('scripts')
    @stack('modals')
    </body>
</html>