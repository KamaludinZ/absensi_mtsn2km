<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->morphs('attendable'); // attendable_id, attendable_type (with index)
            $table->date('date');
            $table->timestampTz('clock_in')->nullable();
            $table->timestampTz('clock_out')->nullable();
            $table->string('selfie_path_in')->nullable();
            $table->string('selfie_path_out')->nullable();
            $table->string('status_in')->nullable();
            $table->string('status_out')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
