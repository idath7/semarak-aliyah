<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('title', 150);
            $table->string('category', 50)->default('akademik');
            $table->string('category_label', 100);
            $table->string('icon', 20)->default('🎯');
            $table->integer('fee')->default(50000);
            $table->integer('max_quota')->default(30);
            $table->integer('current_quota')->default(0);
            $table->string('status', 20)->default('open');
            $table->string('participants_rule', 255);
            $table->string('tm_date', 150);
            $table->string('contact_person', 100);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
