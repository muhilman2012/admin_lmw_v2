<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>@yield('title', 'Error') - LaporMasWapres!</title>
    <link href="{{ asset('tabler/css/tabler.min.css?1692870487') }}" rel="stylesheet"/>
    <link href="{{ asset('tabler/css/tabler-flags.min.css?1692870487') }}" rel="stylesheet"/>
    <link href="{{ asset('tabler/css/tabler-payments.min.css?1692870487') }}" rel="stylesheet"/>
    <link href="{{ asset('tabler/css/tabler-vendors.min.css?1692870487') }}" rel="stylesheet"/>
    <link href="{{ asset('tabler/css/demo.min.css?1692870487') }}" rel="stylesheet"/>
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
  <body class="border-top-wide border-primary d-flex flex-column">
    <script src="{{ asset('tabler/js/demo-theme.min.js?1692870487') }}"></script>
    <div class="page page-center">
        <div class="container-tight py-4">
            @yield('content')
        </div>
    </div>
    <script src="{{ asset('tabler/js/tabler.min.js?1692870487') }}" defer></script>
    <script src="{{ asset('tabler/js/demo.min.js?1692870487') }}" defer></script>
  </body>
</html>