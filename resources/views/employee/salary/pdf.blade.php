<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Gaji - {{ $employee->full_name }} - {{ $monthName }} {{ $year }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
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
            margin: 15px 0;
        }
        .report-title h3 {
            margin: 5px 0;
            font-size: 13px;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .employee-info {
            margin-bottom: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: auto auto;
            gap: 10px;
        }
        .info-label {
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .summary-section {
            margin-top: 20px;
        }
        .signature-section {
            margin-top: 15px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
        }
        .signature-box {
            text-align: center;
        }
        .border-bottom {
            border-bottom: 1px solid #000;
            margin: 20px 0;
            padding-bottom: 5px;
        }
        .text-gray-600 {
            color: #666;
        }
        .font-semibold {
            font-weight: 600;
        }
        .text-lg {
            font-size: 14px;
        }
        .mb-8 {
            margin-bottom: 32px;
        }
        .mt-16 {
            margin-top: 64px;
        }
        .font-bold {
            font-weight: bold;
        }
        .text-green-600 {
            color: #059669;
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
        <h3>LAPORAN GAJI PEGAWAI</h3>
    </div>

    <div class="employee-info">
        <div class="info-grid">
            <div><span class="info-label">Nama Pegawai:</span> {{ $employee->full_name }}</div>
            <div><span class="info-label">Jabatan:</span> {{ $employee->position ?? 'N/A' }}</div>
            <div><span class="info-label">NIP:</span> {{ $employee->nip ?? 'N/A' }}</div>
            <div><span class="info-label">Periode:</span> {{ \Carbon\Carbon::create($year, $month)->isoFormat('MMMM YYYY') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-left">Hari</th>
                <th class="text-left">Tanggal</th>
                <th class="text-left">Jam Masuk</th>
                <th class="text-left">Absensi Masuk</th>
                <th class="text-left">Jam Pulang</th>
                <th class="text-left">Absensi Pulang</th>
                <th class="text-right">Uang Makan</th>
                <th class="text-right">Uang Transport</th>
                <th class="text-right">Tunj. Kinerja</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detailData as $data)
                @php $permissionType = $permissionMap[$data['date']->format('Y-m-d')] ?? null; @endphp
                <tr>
                    <td>{{ $data['date']->isoFormat('dddd') }}</td>
                    <td>{{ $data['date']->isoFormat('D MMM YYYY') }}</td>
                    <td>
                        {{ $data['schedule'] ? \Carbon\Carbon::parse($data['schedule']->start_time)->format('H:i') : '-' }}
                    </td>
                    <td>
                        @if($permissionType)
                            <span class="font-semibold">{{ $permissionType }}</span>
                        @else
                            <div>{{ $data['attendance'] && $data['attendance']->clock_in ? \Carbon\Carbon::parse($data['attendance']->clock_in)->format('H:i') : '-' }}</div>
                            <div class="text-gray-600 text-xs">{{ $data['status_in'] }}</div>
                        @endif
                    </td>
                    <td>
                        {{ $data['schedule'] ? \Carbon\Carbon::parse($data['schedule']->end_time)->format('H:i') : '-' }}
                    </td>
                    <td>
                        @if($permissionType)
                            <span class="font-semibold">{{ $permissionType }}</span>
                        @else
                            <div>{{ $data['attendance'] && $data['attendance']->clock_out ? \Carbon\Carbon::parse($data['attendance']->clock_out)->format('H:i') : '-' }}</div>
                            <div class="text-gray-600 text-xs">{{ $data['status_out'] }}</div>
                        @endif
                    </td>
                    <td class="text-right">Rp {{ number_format($data['daily_meal_allowance'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($data['daily_transport_allowance'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($data['daily_performance_allowance'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data kehadiran untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-section">
        <h3>Ringkasan Gaji</h3>
        <table style="width: 40%; float: right;">
            <tr>
                <td>Gaji Pokok</td>
                <td class="text-right">Rp {{ number_format($summary['base_salary'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Uang Makan</td>
                <td class="text-right">Rp {{ number_format($summary['total_meal_allowance'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Uang Transport</td>
                <td class="text-right">Rp {{ number_format($summary['total_transport_allowance'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Tunjangan Kinerja</td>
                <td class="text-right">Rp {{ number_format($summary['total_performance_allowance'], 0, ',', '.') }}</td>
            </tr>
            <tr style="font-weight: bold; border-top: 2px solid #000;">
                <td>Total Gaji</td>
                <td class="text-right text-green-600">Rp {{ number_format($summary['total_salary'], 0, ',', '.') }}</td>
            </tr>
        </table>
        <div style="clear: both;"></div>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <p class="mb-8">Mengetahui,</p>
            <p>{{ $headerSettings['active_signer_title'] ?? 'Kepala Madrasah' }}</p>
            <p class="mt-16 font-bold border-bottom">({{ $headerSettings['active_signer_name'] ?? '................................' }})</p>
            <p class="text-gray-600">NIP: {{ $headerSettings['active_signer_nip'] ?? '................................' }}</p>
        </div>
        <div class="signature-box">
            <p class="mb-8" style="visibility: hidden; margin: 0;">Mengetahui,</p>
            <p class="mb-8">Pegawai Yang Bersangkutan,</p>
            <p class="mt-16 font-bold border-bottom">({{ $employee->full_name }})</p>
            <p class="text-gray-600">NIP: {{ $employee->nip ?? '-' }}</p>
        </div>
    </div>
</body>
</html>