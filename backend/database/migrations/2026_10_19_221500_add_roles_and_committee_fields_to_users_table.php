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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('panitia')->after('email');
            $table->string('division')->nullable()->after('role');
            $table->string('phone')->nullable()->after('division');
            $table->string('competition_id')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('competition_id');
            $table->string('avatar')->nullable()->after('is_active');

            $table->foreign('competition_id')->references('id')->on('competitions')->nullOnDelete();
            $table->index(['role', 'is_active']);
        });

        // Set existing users to super_admin
        \DB::table('users')->update([
            'role' => 'super_admin',
            'division' => 'BPH / Pimpinan',
            'is_active' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['competition_id']);
            $table->dropIndex(['role', 'is_active']);
            $table->dropColumn(['role', 'division', 'phone', 'competition_id', 'is_active', 'avatar']);
        });
    }
};
