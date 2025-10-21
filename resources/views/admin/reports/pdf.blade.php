<!DOCTYPE html>
<html>
<head>
    <title>Laporan Absensi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
            font-size: 8px;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
        }
        .header h3 {
            margin: 5px 0;
            font-size: 12px;
        }
        .header p {
            margin: 3px 0;
            font-size: 10px;
        }
        .text-center {
            text-align: center;
        }
        .text-red {
            color: #dc2626;
        }
        .text-green {
            color: #16a34a;
        }
        .text-yellow {
            color: #ca8a04;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $schoolName }}</h2>
        <p>{{ $schoolAddress }}</p>
        <h3>Laporan Absensi {{ $type === 'employee' ? 'Pegawai' : 'Siswa' }}</h3>
        <p>Periode: {{ $startDate }} - {{ $endDate }}</p>
    </div>

    @if($type === 'employee')
    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Nama Pegawai</th>
                <th style="width: 8%;">Jabatan</th>
                <th style="width: 7%;">Tanggal</th>
                <th style="width: 7%;">Shift</th>
                <th style="width: 6%;">Jam Masuk</th>
                <th style="width: 6%;">Abs Masuk</th>
                <th style="width: 6%;">Selisih</th>
                <th style="width: 7%;">Status</th>
                <th style="width: 6%;">Jam Pulang</th>
                <th style="width: 6%;">Abs Pulang</th>
                <th style="width: 6%;">Selisih</th>
                <th style="width: 7%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
                @php
                    $shiftName = null;
                    $schedule = null;

                    // Try to get shift from employee_shifts table
                    $employeeShift = \App\Models\EmployeeShift::where('employee_id', $attendance->attendable_id)
                        ->where('date', $attendance->date)
                        ->with('schedule')
                        ->first();

                    if ($employeeShift && $employeeShift->schedule) {
                        $schedule = $employeeShift->schedule;
                        $shiftName = $schedule->shift_name;
                    } else {
                        // Try to get schedule by day of week
                        $dayOfWeek = $attendance->date->dayOfWeek;
                        $schedule = \App\Models\Schedule::where('type', 'pegawai')
                            ->where('day_of_week', $dayOfWeek)
                            ->first();
                        if ($schedule) {
                            $shiftName = $schedule->shift_name;
                        }
                    }
                @endphp
            <tr>
                <td>{{ $attendance->attendable->full_name ?? '-' }}</td>
                <td>{{ $attendance->attendable->position ?? '-' }}</td>
                <td class="text-center">{{ $attendance->date->format('d/m/Y') }}</td>
                <td class="text-center">{{ $shiftName ?? '-' }}</td>
                <td class="text-center">{{ $schedule ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s') : '-' }}</td>
                <td class="text-center">{{ $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-' }}</td>
                <td class="text-center">
                    @if($attendance->clock_in && $schedule)
                        @php
                            // Calculate selisih masuk
                            $absensiMasukTime = \Carbon\Carbon::parse($attendance->clock_in)->format('H:i:s');
                            list($h1, $i1, $s1) = explode(':', $absensiMasukTime);
                            $absensiMasukDetik = ($h1 * 3600) + ($i1 * 60) + $s1;

                            $jamMasukTime = \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s');
                            list($h2, $i2, $s2) = explode(':', $jamMasukTime);
                            $jamMasukDetik = ($h2 * 3600) + ($i2 * 60) + $s2;

                            $selisihDetik = $absensiMasukDetik - $jamMasukDetik;
                            $selisihAbs = abs($selisihDetik);

                            $jam = floor($selisihAbs / 3600);
                            $sisaSetelahJam = $selisihAbs - ($jam * 3600);
                            $menit = floor($sisaSetelahJam / 60);
                            $detik = $sisaSetelahJam - ($menit * 60);

                            $formatWaktu = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);

                            if ($selisihDetik > 0) {
                                $warna = 'text-red';
                                $label = '+' . $formatWaktu;
                            } elseif ($selisihDetik < 0) {
                                $warna = 'text-green';
                                $label = '-' . $formatWaktu;
                            } else {
                                $warna = 'text-green';
                                $label = '00:00:00';
                            }
                        @endphp
                        <span class="{{ $warna }}">{{ $label }}</span>
                    @else
                        -
                    @endif
                </td>
                <td class="text-center">
                    @if($attendance->clock_in && $schedule)
                        @php
                            $absensiMasukTime = \Carbon\Carbon::parse($attendance->clock_in)->format('H:i:s');
                            $jamMasukTime = \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s');

                            if ($absensiMasukTime > $jamMasukTime) {
                                $statusText = 'Terlambat';
                                $statusColor = 'text-red';
                            } else {
                                $statusText = 'Tepat Waktu';
                                $statusColor = 'text-green';
                            }
                        @endphp
                        <span class="{{ $statusColor }}">{{ $statusText }}</span>
                    @else
                        {{ $attendance->clock_in ? 'N/A' : 'Tidak Hadir' }}
                    @endif
                </td>
                <td class="text-center">{{ $schedule ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s') : '-' }}</td>
                <td class="text-center">{{ $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '-' }}</td>
                <td class="text-center">
                    @if($attendance->clock_out && $schedule)
                        @php
                            // Calculate selisih pulang
                            $absensiPulangTime = \Carbon\Carbon::parse($attendance->clock_out)->format('H:i:s');
                            list($h1, $i1, $s1) = explode(':', $absensiPulangTime);
                            $absensiPulangDetik = ($h1 * 3600) + ($i1 * 60) + $s1;

                            $jamPulangTime = \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s');
                            list($h2, $i2, $s2) = explode(':', $jamPulangTime);
                            $jamPulangDetik = ($h2 * 3600) + ($i2 * 60) + $s2;

                            $selisihDetik = $absensiPulangDetik - $jamPulangDetik;
                            $selisihAbs = abs($selisihDetik);

                            $jam = floor($selisihAbs / 3600);
                            $sisaSetelahJam = $selisihAbs - ($jam * 3600);
                            $menit = floor($sisaSetelahJam / 60);
                            $detik = $sisaSetelahJam - ($menit * 60);

                            $formatWaktu = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);

                            if ($selisihDetik < 0) {
                                $warna = 'text-red';
                                $label = '-' . $formatWaktu;
                            } elseif ($selisihDetik > 0) {
                                $warna = 'text-green';
                                $label = '+' . $formatWaktu;
                            } else {
                                $warna = 'text-green';
                                $label = '00:00:00';
                            }
                        @endphp
                        <span class="{{ $warna }}">{{ $label }}</span>
                    @else
                        -
                    @endif
                </td>
                <td class="text-center">
                    @if($attendance->clock_out && $schedule)
                        @php
                            $absensiPulangTime = \Carbon\Carbon::parse($attendance->clock_out)->format('H:i:s');
                            $jamPulangTime = \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s');

                            if ($absensiPulangTime < $jamPulangTime) {
                                $statusText = 'Pulang Awal';
                                $statusColor = 'text-yellow';
                            } else {
                                $statusText = 'Tepat Waktu';
                                $statusColor = 'text-green';
                            }
                        @endphp
                        <span class="{{ $statusColor }}">{{ $statusText }}</span>
                    @else
                        {{ $attendance->clock_out ? 'N/A' : 'Belum Pulang' }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    {{-- Student Report --}}
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Tanggal</th>
                <th style="width: 10%;">NISN</th>
                <th style="width: 15%;">Nama Siswa</th>
                <th style="width: 10%;">Rombel</th>
                <th style="width: 7%;">Jam Masuk</th>
                <th style="width: 7%;">Abs Masuk</th>
                <th style="width: 7%;">Selisih</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 7%;">Jam Pulang</th>
                <th style="width: 7%;">Abs Pulang</th>
                <th style="width: 7%;">Selisih</th>
                <th style="width: 7%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
                @php
                    $dayOfWeek = $attendance->date->dayOfWeek;
                    $schedule = \App\Models\Schedule::where('type', 'siswa')
                        ->where('day_of_week', $dayOfWeek)
                        ->first();
                @endphp
            <tr>
                <td class="text-center">{{ $attendance->date->format('d/m/Y') }}</td>
                <td>{{ $attendance->attendable->nisn ?? '-' }}</td>
                <td>{{ $attendance->attendable->full_name ?? '-' }}</td>
                <td>{{ $attendance->attendable->rombel->name ?? '-' }}</td>
                <td class="text-center">{{ $schedule ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s') : '-' }}</td>
                <td class="text-center">{{ $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-' }}</td>
                <td class="text-center">
                    @if($attendance->clock_in && $schedule)
                        @php
                            $absensiMasukTime = \Carbon\Carbon::parse($attendance->clock_in)->format('H:i:s');
                            list($h1, $i1, $s1) = explode(':', $absensiMasukTime);
                            $absensiMasukDetik = ($h1 * 3600) + ($i1 * 60) + $s1;

                            $jamMasukTime = \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s');
                            list($h2, $i2, $s2) = explode(':', $jamMasukTime);
                            $jamMasukDetik = ($h2 * 3600) + ($i2 * 60) + $s2;

                            $selisihDetik = $absensiMasukDetik - $jamMasukDetik;
                            $selisihAbs = abs($selisihDetik);

                            $jam = floor($selisihAbs / 3600);
                            $sisaSetelahJam = $selisihAbs - ($jam * 3600);
                            $menit = floor($sisaSetelahJam / 60);
                            $detik = $sisaSetelahJam - ($menit * 60);

                            $formatWaktu = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);

                            if ($selisihDetik > 0) {
                                $warna = 'text-red';
                                $label = '+' . $formatWaktu;
                            } elseif ($selisihDetik < 0) {
                                $warna = 'text-green';
                                $label = '-' . $formatWaktu;
                            } else {
                                $warna = 'text-green';
                                $label = '00:00:00';
                            }
                        @endphp
                        <span class="{{ $warna }}">{{ $label }}</span>
                    @else
                        -
                    @endif
                </td>
                <td class="text-center">
                    @if($attendance->clock_in && $schedule)
                        @php
                            $absensiMasukTime = \Carbon\Carbon::parse($attendance->clock_in)->format('H:i:s');
                            $jamMasukTime = \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s');

                            if ($absensiMasukTime > $jamMasukTime) {
                                $statusText = 'Terlambat';
                                $statusColor = 'text-red';
                            } else {
                                $statusText = 'Tepat Waktu';
                                $statusColor = 'text-green';
                            }
                        @endphp
                        <span class="{{ $statusColor }}">{{ $statusText }}</span>
                    @else
                        {{ $attendance->clock_in ? 'N/A' : 'Tidak Hadir' }}
                    @endif
                </td>
                <td class="text-center">{{ $schedule ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s') : '-' }}</td>
                <td class="text-center">{{ $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '-' }}</td>
                <td class="text-center">
                    @if($attendance->clock_out && $schedule)
                        @php
                            $absensiPulangTime = \Carbon\Carbon::parse($attendance->clock_out)->format('H:i:s');
                            list($h1, $i1, $s1) = explode(':', $absensiPulangTime);
                            $absensiPulangDetik = ($h1 * 3600) + ($i1 * 60) + $s1;

                            $jamPulangTime = \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s');
                            list($h2, $i2, $s2) = explode(':', $jamPulangTime);
                            $jamPulangDetik = ($h2 * 3600) + ($i2 * 60) + $s2;

                            $selisihDetik = $absensiPulangDetik - $jamPulangDetik;
                            $selisihAbs = abs($selisihDetik);

                            $jam = floor($selisihAbs / 3600);
                            $sisaSetelahJam = $selisihAbs - ($jam * 3600);
                            $menit = floor($sisaSetelahJam / 60);
                            $detik = $sisaSetelahJam - ($menit * 60);

                            $formatWaktu = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);

                            if ($selisihDetik < 0) {
                                $warna = 'text-red';
                                $label = '-' . $formatWaktu;
                            } elseif ($selisihDetik > 0) {
                                $warna = 'text-green';
                                $label = '+' . $formatWaktu;
                            } else {
                                $warna = 'text-green';
                                $label = '00:00:00';
                            }
                        @endphp
                        <span class="{{ $warna }}">{{ $label }}</span>
                    @else
                        -
                    @endif
                </td>
                <td class="text-center">
                    @if($attendance->clock_out && $schedule)
                        @php
                            $absensiPulangTime = \Carbon\Carbon::parse($attendance->clock_out)->format('H:i:s');
                            $jamPulangTime = \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s');

                            if ($absensiPulangTime < $jamPulangTime) {
                                $statusText = 'Pulang Awal';
                                $statusColor = 'text-yellow';
                            } else {
                                $statusText = 'Tepat Waktu';
                                $statusColor = 'text-green';
                            }
                        @endphp
                        <span class="{{ $statusColor }}">{{ $statusText }}</span>
                    @else
                        {{ $attendance->clock_out ? 'N/A' : 'Belum Pulang' }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <p style="margin-top: 15px; font-size: 9px; text-align: right;">Dicetak pada: {{ $generatedAt }}</p>
</body>
</html>
