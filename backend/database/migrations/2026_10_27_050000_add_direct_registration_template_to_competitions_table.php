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
        Schema::table('competitions', function (Blueprint $table) {
            if (!Schema::hasColumn('competitions', 'direct_registration_template')) {
                $table->text('direct_registration_template')->nullable()->after('direct_registration_note');
            }
        });

        // Seed default wa_tpl_direct_registration if not present
        $defaultTpl = "Assalamu'alaikum Wr. Wb. Kak {nama_pj},\n\nSaya perwakilan dari {nama_sekolah}, bermaksud mendaftarkan delegasi sekolah kami untuk cabang lomba *{cabang_lomba}* pada agenda *{nama_event}*.\n\n• Asal Lembaga: {nama_sekolah}\n• Cabang Lomba: {cabang_lomba}\n• Biaya Pendaftaran: {biaya}\n• Narahubung/Pembina: {nama_pendaftar} ({nomor_wa})\n\nMohon petunjuk teknis pendaftaran langsung, persyaratan berkas, serta ketersediaan kuota delegasi. Terima kasih.\n\nWassalamu'alaikum Wr. Wb.";

        $exists = DB::table('settings')->where('key', 'wa_tpl_direct_registration')->exists();
        if (!$exists) {
            DB::table('settings')->insert([
                'key' => 'wa_tpl_direct_registration',
                'value' => $defaultTpl,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (Schema::hasColumn('competitions', 'direct_registration_template')) {
                $table->dropColumn('direct_registration_template');
            }
        });

        DB::table('settings')->where('key', 'wa_tpl_direct_registration')->delete();
    }
};
