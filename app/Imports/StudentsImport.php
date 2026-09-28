<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Student;
use App\Services\StudentKiosk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Student import from the template (see StudentImportTemplate). Rows are
 * matched on NISN: a known NISN updates that student, a new NISN adds one.
 * Every row is checked first; if any row has a problem nothing is saved.
 *
 * Used with Excel::toCollection(); only the first sheet is read.
 */
class StudentsImport implements WithHeadingRow, WithCalculatedFormulas
{
    public const MAX_ROWS = 2000;

    /** Accepted header names (slugged) per field, first one is the template's */
    private const COLUMNS = [
        'nisn' => ['nisn'],
        'full_name' => ['nama_lengkap', 'nama', 'full_name', 'nama_siswa'],
        'grade' => ['tingkat_kelas', 'tingkat', 'kelas'],
        'rombel' => ['rombel', 'rombongan_belajar'],
        'academic_year' => ['tahun_pelajaran', 'tahun_ajaran', 'academic_year'],
        'is_active' => ['status_aktif', 'status', 'aktif', 'is_active'],
        'rfid_uid' => ['kartu_rfid', 'rfid', 'rfid_uid', 'nomor_kartu'],
    ];

    /**
     * @return array{errors: array<int, string>, created: int, updated: int}
     */
    public static function process(Collection $sheet): array
    {
        $errors = [];

        $first = $sheet->first();
        if (!$first) {
            return ['errors' => ['File kosong atau tidak ada baris data di bawah judul kolom.'], 'created' => 0, 'updated' => 0];
        }

        $headers = array_keys($first->toArray());
        $map = [];
        foreach (self::COLUMNS as $field => $aliases) {
            $map[$field] = collect($aliases)->first(fn ($alias) => in_array($alias, $headers, true));
        }
        foreach (['nisn' => 'NISN', 'full_name' => 'Nama Lengkap', 'rombel' => 'Rombel'] as $field => $label) {
            if (!$map[$field]) {
                $errors[] = "Kolom \"$label\" tidak ditemukan. Gunakan template dari tombol Download Template.";
            }
        }
        if ($errors) {
            return ['errors' => $errors, 'created' => 0, 'updated' => 0];
        }

        $activeYear = AcademicYear::where('status', 'active')->first();
        $years = AcademicYear::all()->keyBy(fn ($y) => self::key($y->year_name));
        $rombels = Rombel::with('grade')->get()->groupBy(fn ($r) => $r->academic_year_id . '|' . self::key($r->name));

        $rows = [];
        $seenNisn = [];
        $seenRfid = [];
        foreach ($sheet as $index => $raw) {
            $line = $index + 2; // row 1 holds the headers
            $get = fn ($field) => $map[$field] ? $raw[$map[$field]] ?? null : null;

            $nisn = self::nisn($get('nisn'));
            $name = trim(preg_replace('/\s+/', ' ', (string) $get('full_name')));
            $rombelName = trim((string) $get('rombel'));
            $yearName = trim((string) $get('academic_year'));
            $gradeName = trim((string) $get('grade'));
            $rfidRaw = $get('rfid_uid');
            $rfid = StudentKiosk::normalizeRfid(is_int($rfidRaw) || is_float($rfidRaw) ? number_format($rfidRaw, 0, '', '') : $rfidRaw);
            $statusRaw = $get('is_active');

            // Skip completely empty rows (Excel often keeps formatted blank rows)
            if ($nisn === '' && $name === '' && $rombelName === '') {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                $errors[] = 'Maksimal ' . self::MAX_ROWS . ' siswa per file. Bagi file menjadi beberapa bagian.';
                break;
            }

            $rowErrors = [];
            if ($nisn === '') {
                $rowErrors[] = 'NISN wajib diisi';
            } elseif (!preg_match('/^[0-9A-Za-z.\-]{1,30}$/', $nisn)) {
                $rowErrors[] = "NISN \"$nisn\" tidak valid";
            } elseif (isset($seenNisn[$nisn])) {
                $rowErrors[] = "NISN $nisn sudah dipakai di baris {$seenNisn[$nisn]}";
            }
            if ($name === '') {
                $rowErrors[] = 'Nama Lengkap wajib diisi';
            } elseif (mb_strlen($name) > 255) {
                $rowErrors[] = 'Nama Lengkap terlalu panjang (maks. 255 karakter)';
            }

            $year = $yearName === '' ? $activeYear : $years->get(self::key($yearName));
            if (!$year) {
                $rowErrors[] = $yearName === ''
                    ? 'Tahun Pelajaran kosong dan belum ada tahun pelajaran yang aktif'
                    : "Tahun Pelajaran \"$yearName\" tidak ditemukan";
            }

            $rombel = null;
            if ($rombelName === '') {
                $rowErrors[] = 'Rombel wajib diisi';
            } elseif ($year) {
                $rombel = $rombels->get($year->id . '|' . self::key($rombelName))?->first();
                if (!$rombel) {
                    $rowErrors[] = "Rombel \"$rombelName\" tidak ada di tahun pelajaran {$year->year_name}";
                } elseif ($gradeName !== '' && self::grade($gradeName) !== self::grade($rombel->grade->name ?? '')) {
                    $rowErrors[] = "Rombel {$rombel->name} bukan tingkat kelas $gradeName (seharusnya {$rombel->grade->name})";
                }
            }

            $active = self::status($statusRaw);
            if ($active === null) {
                $rowErrors[] = "Status Aktif \"$statusRaw\" tidak dikenali (isi 1 atau 0)";
            }

            if ($rfid !== null) {
                if (mb_strlen($rfid) > 50) {
                    $rowErrors[] = 'Kartu RFID terlalu panjang (maks. 50 karakter)';
                } elseif (isset($seenRfid[$rfid])) {
                    $rowErrors[] = "Kartu RFID $rfid sudah dipakai di baris {$seenRfid[$rfid]}";
                }
            }

            if ($rowErrors) {
                $errors[] = "Baris $line: " . implode('; ', $rowErrors) . '.';
                continue;
            }

            $seenNisn[$nisn] = $line;
            if ($rfid !== null) {
                $seenRfid[$rfid] = $line;
            }
            $rows[] = compact('line', 'nisn', 'name', 'rfid', 'active') + ['rombel_id' => $rombel->id];
        }

        if (!$errors && !$rows) {
            $errors[] = 'Tidak ada baris data yang bisa diimpor.';
        }

        // Existing students (also deleted ones: NISN stays unique in the table)
        $existing = Student::withTrashed()->whereIn('nisn', array_column($rows, 'nisn'))->get()->keyBy('nisn');

        $rfids = array_filter(array_column($rows, 'rfid'));
        $rfidOwners = $rfids ? Student::withTrashed()->whereIn('rfid_uid', $rfids)->get()->keyBy('rfid_uid') : collect();
        foreach ($rows as $row) {
            $owner = $row['rfid'] ? $rfidOwners->get($row['rfid']) : null;
            if ($owner && $owner->nisn !== $row['nisn']) {
                $errors[] = "Baris {$row['line']}: Kartu RFID {$row['rfid']} sudah terdaftar untuk {$owner->full_name}.";
            }
        }

        if ($errors) {
            return ['errors' => $errors, 'created' => 0, 'updated' => 0];
        }

        $created = 0;
        $updated = 0;
        DB::transaction(function () use ($rows, $existing, &$created, &$updated) {
            foreach ($rows as $row) {
                $data = [
                    'full_name' => $row['name'],
                    'rombel_id' => $row['rombel_id'],
                    'is_active' => $row['active'],
                ];
                // An empty RFID cell keeps the card already linked
                if ($row['rfid'] !== null) {
                    $data['rfid_uid'] = $row['rfid'];
                }

                $student = $existing->get($row['nisn']);
                if ($student) {
                    if ($student->trashed()) {
                        $student->restore();
                    }
                    if ($row['active']) {
                        $data['graduated_at'] = null;
                    }
                    $student->update($data);
                    $updated++;
                } else {
                    Student::create($data + ['nisn' => $row['nisn']]);
                    $created++;
                }
            }
        });

        return ['errors' => [], 'created' => $created, 'updated' => $updated];
    }

