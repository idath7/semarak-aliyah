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
        if (!Schema::hasTable('wa_logs')) {
            Schema::create('wa_logs', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 30)->index();
                $table->string('recipient_name', 150)->nullable();
                $table->string('type', 50)->default('manual')->index(); // 'test', 'new_registration', 'verification_registration', 'verification_payment', 'manual'
                $table->text('message');
                $table->string('status', 30)->default('pending')->index(); // 'success', 'failed', 'pending'
                $table->integer('response_code')->nullable();
                $table->json('response_data')->nullable();
                $table->string('error_message', 500)->nullable();
                $table->timestamps();
            });
        }

        // Seed default OpenWA Gateway Settings
        $defaultSettings = [
            'wa_gateway_enabled' => '0',
            'wa_gateway_provider' => 'openwa',
            'wa_gateway_url' => 'http://localhost:8085',
            'wa_api_key' => '',
            'wa_sender_session' => 'default',
            'wa_endpoint_send' => '/sendText',
            'wa_endpoint_status' => '/check-auth',
            'wa_auth_header_type' => 'api_key', // 'api_key', 'bearer', 'x_api_key'
            'wa_chat_id_format' => 'c_us', // 'c_us' (628xxx@c.us) or 'number' (628xxx)
            'wa_notify_new_registration' => '1',
            'wa_notify_admin_new_registration' => '1',
            'wa_admin_notification_numbers' => '083850070767',
            'wa_notify_verified_registration' => '1',
            'wa_notify_verified_payment' => '1',
            
            // Template: Notifikasi Pendaftaran Baru (ke Koordinator Sekolah)
            'wa_tpl_new_registration' => "Assalamu'alaikum Wr. Wb. Bapak/Ibu {nama_koordinator} ({nama_sekolah}),\n\nTerima kasih, formulir pendaftaran *{nama_event}* telah berhasil kami terima.\n\n📋 *Rincian Pendaftaran:*\n• Kode Registrasi: *{kode_pendaftaran}*\n• Asal Sekolah: {nama_sekolah}\n• Cabang Lomba / Paket: *{cabang_lomba}*\n• Paket: {paket_pendaftaran}\n• Total Biaya: {total_biaya}\n• Status Pendaftaran: ⏳ *{status_pendaftaran}*\n• Status Pembayaran: ⏳ *{status_pembayaran}*\n\nTim verifikator panitia akan segera memeriksa kelengkapan data berkas dan bukti transfer Anda. Pantau perkembangan status pendaftaran Anda melalui tautan resmi kami:\n{url_status}\n\nWassalamu'alaikum Wr. Wb.\n_Panitia Pelaksana Semarak Aliyah XIV 2026_",

            // Template: Notifikasi Pendaftaran Baru (ke Admin Panitia)
            'wa_tpl_admin_notification' => "🔔 *NOTIFIKASI REGISTRASI MASUK*\n\nTelah masuk pendaftaran baru di sistem *{nama_event}*:\n\n• Asal Sekolah: *{nama_sekolah}*\n• Kode: *{kode_pendaftaran}*\n• Cabang / Paket: *{cabang_lomba}*\n• Koordinator: {nama_koordinator} ({mentor_wa})\n• Total Biaya: {total_biaya}\n• Status Bayar: {status_pembayaran}\n\nSilakan periksa dan verifikasi data pendaftar melalui Panel Admin:\n{admin_pendaftar_url}",

            // Template: Notifikasi Verifikasi Berkas & Pendaftaran
            'wa_tpl_verified_registration' => "Assalamu'alaikum Wr. Wb. Bapak/Ibu {nama_koordinator} ({nama_sekolah}),\n\nKabar baik! Data berkas pendaftaran kontingen Anda untuk *{cabang_lomba}* dengan Kode Registrasi *{kode_pendaftaran}* telah *DIVERIFIKASI RESMI* oleh Panitia *{nama_event}*.\n\nStatus Berkas: ✅ *TERVERIFIKASI*\nStatus Pembayaran: *{status_pembayaran}*\n\nSimpan pesan ini sebagai bukti validasi administrasi. Terima kasih atas partisipasi aktif lembaga Anda!\n\nWassalamu'alaikum Wr. Wb.\n_Panitia Pelaksana Semarak Aliyah XIV 2026_",

            // Template: Notifikasi Verifikasi Pembayaran (Lunas)
            'wa_tpl_verified_payment' => "Assalamu'alaikum Wr. Wb. Bapak/Ibu {nama_koordinator} ({nama_sekolah}),\n\nAlhamdulillah, pembayaran pendaftaran kontingen Anda dengan Kode Registrasi *{kode_pendaftaran}* ({cabang_lomba}) telah *DIVERIFIKASI LUNAS* oleh Bendahara *{nama_event}*.\n\nStatus Pembayaran: ✅ *LUNAS / TERVERIFIKASI*\nTotal Diterima: *{total_biaya}*\n\nSelamat mempersiapkan kontingen terbaik sekolah Anda. Informasi jadwal TM & arena lomba dapat diakses melalui portal resmi.\n\nWassalamu'alaikum Wr. Wb.\n_Panitia Pelaksana Semarak Aliyah XIV 2026_",
        ];

        foreach ($defaultSettings as $key => $val) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $val, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_logs');
    }
};
