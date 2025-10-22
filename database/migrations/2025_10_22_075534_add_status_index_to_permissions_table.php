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
        Schema::table('permissions', function (Blueprint $table) {
            // Add index on status column for faster status filtering queries
            $table->index('status');

            // Add composite index for common query pattern: filtering by type and status
            $table->index(['permissible_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            // Drop the indexes in reverse order
            $table->dropIndex(['permissible_type', 'status']);
            $table->dropIndex(['status']);
        });
    }
};
