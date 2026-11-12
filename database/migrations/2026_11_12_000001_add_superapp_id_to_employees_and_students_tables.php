<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Super App ID (the "id" Super App sends at SSO login) of each GTK and
 * student, so a Super App account maps to exactly one account here.
 * Filled on the first SSO login or by an admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('superapp_id', 64)->nullable()->unique()->after('nip');
        });
        Schema::table('students', function (Blueprint $table) {
            $table->string('superapp_id', 64)->nullable()->unique()->after('nisn');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['superapp_id']);
            $table->dropColumn('superapp_id');
        });
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['superapp_id']);
            $table->dropColumn('superapp_id');
        });
    }
};
