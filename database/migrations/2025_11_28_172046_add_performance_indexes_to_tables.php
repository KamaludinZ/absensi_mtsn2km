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
        // Add missing indexes to attendances table for better query performance
        // Note: date index already exists from previous migration
        Schema::table('attendances', function (Blueprint $table) {
            $table->index('status_in'); // For counting late arrivals
            $table->index('status_out'); // For counting early departures
        });

        // Add indexes to employees table for sorting and searching
        Schema::table('employees', function (Blueprint $table) {
            $table->index('full_name'); // For sorting and searching
            $table->index('is_active'); // For filtering active employees
        });

        // Add indexes to students table for sorting and searching
        Schema::table('students', function (Blueprint $table) {
            $table->index('full_name'); // For ILIKE searches
            $table->index('is_active'); // For filtering active students
        });

        // Add index to permissions table for date range queries
        Schema::table('permissions', function (Blueprint $table) {
            $table->index(['start_date', 'end_date']); // For date range queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['status_in']);
            $table->dropIndex(['status_out']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['full_name']);
            $table->dropIndex(['is_active']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['full_name']);
            $table->dropIndex(['is_active']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex(['start_date', 'end_date']);
        });
    }
};
