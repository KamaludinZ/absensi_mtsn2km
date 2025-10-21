<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Grade;
use App\Models\Schedule;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $pegawaiRole = Role::create(['name' => 'pegawai']);
        $monitoringRole = Role::create(['name' => 'monitoring']);

        // Create admin user
        User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
        ]);

        // Create sample academic year
        $academicYear = AcademicYear::create([
            'year_name' => '2025/2026',
            'status' => 'active',
        ]);

        // Get existing grades (created by migration)
        $grade7 = Grade::where('name', '7')->first();
        $grade8 = Grade::where('name', '8')->first();
        $grade9 = Grade::where('name', '9')->first();

        // Create sample rombels
        $rombels = [
            ['academic_year_id' => $academicYear->id, 'grade_id' => $grade7->id, 'name' => 'VII A'],
            ['academic_year_id' => $academicYear->id, 'grade_id' => $grade7->id, 'name' => 'VII B'],
            ['academic_year_id' => $academicYear->id, 'grade_id' => $grade7->id, 'name' => 'VII C'],
            ['academic_year_id' => $academicYear->id, 'grade_id' => $grade8->id, 'name' => 'VIII A'],
            ['academic_year_id' => $academicYear->id, 'grade_id' => $grade8->id, 'name' => 'VIII B'],
            ['academic_year_id' => $academicYear->id, 'grade_id' => $grade8->id, 'name' => 'VIII C'],
            ['academic_year_id' => $academicYear->id, 'grade_id' => $grade9->id, 'name' => 'IX A'],
            ['academic_year_id' => $academicYear->id, 'grade_id' => $grade9->id, 'name' => 'IX B'],
            ['academic_year_id' => $academicYear->id, 'grade_id' => $grade9->id, 'name' => 'IX C'],
        ];

        foreach ($rombels as $rombelData) {
            Rombel::create($rombelData);
        }

        // Create sample schedules for employees (Monday to Friday)
        for ($day = 1; $day <= 5; $day++) {
            Schedule::create([
                'type' => 'pegawai',
                'day_of_week' => $day,
                'start_time' => '07:00:00',
                'end_time' => '15:00:00',
            ]);
        }

        // Create sample schedules for students (Monday to Friday)
        for ($day = 1; $day <= 5; $day++) {
            Schedule::create([
                'type' => 'siswa',
                'day_of_week' => $day,
                'start_time' => '07:30:00',
                'end_time' => '14:00:00',
            ]);
        }

        // Create default settings
        \App\Models\Setting::create([
            'key' => 'report_header_path',
            'value' => '',
        ]);

        \App\Models\Setting::create([
            'key' => 'school_name',
            'value' => 'MTsN 2 Kota Malang',
        ]);

        \App\Models\Setting::create([
            'key' => 'school_address',
            'value' => 'Kota Malang, Jawa Timur',
        ]);
        
        // Call the dummy data seeder
        $this->call(DummyDataSeeder::class);
    }
}