    /**
     * NISN as text. Excel turns 0012345678 into the number 12345678, so a
     * numeric cell is padded back to the 10 digits of a NISN.
     */
    private static function nisn($value): string
    {
        if (is_int($value) || is_float($value)) {
            $value = number_format($value, 0, '', '');

            return strlen($value) < 10 ? str_pad($value, 10, '0', STR_PAD_LEFT) : $value;
        }

        return preg_replace('/\s+/', '', trim((string) $value));
    }

    private static function status($value): ?bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (is_bool($value)) {
            return $value;
        }

        return match (strtolower(trim((string) $value))) {
            '1', 'aktif', 'ya', 'y', 'true', 'active' => true,
            '0', 'tidak aktif', 'nonaktif', 'non aktif', 'tidak', 't', 'n', 'false', 'inactive' => false,
            default => null,
        };
    }

    /** "7", "VII", "Kelas 7" and "kelas VII" are the same class level */
    private static function grade(string $value): string
    {
        $value = preg_replace('/^kelas/', '', self::key($value));

        return ['vii' => '7', 'viii' => '8', 'ix' => '9'][$value] ?? $value;
    }

    /** Compare names ignoring case and spacing ("VII  a" = "VII A", "2025 / 2026" = "2025/2026") */
    private static function key(?string $value): string
    {
        return strtolower(preg_replace('/\s+/', '', (string) $value));
    }
}
