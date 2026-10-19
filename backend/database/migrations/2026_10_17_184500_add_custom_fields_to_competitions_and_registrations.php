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
            if (!Schema::hasColumn('competitions', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('scoring_rubrics');
            }
        });

        Schema::table('registrations', function (Blueprint $table) {
            if (!Schema::hasColumn('registrations', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('file_documents');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (Schema::hasColumn('competitions', 'custom_fields')) {
                $table->dropColumn('custom_fields');
            }
        });

        Schema::table('registrations', function (Blueprint $table) {
            if (Schema::hasColumn('registrations', 'custom_fields')) {
                $table->dropColumn('custom_fields');
            }
        });
    }
};
