<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Export Laporan Pengaduan</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; font-weight: bold; }
        h1 { font-size: 14px; text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Laporan Pengaduan (Exported {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }})</h1>

    <table>
        <thead>
            <tr>
                <th>No. Tiket</th>
                <th>Status</th>
                <th>Klasifikasi</th>
                <th>Judul Laporan</th>
                <th>Nama Pelapor</th>
                <th>NIK</th>
                <th>Alamat Pelapor</th>
                <th>Kategori</th>
                <th>Distribusi</th>
                <th>Tgl. Dibuat</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reports as $report)
            <tr>
                <td>{{ $report->ticket_number }}</td>
                <td>{{ $report->status }}</td>
                <td>{{ $report->classification ?? '-' }}</td>
                <td>{{ Str::limit($report->subject, 50) }}</td>
                <td>{{ $report->reporter->name ?? '-' }}</td>
                <td>{{ $report->reporter->nik ?? '-' }}</td>
                <td>{{ $report->reporter->address ?? '-' }}</td>
                <td>{{ $report->category->name ?? '-' }}</td>
                <td>{{ $report->unitKerja->name ?? $report->deputy->name ?? '-' }}</td>
                <td>{{ $report->created_at->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>