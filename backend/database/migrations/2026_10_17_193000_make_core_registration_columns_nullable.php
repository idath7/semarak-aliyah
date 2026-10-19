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
            $table->string('school_name', 200)->nullable()->change();
            $table->string('mentor_name', 150)->nullable()->change();
            $table->string('mentor_wa', 30)->nullable()->change();
            $table->string('package_type', 100)->nullable()->default('Paket Satuan')->change();
            $table->text('participants_names')->nullable()->change();
        });

        Schema::table('competitions', function (Blueprint $table) {
            if (!Schema::hasColumn('competitions', 'form_settings')) {
                $table->json('form_settings')->nullable()->after('custom_fields');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (Schema::hasColumn('competitions', 'form_settings')) {
                $table->dropColumn('form_settings');
            }
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('school_name', 200)->nullable(false)->change();
            $table->string('mentor_name', 150)->nullable(false)->change();
            $table->string('mentor_wa', 30)->nullable(false)->change();
            $table->string('package_type', 100)->nullable(false)->change();
            $table->text('participants_names')->nullable(false)->change();
        });
    }
};
