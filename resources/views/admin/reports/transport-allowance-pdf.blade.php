<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Uang Transport</title>
    <style>
        @page {
            margin: 2cm 1.5cm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 3px solid #000;
            padding-bottom: 8px;
        }
        .header h2 {
            margin: 4px 0;
            font-size: 14px;
        }
        .header p {
            margin: 2px 0;
            font-size: 9px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px 3px;
            text-align: left;
            font-size: 9px;
        }
        th {
            background-color: #e0e0e0;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 25px;
            text-align: right;
            font-size: 9px;
        }
        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        h3 {
            font-size: 12px;
            margin: 5px 0;
        }
        p {
            font-size: 9px;
            margin: 3px 0;
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

    <h3 style="text-align: center;">LAPORAN UANG TRANSPORT PEGAWAI</h3>
    <p style="text-align: center;">Bulan: {{ $monthName }} {{ $selectedYear }}</p>

    <table>
        <thead>
            <tr>
                <th width="6%">No</th>
                <th width="28%">Nama Pegawai</th>
                <th width="24%">Jabatan</th>
                <th width="12%">Hari Hadir</th>
                <th width="15%">Uang Transport/Hari</th>
                <th width="15%">Total Uang Transport</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAllowance = 0; @endphp
            @foreach($transportAllowanceData as $index => $data)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $data['employee']->full_name }}</td>
                    <td>{{ $data['employee']->position }}</td>
                    <td class="text-center">{{ $data['present_days'] }}</td>
                    <td class="text-right">Rp {{ number_format($data['daily_transport_allowance'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($data['total_transport_allowance'], 0, ',', '.') }}</td>
                </tr>
                @php $totalAllowance += $data['total_transport_allowance']; @endphp
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
