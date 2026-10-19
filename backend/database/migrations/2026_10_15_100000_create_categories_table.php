<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('name', 100);
                $table->string('label', 150)->nullable();
                $table->string('icon', 50)->default('ri-price-tag-3-line');
                $table->string('color', 50)->default('primary');
                $table->text('description')->nullable();
                $table->timestamps();
            });

            $defaults = [
                ['id' => 'seni', 'name' => 'Seni & Budaya', 'label' => 'Bidang Seni & Budaya', 'icon' => 'ri-palette-line', 'color' => 'primary'],
                ['id' => 'olahraga', 'name' => 'Olahraga', 'label' => 'Bidang Olahraga', 'icon' => 'ri-football-line', 'color' => 'success'],
                ['id' => 'akademik', 'name' => 'Sains & Akademik', 'label' => 'Bidang Sains & Akademik', 'icon' => 'ri-book-read-line', 'color' => 'warning'],
                ['id' => 'keagamaan', 'name' => 'Keagamaan & Da\'wah', 'label' => 'Bidang Keagamaan & Da\'wah', 'icon' => 'ri-compassion-line', 'color' => 'info'],
                ['id' => 'teknologi', 'name' => 'Teknologi & E-Sport', 'label' => 'Bidang Teknologi & E-Sport', 'icon' => 'ri-gamepad-line', 'color' => 'secondary'],
            ];

            foreach ($defaults as $d) {
                DB::table('categories')->insert(array_merge($d, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
