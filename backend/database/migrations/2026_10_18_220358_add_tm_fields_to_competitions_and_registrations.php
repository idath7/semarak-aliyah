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
        Schema::table('competitions', function (Blueprint $table) {
            if (!Schema::hasColumn('competitions', 'tm_settings')) {
                $table->json('tm_settings')->nullable()->after('form_settings');
            }
        });

        Schema::table('registrations', function (Blueprint $table) {
            if (!Schema::hasColumn('registrations', 'draw_number')) {
                $table->integer('draw_number')->nullable()->after('verification_notes');
            }
            if (!Schema::hasColumn('registrations', 'draw_notes')) {
                $table->string('draw_notes', 255)->nullable()->after('draw_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (Schema::hasColumn('competitions', 'tm_settings')) {
                $table->dropColumn('tm_settings');
            }
        });

        Schema::table('registrations', function (Blueprint $table) {
            if (Schema::hasColumn('registrations', 'draw_number')) {
                $table->dropColumn('draw_number');
            }
            if (Schema::hasColumn('registrations', 'draw_notes')) {
                $table->dropColumn('draw_notes');
            }
        });
    }
};
