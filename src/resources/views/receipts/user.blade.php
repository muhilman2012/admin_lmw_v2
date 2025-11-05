<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tanda Terima Pengaduan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .header img {
            width: 150px;
            margin-bottom: 5px;
        }
        .header h1 {
            font-size: 16px;
            text-transform: uppercase;
            margin: 0;
        }
        .header h3 {
            margin: 2px 0;
        }
        .box {
            border: 1px solid #000;
            padding: 15px;
            margin-bottom: 20px;
        }
        .watermark {
            position: fixed;
            top: 35%;
            left: 13%;
            opacity: 0.1;
            z-index: -1;
        }
    </style>
</head>
<body>
    <div class="a5-page">
        <div class="header">
            <img src="{{ public_path('tabler/img/logo/LaporMasWapres.png') }}" alt="Logo">
            <h1>Tanda Terima Pengaduan</h1>
            <h3>{{ date('d-m-Y', strtotime($report->created_at)) }}</h3>
            <h3>Nomor Tiket: <strong>{{ $report->ticket_number }}</strong></h3>
        </div>
            <div class="box">
                <div class="watermark">
                    <img src="{{ public_path('tabler/img/logo/LaporMasWapres.png') }}" alt="Logo" style="width: 500px;">
                </div>
                <p>
                    <strong>Identitas Pengadu:</strong> {{ $report->reporter->name }} /
                    {{ substr($report->reporter->nik, 0, 6) . 'XXXXXX' . substr($report->reporter->nik, -4) }} / 
                    @if(!empty($report->reporter->phone_number))
                        {{ substr($report->reporter->phone_number, 0, 3) . 'XXXXX' . substr($report->reporter->phone_number, -4) }}
                    @else
                        <span class="text-danger">Tidak ada Nomor HP</span>
                    @endif
                </p>
                <p><strong>Catatan:</strong></p>
                <ol>
                    <li>Tanda Terima Pengaduan ini digunakan hanya untuk kepentingan pelayanan pengaduan masyarakat dan tidak dapat digunakan untuk kepentingan peradilan hukum lainnya.</li>
                    <li>Pengadu dapat melacak status pengaduan <strong>secara berkala</strong> melalui nomor <i>whatsapp</i> <strong>081117042204</strong> tanpa perlu hadir langsung ke kantor Sekretariat Wakil Presiden.</li>
                    <li>Jika diminta untuk melengkapi kekurangan dokumen pendukung dapat dikirim melalui nomor <i>whatsapp</i> <strong>081117042204</strong> pada jam kerja Senin - Jumat pukul 08.00 - 14.00 WIB.</li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html>