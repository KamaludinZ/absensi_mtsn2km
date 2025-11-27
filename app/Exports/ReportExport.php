<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;

class ReportExport implements FromArray, WithMapping, WithHeadings
{
    protected $data;
    protected $reportTitle;

    public function __construct($data, $reportTitle = 'Report')
    {
        $this->data = $data;
        $this->reportTitle = $reportTitle;
    }

    /**
    * @return array
    */
    public function array(): array
    {
        return $this->data->toArray();
    }

    /**
    * @param mixed $row
    *
    * @return array
    */
    public function map($row): array
    {
        return array_values($row);
    }

    public function headings(): array
    {
        if (!empty($this->data) && is_array($this->data) && count($this->data) > 0) {
            return array_keys($this->data[0]);
        }

        return [];
    }
}
