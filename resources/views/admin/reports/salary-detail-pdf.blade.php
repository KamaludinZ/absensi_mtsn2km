<!DOCTYPE html>
<html>
<head>
    <title>Laporan Gaji - {{ $employee->full_name }} ({{ \Carbon\Carbon::create($year, $month)->isoFormat('MMMM YYYY') }})</title>
    <style>
        @page {
            margin: 20px;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt; /* Reduced font size */
            line-height: 1.2;
        }
        h1 {
            font-size: 14pt;
            text-align: center;
            margin: 0 0 5px 0;
        }
        .header-info {
            width: 100%;
            margin-bottom: 10px;
            font-size: 8pt;
            border-collapse: collapse;
        }
        .header-info td {
            padding: 1px 2px;
            border: none;
        }
        .header-info .label {
            width: 100px;
            font-weight: bold;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .main-table th, .main-table td {
            border: 1px solid #999;
            padding: 3px;
            text-align: left;
        }
        .main-table th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .summary-section {
            width: 45%;
            margin-left: auto;
            margin-top: 10px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            border: 1px solid #999;
            padding: 3px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .signature-table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }
        .signature-table td {
            border: none;
            width: 50%;
            text-align: center;
            padding: 0;
        }
        .signature-space {
            height: 50px;
        }
        .signature-name {
            display: inline-block;
            border-top: 1px solid black;
            padding: 2px 10px 0 10px;
        }
    </style>
</head>
<body>
    <h1>LAPORAN GAJI PEGAWAI</h1>

    <table class="header-info">
        <tr>
            <td class="label">Nama Pegawai</td>
            <td>: {{ $employee->full_name }}</td>
            <td class="label">Periode</td>
            <td>: {{ \Carbon\Carbon::create($year, $month)->isoFormat('MMMM YYYY') }}</td>
        </tr>
        <tr>
            <td class="label">Jabatan</td>
            <td>: {{ $employee->position ?? 'N/A' }}</td>
            <td class="label">NIP</td>
            <td>: {{ $employee->nip ?? 'N/A' }}</td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 8%;">Hari</th>
                <th style="width: 12%;">Tanggal</th>
                <th style="width: 8%;">Jam Masuk</th>
                <th style="width: 15%;">Absensi Masuk</th>
                <th style="width: 8%;">Jam Pulang</th>
                <th style="width: 15%;">Absensi Pulang</th>
                <th style="width: 10%;">Uang Makan</th>
                <th style="width: 10%;">Uang Transport</th>
                <th style="width: 10%;">Tunj. Kinerja</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detailData as $data)
                <tr>
                    <td>{{ $data['date']->isoFormat('dddd') }}</td>
                    <td>{{ $data['date']->isoFormat('D MMM YYYY') }}</td>
                    <td class="text-center">{{ $data['schedule'] ? \Carbon\Carbon::parse($data['schedule']->start_time)->format('H:i') : '-' }}</td>
                    <td>
                        {{ $data['attendance'] && $data['attendance']->clock_in ? \Carbon\Carbon::parse($data['attendance']->clock_in)->format('H:i') : '-' }}
                        ({{ $data['status_in'] }})
                    </td>
                    <td class="text-center">{{ $data['schedule'] ? \Carbon\Carbon::parse($data['schedule']->end_time)->format('H:i') : '-' }}</td>
                    <td>
                        {{ $data['attendance'] && $data['attendance']->clock_out ? \Carbon\Carbon::parse($data['attendance']->clock_out)->format('H:i:s') : '-' }}
                        ({{ $data['status_out'] }})
                    </td>
                    <td class="text-right">Rp{{ number_format($data['daily_meal_allowance'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp{{ number_format($data['daily_transport_allowance'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp{{ number_format($data['daily_performance_allowance'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data kehadiran untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-section">
        <table class="summary-table">
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
            <tr>
                <td class="font-bold">Total Gaji</td>
                <td class="text-right font-bold">Rp {{ number_format($summary['total_salary'], 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <table class="signature-table">
        <tr>
            <td>
                <p>Pegawai Yang Bersangkutan,</p>
                <div class="signature-space"></div>
                <p class="signature-name">({{ $employee->full_name }})</p>
            </td>
            <td>
                <p>Mengetahui,</p>
                <div class="signature-space"></div>
                <p class="signature-name">(........................................)</p>
            </td>
        </tr>
    </table>
</body>
</html>
