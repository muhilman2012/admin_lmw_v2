<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang di LMW API v2</title>
    <style>
        body { font-family: sans-serif; text-align: center; margin-top: 100px; background-color: #f7f7f7; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Lapor Mas Wapres API v2.0</h1>
        <p>Endpoint API ini <strong>aktif</strong> dan <strong>berjalan</strong> dengan baik.</p>
        <hr>
        <p>Akses ke fungsionalitas API harus dimulai dengan path <code>/api/</code>.</p>
        <p>Akses web aplikasi utama tersedia di: <a href="https://{{ $request->getHost() }}" target="_blank">https://{{ $request->getHost() }}</a></p>
        <p class="small text-muted mt-4">Status: Running on Port 8080 (API Service).</p>
    </div>
</body>
</html>