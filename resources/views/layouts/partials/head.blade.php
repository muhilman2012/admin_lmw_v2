<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>@yield('title', 'Dashboard') - LaporMasWapres!</title>

    <link rel="icon" href="{{ asset('tabler/img/logo/LaporMasWapres.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('tabler/img/logo/LaporMasWapres.png') }}">

    <link href="{{ asset('tabler/libs/jsvectormap/dist/jsvectormap.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/css/tabler.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/css/addon-css.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/css/tabler-icons.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/css/tabler-themes.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/libs/tom-select/dist/css/tom-select.bootstrap5.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/css/tabler-vendors.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/css/litepicker.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/libs/dropzone/dist/dropzone.css') }}" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('assets/css/sweetalert2.min.css') }}">
    
    <style>
        @import url("{{ asset('tabler/fonts/inter.css') }}");
    </style>
    <style>
        .text-two-lines {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>

    @stack('styles')
</head>