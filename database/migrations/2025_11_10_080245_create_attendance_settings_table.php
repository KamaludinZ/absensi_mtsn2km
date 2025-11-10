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
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique();
            $table->string('setting_value');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default values
        DB::table('attendance_settings')->insert([
            [
                'setting_key' => 'require_selfie_photo',
                'setting_value' => 'true',
                'description' => 'Wajibkan foto selfie saat absensi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'setting_key' => 'require_check_in',
                'setting_value' => 'true',
                'description' => 'Wajibkan absensi datang',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'setting_key' => 'require_check_out',
                'setting_value' => 'true',
                'description' => 'Wajibkan absensi pulang',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
