<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>@yield('title', 'Login') - LaporMasWapres</title>

    {{-- Favicon untuk browser --}}
    <link rel="icon" href="{{ asset('tabler/img/logo/LaporMasWapres.png') }}" type="image/png">
    {{-- Apple Touch Icon untuk perangkat seluler --}}
    <link rel="apple-touch-icon" href="{{ asset('tabler/img/logo/LaporMasWapres.png') }}">
    
    <link href="{{ asset('tabler/css/tabler.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('tabler/css/tabler-flags.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('tabler/css/tabler-payments.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('tabler/css/tabler-vendors.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('tabler/css/demo.min.css') }}" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('assets/css/sweetalert2.min.css') }}">
    <style>
      @import url('https://rsms.me/inter/inter.css');
      :root {
        --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
      }
      body {
        font-feature-settings: "cv03", "cv04", "cv11";
      }
    </style>
</head>
<body class="d-flex flex-column">
    <script src="{{ asset('tabler/js/demo-theme.min.js') }}"></script>
    <div class="page page-center">
        @yield('content')
    </div>

    <script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('tabler/js/tabler.min.js') }}" defer></script>
    <script src="{{ asset('tabler/js/demo.min.js') }}" defer></script>

    @stack('scripts')
    
    @if(session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Login Gagal',
                text: "{{ session('error') }}",
            });
        </script>
    @endif
</body>
</html>