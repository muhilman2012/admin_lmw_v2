<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitor Antrean | Lapor Mas Wapres</title>
    <link href="{{ asset('tabler/css/tabler.min.css') }}" rel="stylesheet">
    <link href="{{ asset('tabler/css/tabler-icons.min.css') }}" rel="stylesheet" />
     <link rel="icon" href="{{ asset('tabler/img/logo/LaporMasWapres.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('tabler/img/logo/LaporMasWapres.png') }}">
    <style>
        :root {
            --bg-blue-dark: #f0f4f8; /* Warna latar keabu-abuan seperti gambar ESB */
            --main-blue: #0052cc;    /* Biru Lapor Mas Wapres */
            --secondary-blue: #e0e9f5; /* Biru muda untuk latar card */
        }
        body { 
            background-color: var(--bg-blue-dark); 
            color: #1f2937;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .monitor-container {
            flex-grow: 1;
            padding: 20px;
        }
        /* Styling Header ala ESB */
        .monitor-header {
            background-color: #ffffff;
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        /* Styling Card ala ESB (bersih, rounded, shadow tipis) */
        .monitor-card {
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: none;
            height: 100%;
        }
        .monitor-card .card-header {
            background-color: transparent;
            border-bottom: 1px solid #e5e7eb;
            padding: 15px 20px;
        }
        /* Warna Header Kolom */
        .header-loket { background-color: var(--main-blue); color: white; border-radius: 10px 10px 0 0; }
        .header-panggilan { background-color: #ef4444; color: white; border-radius: 10px 10px 0 0; } /* Merah seperti ESB */
        .header-riwayat { background-color: #22c55e; color: white; border-radius: 10px 10px 0 0; } /* Hijau seperti ESB */

        /* Panggilan Utama (Tengah) ala ESB */
        .panggilan-utama {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
        }
        .number-huge {
            font-size: 15rem;
            font-weight: 800;
            color: #000000;
            line-height: 1;
            margin: 20px 0;
        }
        .loket-text {
            font-size: 3rem;
            font-weight: 700;
            color: var(--main-blue);
        }

        /* List Items (Kiri & Kanan) ala ESB */
        .queue-item {
            background-color: var(--secondary-blue);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 5px solid var(--main-blue);
        }
        .queue-item.active {
            background-color: #dbeafe;
            border-left-color: #2563eb;
        }
    </style>
    @livewireStyles
</head>
<body>
    @yield('content')
    <script src="{{ asset('assets/js/axios.min.js') }}"></script>
    <script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
    @vite(['resources/js/app.js']) 
    @livewireScripts
    @stack('scripts')
</body>
</html>