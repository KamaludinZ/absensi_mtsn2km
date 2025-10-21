<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Student;
use App\Models\Rombel;
use App\Models\Grade;
use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    public function run()
    {
        // Create academic years
        $academicYears = [
            ['name' => '2024/2025', 'status' => 'active'],
            ['name' => '2025/2026', 'status' => 'inactive'],
        ];
        
        foreach ($academicYears as $year) {
            AcademicYear::create($year);
        }
        
        // Get academic years for reference
        $activeYear = AcademicYear::where('status', 'active')->first();
        
        // Create grades
        $grades = [
            ['name' => 'VII', 'description' => 'Kelas VII'],
            ['name' => 'VIII', 'description' => 'Kelas VIII'],
            ['name' => 'IX', 'description' => 'Kelas IX'],
        ];
        
        foreach ($grades as $grade) {
            Grade::create($grade);
        }
        
        // Get grades for reference
        $gradeVII = Grade::where('name', 'VII')->first();
        $gradeVIII = Grade::where('name', 'VIII')->first();
        $gradeIX = Grade::where('name', 'IX')->first();
        
        // Create rombels
        $rombelsData = [
            ['name' => 'VII A', 'grade_id' => $gradeVII->id, 'academic_year_id' => $activeYear->id],
            ['name' => 'VII B', 'grade_id' => $gradeVII->id, 'academic_year_id' => $activeYear->id],
            ['name' => 'VII C', 'grade_id' => $gradeVII->id, 'academic_year_id' => $activeYear->id],
            ['name' => 'VIII A', 'grade_id' => $gradeVIII->id, 'academic_year_id' => $activeYear->id],
            ['name' => 'VIII B', 'grade_id' => $gradeVIII->id, 'academic_year_id' => $activeYear->id],
            ['name' => 'IX A', 'grade_id' => $gradeIX->id, 'academic_year_id' => $activeYear->id],
        ];
        
        foreach ($rombelsData as $rombel) {
            Rombel::create($rombel);
        }
        
        // Get all rombels
        $rombels = Rombel::all();
        
        // Create employees
        $employees = [
            [
                'full_name' => 'Ahmad Fauzi',
                'position' => 'Guru Matematika',
                'phone' => '081234567890',
                'email' => 'ahmad.fauzi@mts2malang.sch.id',
                'is_active' => true,
            ],
            [
                'full_name' => 'Siti Nurhaliza',
                'position' => 'Guru Bahasa Indonesia',
                'phone' => '082345678901',
                'email' => 'siti.nurhaliza@mts2malang.sch.id',
                'is_active' => true,
            ],
            [
                'full_name' => 'Budi Santoso',
                'position' => 'Guru IPA',
                'phone' => '083456789012',
                'email' => 'budi.santoso@mts2malang.sch.id',
                'is_active' => true,
            ],
            [
                'full_name' => 'Dewi Kusuma',
                'position' => 'Guru Bahasa Inggris',
                'phone' => '084567890123',
                'email' => 'dewi.kusuma@mts2malang.sch.id',
                'is_active' => true,
            ],
            [
                'full_name' => 'Rizki Pratama',
                'position' => 'Guru IPS',
                'phone' => '085678901234',
                'email' => 'rizki.pratama@mts2malang.sch.id',
                'is_active' => true,
            ],
        ];

        foreach ($employees as $employeeData) {
            $employee = Employee::create($employeeData);
            
            // Create a user for each employee
            $user = \App\Models\User::create([
                'name' => $employee->full_name,
                'email' => $employee->email,
                'password' => bcrypt('password'),
                'role' => 'employee',
            ]);
            
            $employee->update(['user_id' => $user->id]);
        }
        
        // Create students
        $students = [
            [
                'full_name' => 'Andi Prasetyo',
                'rombel_id' => $rombels->where('name', 'VII A')->first()->id ?? null,
                'is_active' => true,
            ],
            [
                'full_name' => 'Bella Sari',
                'rombel_id' => $rombels->where('name', 'VII A')->first()->id ?? null,
                'is_active' => true,
            ],
            [
                'full_name' => 'Cahya Nugroho',
                'rombel_id' => $rombels->where('name', 'VII B')->first()->id ?? null,
                'is_active' => true,
            ],
            [
                'full_name' => 'Dian Puspita',
                'rombel_id' => $rombels->where('name', 'VII B')->first()->id ?? null,
                'is_active' => true,
            ],
            [
                'full_name' => 'Eko Saputra',
                'rombel_id' => $rombels->where('name', 'VII C')->first()->id ?? null,
                'is_active' => true,
            ],
            [
                'full_name' => 'Fitri Lestari',
                'rombel_id' => $rombels->where('name', 'VII C')->first()->id ?? null,
                'is_active' => true,
            ],
            [
                'full_name' => 'Guntur Ramadhan',
                'rombel_id' => $rombels->where('name', 'VIII A')->first()->id ?? null,
                'is_active' => true,
            ],
            [
                'full_name' => 'Hani Putri',
                'rombel_id' => $rombels->where('name', 'VIII A')->first()->id ?? null,
                'is_active' => true,
            ],
            [
                'full_name' => 'Indra Lesmana',
                'rombel_id' => $rombels->where('name', 'VIII B')->first()->id ?? null,
                'is_active' => true,
            ],
            [
                'full_name' => 'Juni Astuti',
                'rombel_id' => $rombels->where('name', 'VIII B')->first()->id ?? null,
                'is_active' => true,
            ],
            [
                'full_name' => 'Kurnia Wati',
                'rombel_id' => $rombels->where('name', 'IX A')->first()->id ?? null,
                'is_active' => true,
            ],
            [
                'full_name' => 'Lukman Hakim',
                'rombel_id' => $rombels->where('name', 'IX A')->first()->id ?? null,
                'is_active' => true,
            ],
            [
                'full_name' => 'Maya Sari',
                'rombel_id' => $rombels->where('name', 'IX A')->first()->id ?? null,
                'is_active' => true,
            ],
            [
                'full_name' => 'Nur Hidayat',
                'rombel_id' => $rombels->where('name', 'IX A')->first()->id ?? null,
                'is_active' => true,
            ],
            [
                'full_name' => 'Oki Prasetya',
                'rombel_id' => $rombels->where('name', 'IX A')->first()->id ?? null,
                'is_active' => true,
            ],
        ];

        foreach ($students as $studentData) {
            if ($studentData['rombel_id']) {
                Student::create($studentData);
            }
        }
    }
}