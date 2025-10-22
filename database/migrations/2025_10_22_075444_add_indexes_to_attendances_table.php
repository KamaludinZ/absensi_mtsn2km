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
            // Add index on date column for faster date-based queries
            $table->index('date');

            // Add composite indexes for frequently used query combinations
            $table->index(['attendable_type', 'date']); // For dashboard queries filtering by type and date
            $table->index(['attendable_id', 'date']); // For individual attendance history queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Drop the indexes in reverse order
            $table->dropIndex(['attendable_id', 'date']);
            $table->dropIndex(['attendable_type', 'date']);
            $table->dropIndex(['date']);
        });
    }
};
