<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new time difference format setting
        DB::table('attendance_settings')->insert([
            'setting_key' => 'time_difference_format',
            'setting_value' => 'time', // default to time format (HH:mm:ss)
            'description' => 'Format perhitungan selisih waktu: time (HH:mm:ss) atau minutes (total menit)',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('attendance_settings')->where('setting_key', 'time_difference_format')->delete();
    }
};
