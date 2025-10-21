<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class Holiday2025Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $holidays2025 = [
            ['2025-01-01', 'Tahun Baru Masehi', 'Tahun Baru Masehi'],
            ['2025-02-10', 'Tahun Baru Imlek 2576', 'Tahun Baru Imlek'],
            ['2025-03-29', 'Hari Raya Nyepi Tahun Baru Saka 1947', 'Hari Raya Nyepi'],
            ['2025-04-18', 'Wafat Yesus Kristus', 'Wafat Yesus Kristus'],
            ['2025-05-01', 'Hari Buruh Internasional', 'Hari Buruh'],
            ['2025-05-10', 'Kenaikan Isa Almasih', 'Kenaikan Isa Almasih'],
            ['2025-05-29', 'Hari Raya Waisak 2569 BE', 'Hari Raya Waisak'],
            ['2025-06-01', 'Hari Lahir Pancasila', 'Hari Lahir Pancasila'],
            ['2025-06-05', 'Hari Raya Idul Fitri 1446 Hijriah', 'Hari Raya Idul Fitri'],
            ['2025-06-06', 'Hari Raya Idul Fitri 1446 Hijriah', 'Hari Raya Idul Fitri'],
            ['2025-08-17', 'Hari Kemerdekaan Republik Indonesia', 'Hari Kemerdekaan RI'],
            ['2025-09-07', 'Hari Raya Idul Adha 1446 Hijriah', 'Hari Raya Idul Adha'],
            ['2025-10-02', 'Maulid Nabi Muhammad SAW', 'Maulid Nabi'],
            ['2025-11-01', 'Hari Raya Halloween', 'Hari Raya Halloween'],
            ['2025-12-25', 'Hari Raya Natal', 'Hari Raya Natal'],
        ];

        foreach ($holidays2025 as $holiday) {
            \App\Models\Holiday::create([
                'name' => $holiday[1],
                'date' => $holiday[0],
                'description' => $holiday[2],
                'is_active' => true,
            ]);
        }
    }
}
