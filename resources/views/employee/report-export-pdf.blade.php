<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Absensi Pegawai</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 15px;
            font-size: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 3px solid #000;
            padding-bottom: 5px;
            position: relative;
        }
        .header-content {
            display: table;
            width: 100%;
        }
        .logo-left, .logo-right {
            display: table-cell;
            width: 80px;
            vertical-align: middle;
        }
        .logo-left img, .logo-right img {
            max-width: 70px;
            max-height: 70px;
        }
        .header-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
            font-weight: normal;
        }
        .header p {
            margin: 2px 0;
            font-size: 10px;
        }
        .report-title {
            text-align: center;
            margin: 10px 0;
        }
        .report-title h3 {
            margin: 5px 0;
            font-size: 13px;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .employee-info {
            margin-bottom: 15px;
        }
        .employee-info table {
            border: none;
        }
        .employee-info td {
            border: none;
            padding: 2px 4px;
            font-size: 10px;
        }
        .employee-info td:first-child,
        .employee-info td:nth-child(3) {
            width: 120px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px 3px;
            text-align: left;
            font-size: 8px;
        }
        th {
            background-color: #e0e0e0;
            font-weight: bold;
            text-align: center;
        }
        .tc { text-align: center; }
        .signature-section {
            margin-top: 10px;
            page-break-inside: avoid;
        }
        .signature-box {
            display: inline-block;
            width: 45%;
            text-align: center;
            vertical-align: top;
        }
        .signature-box.right {
            float: right;
        }
        .signature-name {
            margin-top: 45px;
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 200px;
            padding: 0 10px;
        }
        .signature-info {
            font-size: 10px;
            margin-top: 5px;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
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
        <h3>LAPORAN ABSENSI PEGAWAI</h3>
    </div>

    <div class="employee-info">
        <table>
            <tr>
                <td>Nama Pegawai</td>
                <td>: {{ $employee->full_name }}</td>
                <td>Jabatan</td>
                <td>: {{ $employee->position ?? '-' }}</td>
            </tr>
            <tr>
                <td>NIP</td>
                <td>: {{ $employee->nip ?? '-' }}</td>
                <td>Periode</td>
                <td>: {{ $period ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th class="tc" style="width: 8%;">No</th>
                <th class="tc" style="width: 10%;">Tanggal</th>
                <th class="tc" style="width: 10%;">Hari</th>
                <th class="tc" style="width: 8%;">Jam Masuk</th>
                <th class="tc" style="width: 10%;">Absen Masuk</th>
                <th class="tc" style="width: 10%;">Status Masuk</th>
                <th class="tc" style="width: 8%;">Jam Pulang</th>
                <th class="tc" style="width: 10%;">Absen Pulang</th>
                <th class="tc" style="width: 10%;">Status Pulang</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $index => $data)
            <tr>
                <td class="tc">{{ $index + 1 }}</td>
                <td class="tc">{{ $data['date'] }}</td>
                <td class="tc">{{ $data['day'] }}</td>
                <td class="tc">{{ $data['schedule_start'] }}</td>
                <td class="tc">{{ $data['clock_in'] }}</td>
                <td class="tc">{{ $data['status_in'] }}</td>
                <td class="tc">{{ $data['schedule_end'] }}</td>
                <td class="tc">{{ $data['clock_out'] }}</td>
                <td class="tc">{{ $data['status_out'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="tc">Tidak ada data absensi</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-section clearfix">
        <div class="signature-box">
            <p style="visibility: hidden; margin: 0;">Mengetahui,</p>
            <p>Pegawai Yang Bersangkutan,</p>
            <div class="signature-name">({{ $employee->full_name }})</div>
            <p class="signature-info">NIP: {{ $employee->nip ?? '-' }}</p>
        </div>
        <div class="signature-box right">
            <p>Mengetahui,</p>
            <p>Kepala Madrasah</p>
            <div class="signature-name">({{ $headerSettings['headmaster_name'] ?? '................................' }})</div>
            <p class="signature-info">NIP: {{ $headerSettings['headmaster_nip'] ?? '................................' }}</p>
        </div>
    </div>
</body>
</html>
