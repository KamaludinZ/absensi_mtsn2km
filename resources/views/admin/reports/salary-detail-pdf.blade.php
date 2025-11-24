<!DOCTYPE html>
<html>
<head>
    <title>Laporan Gaji - {{ $employee->full_name }} ({{ \Carbon\Carbon::create($year, $month)->isoFormat('MMMM YYYY') }})</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0.5in;
            font-size: 10pt;
        }
        h1, h2, h3 {
            text-align: center;
            margin-bottom: 10px;
        }
        .header-info {
            width: 100%;
            margin-bottom: 20px;
        }
        .header-info td {
            padding: 2px 0;
        }
        .header-info .label {
            width: 150px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .summary-section {
            width: 50%;
            margin-left: auto;
            margin-top: 30px;
            border: 1px solid #ddd;
            padding: 10px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }
        .summary-total {
            font-weight: bold;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            margin-top: 5px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-around;
            text-align: center;
            width: 100%;
        }
        .signature-box {
            width: 30%;
            display: inline-block;
            margin-top: 50px; /* Space for signature */
        }
        .signature-name {
            margin-top: 5px;
            border-top: 1px solid black;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <h1>LAPORAN GAJI PEGAWAI</h1>
    <h2>MTsN 2 Kota Malang</h2>

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

    <h3>Rincian Kehadiran Harian</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">Hari</th>
                <th style="width: 15%;">Tanggal</th>
                <th style="width: 10%;">Jam Masuk</th>
                <th style="width: 15%;">Absensi Masuk</th>
                <th style="width: 10%;">Jam Pulang</th>
                <th style="width: 15%;">Absensi Pulang</th>
                <th style="width: 8%;">Uang Makan</th>
                <th style="width: 8%;">Uang Transport</th>
                <th style="width: 9%;">Tunj. Kinerja</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detailData as $data)
                <tr>
                    <td>{{ $data['date']->isoFormat('dddd') }}</td>
                    <td>{{ $data['date']->isoFormat('D MMMM YYYY') }}</td>
                    <td>{{ $data['attendance'] ? ($data['attendance']->clock_in ? $data['attendance']->clock_in->format('H:i:s') : '-') : 'Libur/Tidak Ada Jadwal' }}</td>
                    <td>{{ $data['attendance'] ? $data['attendance']->status_in ?? 'Tepat Waktu' : '-' }}</td>
                    <td>{{ $data['attendance'] ? ($data['attendance']->clock_out ? $data['attendance']->clock_out->format('H:i:s') : '-') : '-' }}</td>
                    <td>{{ $data['attendance'] ? $data['attendance']->status_out ?? 'Tepat Waktu' : '-' }}</td>
                    <td class="text-right">Rp {{ number_format($data['daily_meal_allowance'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($data['daily_transport_allowance'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($data['daily_performance_allowance'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">
                        Tidak ada data kehadiran untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-section">
        <h3>Ringkasan Gaji</h3>
        <div class="summary-item">
            <span>Gaji Pokok</span>
            <span>Rp {{ number_format($summary['base_salary'], 0, ',', '.') }}</span>
        </div>
        <div class="summary-item">
            <span>Total Uang Makan</span>
            <span>Rp {{ number_format($summary['total_meal_allowance'], 0, ',', '.') }}</span>
        </div>
        <div class="summary-item">
            <span>Total Uang Transport</span>
            <span>Rp {{ number_format($summary['total_transport_allowance'], 0, ',', '.') }}</span>
        </div>
        <div class="summary-item">
            <span>Total Tunjangan Kinerja</span>
            <span>Rp {{ number_format($summary['total_performance_allowance'], 0, ',', '.') }}</span>
        </div>
        <div class="summary-item summary-total">
            <span>Total Gaji</span>
            <span>Rp {{ number_format($summary['total_salary'], 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <p>Pegawai Yang Bersangkutan,</p>
            <div class="signature-name">({{ $employee->full_name }})</div>
        </div>
        <div class="signature-box">
            <p>Mengetahui,</p>
            <div class="signature-name">(........................................)</div>
        </div>
    </div>
</body>
</html>