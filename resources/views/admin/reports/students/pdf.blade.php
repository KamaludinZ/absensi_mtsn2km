<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Absensi Siswa</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 9px; margin: 10px; }
        table { width: 100%; border-collapse: collapse; }
        .data th, .data td { border: 1px solid #000; padding: 3px; }
        .data th { background-color: #ddd; text-align: center; }
        .tc { text-align: center; }
        .muted { color: #666; }
        .header { border-bottom: 3px solid #000; padding-bottom: 5px; margin-bottom: 10px; }
        .header td { border: none; vertical-align: middle; text-align: center; }
        .header img { max-width: 50px; max-height: 50px; }
        .header h1 { margin: 0; font-size: 13px; text-transform: uppercase; }
        .header h2 { margin: 2px 0; font-size: 11px; font-weight: normal; }
        .header p { margin: 1px 0; font-size: 8px; }
        .title { text-align: center; margin: 8px 0; }
        .title h3 { margin: 2px 0; font-size: 11px; text-decoration: underline; text-transform: uppercase; }
        .title p { margin: 2px 0; }
        .info td { padding: 1px 4px; }
        .signature { width: 35%; margin-left: 65%; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 60px;">
                @if(!empty($headerSettings['left_logo']))<img src="{{ $headerSettings['left_logo'] }}" alt="">@endif
            </td>
            <td>
                <h1>{{ $headerSettings['school_name'] }}</h1>
                <h2>{{ $headerSettings['school_full_name'] }}</h2>
                <p>{{ $headerSettings['school_address'] }}</p>
                <p>
                    @if(!empty($headerSettings['school_phone']))Telp: {{ $headerSettings['school_phone'] }}@endif
                    @if(!empty($headerSettings['school_phone']) && !empty($headerSettings['school_email'])) | @endif
                    @if(!empty($headerSettings['school_email']))Email: {{ $headerSettings['school_email'] }}@endif
                </p>
            </td>
            <td style="width: 60px;">
                @if(!empty($headerSettings['right_logo']))<img src="{{ $headerSettings['right_logo'] }}" alt="">@endif
            </td>
        </tr>
    </table>

    <div class="title">
        <h3>Rekap Absensi Siswa</h3>
        <p>{{ $scopeLabel }}</p>
        <p>Periode {{ $start->translatedFormat('d F Y') }} – {{ $end->translatedFormat('d F Y') }} · {{ $schoolDays->count() }} hari sekolah</p>
    </div>

    @if($studentRow)
        <table class="info" style="width: auto; margin-bottom: 8px;">
            <tr><td>NISN</td><td>: {{ $student->nisn ?? '-' }}</td></tr>
            <tr><td>Nama</td><td>: {{ $student->full_name }}</td></tr>
            <tr><td>Rombel</td><td>: {{ $student->rombel->name ?? '-' }}</td></tr>
            <tr><td>Rekap</td><td>: Hadir {{ $studentRow['counts']['H'] }}, Terlambat {{ $studentRow['counts']['T'] }},
                Sakit {{ $studentRow['counts']['S'] }}, Izin {{ $studentRow['counts']['I'] }}, Alpa {{ $studentRow['counts']['A'] }}
                @if($studentRow['counts']['M']), Izin belum disetujui {{ $studentRow['counts']['M'] }}@endif
                · Kehadiran {{ $studentRow['percentage'] !== null ? $studentRow['percentage'] . '%' : '-' }}</td></tr>
        </table>
        <table class="data">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Absen Masuk</th>
                    <th>Absen Pulang</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($calendar->filter(fn ($d) => $d['code'] !== null) ->values() as $i => $day)
                    <tr>
                        <td class="tc">{{ $i + 1 }}</td>
                        <td>{{ $day['date']->translatedFormat('l, d/m/Y') }}</td>
                        <td class="tc">{{ $day['label'] }}</td>
                        <td class="tc">{{ $day['attendance']?->clock_in?->format('H:i') ?? '-' }}</td>
                        <td class="tc">{{ $day['attendance']?->clock_out?->format('H:i') ?? '-' }}</td>
                        <td>{{ $day['note'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th>No</th>
                    <th>NISN</th>
                    <th>Nama Siswa</th>
                    <th>Rombel</th>
                    <th>Hari Sekolah</th>
                    <th>Hadir</th>
                    <th>Terlambat</th>
                    <th>Sakit</th>
                    <th>Izin</th>
                    <th>Alpa</th>
                    <th>Izin Belum Disetujui</th>
                    <th>% Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $row)
                    <tr>
                        <td class="tc">{{ $i + 1 }}</td>
                        <td>{{ $row['student']->nisn ?? '-' }}</td>
                        <td>{{ $row['student']->full_name }}</td>
                        <td>{{ $rombel->name ?? $row['student']->rombel->name ?? '-' }}</td>
                        <td class="tc">{{ $row['effective_days'] }}</td>
                        @foreach(['H', 'T', 'S', 'I', 'A', 'M'] as $code)
                            <td class="tc">{{ $row['counts'][$code] }}</td>
                        @endforeach
                        <td class="tc">{{ $row['percentage'] !== null ? $row['percentage'] . '%' : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="tc muted">Tidak ada siswa untuk filter ini</td></tr>
                @endforelse
                <tr>
                    <th colspan="5">Jumlah</th>
                    @foreach(['H', 'T', 'S', 'I', 'A', 'M'] as $code)
                        <th>{{ $totals[$code] }}</th>
                    @endforeach
                    <th></th>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="signature">
        <p>Mengetahui,</p>
        <p>{{ $headerSettings['active_signer_title'] }}</p>
        <br><br><br>
        <p>({{ $headerSettings['active_signer_name'] }})</p>
        <p>NIP: {{ $headerSettings['active_signer_nip'] }}</p>
    </div>

    <p class="muted" style="margin-top: 10px; text-align: right;">Dicetak: {{ $generatedAt }}</p>
</body>
</html>
