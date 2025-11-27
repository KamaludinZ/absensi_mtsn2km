<!DOCTYPE html>
<html>
<head>
    <title>Laporan Tunjangan Kinerja</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
        }
        .header-content {
            display: table;
            width: 100%;
        }
        .logo-left, .logo-right {
            display: table-cell;
            width: 70px;
            vertical-align: middle;
        }
        .logo-left img, .logo-right img {
            max-width: 60px;
            max-height: 60px;
        }
        .header-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 3px 0;
            font-size: 12px;
            font-weight: normal;
        }
        .header p {
            margin: 1px 0;
            font-size: 9px;
        }
        .report-title {
            text-align: center;
            margin: 10px 0;
        }
        .report-title h3 {
            margin: 3px 0;
            font-size: 12px;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .employee-info {
            margin-bottom: 15px;
        }
        .employee-info table {
            width: 100%;
            border: none;
            border-collapse: collapse;
        }
        .employee-info td {
            border: none;
            padding: 3px 5px;
            font-size: 10px;
        }
        .employee-info td:first-child,
        .employee-info td:nth-child(3) {
            width: 120px;
            font-weight: bold;
        }
        .summary {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .summary-box {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 5px;
            flex: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
        }
        .green { background-color: #e6f4ea; color: #137333; }
        .red { background-color: #fce8e6; color: #c5221f; }
        .purple { background-color: #f3e8ff; color: #7e22ce; }
        .orange { background-color: #fef3c7; color: #d97706; }
        .indigo { background-color: #e0e7ff; color: #4f46e5; }
        .gray { background-color: #f3f4f6; color: #374151; }
        .yellow { background-color: #fef08a; color: #854d0e; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-left">
                @if(!empty($headerSettings['left_logo']))
                    <img src="{{ $headerSettings['left_logo'] }}" alt="Logo Kiri">
                @endif
            </div>
            <div class="header-text">
                <h1>{{ $headerSettings['school_name'] ?? 'MTsN 2 KOTA MALANG' }}</h1>
                <h2>{{ $headerSettings['school_full_name'] ?? 'MADRASAH TSANAWIYAH NEGERI 2 KOTA MALANG' }}</h2>
                <p>{{ $headerSettings['school_address'] ?? 'Jl. KH. Hasyim Asy\'ari No. 1, Kota Malang, Jawa Timur 65144' }}</p>
                <p>
                    @if(!empty($headerSettings['school_phone']))
                        Telp: {{ $headerSettings['school_phone'] }}
                    @endif
                    @if(!empty($headerSettings['school_phone']) && !empty($headerSettings['school_email']))
                        |
                    @endif
                    @if(!empty($headerSettings['school_email']))
                        Email: {{ $headerSettings['school_email'] }}
                    @endif
                </p>
            </div>
            <div class="logo-right">
                @if(!empty($headerSettings['right_logo']))
                    <img src="{{ $headerSettings['right_logo'] }}" alt="Logo Kanan">
                @endif
            </div>
        </div>
    </div>

    <div class="report-title">
        <h3>LAPORAN TUNJANGAN KINERJA PEGAWAI</h3>
    </div>

    <div class="employee-info">
        <table>
            <tr>
                <td>Nama Pegawai</td>
                <td>: {{ $employee->full_name }}</td>
                <td>Jabatan</td>
                <td>: {{ $employee->position ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>NIP</td>
                <td>: {{ $employee->nip ?? 'N/A' }}</td>
                <td>Periode</td>
                <td>: {{ \Carbon\Carbon::create($year, $month)->isoFormat('MMMM YYYY') }}</td>
            </tr>
        </table>
    </div>

    <div class="summary">
        <div class="summary-box">
            <strong>Total Tunjangan Kinerja:</strong><br>
            Rp {{ number_format($summary['total_performance_allowance'] ?? 0, 0, ',', '.') }}
        </div>
        <div class="summary-box">
            <strong>Total Uang Makan:</strong><br>
            Rp {{ number_format($summary['total_meal_allowance'] ?? 0, 0, ',', '.') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Shift</th>
                <th>Status Masuk</th>
                <th>Status Pulang</th>
                <th>Uang Tunjangan Harian</th>
                <th>Uang Makan Harian</th>
            </tr>
        </thead>
        <tbody>
            @foreach($detailData as $dayData)
            <tr>
                <td>
                    {{ $dayData['date']->format('d/m/Y') }}<br>
                    <small>{{ $dayData['date']->isoFormat('dddd') }}</small>
                </td>
                <td>
                    @if($dayData['shift'])
                        {{ $dayData['shift']->schedule->shift_name ?? 'N/A' }}
                    @else
                        -
                    @endif
                </td>
                <td>
                    <span class="status-badge
                        @if(str_contains($dayData['status_in'], 'Tepat Waktu')) 
                            green
                        @elseif(str_contains($dayData['status_in'], 'Terlambat')) 
                            red
                        @elseif(str_contains($dayData['status_in'], 'Cuti')) 
                            purple
                        @elseif(str_contains($dayData['status_in'], 'Sakit')) 
                            orange
                        @elseif(str_contains($dayData['status_in'], 'Dinas Luar')) 
                            indigo
                        @else 
                            gray
                        @endif
                    ">
                        {{ $dayData['status_in'] }}
                    </span>
                </td>
                <td>
                    <span class="status-badge
                        @if(str_contains($dayData['status_out'], 'Tepat Waktu')) 
                            green
                        @elseif(str_contains($dayData['status_out'], 'Pulang Awal')) 
                            red
                        @elseif(str_contains($dayData['status_out'], 'Cuti')) 
                            purple
                        @elseif(str_contains($dayData['status_out'], 'Sakit')) 
                            orange
                        @elseif(str_contains($dayData['status_out'], 'Dinas Luar')) 
                            indigo
                        @elseif(str_contains($dayData['status_out'], 'Belum Pulang')) 
                            yellow
                        @else 
                            gray
                        @endif
                    ">
                        {{ $dayData['status_out'] }}
                    </span>
                </td>
                <td class="text-right">Rp {{ number_format($dayData['daily_performance_allowance'], 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($dayData['daily_meal_allowance'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>