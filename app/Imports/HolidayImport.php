<?php

namespace App\Imports;

use App\Models\Holiday;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class HolidayImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Format date from Excel format (assuming date is in format like '2024-12-25' or '25/12/2024')
        $date = null;
        
        if (isset($row['date']) && $row['date']) {
            if (is_string($row['date'])) {
                // If it's a string date like '2024-12-25' or '25/12/2024'
                $date = Carbon::parse($row['date']);
            } else {
                // If it's an Excel date number, use Carbon's Excel date functionality
                $date = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date']));
            }
        }
        
        return new Holiday([
            'name' => $row['name'] ?? $row['nama'] ?? $row['holiday_name'] ?? 'Hari Libur',
            'date' => $date,
            'description' => $row['description'] ?? $row['deskripsi'] ?? $row['keterangan'] ?? null,
            'is_active' => filter_var($row['is_active'] ?? $row['active'] ?? true, FILTER_VALIDATE_BOOLEAN) ?? true,
        ]);
    }
}
