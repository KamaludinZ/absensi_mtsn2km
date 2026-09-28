<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Student attendance recap workbook: the recap sheet, plus a day-by-day sheet
 * (one row per student, one column per date) for a class recap.
 */
class StudentRecapExport implements WithMultipleSheets
{
    public function __construct(private array $report)
    {
    }

    public function sheets(): array
    {
        $sheets = [new StudentRecap\ViewSheet('admin.reports.students.excel.recap', $this->report, 'Rekap')];

        if (!$this->report['studentRow']) {
            $sheets[] = new StudentRecap\ViewSheet('admin.reports.students.excel.daily', $this->report, 'Harian');
        }

        return $sheets;
    }
}
