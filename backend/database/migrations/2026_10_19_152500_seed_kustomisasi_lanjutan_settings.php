<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultSettings = [
            // 1. Banner Pengumuman Mengambang (Floating Announcement Ribbon)
            'announcement_banner_active' => '1',
            'announcement_banner_text' => '📢 Pendaftaran Semarak Aliyah XIV 2026 Resmi Dibuka! Raih Piala Bergilir, Medali Kejuaraan, & Beasiswa Pendidikan.',
            'announcement_banner_type' => 'gold', // 'gold', 'primary', 'success', 'danger', 'info'
            'announcement_banner_btn_text' => 'Daftar Kontingen Sekarang',
            'announcement_banner_btn_url' => '/#biaya',
            'announcement_banner_position' => 'top', // 'top', 'bottom'
            'announcement_banner_dismissable' => '1',

            // 2. Pop-up Pengumuman Otomatis (Entrance Modal Popup)
            'announcement_popup_active' => '0',
            'announcement_popup_frequency' => 'once_per_session', // 'once_per_session', 'always'
            'announcement_popup_title' => 'Pengumuman Penting: Technical Meeting & Petunjuk Teknis',
            'announcement_popup_badge' => 'Informasi Resmi Panitia',
            'announcement_popup_image' => '/assets/img/pamflet.jpg',
            'announcement_popup_content' => 'Selamat datang di Portal Resmi Semarak Aliyah XIV 2026! Pastikan delegasi sekolah/madrasah Anda telah mengunduh Buku Panduan Juknis resmi dan melengkapi berkas pendaftaran sebelum kuota masing-masing cabang lomba terpenuhi.',
            'announcement_popup_btn_text' => 'Unduh Juknis Lengkap PDF',
            'announcement_popup_btn_url' => '/assets/doc/JUKNIS-SEMARAK-2026.pdf',

            // 3. Tombol Mengambang WhatsApp & Helpdesk Cepat (Floating WA Support)
            'floating_wa_active' => '1',
            'floating_wa_number' => '0838 5007 0767',
            'floating_wa_message' => 'Halo Panitia Semarak Aliyah XIV 2026, saya ingin bertanya seputar pendaftaran kontingen sekolah...',
            'floating_wa_label' => 'Bantuan Panitia',
            'floating_wa_position' => 'bottom-right', // 'bottom-right', 'bottom-left'
            'floating_wa_animation' => 'pulse', // 'pulse', 'bounce', 'none'

            // 4. Countdown Timer & Target Acara
            'countdown_active' => '1',
            'countdown_title' => 'Batas Akhir Pendaftaran & TM Ditutup Dalam:',
            'countdown_target_date' => '2026-10-24T23:59',
            'countdown_ended_text' => 'Pendaftaran Gelombang Utama Resmi Ditutup! Hubungi Panitia untuk Kuota Susulan.',
            'countdown_show_seconds' => '1',

            // 5. Injeksi Kode Kustom (Custom CSS & Scripts)
            'custom_css' => '',
            'custom_head_script' => '',
            'custom_body_script' => '',

            // 6. Efek Visual & Interaksi Web
            'visual_particles_active' => '1',
            'visual_particles_type' => 'glitter', // 'glitter', 'confetti', 'snow'
            'back_to_top_active' => '1',
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
        $keys = [
            'announcement_banner_active',
            'announcement_banner_text',
            'announcement_banner_type',
            'announcement_banner_btn_text',
            'announcement_banner_btn_url',
            'announcement_banner_position',
            'announcement_banner_dismissable',
            'announcement_popup_active',
            'announcement_popup_frequency',
            'announcement_popup_title',
            'announcement_popup_badge',
            'announcement_popup_image',
            'announcement_popup_content',
            'announcement_popup_btn_text',
            'announcement_popup_btn_url',
            'floating_wa_active',
            'floating_wa_number',
            'floating_wa_message',
            'floating_wa_label',
            'floating_wa_position',
            'floating_wa_animation',
            'countdown_active',
            'countdown_title',
            'countdown_target_date',
            'countdown_ended_text',
            'countdown_show_seconds',
            'custom_css',
            'custom_head_script',
            'custom_body_script',
            'visual_particles_active',
            'visual_particles_type',
            'back_to_top_active',
        ];

        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};
