<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Grade;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add grade_id column to rombels table
        Schema::table('rombels', function (Blueprint $table) {
            $table->unsignedBigInteger('grade_id')->nullable()->after('academic_year_id');
            $table->foreign('grade_id')->references('id')->on('grades')->onDelete('set null');
        });
        
        // Migrate existing grade data from the grade column to the new grade_id relationship
        $rombels = \App\Models\Rombel::all();
        foreach ($rombels as $rombel) {
            if ($rombel->grade) {
                $grade = Grade::where('name', $rombel->grade)->first();
                if ($grade) {
                    $rombel->update(['grade_id' => $grade->id]);
                }
            }
        }
        
        // Drop the grade column
        Schema::table('rombels', function (Blueprint $table) {
            $table->dropColumn('grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the grade column
        Schema::table('rombels', function (Blueprint $table) {
            $table->string('grade')->nullable()->after('name');
        });
        
        // Re-populate grade data
        $rombels = \App\Models\Rombel::all();
        foreach ($rombels as $rombel) {
            if ($rombel->grade_id) {
                $grade = Grade::find($rombel->grade_id);
                if ($grade) {
                    $rombel->update(['grade' => $grade->name]);
                }
            }
        }
        
        // Drop the foreign key and grade_id column
        Schema::table('rombels', function (Blueprint $table) {
            $table->dropForeign(['grade_id']);
            $table->dropColumn('grade_id');
        });
    }
};
