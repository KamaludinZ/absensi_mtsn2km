<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Helpers\AttendanceHelper;
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

            // Calculate Selisih Masuk using helper
            $selisihMasuk = AttendanceHelper::formatCheckInDifference($attendance);
            $statusMasuk = '-';

            if ($attendance->clock_in && $schedule) {
                $actualTime = Carbon::parse($attendance->clock_in)->format('H:i:s');
                $scheduledTime = Carbon::parse($schedule->start_time)->format('H:i:s');

                if ($actualTime > $scheduledTime) {
                    $statusMasuk = 'Terlambat';
                } else {
                    $statusMasuk = 'Tepat Waktu';
                }
            } elseif (!$attendance->clock_in) {
                $statusMasuk = 'Tidak Hadir';
            } elseif (!$schedule) {
                $statusMasuk = 'N/A';
            }

            // Calculate Selisih Pulang using helper
            $selisihPulang = AttendanceHelper::formatCheckOutDifference($attendance);
            $statusPulang = '-';

            if ($attendance->clock_out && $schedule) {
                $actualTime = Carbon::parse($attendance->clock_out)->format('H:i:s');
                $scheduledTime = Carbon::parse($schedule->end_time)->format('H:i:s');

                if ($actualTime < $scheduledTime) {
                    $statusPulang = 'Pulang Awal';
                } else {
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

            // Calculate Selisih Masuk using helper
            $selisihMasuk = AttendanceHelper::formatCheckInDifference($attendance);
            $statusMasuk = '-';

            if ($attendance->clock_in && $schedule) {
                $actualTime = Carbon::parse($attendance->clock_in)->format('H:i:s');
                $scheduledTime = Carbon::parse($schedule->start_time)->format('H:i:s');

                if ($actualTime > $scheduledTime) {
                    $statusMasuk = 'Terlambat';
                } else {
                    $statusMasuk = 'Tepat Waktu';
                }
            } elseif (!$attendance->clock_in) {
                $statusMasuk = 'Tidak Hadir';
            } elseif (!$schedule) {
                $statusMasuk = 'N/A';
            }

            // Calculate Selisih Pulang using helper
            $selisihPulang = AttendanceHelper::formatCheckOutDifference($attendance);
            $statusPulang = '-';

            if ($attendance->clock_out && $schedule) {
                $actualTime = Carbon::parse($attendance->clock_out)->format('H:i:s');
                $scheduledTime = Carbon::parse($schedule->end_time)->format('H:i:s');

                if ($actualTime < $scheduledTime) {
                    $statusPulang = 'Pulang Awal';
                } else {
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
