<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Gaji</title>
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

    <h3 style="text-align: center; text-transform: uppercase; text-decoration: underline;">LAPORAN GAJI PEGAWAI</h3>
    <p style="text-align: center;">Bulan: {{ $monthName }} {{ $selectedYear }}</p>

    <table>
        <thead>
            <tr>
                <th width="3%">No</th>
                <th width="20%">Nama Pegawai</th>
                <th width="15%">Jabatan</th>
                <th width="12%">Gaji Pokok</th>
                <th width="12%">Uang Makan</th>
                <th width="12%">Uang Transport</th>
                <th width="12%">Tunj. Kinerja</th>
                <th width="14%">Total Gaji</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalBaseSalary = 0;
                $totalMeal = 0;
                $totalTransport = 0;
                $totalPerformance = 0;
                $totalAllSalary = 0;
            @endphp
            @foreach($salaryData as $index => $data)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $data['employee']->full_name }}</td>
                    <td>{{ $data['employee']->position }}</td>
                    <td class="text-right">Rp {{ number_format($data['base_salary'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($data['total_meal_allowance'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($data['total_transport_allowance'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($data['total_performance_allowance'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($data['total_salary'], 0, ',', '.') }}</td>
                </tr>
                @php
                    $totalBaseSalary += $data['base_salary'];
                    $totalMeal += $data['total_meal_allowance'];
                    $totalTransport += $data['total_transport_allowance'];
                    $totalPerformance += $data['total_performance_allowance'];
                    $totalAllSalary += $data['total_salary'];
                @endphp
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL ({{ count($salaryData) }} Pegawai)</td>
                <td class="text-right">Rp {{ number_format($totalBaseSalary, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalMeal, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalTransport, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalPerformance, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalAllSalary, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
