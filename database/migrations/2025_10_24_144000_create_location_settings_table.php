<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('location_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique(); // e.g., 'geofence_center_lat', 'geofence_center_lng', 'geofence_radius'
            $table->text('setting_value'); // JSON string or value
            $table->string('description')->nullable();
            $table->timestamps();
        });
        
        // Insert default geofence settings
        DB::table('location_settings')->insert([
            [
                'setting_key' => 'geofence_center_lat',
                'setting_value' => '-6.200000', // Default to Jakarta as example
                'description' => 'Latitude coordinate of geofence center',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'geofence_center_lng',
                'setting_value' => '106.816666', // Default to Jakarta as example
                'description' => 'Longitude coordinate of geofence center',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'geofence_radius',
                'setting_value' => '50', // 50 meters radius
                'description' => 'Geofence radius in meters',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'location_required',
                'setting_value' => 'true',
                'description' => 'Whether location is required for attendance',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_settings');
    }
};