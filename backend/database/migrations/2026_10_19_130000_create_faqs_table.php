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
        // 1. FAQs Table
        if (!Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table) {
                $table->id();
                $table->string('question', 500);
                $table->longText('answer');
                $table->string('category', 100)->nullable()->default('Umum');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Seed Default FAQ Section Settings
        $defaultSettings = [
            'faq_section_tag' => 'Bantuan Informasi',
            'faq_section_title' => 'Pertanyaan Sering Diajukan (FAQ)',
            'faq_section_desc' => 'Jawaban atas pertanyaan umum seputar pendaftaran dan pelaksanaan Semarak Aliyah XIV 2026.',
            'faq_is_active' => '1',
        ];

        foreach ($defaultSettings as $key => $val) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $val, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // 3. Seed Initial FAQs if empty
        if (DB::table('faqs')->count() === 0) {
            $initialFaqs = [
                [
                    'question' => 'Siapa saja yang berhak menjadi peserta Semarak Aliyah XIV 2026?',
                    'answer' => '<p>Seluruh siswa-siswi aktif tingkat <strong>SMP / MTs sederajat kelas 7, 8, dan 9 se-Kabupaten Jember</strong> baik dari sekolah negeri maupun swasta, dibuktikan dengan <u>Kartu Tanda Siswa (KTS)</u> atau surat tugas/delegasi resmi dari kepala sekolah/madrasah.</p>',
                    'category' => 'Pendaftaran',
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'question' => 'Bagaimana sistem pembayaran dan konfirmasi biaya pendaftaran?',
                    'answer' => '<p>Pembayaran dapat ditransfer melalui rekening resmi <strong>Bank Syariah Indonesia (BSI)</strong>:</p><ul><li>No. Rekening: <strong>7124686746</strong></li><li>Atas Nama: <strong>MARIATUL AWALIYAH</strong></li></ul><p>Setelah transfer berhasil, bukti pembayaran dapat langsung diunggah melalui formulir pendaftaran online atau dikonfirmasikan via WhatsApp ke tim bendahara panitia.</p>',
                    'category' => 'Pembayaran & Biaya',
                    'sort_order' => 2,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'question' => 'Apakah ada kuota maksimal untuk masing-masing cabang perlombaan?',
                    'answer' => '<p><strong>Ya</strong>, setiap cabang lomba memiliki batas kuota maksimal untuk menjaga kualitas dan kenyamanan kompetisi.</p><p>Pendaftaran akan <em>otomatis ditutup</em> ketika kuota cabang lomba telah terpenuhi. Sekolah yang mendaftar setelahnya akan dialihkan ke status <u>Waiting List (Daftar Tunggu)</u>.</p>',
                    'category' => 'Kompetisi & Juknis',
                    'sort_order' => 3,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'question' => 'Apakah satu sekolah boleh mendaftar lebih dari satu cabang lomba?',
                    'answer' => '<p><strong>Sangat diperbolehkan!</strong> Bahkan panitia menyediakan <strong>Paket All-In Komplit seharga Rp 200.000</strong> (diskon 50% dari total Rp 400.000) bagi sekolah/madrasah yang ingin mengirimkan delegasi terbaiknya di <u>seluruh 8 cabang lomba</u> tanpa batas kuota pendaftaran cabang, sekaligus berhak memperebutkan <em>Piala Bergilir Juara Umum</em>.</p>',
                    'category' => 'Pendaftaran',
                    'sort_order' => 4,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'question' => 'Kapan dan di mana Technical Meeting (TM) dilaksanakan?',
                    'answer' => '<p>Technical Meeting (TM) dilaksanakan secara <strong>offline tatap muka</strong> di Kampus <strong>MA Unggulan Wahid Hasyim Balung</strong> sesuai jadwal masing-masing cabang lomba (dimulai pukul 08:00 WIB untuk cabang Voli &amp; Olimpiade, dan pukul 14:00 WIB untuk Turnamen Mobile Legends).</p><p>Bagi peserta dari luar kota atau yang berhalangan hadir, panitia juga menyediakan tautan pendampingan secara online.</p>',
                    'category' => 'Pelaksanaan & TM',
                    'sort_order' => 5,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'question' => 'Apa saja fasilitas dan penghargaan yang didapatkan oleh peserta & pembimbing?',
                    'answer' => '<p>Setiap kontingen dan peserta berhak mendapatkan fasilitas resmi:</p><ul><li><strong>E-Sertifikat resmi ber-barcode</strong> untuk seluruh peserta &amp; guru pendamping.</li><li><strong>ID Card resmi kontingen</strong> dan konsumsi saat hari perlombaan.</li><li><strong>Trophy / Piala eksklusif, Medali Juara, Piagam Resmi</strong>, uang pembinaan jutaan rupiah, serta <u>beasiswa pendidikan</u> bagi para pemenang.</li></ul>',
                    'category' => 'Umum',
                    'sort_order' => 6,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            DB::table('faqs')->insert($initialFaqs);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
        DB::table('settings')->whereIn('key', [
            'faq_section_tag',
            'faq_section_title',
            'faq_section_desc',
            'faq_is_active',
        ])->delete();
    }
};
