<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Carbon\Carbon;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $attendances;
    protected $type;
    protected $startDate;
    protected $endDate;

    public function __construct($attendances, $type, $startDate, $endDate)
    {
        $this->attendances = $attendances;
        $this->type = $type;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return $this->attendances;
    }

    public function headings(): array
    {
        if ($this->type === 'employee') {
            return [
                'Nama Pegawai',
                'Jabatan',
                'Tanggal',
                'Nama Shift',
                'Jam Masuk',
                'Absensi Masuk',
                'Selisih Masuk',
                'Status Masuk',
                'Selfie Masuk',
                'Jam Pulang',
                'Absensi Pulang',
                'Selisih Pulang',
                'Status Pulang',
                'Selfie Pulang',
            ];
        } else {
            return [
                'Tanggal',
                'NISN',
                'Nama Siswa',
                'Rombel',
                'Jam Masuk',
                'Absensi Masuk',
                'Selisih Masuk',
                'Status Masuk',
                'Selfie Masuk',
                'Jam Pulang',
                'Absensi Pulang',
                'Selisih Pulang',
                'Status Pulang',
                'Selfie Pulang',
            ];
        }
    }

    public function map($attendance): array
    {
        if ($this->type === 'employee') {
            // Get shift information
            $shiftName = '-';
            $schedule = null;

            // Try to get shift from employee_shifts table
            $employeeShift = \App\Models\EmployeeShift::where('employee_id', $attendance->attendable_id)
                ->where('date', $attendance->date)
                ->with('schedule')
                ->first();

            if ($employeeShift && $employeeShift->schedule) {
                $schedule = $employeeShift->schedule;
                $shiftName = $schedule->shift_name ?? '-';
            } else {
                // Try to get schedule by day of week
                $dayOfWeek = Carbon::parse($attendance->date)->dayOfWeek;
                $schedule = \App\Models\Schedule::where('type', 'pegawai')
                    ->where('day_of_week', $dayOfWeek)
                    ->first();
                if ($schedule) {
                    $shiftName = $schedule->shift_name ?? '-';
                }
            }

            // Calculate Selisih Masuk
            $selisihMasuk = '-';
            $statusMasuk = '-';
            if ($attendance->clock_in && $schedule) {
                // Ambil waktu ABSENSI MASUK dalam format H:i:s
                $absensiMasukTime = Carbon::parse($attendance->clock_in)->format('H:i:s');
                list($h1, $i1, $s1) = explode(':', $absensiMasukTime);
                $absensiMasukDetik = ($h1 * 3600) + ($i1 * 60) + $s1;

                // Ambil waktu JAM MASUK dalam format H:i:s
                $jamMasukTime = Carbon::parse($schedule->start_time)->format('H:i:s');
                list($h2, $i2, $s2) = explode(':', $jamMasukTime);
                $jamMasukDetik = ($h2 * 3600) + ($i2 * 60) + $s2;

                // Hitung selisih: ABSENSI MASUK - JAM MASUK
                $selisihDetik = $absensiMasukDetik - $jamMasukDetik;

                // Convert ke format H:i:s
                $selisihAbs = abs($selisihDetik);

                $jam = floor($selisihAbs / 3600);
                $sisaSetelahJam = $selisihAbs - ($jam * 3600);
                $menit = floor($sisaSetelahJam / 60);
                $detik = $sisaSetelahJam - ($menit * 60);

                $formatWaktu = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);

                if ($selisihDetik > 0) {
                    // Absensi masuk > jam masuk (terlambat)
                    $selisihMasuk = '+' . $formatWaktu;
                    $statusMasuk = 'Terlambat';
                } elseif ($selisihDetik < 0) {
                    // Absensi masuk < jam masuk (lebih awal)
                    $selisihMasuk = '-' . $formatWaktu;
                    $statusMasuk = 'Tepat Waktu';
                } else {
                    // Tepat waktu
                    $selisihMasuk = '00:00:00';
                    $statusMasuk = 'Tepat Waktu';
                }
            } elseif (!$attendance->clock_in) {
                $statusMasuk = 'Tidak Hadir';
            } elseif (!$schedule) {
                $statusMasuk = 'N/A';
            }

            // Calculate Selisih Pulang
            $selisihPulang = '-';
            $statusPulang = '-';
            if ($attendance->clock_out && $schedule) {
                // Ambil waktu ABSENSI PULANG dalam format H:i:s
                $absensiPulangTime = Carbon::parse($attendance->clock_out)->format('H:i:s');
                list($h1, $i1, $s1) = explode(':', $absensiPulangTime);
                $absensiPulangDetik = ($h1 * 3600) + ($i1 * 60) + $s1;

                // Ambil waktu JAM PULANG dalam format H:i:s
                $jamPulangTime = Carbon::parse($schedule->end_time)->format('H:i:s');
                list($h2, $i2, $s2) = explode(':', $jamPulangTime);
                $jamPulangDetik = ($h2 * 3600) + ($i2 * 60) + $s2;

                // Hitung selisih: ABSENSI PULANG - JAM PULANG
                $selisihDetik = $absensiPulangDetik - $jamPulangDetik;

                // Convert ke format H:i:s
                $selisihAbs = abs($selisihDetik);

                $jam = floor($selisihAbs / 3600);
                $sisaSetelahJam = $selisihAbs - ($jam * 3600);
                $menit = floor($sisaSetelahJam / 60);
                $detik = $sisaSetelahJam - ($menit * 60);

                $formatWaktu = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);

                if ($selisihDetik < 0) {
                    // Absensi pulang < Jam pulang = Pulang Awal
                    $selisihPulang = '-' . $formatWaktu;
                    $statusPulang = 'Pulang Awal';
                } elseif ($selisihDetik > 0) {
                    // Absensi pulang > Jam pulang = Pulang Lebih Lama
                    $selisihPulang = '+' . $formatWaktu;
                    $statusPulang = 'Tepat Waktu';
                } else {
                    // Tepat waktu
                    $selisihPulang = '00:00:00';
                    $statusPulang = 'Tepat Waktu';
                }
            } elseif (!$attendance->clock_out) {
                $statusPulang = 'Belum Pulang';
            } elseif (!$schedule) {
                $statusPulang = 'N/A';
            }

            return [
                $attendance->attendable->full_name ?? '-',
                $attendance->attendable->position ?? '-',
                Carbon::parse($attendance->date)->format('d/m/Y'),
                $shiftName,
                $schedule ? Carbon::parse($schedule->start_time)->format('H:i:s') : '-',
                $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i:s') : '-',
                $selisihMasuk,
                $statusMasuk,
                $attendance->selfie_path_in ? 'Tersedia' : 'Tidak',
                $schedule ? Carbon::parse($schedule->end_time)->format('H:i:s') : '-',
                $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i:s') : '-',
                $selisihPulang,
                $statusPulang,
                $attendance->selfie_path_out ? 'Tersedia' : 'Tidak',
            ];
        } else {
            // Student export - also update to match the new format
            // Get schedule
            $schedule = null;
            $dayOfWeek = Carbon::parse($attendance->date)->dayOfWeek;
            $schedule = \App\Models\Schedule::where('type', 'siswa')
                ->where('day_of_week', $dayOfWeek)
                ->first();

            // Calculate Selisih Masuk
            $selisihMasuk = '-';
            $statusMasuk = '-';
            if ($attendance->clock_in && $schedule) {
                $absensiMasukTime = Carbon::parse($attendance->clock_in)->format('H:i:s');
                list($h1, $i1, $s1) = explode(':', $absensiMasukTime);
                $absensiMasukDetik = ($h1 * 3600) + ($i1 * 60) + $s1;

                $jamMasukTime = Carbon::parse($schedule->start_time)->format('H:i:s');
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
                    $selisihMasuk = '+' . $formatWaktu;
                    $statusMasuk = 'Terlambat';
                } elseif ($selisihDetik < 0) {
                    $selisihMasuk = '-' . $formatWaktu;
                    $statusMasuk = 'Tepat Waktu';
                } else {
                    $selisihMasuk = '00:00:00';
                    $statusMasuk = 'Tepat Waktu';
                }
            } elseif (!$attendance->clock_in) {
                $statusMasuk = 'Tidak Hadir';
            } elseif (!$schedule) {
                $statusMasuk = 'N/A';
            }

            // Calculate Selisih Pulang
            $selisihPulang = '-';
            $statusPulang = '-';
            if ($attendance->clock_out && $schedule) {
                $absensiPulangTime = Carbon::parse($attendance->clock_out)->format('H:i:s');
                list($h1, $i1, $s1) = explode(':', $absensiPulangTime);
                $absensiPulangDetik = ($h1 * 3600) + ($i1 * 60) + $s1;

                $jamPulangTime = Carbon::parse($schedule->end_time)->format('H:i:s');
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
                    $selisihPulang = '-' . $formatWaktu;
                    $statusPulang = 'Pulang Awal';
                } elseif ($selisihDetik > 0) {
                    $selisihPulang = '+' . $formatWaktu;
                    $statusPulang = 'Tepat Waktu';
                } else {
                    $selisihPulang = '00:00:00';
                    $statusPulang = 'Tepat Waktu';
                }
            } elseif (!$attendance->clock_out) {
                $statusPulang = 'Belum Pulang';
            } elseif (!$schedule) {
                $statusPulang = 'N/A';
            }

            return [
                Carbon::parse($attendance->date)->format('d/m/Y'),
                $attendance->attendable->nisn ?? '-',
                $attendance->attendable->full_name ?? '-',
                $attendance->attendable->rombel->name ?? '-',
                $schedule ? Carbon::parse($schedule->start_time)->format('H:i:s') : '-',
                $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i:s') : '-',
                $selisihMasuk,
                $statusMasuk,
                $attendance->selfie_path_in ? 'Tersedia' : 'Tidak',
                $schedule ? Carbon::parse($schedule->end_time)->format('H:i:s') : '-',
                $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i:s') : '-',
                $selisihPulang,
                $statusPulang,
                $attendance->selfie_path_out ? 'Tersedia' : 'Tidak',
            ];
        }
    }

    public function title(): string
    {
        return 'Laporan Absensi';
    }
}
