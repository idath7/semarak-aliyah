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
        // 1. Add event-day columns to registrations table
        Schema::table('registrations', function (Blueprint $table) {
            if (!Schema::hasColumn('registrations', 'barcode')) {
                $table->string('barcode', 100)->nullable()->after('reg_code');
            }
            if (!Schema::hasColumn('registrations', 'checkin_status')) {
                $table->string('checkin_status', 50)->default('belum_hadir')->after('payment_status');
            }
            if (!Schema::hasColumn('registrations', 'checkin_at')) {
                $table->timestamp('checkin_at')->nullable()->after('checkin_status');
            }
            if (!Schema::hasColumn('registrations', 'file_documents')) {
                $table->text('file_documents')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('registrations', 'verification_notes')) {
                $table->text('verification_notes')->nullable()->after('file_documents');
            }
        });

        // 2. Create jury_scores table
        if (!Schema::hasTable('jury_scores')) {
            Schema::create('jury_scores', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('registration_id');
                $table->string('competition_id', 50);
                $table->string('jury_name', 100)->default('Dewan Juri');
                $table->text('criteria_scores')->nullable(); // JSON rubrik skor
                $table->decimal('total_score', 6, 2)->default(0.00);
                $table->string('performance_duration', 50)->nullable(); // Durasi panggung (e.g. 06:45)
                $table->boolean('is_locked')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('registration_id')->references('id')->on('registrations')->onDelete('cascade');
                $table->index(['competition_id', 'total_score']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jury_scores');

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['barcode', 'checkin_status', 'checkin_at', 'file_documents', 'verification_notes']);
        });
    }
};
