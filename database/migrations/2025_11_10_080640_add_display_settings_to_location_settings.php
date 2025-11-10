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
        // Add new display settings
        DB::table('location_settings')->insert([
            [
                'setting_key' => 'display_with_coordinates',
                'setting_value' => 'true',
                'description' => 'Tampilkan dengan titik koordinat',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'display_with_map',
                'setting_value' => 'true',
                'description' => 'Tampilkan dengan maps sesuai koordinat',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('location_settings')->whereIn('setting_key', [
            'display_with_coordinates',
            'display_with_map'
        ])->delete();
    }
};
