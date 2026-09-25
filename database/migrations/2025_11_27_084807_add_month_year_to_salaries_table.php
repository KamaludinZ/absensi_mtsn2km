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
        Schema::table('salaries', function (Blueprint $table) {
            // Add month and year columns
            $table->unsignedTinyInteger('month')->default(1)->after('employee_id');
            $table->unsignedSmallInteger('year')->default(2025)->after('month');

            // Add unique constraint for employee_id, month, year combination
            $table->unique(['employee_id', 'month', 'year'], 'salaries_employee_month_year_unique');
        });

        // Update existing records to current month/year (works on any database)
        \DB::table('salaries')
            ->where('month', 1)
            ->where('year', 2025)
            ->update(['month' => now()->month, 'year' => now()->year]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            // Drop unique constraint
            $table->dropUnique('salaries_employee_month_year_unique');

            // Drop month and year columns
            $table->dropColumn(['month', 'year']);
        });
    }
};
