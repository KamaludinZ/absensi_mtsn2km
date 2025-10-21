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
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('shift_name')->nullable()->after('date');
            $table->string('shift_type')->nullable()->after('shift_name');
            $table->time('scheduled_start_time')->nullable()->after('shift_type');
            $table->time('scheduled_end_time')->nullable()->after('scheduled_start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['shift_name', 'shift_type', 'scheduled_start_time', 'scheduled_end_time']);
        });
    }
};
