<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Date a student graduated. Graduated students are also inactive, so they
     * can no longer use the attendance page or their RFID card.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('graduated_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('graduated_at');
        });
    }
};
