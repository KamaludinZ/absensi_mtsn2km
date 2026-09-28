<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Student permissions need their own kind: S (Sakit) already exists for
 * employees, I (Izin) is added so the student recap can tell them apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->setTypes(['C', 'S', 'DL', 'I']);
    }

    public function down(): void
    {
        DB::table('permissions')->where('type', 'I')->update(['type' => null]);
        $this->setTypes(['C', 'S', 'DL']);
    }

    private function setTypes(array $types): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $list = implode(',', array_map(fn ($t) => "'" . $t . "'", $types));
            DB::statement('ALTER TABLE permissions DROP CONSTRAINT IF EXISTS permissions_type_check');
            DB::statement("ALTER TABLE permissions ADD CONSTRAINT permissions_type_check CHECK (type::text = ANY (ARRAY[$list]::text[]))");

            return;
        }

        Schema::table('permissions', function (Blueprint $table) use ($types) {
            $table->enum('type', $types)->nullable()->change();
        });
    }
};
