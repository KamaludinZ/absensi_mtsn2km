<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Tunjangan Kinerja</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 16px;
        }
        .header p {
            margin: 3px 0;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #e0e0e0;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(isset($headerSettings['logo_path']) && $headerSettings['logo_path'])
            <img src="{{ public_path('storage/' . $headerSettings['logo_path']) }}" alt="Logo" style="height: 60px; margin-bottom: 10px;">
        @endif
        <h2>{{ $headerSettings['institution_name'] ?? 'MTsN 2 Kota Malang' }}</h2>
        <p>{{ $headerSettings['address'] ?? '' }}</p>
        <p>{{ $headerSettings['phone'] ?? '' }} | {{ $headerSettings['email'] ?? '' }}</p>
    </div>

    <h3 style="text-align: center;">LAPORAN TUNJANGAN KINERJA PEGAWAI</h3>
    <p style="text-align: center;">Bulan: {{ $monthName }} {{ $selectedYear }}</p>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="25%">Nama Pegawai</th>
                <th width="20%">Jabatan</th>
                <th width="15%">Hari Sempurna</th>
                <th width="17.5%">Tunjangan/Hari</th>
                <th width="17.5%">Total Tunjangan</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAllowance = 0; @endphp
            @foreach($performanceData as $index => $data)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $data['employee']->full_name }}</td>
                    <td>{{ $data['employee']->position }}</td>
                    <td class="text-center">{{ $data['perfect_days'] }}</td>
                    <td class="text-right">Rp {{ number_format($data['daily_performance_allowance'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($data['total_performance_allowance'], 0, ',', '.') }}</td>
                </tr>
                @php $totalAllowance += $data['total_performance_allowance']; @endphp
            @endforeach
            <tr class="total-row">
                <td colspan="5" class="text-right">TOTAL</td>
                <td class="text-right">Rp {{ number_format($totalAllowance, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Malang, {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}</p>
        <p>Kepala Madrasah,</p>
        <br><br><br>
        <p>{{ $headerSettings['headmaster_name'] ?? '____________________' }}</p>
        <p>NIP. {{ $headerSettings['headmaster_nip'] ?? '____________________' }}</p>
    </div>
</body>
</html>
