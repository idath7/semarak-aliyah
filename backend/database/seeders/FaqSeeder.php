<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faq;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'id' => 1,
                'question' => 'Bagaimana alur pendaftaran kontingen delegasi sekolah/madrasah?',
                'answer' => '<p>Alur pendaftaran Semarak Aliyah XIV sangat mudah dan dapat dilakukan secara mandiri oleh guru pembina atau perwakilan sekolah:</p><ol><li><strong>Buat Akun / Login Pembina</strong> melalui tombol Registrasi di website resmi.</li><li><strong>Pilih Cabang Lomba</strong> atau gunakan opsi <strong>Paket Komplit (All-In)</strong> untuk mengikutsertakan seluruh cabang sekaligus.</li><li><strong>Isi Data Peserta</strong> (nama lengkap, kelas, NISN) dan unggah persyaratan berkas.</li><li><strong>Lakukan Pembayaran</strong> sesuai nominal tagihan invoice ke rekening resmi panitia, kemudian unggah bukti transfer.</li><li>Panitia akan memverifikasi dalam waktu 1x24 jam dan kartu peserta (ID Card) resmi ber-barcode siap diunduh.</li></ol>',
                'category' => 'Pendaftaran & Akun',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'id' => 2,
                'question' => 'Bagaimana sistem pembayaran dan konfirmasi biaya pendaftaran?',
                'answer' => '<p>Pembayaran dapat ditransfer melalui rekening resmi <strong>Bank Syariah Indonesia (BSI)</strong>:</p><ul><li>No. Rekening : <strong>7123456789</strong></li><li>Atas Nama : <strong>Semarak Aliyah</strong></li></ul><p>Setelah transfer berhasil, bukti pembayaran dapat langsung diunggah melalui formulir pendaftaran online atau dikonfirmasikan via WhatsApp ke tim bendahara panitia.</p>',
                'category' => 'Pembayaran & Biaya',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'id' => 3,
                'question' => 'Apa keuntungan memilih Paket Komplit (All-In) dibanding Paket Satuan?',
                'answer' => '<p>Paket Komplit (All-In) dirancang khusus untuk memfasilitasi sekolah/madrasah yang ingin mengirimkan delegasi di banyak cabang lomba dengan berbagai keuntungan:</p><ul><li><strong>Hemat Biaya Lebih dari 50%</strong> (hanya Rp 200.000 untuk seluruh cabang lomba resmi).</li><li>Mendapatkan <strong>Kode Voucher Khusus</strong> yang membebaskan biaya pendaftaran di semua cabang berikutnya (Rp 0).</li><li><strong>Prioritas Kuota & Verifikasi Instan</strong> oleh panitia.</li><li>Bonus pendampingan administrasi dan merchandise resmi kontingen.</li></ul>',
                'category' => 'Paket & Biaya',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'id' => 4,
                'question' => 'Apakah satu sekolah boleh mengirimkan lebih dari 1 tim pada cabang beregu/turnamen?',
                'answer' => '<p>Tergantung regulasi pada masing-masing cabang lomba:</p><ul><li><strong>Tournament Mobile Legends (MLBB)</strong>: Diperbolehkan mendaftarkan hingga <strong>maksimal 2 Tim</strong> per sekolah/madrasah.</li><li><strong>Bola Voli & Fesban</strong>: Maksimal <strong>1 Tim</strong> per lembaga untuk memberikan pemerataan kuota antar sekolah.</li><li><strong>Olimpiade (MIPA & IPS)</strong>: Maksimal <strong>3 peserta</strong> per sekolah.</li></ul>',
                'category' => 'Ketentuan Lomba',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'id' => 5,
                'question' => 'Kapan dan di mana Technical Meeting (TM) dilaksanakan?',
                'answer' => '<p>Technical Meeting (TM) dilaksanakan secara <strong>offline tatap muka</strong> di Kampus <strong>MA Unggulan Wahid Hasyim Balung</strong> sesuai jadwal masing-masing cabang lomba (dimulai pukul 08:00 WIB untuk cabang Voli & Olimpiade, dan pukul 14:00 WIB untuk Turnamen Mobile Legends).</p><p>Bagi peserta dari luar kota atau yang berhalangan hadir, panitia juga menyediakan tautan pendampingan secara online.</p>',
                'category' => 'Pelaksanaan & TM',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'id' => 6,
                'question' => 'Apa saja fasilitas dan penghargaan yang didapatkan oleh peserta & pembimbing?',
                'answer' => '<p>Setiap kontingen dan peserta berhak mendapatkan fasilitas resmi:</p><ul><li><strong>E-Sertifikat resmi ber-barcode</strong> untuk seluruh peserta & guru pendamping.</li><li><strong>ID Card resmi kontingen</strong> dan konsumsi saat hari perlombaan.</li><li><strong>Trophy / Piala eksklusif, Medali Juara, Piagam Resmi</strong>, uang pembinaan jutaan rupiah, serta <u>beasiswa pendidikan</u> bagi para pemenang.</li></ul>',
                'category' => 'Umum',
                'sort_order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($faqs as $item) {
            Faq::updateOrCreate(['id' => $item['id']], $item);
        }
    }
}
