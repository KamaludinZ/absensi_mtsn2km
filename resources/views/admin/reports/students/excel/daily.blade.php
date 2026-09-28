@php
    $dates = $schoolDays->keys();
@endphp
<table>
    <tr><td colspan="{{ $dates->count() + 3 }}"><strong>ABSENSI HARIAN SISWA</strong> - {{ $scopeLabel }}</td></tr>
    <tr><td colspan="{{ $dates->count() + 3 }}">Periode {{ $start->format('d/m/Y') }} - {{ $end->format('d/m/Y') }}. H = hadir, T = terlambat, S = sakit, I = izin, A = alpa, M = izin belum disetujui</td></tr>
    <tr><td></td></tr>
</table>
<table>
    <thead>
        <tr>
            <th><strong>No</strong></th>
            <th><strong>Nama Siswa</strong></th>
            <th><strong>Rombel</strong></th>
            @foreach($dates as $date)
                <th><strong>{{ \Carbon\Carbon::parse($date)->format('d/m') }}</strong></th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row['student']->full_name }}</td>
                <td>{{ $rombel->name ?? $row['student']->rombel->name ?? '-' }}</td>
                @foreach($dates as $date)
                    <td>{{ $row['daily'][$date]['code'] ?? '' }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
