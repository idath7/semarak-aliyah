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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('npsn', 20)->nullable()->index();
            $table->string('name', 150)->index();
            $table->string('jenjang', 20)->index(); // SMP or MTs
            $table->string('status', 20)->default('Swasta')->index(); // Negeri or Swasta
            $table->string('kecamatan', 100)->index();
            $table->string('kabupaten', 100)->default('Kabupaten Jember')->index();
            $table->string('alamat', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('principal_name', 150)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            // Composite indexes for fast search & filtering
            $table->index(['jenjang', 'status', 'kecamatan']);
            $table->index(['name', 'kecamatan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
