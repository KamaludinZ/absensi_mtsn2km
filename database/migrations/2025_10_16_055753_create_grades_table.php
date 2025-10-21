<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., '7', '8', '9'
            $table->string('description')->nullable(); // e.g., 'Kelas 7', 'Kelas 8', 'Kelas 9'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // Insert default grades
        \DB::table('grades')->insert([
            ['name' => '7', 'description' => 'Kelas 7', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '8', 'description' => 'Kelas 8', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '9', 'description' => 'Kelas 9', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
