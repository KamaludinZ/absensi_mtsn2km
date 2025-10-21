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
        Schema::table('rombels', function (Blueprint $table) {
            $table->string('grade')->nullable()->after('name');
        });
        
        // Update existing rombels to populate the grade field
        $rombels = \App\Models\Rombel::all();
        foreach ($rombels as $rombel) {
            $grade = null;
            if (preg_match('/^(VII|VIII|IX)/', $rombel->name, $matches)) {
                switch($matches[1]) {
                    case 'VII': $grade = '7'; break;
                    case 'VIII': $grade = '8'; break;
                    case 'IX': $grade = '9'; break;
                }
            }
            if ($grade) {
                $rombel->update(['grade' => $grade]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rombels', function (Blueprint $table) {
            $table->dropColumn('grade');
        });
    }
};
