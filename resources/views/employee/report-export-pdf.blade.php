<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Absensi Pegawai</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .employee-info {
            margin-bottom: 20px;
        }
        .employee-info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        .center {
            text-align: center;
        }
        .right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN ABSENSI PEGAWAI</h2>
    </div>
    
    <div class="employee-info">
        <p><strong>Nama Pegawai:</strong> {{ $employee->full_name }}</p>
        <p><strong>Jabatan:</strong> {{ $employee->position }}</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th class="center">Tanggal</th>
                <th>Nama Shift</th>
                <th class="center">Jam Masuk</th>
                <th>Selisih Masuk</th>
                <th class="center">Jam Pulang</th>
                <th>Selisih Pulang</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $attendance)
            <tr>
                <td class="center">{{ $attendance->date ? $attendance->date->format('d/m/Y') : '-' }}</td>
                <td>{{ \App\Http\Controllers\Employee\AttendanceController::getShiftName($attendance) }}</td>
                <td class="center">{{ $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-' }}</td>
                <td>{{ \App\Http\Controllers\Employee\AttendanceController::getInTimeDifference($attendance) }}</td>
                <td class="center">{{ $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '-' }}</td>
                <td>{{ \App\Http\Controllers\Employee\AttendanceController::getOutTimeDifference($attendance) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="center">Tidak ada data absensi</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>