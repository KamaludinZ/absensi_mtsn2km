<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\Rombel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $rombel = Rombel::where('name', $row['rombel'])->first();

        if (!$rombel) {
            return null;
        }

        return new Student([
            'full_name' => $row['full_name'],
            'rombel_id' => $rombel->id,
            'is_active' => $row['is_active'] ?? true,
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string',
            'rombel' => 'required|string',
        ];
    }
}
