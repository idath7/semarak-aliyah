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
        if (!Schema::hasTable('tournament_matches')) {
            Schema::create('tournament_matches', function (Blueprint $table) {
                $table->id();
                $table->string('competition_id', 50);
                $table->string('match_number', 30)->nullable(); // e.g. "M-01", "QF-1", "SF-1", "FINAL"
                $table->string('round_name', 50)->default('Babak Penyisihan'); // 'Penyisihan', 'Perempat Final', 'Semi Final', 'Perebutan Juara 3', 'Grand Final'
                $table->integer('round_order')->default(1); // 1 = Babak 1, 2 = QF, 3 = SF, 4 = Final

                // Team 1
                $table->unsignedBigInteger('team1_registration_id')->nullable();
                $table->string('team1_placeholder', 100)->nullable(); // e.g. "Pemenang QF-1"
                $table->integer('team1_score')->default(0); // Sets or Games won (e.g. 2)
                $table->text('team1_set_scores')->nullable(); // JSON or text, e.g. "25, 25"

                // Team 2
                $table->unsignedBigInteger('team2_registration_id')->nullable();
                $table->string('team2_placeholder', 100)->nullable(); // e.g. "Pemenang QF-2"
                $table->integer('team2_score')->default(0); // Sets or Games won (e.g. 1)
                $table->text('team2_set_scores')->nullable(); // JSON or text, e.g. "23, 21"

                // Outcome & Progression
                $table->unsignedBigInteger('winner_registration_id')->nullable();
                $table->unsignedBigInteger('next_match_id')->nullable(); // ID of match winner advances to
                $table->string('next_match_slot', 10)->nullable(); // 'team1' or 'team2'

                // Match Status & Arena Info
                $table->enum('status', ['scheduled', 'ongoing', 'completed'])->default('scheduled');
                $table->string('court', 50)->nullable(); // e.g. 'Lapangan Utama', 'Lapangan 2'
                $table->string('scheduled_time', 50)->nullable(); // e.g. '09:00 WIB'
                $table->string('referee_name', 100)->nullable(); // Wasit / Referee
                $table->text('notes')->nullable();

                $table->timestamps();

                $table->foreign('competition_id')->references('id')->on('competitions')->onDelete('cascade');
                $table->foreign('team1_registration_id')->references('id')->on('registrations')->onDelete('set null');
                $table->foreign('team2_registration_id')->references('id')->on('registrations')->onDelete('set null');
                $table->foreign('winner_registration_id')->references('id')->on('registrations')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
