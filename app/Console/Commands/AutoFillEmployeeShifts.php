<?php

namespace App\Console\Commands;

use App\Services\EmployeeShiftAutoFill;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Daily: fill /admin/employee-shifts for the rest of the month from the
 * shifts' Hari Penerapan and Jabatan Penerapan (see EmployeeShiftAutoFill).
 */
class AutoFillEmployeeShifts extends Command
{
    protected $signature = 'shifts:auto-fill';

    protected $description = 'Isi otomatis shift pegawai sesuai hari & jabatan penerapan (hari ini s.d. akhir bulan)';

    public function handle(): int
    {
        $today = Carbon::today();
        $count = EmployeeShiftAutoFill::fillPending($today->year, $today->month, $today);

        $this->info("{$count} shift pegawai diisi otomatis.");

        return self::SUCCESS;
    }
}
