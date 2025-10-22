<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Absensi Pegawai</title>
    <style>
        body { font-family: Arial; margin: 15px; font-size: 10px; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h2 { margin: 5px 0; font-size: 14px; }
        .employee-info { margin-bottom: 15px; }
        .employee-info p { margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; font-size: 9px; }
        th { background-color: #ddd; font-weight: bold; }
        .tc { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN ABSENSI PEGAWAI</h2>
    </div>

    <div class="employee-info">
        <p><strong>Nama:</strong> {{ $employee->full_name }}</p>
        <p><strong>Jabatan:</strong> {{ $employee->position }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="tc">Tanggal</th>
                <th>Shift</th>
                <th class="tc">Jam Masuk</th>
                <th class="tc">Absen Masuk</th>
                <th class="tc">Selisih</th>
                <th class="tc">Jam Pulang</th>
                <th class="tc">Absen Pulang</th>
                <th class="tc">Selisih</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $data)
            <tr>
                <td class="tc">{{ $data['date'] }}</td>
                <td>{{ $data['shift_name'] }}</td>
                <td class="tc">{{ $data['schedule_start'] }}</td>
                <td class="tc">{{ $data['clock_in'] }}</td>
                <td class="tc">{{ $data['in_diff'] }}</td>
                <td class="tc">{{ $data['schedule_end'] }}</td>
                <td class="tc">{{ $data['clock_out'] }}</td>
                <td class="tc">{{ $data['out_diff'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="tc">Tidak ada data absensi</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>