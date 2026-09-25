<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remember the class (rombel) a student was in when an attendance or a
     * permission was recorded, so history stays correct after the student
     * moves to another class or is promoted.
     */
    public function up(): void
    {
        foreach (['attendances', 'permissions'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('rombel_id')->nullable()->constrained('rombels')->nullOnDelete();
                $table->index('rombel_id');
            });
        }

        // Existing rows: best available information is the student's current class.
        $student = 'App\\Models\\Student';

        DB::update(
            'UPDATE attendances SET rombel_id = (SELECT students.rombel_id FROM students WHERE students.id = attendances.attendable_id)
             WHERE attendable_type = ? AND rombel_id IS NULL',
            [$student]
        );

        DB::update(
            'UPDATE permissions SET rombel_id = (SELECT students.rombel_id FROM students WHERE students.id = permissions.permissible_id)
             WHERE permissible_type = ? AND rombel_id IS NULL',
            [$student]
        );
    }

    public function down(): void
    {
        foreach (['attendances', 'permissions'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['rombel_id']);
                $table->dropIndex(['rombel_id']);
                $table->dropColumn('rombel_id');
            });
        }
    }
};
