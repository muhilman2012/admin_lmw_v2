<table>
    <tr>
        <th colspan="2" style="font-weight: bold; font-size: 14px;">LAPORAN RINGKASAN MANAGER ON DUTY (MOD)</th>
    </tr>
    <tr>
        <td>Tanggal Laporan</td>
        <td>{{ \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('d F Y') }}</td>
    </tr>
    <tr>
        <td>Total Pengadu Masuk</td>
        <td>{{ $totalReports }} Orang</td>
    </tr>
    <tr><td colspan="2"></td></tr>
    
    <tr>
        <th colspan="2" style="font-weight: bold;">Distribusi Antrean Loket</th>
    </tr>
    @forelse($loketStats as $loket)
        <tr>
            <td>Loket {{ $loket->counter_number }}</td>
            <td>{{ $loket->total }} Orang</td>
        </tr>
    @empty
        <tr>
            <td colspan="2">Tidak ada data antrean di loket pada hari ini.</td>
        </tr>
    @endforelse
</table>