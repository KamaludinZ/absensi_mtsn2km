<?php

namespace Tests\Feature;

use App\Exports\StudentImportTemplate;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Rombel $oldViiA;
    private Rombel $viiA;
    private Rombel $viiiA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);

        [$g7, $g8] = Grade::orderBy('name')->get()->all();
        $old = AcademicYear::create(['year_name' => '2025/2026', 'status' => 'inactive']);
        $active = AcademicYear::create(['year_name' => '2026/2027', 'status' => 'active']);
        // Same rombel name in two academic years
        $this->oldViiA = Rombel::create(['academic_year_id' => $old->id, 'grade_id' => $g7->id, 'name' => 'VII A']);
        $this->viiA = Rombel::create(['academic_year_id' => $active->id, 'grade_id' => $g7->id, 'name' => 'VII A']);
        $this->viiiA = Rombel::create(['academic_year_id' => $active->id, 'grade_id' => $g8->id, 'name' => 'VIII A']);
    }

    /** An .xlsx upload with the template headers and these rows */
    private function file(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray(array_merge([StudentImportTemplate::HEADERS], $rows), null, 'A1', true);
        $path = tempnam(sys_get_temp_dir(), 'siswa') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'siswa.xlsx', null, null, true);
    }

    private function import(array $rows)
    {
        return $this->actingAs($this->admin)
            ->from(route('admin.students.import-form'))
            ->post(route('admin.students.import'), ['file' => $this->file($rows)]);
    }

    public function test_template_download_has_data_rombel_and_help_sheets(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.students.download-template'))->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'tpl') . '.xlsx';
        file_put_contents($path, $response->streamedContent());
        $book = IOFactory::load($path);

        $this->assertSame(['Data Siswa', 'Daftar Rombel', 'Petunjuk'], $book->getSheetNames());
        $this->assertSame(StudentImportTemplate::HEADERS, $book->getSheet(0)->rangeToArray('A1:G1')[0]);
        $this->assertSame('VII A', $book->getSheet(1)->getCell('C2')->getValue());
    }

    public function test_import_adds_new_and_updates_existing_students_by_nisn(): void
    {
        $existing = Student::create(['nisn' => '0011111111', 'full_name' => 'Nama Lama', 'rombel_id' => $this->oldViiA->id, 'is_active' => true]);

        $this->import([
            ['0011111111', 'Nama Baru', '8', 'VIII A', '', '1', ''],
            ['0022222222', 'Siswa Baru', 'VII', 'vii  a', '2026/2027', '', '04 a1 b2'],
            [22233344, 'Nol Hilang', '', 'VII A', '', 'Tidak Aktif', ''], // Excel dropped the leading zeros
            ['', '', '', '', '', '', ''],                                  // empty row is skipped
        ])->assertRedirect(route('admin.students.index'))
            ->assertSessionHas('success', 'Import selesai: 2 siswa ditambahkan, 1 siswa diperbarui.');

        $existing->refresh();
        $this->assertSame('Nama Baru', $existing->full_name);
        $this->assertSame($this->viiiA->id, $existing->rombel_id);

        $new = Student::where('nisn', '0022222222')->first();
        $this->assertSame($this->viiA->id, $new->rombel_id); // the active year's VII A, not the old one
        $this->assertSame('04A1B2', $new->rfid_uid);
        $this->assertTrue($new->is_active);

        $padded = Student::where('nisn', '0022233344')->first();
        $this->assertNotNull($padded);
        $this->assertFalse($padded->is_active);
    }

    public function test_any_error_saves_nothing_and_lists_the_rows(): void
    {
        $this->import([
            ['0033333333', 'Benar', '7', 'VII A', '', '1', ''],
            ['0033333333', 'Dobel', '7', 'VII A', '', '1', ''],
            ['0044444444', 'Salah Rombel', '7', 'VII Z', '', '1', ''],
            ['0055555555', 'Salah Tingkat', '9', 'VIII A', '', '1', ''],
            ['0066666666', 'Tahun Salah', '7', 'VII A', '2099/2100', '1', ''],
            ['', 'Tanpa NISN', '7', 'VII A', '', '1', ''],
        ])->assertRedirect(route('admin.students.import-form'))
            ->assertSessionHas('import_errors', function (array $errors) {
                $text = implode("\n", $errors);

                return count($errors) === 5
                    && str_contains($text, 'Baris 3: NISN 0033333333 sudah dipakai di baris 2')
                    && str_contains($text, 'Baris 4: Rombel "VII Z" tidak ada')
                    && str_contains($text, 'Baris 5: Rombel VIII A bukan tingkat kelas 9')
                    && str_contains($text, 'Baris 6: Tahun Pelajaran "2099/2100" tidak ditemukan')
                    && str_contains($text, 'Baris 7: NISN wajib diisi');
            });

        $this->assertSame(0, Student::count());
    }

    public function test_rfid_card_of_another_student_is_rejected(): void
    {
        Student::create(['nisn' => '0077777777', 'full_name' => 'Pemilik', 'rombel_id' => $this->viiA->id, 'is_active' => true, 'rfid_uid' => 'ABC123']);

        $this->import([['0088888888', 'Peminjam', '7', 'VII A', '', '1', 'abc123']])
            ->assertSessionHas('import_errors', fn ($errors) => str_contains($errors[0], 'sudah terdaftar untuk Pemilik'));

        $this->assertNull(Student::where('nisn', '0088888888')->first());
    }

    public function test_wrong_file_is_rejected_with_a_clear_message(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([['full_name', 'rombel'], ['Andi', 'VII A']]);
        $path = tempnam(sys_get_temp_dir(), 'old') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $this->actingAs($this->admin)
            ->from(route('admin.students.import-form'))
            ->post(route('admin.students.import'), ['file' => new UploadedFile($path, 'lama.xlsx', null, null, true)])
            ->assertSessionHas('import_errors', fn ($errors) => str_contains($errors[0], 'Kolom "NISN" tidak ditemukan'));
    }

    public function test_current_students_template_round_trips(): void
    {
        Student::create(['nisn' => '0099999999', 'full_name' => 'Andi', 'rombel_id' => $this->viiA->id, 'is_active' => true]);

        $response = $this->actingAs($this->admin)->get(route('admin.students.download-template', ['with_students' => 1]))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'data') . '.xlsx';
        file_put_contents($path, $response->streamedContent());

        $this->actingAs($this->admin)
            ->post(route('admin.students.import'), ['file' => new UploadedFile($path, 'data.xlsx', null, null, true)])
            ->assertSessionHas('success', 'Import selesai: 0 siswa ditambahkan, 1 siswa diperbarui.');
    }

    public function test_student_list_shows_the_academic_year_and_filters_by_it(): void
    {
        Student::create(['nisn' => '1', 'full_name' => 'Siswa Lama', 'rombel_id' => $this->oldViiA->id, 'is_active' => true]);
        Student::create(['nisn' => '2', 'full_name' => 'Siswa Baru', 'rombel_id' => $this->viiA->id, 'is_active' => true]);

        $this->actingAs($this->admin)->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('Tahun Pelajaran')
            ->assertSee('2025/2026')
            ->assertSee('2026/2027');

        $this->actingAs($this->admin)
            ->get(route('admin.students.index', ['academic_year_id' => $this->viiA->academic_year_id]))
            ->assertSee('Siswa Baru')
            ->assertDontSee('Siswa Lama');
    }
}
