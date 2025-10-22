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
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event_type'); // login, logout, permission_change, data_access, etc.
            $table->string('severity')->default('info'); // info, warning, critical
            $table->text('description');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Additional context data
            $table->timestamps();

            $table->index('event_type');
            $table->index('severity');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_logs');
    }
};
