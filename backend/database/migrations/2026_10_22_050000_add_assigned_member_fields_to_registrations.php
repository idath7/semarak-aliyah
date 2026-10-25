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
            if (!Schema::hasColumn('registrations', 'assigned_name')) {
                $table->string('assigned_name')->nullable()->after('mentor_wa');
            }
            if (!Schema::hasColumn('registrations', 'assigned_phone')) {
                $table->string('assigned_phone', 30)->nullable()->after('assigned_name')->index();
            }
            if (!Schema::hasColumn('registrations', 'assigned_role')) {
                $table->string('assigned_role', 50)->nullable()->after('assigned_phone'); // Koordinator Cabang, Kapten Tim, Siswa Peserta
            }
            if (!Schema::hasColumn('registrations', 'assigned_notes')) {
                $table->text('assigned_notes')->nullable()->after('assigned_role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn([
                'assigned_name',
                'assigned_phone',
                'assigned_role',
                'assigned_notes',
            ]);
        });
    }
};
