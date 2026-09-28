<?php

namespace App\Exports;

use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Student;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Workbook for the student import: the "Data Siswa" sheet that is imported
 * (optionally filled with the current students, to update them in bulk), the
 * valid rombels, and the instructions.
 */
class StudentImportTemplate
{
    public const HEADERS = ['NISN', 'Nama Lengkap', 'Tingkat Kelas', 'Rombel', 'Tahun Pelajaran', 'Status Aktif', 'Kartu RFID'];

    private const FORM_ROWS = 2000;

    public static function build(bool $withStudents = false): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $activeYear = AcademicYear::where('status', 'active')->first();
        $rombels = Rombel::with(['grade', 'academicYear'])->get()
            ->sortBy(fn ($r) => [$r->academicYear->status === 'active' ? 0 : 1, $r->academicYear->year_name ?? '', $r->grade->name ?? '', $r->name])
            ->values();

        // Sheet 1: data (the only sheet that is imported)
        $data = $spreadsheet->getActiveSheet()->setTitle('Data Siswa');
        $data->fromArray(self::HEADERS, null, 'A1');
        self::header($data, 'A1:G1');
        foreach (['A' => 16, 'B' => 32, 'C' => 14, 'D' => 12, 'E' => 17, 'F' => 13, 'G' => 18] as $col => $width) {
            $data->getColumnDimension($col)->setWidth($width);
        }
        $last = self::FORM_ROWS + 1;
        // NISN and card numbers are text, so leading zeros are kept
        $data->getStyle("A2:A$last")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $data->getStyle("G2:G$last")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $data->freezePane('A2');

        if ($withStudents) {
            $row = 2;
            Student::with(['rombel.grade', 'rombel.academicYear'])->where('is_active', true)->get()
                ->sortBy(fn ($s) => [$s->rombel->name ?? '', $s->full_name])
                ->each(function (Student $s) use ($data, &$row) {
                    $data->setCellValueExplicit("A$row", (string) $s->nisn, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $data->setCellValue("B$row", $s->full_name);
                    $data->setCellValue("C$row", $s->rombel->grade->name ?? '');
                    $data->setCellValue("D$row", $s->rombel->name ?? '');
                    $data->setCellValue("E$row", $s->rombel->academicYear->year_name ?? '');
                    $data->setCellValue("F$row", $s->is_active ? 1 : 0);
                    $data->setCellValueExplicit("G$row", (string) $s->rfid_uid, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $row++;
                });
        }

        // Sheet 2: valid rombels
        $list = $spreadsheet->createSheet()->setTitle('Daftar Rombel');
        $list->fromArray(['Tahun Pelajaran', 'Tingkat Kelas', 'Rombel', 'Keterangan'], null, 'A1');
        self::header($list, 'A1:D1');
        foreach ($rombels as $i => $rombel) {
            $list->fromArray([
                $rombel->academicYear->year_name ?? '-',
                $rombel->grade->name ?? '-',
                $rombel->name,
                ($rombel->academicYear->status ?? null) === 'active' ? 'Tahun aktif' : '',
            ], null, 'A' . ($i + 2));
        }
        foreach (['A' => 17, 'B' => 14, 'C' => 14, 'D' => 14] as $col => $width) {
            $list->getColumnDimension($col)->setWidth($width);
        }

        // Dropdowns on the data sheet
        $rombelNames = $rombels->pluck('name')->unique()->values();
        $yearNames = $rombels->pluck('academicYear.year_name')->filter()->unique()->values();
        self::dropdown($data, "C2:C$last", '"7,8,9"', 'Isi 7, 8 atau 9.', false);
        self::dropdown($data, "F2:F$last", '"1,0"', 'Isi 1 (aktif) atau 0 (tidak aktif). Kosong = aktif.', false);
        if ($rombelNames->isNotEmpty()) {
            self::dropdown($data, "D2:D$last", self::listFormula($rombelNames->all()), 'Pilih rombel dari sheet Daftar Rombel.', true);
        }
        if ($yearNames->isNotEmpty()) {
            self::dropdown($data, "E2:E$last", self::listFormula($yearNames->all()), 'Kosong = tahun pelajaran aktif.', false);
        }

        // Sheet 3: instructions
        $help = $spreadsheet->createSheet()->setTitle('Petunjuk');
        $sample = $rombels->first(fn ($r) => ($r->academicYear->status ?? null) === 'active') ?? $rombels->first();
        $lines = [
            ['PETUNJUK IMPORT DATA SISWA'],
            [''],
            ['1. Isi data di sheet "Data Siswa" mulai baris 2. Jangan ubah judul kolom di baris 1.'],
            ['2. NISN wajib dan unik. Jika NISN sudah terdaftar, data siswa itu DIPERBARUI; jika belum, siswa DITAMBAHKAN.'],
            ['3. Rombel wajib dan harus sama dengan nama di sheet "Daftar Rombel".'],
            ['4. Tahun Pelajaran boleh dikosongkan: otomatis tahun pelajaran aktif' . ($activeYear ? ' (' . $activeYear->year_name . ')' : '') . '.'],
            ['5. Tingkat Kelas boleh dikosongkan; jika diisi (7, 8, 9) akan dicek cocok dengan rombel.'],
            ['6. Status Aktif: 1 = aktif, 0 = tidak aktif. Kosong = aktif.'],
            ['7. Kartu RFID opsional. Jika kosong, kartu yang sudah terdaftar tidak diubah.'],
            ['8. Semua baris diperiksa dulu. Jika ada yang salah, tidak ada data yang disimpan dan daftar kesalahannya ditampilkan per baris.'],
            ['9. Maksimal ' . \App\Imports\StudentsImport::MAX_ROWS . ' siswa per file. Format file: .xlsx, .xls atau .csv.'],
            [''],
            ['CONTOH PENGISIAN (jangan disalin ke sheet Data Siswa jika bukan data asli):'],
            self::HEADERS,
            ['0012345678', 'Ahmad Rizki', $sample->grade->name ?? '7', $sample->name ?? 'VII A', $sample->academicYear->year_name ?? '2025/2026', '1', ''],
            ['0087654321', 'Siti Aminah', $sample->grade->name ?? '7', $sample->name ?? 'VII A', '', '1', '04A1B2C3'],
        ];
        $help->fromArray($lines, null, 'A1', true);
        $help->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $help->getStyle('A13')->getFont()->setBold(true);
        self::header($help, 'A14:G14');
        $help->getStyle('A15:G16')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $help->getColumnDimension('A')->setWidth(16);
        $help->getColumnDimension('B')->setWidth(20);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private static function header(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        ]);
    }

    /** Excel list validation; a quoted list is limited to 255 characters */
    private static function listFormula(array $values): string
    {
        $formula = '"' . implode(',', array_map(fn ($v) => str_replace(['"', ','], '', $v), $values)) . '"';

        return strlen($formula) <= 255 ? $formula : "'Daftar Rombel'!\$C\$2:\$C\$" . (count($values) + 1);
    }

    private static function dropdown(Worksheet $sheet, string $range, string $formula, string $prompt, bool $strict): void
    {
        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle($strict ? DataValidation::STYLE_STOP : DataValidation::STYLE_WARNING)
            ->setAllowBlank(true)
            ->setShowDropDown(true) // PhpSpreadsheet: true means the in-cell arrow is shown
            ->setShowErrorMessage(true)
            ->setShowInputMessage(true)
            ->setPromptTitle('Petunjuk')
            ->setPrompt($prompt)
            ->setErrorTitle('Isian tidak valid')
            ->setError($prompt)
            ->setFormula1($formula);
        $sheet->setDataValidation($range, $validation);
    }
}
