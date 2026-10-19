<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('reg_code', 30)->unique();
            $table->string('school_name', 200);
            $table->string('mentor_name', 150);
            $table->string('mentor_wa', 30);
            $table->string('package_type', 100);
            $table->string('competition_id', 50);
            $table->string('competition_title', 150);
            $table->text('participants_names');
            $table->text('notes')->nullable();
            $table->integer('total_fee')->default(50000);
            $table->string('payment_status', 50)->default('Menunggu Verifikasi');
            $table->string('payment_method', 50)->default('Transfer Bank / Tunai');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
