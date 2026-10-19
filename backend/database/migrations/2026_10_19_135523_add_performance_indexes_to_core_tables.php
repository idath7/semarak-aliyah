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
        // 1. Indexes on registrations table
        Schema::table('registrations', function (Blueprint $table) {
            $table->index('barcode', 'idx_reg_barcode');
            $table->index('competition_id', 'idx_reg_competition_id');
            $table->index('payment_status', 'idx_reg_payment_status');
            $table->index('checkin_status', 'idx_reg_checkin_status');
            $table->index(['competition_id', 'payment_status'], 'idx_reg_comp_payment');
        });

        // 2. Indexes on tournament_matches table
        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->index('competition_id', 'idx_matches_competition_id');
            $table->index('status', 'idx_matches_status');
            $table->index('team1_registration_id', 'idx_matches_team1_reg_id');
            $table->index('team2_registration_id', 'idx_matches_team2_reg_id');
            $table->index('winner_registration_id', 'idx_matches_winner_reg_id');
        });

        // 3. Composite index on sponsors table
        Schema::table('sponsors', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order'], 'idx_sponsors_active_sort');
        });

        // 4. Composite index on galleries table
        Schema::table('galleries', function (Blueprint $table) {
            $table->index(['is_active', 'row_position', 'sort_order'], 'idx_galleries_active_row_sort');
        });

        // 5. Composite index on faqs table
        Schema::table('faqs', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order'], 'idx_faqs_active_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex('idx_reg_barcode');
            $table->dropIndex('idx_reg_competition_id');
            $table->dropIndex('idx_reg_payment_status');
            $table->dropIndex('idx_reg_checkin_status');
            $table->dropIndex('idx_reg_comp_payment');
        });

        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->dropIndex('idx_matches_competition_id');
            $table->dropIndex('idx_matches_status');
            $table->dropIndex('idx_matches_team1_reg_id');
            $table->dropIndex('idx_matches_team2_reg_id');
            $table->dropIndex('idx_matches_winner_reg_id');
        });

        Schema::table('sponsors', function (Blueprint $table) {
            $table->dropIndex('idx_sponsors_active_sort');
        });

        Schema::table('galleries', function (Blueprint $table) {
            $table->dropIndex('idx_galleries_active_row_sort');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropIndex('idx_faqs_active_sort');
        });
    }
};
