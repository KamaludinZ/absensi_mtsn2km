<table>
    <tr><td colspan="12"><strong>REKAP ABSENSI SISWA</strong></td></tr>
    <tr><td colspan="12">{{ $scopeLabel }}</td></tr>
    <tr><td colspan="12">Periode {{ $start->format('d/m/Y') }} - {{ $end->format('d/m/Y') }} ({{ $schoolDays->count() }} hari sekolah s.d. {{ $generatedAt }})</td></tr>
    <tr><td colspan="12"></td></tr>
</table>

@if($studentRow)
    <table>
        <tr><td>NISN</td><td>{{ $student->nisn ?? '-' }}</td></tr>
        <tr><td>Nama</td><td>{{ $student->full_name }}</td></tr>
        <tr><td>Rombel</td><td>{{ $student->rombel->name ?? '-' }}</td></tr>
        <tr><td>Hadir</td><td>{{ $studentRow['counts']['H'] }}</td></tr>
        <tr><td>Terlambat</td><td>{{ $studentRow['counts']['T'] }}</td></tr>
        <tr><td>Sakit</td><td>{{ $studentRow['counts']['S'] }}</td></tr>
        <tr><td>Izin</td><td>{{ $studentRow['counts']['I'] }}</td></tr>
        <tr><td>Alpa</td><td>{{ $studentRow['counts']['A'] }}</td></tr>
        <tr><td>Izin belum disetujui</td><td>{{ $studentRow['counts']['M'] }}</td></tr>
        <tr><td>% Kehadiran</td><td>{{ $studentRow['percentage'] ?? '-' }}</td></tr>
        <tr><td></td></tr>
    </table>
    <table>
        <thead>
            <tr>
                <th><strong>Tanggal</strong></th>
                <th><strong>Hari</strong></th>
                <th><strong>Status</strong></th>
                <th><strong>Absen Masuk</strong></th>
                <th><strong>Absen Pulang</strong></th>
                <th><strong>Keterangan</strong></th>
            </tr>
        </thead>
        <tbody>
            @foreach($calendar as $day)
                <tr>
                    <td>{{ $day['date']->format('d/m/Y') }}</td>
                    <td>{{ $day['date']->translatedFormat('l') }}</td>
                    <td>{{ $day['label'] }}</td>
                    <td>{{ $day['attendance']?->clock_in?->format('H:i') ?? '-' }}</td>
                    <td>{{ $day['attendance']?->clock_out?->format('H:i') ?? '-' }}</td>
                    <td>{{ $day['note'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <table>
        <thead>
            <tr>
                <th><strong>No</strong></th>
                <th><strong>NISN</strong></th>
                <th><strong>Nama Siswa</strong></th>
                <th><strong>Rombel</strong></th>
                <th><strong>Hari Sekolah</strong></th>
                <th><strong>Hadir</strong></th>
                <th><strong>Terlambat</strong></th>
                <th><strong>Sakit</strong></th>
                <th><strong>Izin</strong></th>
                <th><strong>Alpa</strong></th>
                <th><strong>Izin Belum Disetujui</strong></th>
                <th><strong>% Kehadiran</strong></th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td data-format="@">{{ $row['student']->nisn ?? '-' }}</td>
                    <td>{{ $row['student']->full_name }}</td>
                    <td>{{ $rombel->name ?? $row['student']->rombel->name ?? '-' }}</td>
                    <td>{{ $row['effective_days'] }}</td>
                    <td>{{ $row['counts']['H'] }}</td>
                    <td>{{ $row['counts']['T'] }}</td>
                    <td>{{ $row['counts']['S'] }}</td>
                    <td>{{ $row['counts']['I'] }}</td>
                    <td>{{ $row['counts']['A'] }}</td>
                    <td>{{ $row['counts']['M'] }}</td>
                    <td>{{ $row['percentage'] ?? '-' }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="5"><strong>Jumlah</strong></td>
                @foreach(['H', 'T', 'S', 'I', 'A', 'M'] as $code)
                    <td><strong>{{ $totals[$code] }}</strong></td>
                @endforeach
                <td></td>
            </tr>
        </tbody>
    </table>
@endif
