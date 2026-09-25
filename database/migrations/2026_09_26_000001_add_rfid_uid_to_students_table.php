<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Card number of the student's RFID card, used on the attendance page.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('rfid_uid', 50)->nullable()->unique()->after('nisn');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['rfid_uid']);
            $table->dropColumn('rfid_uid');
        });
    }
};
