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
        Schema::table('registrations', function (Blueprint $table) {
            if (Schema::hasColumn('registrations', 'mentor_wa')) {
                $table->index('mentor_wa', 'idx_reg_mentor_wa');
            }
            if (Schema::hasColumn('registrations', 'school_name')) {
                $table->index('school_name', 'idx_reg_school_name');
            }
            if (Schema::hasColumn('registrations', 'registrant_id')) {
                $table->index('registrant_id', 'idx_reg_registrant_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex('idx_reg_mentor_wa');
            $table->dropIndex('idx_reg_school_name');
            $table->dropIndex('idx_reg_registrant_id');
        });
    }
};
