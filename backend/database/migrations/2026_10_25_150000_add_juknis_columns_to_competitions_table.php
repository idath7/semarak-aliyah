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
            if (!Schema::hasColumn('competitions', 'juknis_file')) {
                $table->string('juknis_file')->nullable()->after('description');
            }
            if (!Schema::hasColumn('competitions', 'juknis_original_name')) {
                $table->string('juknis_original_name')->nullable()->after('juknis_file');
            }
            if (!Schema::hasColumn('competitions', 'juknis_size')) {
                $table->unsignedBigInteger('juknis_size')->nullable()->after('juknis_original_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (Schema::hasColumn('competitions', 'juknis_size')) {
                $table->dropColumn('juknis_size');
            }
            if (Schema::hasColumn('competitions', 'juknis_original_name')) {
                $table->dropColumn('juknis_original_name');
            }
            if (Schema::hasColumn('competitions', 'juknis_file')) {
                $table->dropColumn('juknis_file');
            }
        });
    }
};
