<!DOCTYPE html>
<html>
<head>
    <title>Laporan Uang Makan - {{ $employee->full_name }} ({{ \Carbon\Carbon::create($year, $month)->isoFormat('MMMM YYYY') }})</title>
    <style>
        @page {
            margin: 20px;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 7pt; /* Reduced font size */
            line-height: 1.1;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 3px solid #000;
            padding-bottom: 5px;
        }
        .header-content {
            display: table;
            width: 100%;
        }
        .logo-left, .logo-right {
            display: table-cell;
            width: 60px;
            vertical-align: middle;
        }
        .logo-left img, .logo-right img {
            max-width: 50px;
            max-height: 50px;
        }
        .header-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 11pt;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 2px 0;
            font-size: 10pt;
            font-weight: normal;
        }
        .header p {
            margin: 1px 0;
            font-size: 7pt;
        }
        .report-title {
            text-align: center;
            margin: 10px 0;
        }
        .report-title h3 {
            margin: 3px 0;
            font-size: 10pt;
            text-transform: uppercase;
            text-decoration: underline;
        }
        h1 {
            font-size: 12pt;
            text-align: center;
            margin: 0 0 5px 0;
        }
        .header-info {
            width: 100%;
            margin-bottom: 5px;
            font-size: 7pt;
            border-collapse: collapse;
        }
        .header-info td {
            padding: 1px;
            border: none;
        }
        .header-info .label {
            width: 80px;
            font-weight: bold;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .main-table th, .main-table td {
            border: 1px solid #999;
            padding: 2px;
            text-align: left;
        }
        .main-table th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .summary-section {
            width: 50%;
            margin-left: auto;
            margin-top: 5px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
        }
        .summary-table td {
            border: 1px solid #999;
            padding: 2px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
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
        <h3>LAPORAN UANG MAKAN PEGAWAI</h3>
    </div>

    <table class="header-info">
        <tr>
            <td class="label">Nama Pegawai</td>
            <td>: {{ $employee->full_name }}</td>
            <td class="label">Jabatan</td>
            <td>: {{ $employee->position ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">NIP</td>
            <td>: {{ $employee->nip ?? 'N/A' }}</td>
            <td class="label">Periode</td>
            <td>: {{ \Carbon\Carbon::create($year, $month)->isoFormat('MMMM YYYY') }}</td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th>Hari</th>
                <th>Tanggal</th>
                <th>Jam Masuk</th>
                <th>Absensi Masuk</th>
                <th>Jam Pulang</th>
                <th>Absensi Pulang</th>
                <th>U. Makan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detailData as $data)
                @php $permissionType = $permissionMap[$data['date']->format('Y-m-d')] ?? null; @endphp
                <tr>
                    <td>{{ $data['date']->isoFormat('dddd') }}</td>
                    <td>{{ $data['date']->isoFormat('D MMM YYYY') }}</td>
                    <td class="text-center">{{ $data['schedule'] ? \Carbon\Carbon::parse($data['schedule']->start_time)->format('H:i') : '-' }}</td>
                    <td>
                        @if($permissionType)
                            <span class="font-bold">{{ $permissionType }}</span>
                        @else
                            {{ $data['attendance'] && $data['attendance']->clock_in ? \Carbon\Carbon::parse($data['attendance']->clock_in)->format('H:i') : '-' }}
                            ({{ $data['status_in'] }})
                        @endif
                    </td>
                    <td class="text-center">{{ $data['schedule'] ? \Carbon\Carbon::parse($data['schedule']->end_time)->format('H:i') : '-' }}</td>
                    <td>
                        @if($permissionType)
                            <span class="font-bold">{{ $permissionType }}</span>
                        @else
                            {{ $data['attendance'] && $data['attendance']->clock_out ? \Carbon\Carbon::parse($data['attendance']->clock_out)->format('H:i') : '-' }}
                            ({{ $data['status_out'] }})
                        @endif
                    </td>
                    <td class="text-right">Rp{{ number_format($data['daily_meal_allowance'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data kehadiran untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-section">
        <table class="summary-table">
            <tr>
                <td>Total Uang Makan</td>
                <td class="text-right">Rp {{ number_format($summary['total_meal_allowance'], 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

</body>
</html>
