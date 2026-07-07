<table>
    <tr>
        <th colspan="7" style="text-align: center; font-weight: bold; font-size: 14px;">
            MOD <br> {{ strtoupper(\Carbon\Carbon::parse($date)->locale('id')->translatedFormat('d F Y')) }}
        </th>
    </tr>
    <tr>
        <th style="background-color: #f3f4f6; font-weight: bold;">NO</th>
        <th style="background-color: #f3f4f6; font-weight: bold;">NAMA PELAPOR</th>
        <th style="background-color: #f3f4f6; font-weight: bold;">NO REGISTER LAPOR</th>
        <th style="background-color: #f3f4f6; font-weight: bold;">INTI PENGADUAN</th>
        <th style="background-color: #f3f4f6; font-weight: bold;">KEPERLUAN</th>
        <th style="background-color: #f3f4f6; font-weight: bold;">UNIT/ JF YANG MENANGANI</th>
        <th style="background-color: #f3f4f6; font-weight: bold;">KETERANGAN</th>
    </tr>
    
    @forelse($notes as $index => $note)
    <tr>
        <td>{{ $index + 1 }}</td>
        <td>{{ $note->report->reporter_name ?? '-' }}</td>
        <td>{{ $note->report->ticket_number ?? '-' }}</td>
        <td>{{ $note->report->title ?? 'Judul Tidak Tersedia' }}</td> 
        <td>Konfirmasi Tindak Lanjut dari Aduan</td>
        <td>{{ $note->actualUser->name ?? 'Petugas Tidak Diketahui' }}</td>
        <td>{{ $note->note }}</td>
    </tr>
    @empty
    <tr>
        <td colspan="7" style="text-align: center;">Tidak ada catatan MOD pada tanggal ini.</td>
    </tr>
    @endforelse
</table>