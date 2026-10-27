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
            if (!Schema::hasColumn('competitions', 'registration_type')) {
                $table->string('registration_type')->default('online')->after('status');
            }
            if (!Schema::hasColumn('competitions', 'direct_registration_note')) {
                $table->text('direct_registration_note')->nullable()->after('registration_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (Schema::hasColumn('competitions', 'direct_registration_note')) {
                $table->dropColumn('direct_registration_note');
            }
            if (Schema::hasColumn('competitions', 'registration_type')) {
                $table->dropColumn('registration_type');
            }
        });
    }
};
