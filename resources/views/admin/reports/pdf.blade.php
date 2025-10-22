<!DOCTYPE html>
<html>
<head>
    <title>Laporan Absensi</title>
    <style>
        body { font-family: Arial; font-size: 7px; margin: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 2px; font-size: 7px; }
        th { background-color: #ddd; font-weight: bold; text-align: center; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h2 { margin: 3px 0; font-size: 12px; }
        .header h3 { margin: 3px 0; font-size: 10px; }
        .header p { margin: 2px 0; font-size: 8px; }
        .tc { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $schoolName }}</h2>
        <p>{{ $schoolAddress }}</p>
        <h3>Laporan Absensi {{ $type === 'employee' ? 'Pegawai' : 'Siswa' }}</h3>
        <p>Periode: {{ $startDate }} - {{ $endDate }}</p>
    </div>

    @if($type === 'employee')
    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>Tanggal</th>
                <th>Shift</th>
                <th>Jam Masuk</th>
                <th>Abs Masuk</th>
                <th>Selisih</th>
                <th>Status</th>
                <th>Jam Pulang</th>
                <th>Abs Pulang</th>
                <th>Selisih</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $data)
            <tr>
                <td>{{ $data['name'] }}</td>
                <td>{{ $data['position'] }}</td>
                <td class="tc">{{ $data['date'] }}</td>
                <td class="tc">{{ $data['shift_name'] ?? '-' }}</td>
                <td class="tc">{{ $data['schedule_start'] }}</td>
                <td class="tc">{{ $data['clock_in_time'] }}</td>
                <td class="tc">{{ $data['clock_in_diff'] }}</td>
                <td class="tc">{{ $data['clock_in_status'] }}</td>
                <td class="tc">{{ $data['schedule_end'] }}</td>
                <td class="tc">{{ $data['clock_out_time'] }}</td>
                <td class="tc">{{ $data['clock_out_diff'] }}</td>
                <td class="tc">{{ $data['clock_out_status'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    {{-- Student Report --}}
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>NISN</th>
                <th>Nama</th>
                <th>Rombel</th>
                <th>Jam Masuk</th>
                <th>Abs Masuk</th>
                <th>Selisih</th>
                <th>Status</th>
                <th>Jam Pulang</th>
                <th>Abs Pulang</th>
                <th>Selisih</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $data)
            <tr>
                <td class="tc">{{ $data['date'] }}</td>
                <td>{{ $data['nisn'] }}</td>
                <td>{{ $data['name'] }}</td>
                <td>{{ $data['rombel'] }}</td>
                <td class="tc">{{ $data['schedule_start'] }}</td>
                <td class="tc">{{ $data['clock_in_time'] }}</td>
                <td class="tc">{{ $data['clock_in_diff'] }}</td>
                <td class="tc">{{ $data['clock_in_status'] }}</td>
                <td class="tc">{{ $data['schedule_end'] }}</td>
                <td class="tc">{{ $data['clock_out_time'] }}</td>
                <td class="tc">{{ $data['clock_out_diff'] }}</td>
                <td class="tc">{{ $data['clock_out_status'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <p style="margin-top: 10px; font-size: 7px; text-align: right;">Dicetak: {{ $generatedAt }}</p>
</body>
</html>
