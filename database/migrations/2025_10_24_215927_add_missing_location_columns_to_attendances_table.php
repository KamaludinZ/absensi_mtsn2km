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
        Schema::table('attendances', function (Blueprint $table) {
            // Add clock-in location columns if they don't exist
            if (!Schema::hasColumn('attendances', 'clock_in_latitude')) {
                $table->decimal('clock_in_latitude', 10, 8)->nullable()->after('clock_in');
            }
            
            if (!Schema::hasColumn('attendances', 'clock_in_longitude')) {
                $table->decimal('clock_in_longitude', 11, 8)->nullable()->after('clock_in_latitude');
            }
            
            if (!Schema::hasColumn('attendances', 'clock_in_accuracy')) {
                $table->decimal('clock_in_accuracy', 8, 2)->nullable()->after('clock_in_longitude');
            }
            
            // Add clock-out location columns if they don't exist
            if (!Schema::hasColumn('attendances', 'clock_out_latitude')) {
                $table->decimal('clock_out_latitude', 10, 8)->nullable()->after('clock_out');
            }
            
            if (!Schema::hasColumn('attendances', 'clock_out_longitude')) {
                $table->decimal('clock_out_longitude', 11, 8)->nullable()->after('clock_out_latitude');
            }
            
            if (!Schema::hasColumn('attendances', 'clock_out_accuracy')) {
                $table->decimal('clock_out_accuracy', 8, 2)->nullable()->after('clock_out_longitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Only drop columns if they exist
            if (Schema::hasColumn('attendances', 'clock_in_latitude')) {
                $table->dropColumn('clock_in_latitude');
            }
            
            if (Schema::hasColumn('attendances', 'clock_in_longitude')) {
                $table->dropColumn('clock_in_longitude');
            }
            
            if (Schema::hasColumn('attendances', 'clock_in_accuracy')) {
                $table->dropColumn('clock_in_accuracy');
            }
            
            if (Schema::hasColumn('attendances', 'clock_out_latitude')) {
                $table->dropColumn('clock_out_latitude');
            }
            
            if (Schema::hasColumn('attendances', 'clock_out_longitude')) {
                $table->dropColumn('clock_out_longitude');
            }
            
            if (Schema::hasColumn('attendances', 'clock_out_accuracy')) {
                $table->dropColumn('clock_out_accuracy');
            }
        });
    }
};
