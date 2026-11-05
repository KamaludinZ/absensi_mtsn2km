<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee shifts can say on which weekdays (Hari Penerapan) and for which
 * positions (Jabatan Penerapan) they apply, so /admin/employee-shifts can be
 * filled in automatically. employee_shift_autofills remembers which dates
 * were already filled, so later manual changes on those dates are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // ISO weekdays, 1 = Senin ... 7 = Minggu (same as the active days setting)
            $table->json('apply_days')->nullable()->after('shift_name');
            $table->json('apply_positions')->nullable()->after('apply_days');
        });

        Schema::create('employee_shift_autofills', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedInteger('filled_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_shift_autofills');

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['apply_days', 'apply_positions']);
        });
    }
};
