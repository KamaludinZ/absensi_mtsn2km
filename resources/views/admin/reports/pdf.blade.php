<!DOCTYPE html>
<html>
<head>
    <title>Laporan Absensi</title>
    <style>
        body { font-family: Arial; font-size: 7px; margin: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 2px; font-size: 7px; }
        th { background-color: #ddd; font-weight: bold; text-align: center; }
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
            width: 50px;
            vertical-align: middle;
        }
        .logo-left img, .logo-right img {
            max-width: 40px;
            max-height: 40px;
        }
        .header-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 11px;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 2px 0;
            font-size: 10px;
            font-weight: normal;
        }
        .header h3 {
            margin: 3px 0;
            font-size: 10px;
        }
        .header p {
            margin: 1px 0;
            font-size: 7px;
        }
        .report-title {
            text-align: center;
            margin: 8px 0;
        }
        .report-title h3 {
            margin: 2px 0;
            font-size: 9px;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .tc { text-align: center; }
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
                <h1>{{ $headerSettings['school_name'] ?? $schoolName }}</h1>
                <h2>{{ $headerSettings['school_full_name'] ?? 'MADRASAH TSANAWIYAH NEGERI 2 KOTA MALANG' }}</h2>
                <p>{{ $headerSettings['school_address'] ?? $schoolAddress }}</p>
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
        <h3>Laporan Absensi {{ $type === 'employee' ? 'Pegawai' : 'Siswa' }}</h3>
        <p>Periode: {{ $startDate }} - {{ $endDate }}</p>
    </div>

    @if($type === 'employee')
    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>Tanggal</th>
                <th>Shift</th>
                <th>Jam Masuk</th>
                <th>Abs Masuk</th>
                <th>Selisih</th>
                <th>Status</th>
                <th>Jam Pulang</th>
                <th>Abs Pulang</th>
                <th>Selisih</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $data)
            <tr>
                <td>{{ $data['name'] }}</td>
                <td>{{ $data['position'] }}</td>
                <td class="tc">{{ $data['date'] }}</td>
                <td class="tc">{{ $data['shift_name'] ?? '-' }}</td>
                <td class="tc">{{ $data['schedule_start'] }}</td>
                <td class="tc">{{ $data['clock_in_time'] }}</td>
                <td class="tc">{{ $data['clock_in_diff'] }}</td>
                <td class="tc">{{ $data['clock_in_status'] }}</td>
                <td class="tc">{{ $data['schedule_end'] }}</td>
                <td class="tc">{{ $data['clock_out_time'] }}</td>
                <td class="tc">{{ $data['clock_out_diff'] }}</td>
                <td class="tc">{{ $data['clock_out_status'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    {{-- Student Report --}}
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>NISN</th>
                <th>Nama</th>
                <th>Rombel</th>
                <th>Jam Masuk</th>
                <th>Abs Masuk</th>
                <th>Selisih</th>
                <th>Status</th>
                <th>Jam Pulang</th>
                <th>Abs Pulang</th>
                <th>Selisih</th>
                <th>Status</th>
                <th>Koordinat Masuk</th>
                <th>Koordinat Pulang</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $data)
            <tr>
                <td class="tc">{{ $data['date'] }}</td>
                <td>{{ $data['nisn'] }}</td>
                <td>{{ $data['name'] }}</td>
                <td>{{ $data['rombel'] }}</td>
                <td class="tc">{{ $data['schedule_start'] }}</td>
                <td class="tc">{{ $data['clock_in_time'] }}</td>
                <td class="tc">{{ $data['clock_in_diff'] }}</td>
                <td class="tc">{{ $data['clock_in_status'] }}</td>
                <td class="tc">{{ $data['schedule_end'] }}</td>
                <td class="tc">{{ $data['clock_out_time'] }}</td>
                <td class="tc">{{ $data['clock_out_diff'] }}</td>
                <td class="tc">{{ $data['clock_out_status'] }}</td>
                <td>
                    @if($data['attendance']->clock_in_latitude && $data['attendance']->clock_in_longitude)
                        Lat: {{ $data['attendance']->clock_in_latitude }}, Lng: {{ $data['attendance']->clock_in_longitude }}
                        @if($data['attendance']->clock_in_accuracy)
                            (Ak: {{ $data['attendance']->clock_in_accuracy }}m)
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td>
                    @if($data['attendance']->clock_out_latitude && $data['attendance']->clock_out_longitude)
                        Lat: {{ $data['attendance']->clock_out_latitude }}, Lng: {{ $data['attendance']->clock_out_longitude }}
                        @if($data['attendance']->clock_out_accuracy)
                            (Ak: {{ $data['attendance']->clock_out_accuracy }}m)
                        @endif
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <p style="margin-top: 10px; font-size: 7px; text-align: right;">Dicetak: {{ $generatedAt }}</p>
</body>
</html>
