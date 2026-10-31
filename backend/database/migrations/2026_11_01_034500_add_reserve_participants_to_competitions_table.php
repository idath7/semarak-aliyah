<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('competitions', 'reserve_participants')) {
            Schema::table('competitions', function (Blueprint $table) {
                $table->integer('reserve_participants')->nullable()->default(0)->after('min_participants');
            });
        }

        // Kalibrasi data eksisting berdasarkan aturan juknis tiap cabang
        // Volleyball Putra: 6 inti + 4 cadangan
        DB::table('competitions')->where('id', 'volleyball-putra')->update([
            'min_participants' => 6,
            'reserve_participants' => 4,
            'max_participants' => 10,
        ]);

        // Volleyball Putri: 6 inti + 4 cadangan
        DB::table('competitions')->where('id', 'volleyball-putri')->update([
            'min_participants' => 6,
            'reserve_participants' => 4,
            'max_participants' => 10,
        ]);

        // Mobile Legends: 5 inti + 1 cadangan
        DB::table('competitions')->where('id', 'mobile-legends')->update([
            'min_participants' => 5,
            'reserve_participants' => 1,
            'max_participants' => 6,
        ]);

        // Albanjari: 10 peserta (semua tampil, tanpa cadangan = 0)
        DB::table('competitions')->where('id', 'albanjari')->update([
            'min_participants' => 10,
            'reserve_participants' => 0,
            'max_participants' => 10,
        ]);

        // Lomba perorangan: cadangan = 0
        DB::table('competitions')->whereIn('id', ['mtq', 'pidato-3-bahasa', 'singing-competition', 'olimpiade-aswaja'])->update([
            'reserve_participants' => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('competitions', 'reserve_participants')) {
            Schema::table('competitions', function (Blueprint $table) {
                $table->dropColumn('reserve_participants');
            });
        }
    }
};

