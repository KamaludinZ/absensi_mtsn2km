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
        Schema::table('holidays', function (Blueprint $table) {
            // Add index on date column for faster date-based queries (whereMonth, whereYear)
            $table->index('date');

            // Add index on is_active for filtering active holidays
            $table->index('is_active');

            // Add composite index for common query pattern: active holidays by date
            $table->index(['is_active', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            // Drop the indexes in reverse order
            $table->dropIndex(['is_active', 'date']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['date']);
        });
    }
};
