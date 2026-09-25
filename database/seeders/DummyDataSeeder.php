<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Sample employees and students for local development only.
 * DatabaseSeeder skips this seeder in production.
 *
 * Relies on the roles, academic year and rombels created by DatabaseSeeder
 * and the grades inserted by the grades migration. Safe to run twice.
 */
class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $pegawaiRole = Role::firstOrCreate(['name' => 'pegawai']);

        $employees = [
            ['username' => 'ahmad.fauzi', 'full_name' => 'Ahmad Fauzi', 'position' => 'Guru Matematika', 'phone' => '081234567890'],
            ['username' => 'siti.nurhaliza', 'full_name' => 'Siti Nurhaliza', 'position' => 'Guru Bahasa Indonesia', 'phone' => '082345678901'],
            ['username' => 'budi.santoso', 'full_name' => 'Budi Santoso', 'position' => 'Guru IPA', 'phone' => '083456789012'],
            ['username' => 'dewi.kusuma', 'full_name' => 'Dewi Kusuma', 'position' => 'Guru Bahasa Inggris', 'phone' => '084567890123'],
            ['username' => 'rizki.pratama', 'full_name' => 'Rizki Pratama', 'position' => 'Staf Tata Usaha', 'phone' => '085678901234'],
        ];

        foreach ($employees as $data) {
            $user = User::firstOrCreate(
                ['username' => $data['username']],
                [
                    'name' => $data['full_name'],
                    'password' => Hash::make('password123'),
                    'role_id' => $pegawaiRole->id,
                ]
            );

            Employee::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $data['full_name'],
                    'position' => $data['position'],
                    'phone' => $data['phone'],
                    'email' => $data['username'] . '@example.com',
                    'is_active' => true,
                ]
            );
        }

        $rombels = Rombel::orderBy('name')->get();
        if ($rombels->isEmpty()) {
            $this->command?->warn('No rombels found; run DatabaseSeeder first. Skipping sample students.');
            return;
        }

        $names = [
            'Andi Prasetyo', 'Bella Sari', 'Cahya Nugroho', 'Dian Puspita', 'Eko Saputra',
            'Fitri Lestari', 'Guntur Ramadhan', 'Hana Wijaya', 'Indra Kurniawan', 'Joko Susilo',
        ];

        foreach ($names as $i => $name) {
            Student::firstOrCreate(
                ['nisn' => sprintf('00%08d', $i + 1)],
                [
                    'full_name' => $name,
                    'rombel_id' => $rombels[$i % $rombels->count()]->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
