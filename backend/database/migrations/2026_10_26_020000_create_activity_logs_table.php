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
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_name')->nullable();
                $table->string('role')->nullable();
                $table->string('action')->index(); // 'login', 'logout', 'verification', 'checkin', 'password_change', etc.
                $table->string('activity_type')->default('panitia')->index(); // 'panitia', 'auth', 'verification', 'checkin', 'system'
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('highlight')->nullable();
                $table->string('badge_icon')->default('ri-shield-user-line');
                $table->string('badge_class')->default('bg-primary-subtle text-primary');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('properties')->nullable();
                $table->timestamps();

                $table->index(['created_at']);
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
