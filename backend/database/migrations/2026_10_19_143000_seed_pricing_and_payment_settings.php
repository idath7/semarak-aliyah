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
            // Paket All-In Komplit Settings
            'package_allin_is_active' => '1',
            'package_allin_name' => 'Paket All-In Komplit',
            'package_allin_price' => '200000',
            'package_allin_original_price' => '400000',
            'package_allin_tag' => 'Paling Hemat • Rekomendasi',
            'package_allin_desc' => 'Pilihan terbaik & paling hemat! Daftarkan kontingen sekolah Anda ke seluruh 8 cabang lomba sekaligus hanya dengan setengah harga.',
            'package_allin_features' => json_encode([
                'Bebas daftarkan kontingen di seluruh 8 cabang lomba',
                'Hemat biaya pendaftaran total hingga Rp 200.000,- (50%)',
                'Prioritas verifikasi berkas cepat & reservasi kuota kontingen',
                'Berhak memperebutkan Piala Bergilir Juara Umum Semarak',
                'E-Sertifikat resmi ber-barcode untuk seluruh siswa & guru pembimbing',
            ], JSON_UNESCAPED_UNICODE),

            // Paket Satuan Settings
            'package_satuan_name' => 'Paket Fleksibel Satuan',
            'package_satuan_desc' => 'Pilihan tepat bagi madrasah/sekolah yang ingin mengirimkan delegasi spesifik pada cabang lomba tertentu.',
            'package_satuan_features' => json_encode([
                'Bebas memilih cabang lomba sesuai minat & bakat siswa',
                'Sudah termasuk kuota maksimal peserta sesuai ketentuan juknis',
                'E-Sertifikat resmi ber-barcode untuk seluruh peserta & guru pembimbing',
                'Fasilitas ID Card resmi dan akses Technical Meeting (Offline & Zoom)',
                'Konsultasi dan panduan teknis langsung via Helpdesk Panitia',
            ], JSON_UNESCAPED_UNICODE),

            // Rekening & Petunjuk Pembayaran
            'payment_bank_name' => 'Bank Syariah Indonesia (BSI)',
            'payment_account_number' => '7124686746',
            'payment_account_holder' => 'MARIATUL AWALIYAH',
            'payment_confirmation_wa' => '0838 5007 0767',
            'payment_instructions' => '<p>Transfer sesuai nominal total tagihan ke rekening resmi panitia. Simpan tangkapan layar (screenshot) atau foto struk bukti pembayaran asli untuk diunggah pada form pendaftaran atau konfirmasikan ke WhatsApp panitia.</p>',
            'payment_cash_enabled' => '1',
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
        DB::table('settings')->whereIn('key', [
            'package_allin_is_active',
            'package_allin_name',
            'package_allin_price',
            'package_allin_original_price',
            'package_allin_tag',
            'package_allin_desc',
            'package_allin_features',
            'package_satuan_name',
            'package_satuan_desc',
            'package_satuan_features',
            'payment_bank_name',
            'payment_account_number',
            'payment_account_holder',
            'payment_confirmation_wa',
            'payment_instructions',
            'payment_cash_enabled',
        ])->delete();
    }
};
