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
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->boolean('successful')->default(false);
            $table->string('failure_reason')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();

            $table->index('username');
            $table->index('ip_address');
            $table->index('attempted_at');
            $table->index(['successful', 'attempted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
