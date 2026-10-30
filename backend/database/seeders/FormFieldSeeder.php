<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Competition;

class FormFieldSeeder extends Seeder
{
    /**
     * 7 Standar Wajib Field Identitas Sekolah & Pembina untuk seluruh cabang lomba:
     * 1. Nama Sekolah / Madrasah (text, required: true)
     * 2. Kecamatan Sekolah (select, 31 Kec Jember + Luar Jember, required: true)
     * 3. NPSN (opsional) (text, required: false)
     * 4. Alamat Sekolah / Madrasah (textarea, required: true)
     * 5. Nama Pendamping (text, required: true)
     * 6. Nomor WA / Kontak Pendamping (number/hanya angka, required: true)
     * 7. Email Pendamping (email, required: true)
     */
    public static function getMandatoryIdentityFields(): array
    {
        $kecamatanList = [
            'Ajung', 'Ambulu', 'Arjasa', 'Balung', 'Bangsalsari', 'Gumukmas',
            'Jelbuk', 'Jenggawah', 'Jombang', 'Kalisat', 'Kaliwates', 'Kencong',
            'Ledokombo', 'Mayang', 'Mumbulsari', 'Pakusari', 'Panti', 'Patrang',
            'Puger', 'Rambipuji', 'Semboro', 'Silo', 'Sukorambi', 'Sukowono',
            'Sumberbaru', 'Sumberjambe', 'Sumbersari', 'Tanggul', 'Tempurejo',
            'Umbulsari', 'Wuluhan', 'Luar Kabupaten Jember'
        ];

        return [
            [
                'id' => 'cf_school',
                'name' => 'school_name',
                'label' => 'Nama Sekolah / Madrasah',
                'type' => 'text',
                'placeholder' => 'Contoh: MTs Negeri 1 Jember / SMPN 1 Balung',
                'help_text' => 'Nama resmi lembaga sekolah atau madrasah pengirim delegasi',
                'required' => true,
                'options' => [],
                'allowed_extensions' => [],
                'max_size_mb' => null,
                'is_mandatory_identity' => true,
            ],
            [
                'id' => 'cf_district',
                'name' => 'school_district',
                'label' => 'Kecamatan Sekolah',
                'type' => 'select',
                'placeholder' => 'Pilih Kecamatan Asal Sekolah...',
                'help_text' => 'Wilayah kecamatan tempat sekolah / madrasah berada di Kabupaten Jember',
                'required' => true,
                'options' => $kecamatanList,
                'allowed_extensions' => [],
                'max_size_mb' => null,
                'is_mandatory_identity' => true,
            ],
            [
                'id' => 'cf_npsn',
                'name' => 'school_npsn',
                'label' => 'Nomor Pokok Sekolah Nasional (NPSN)',
                'type' => 'text',
                'placeholder' => 'Contoh: 20524589 (opsional jika ada)',
                'help_text' => 'Nomor Pokok Sekolah Nasional resmi (opsional jika belum memiliki)',
                'required' => false,
                'options' => [],
                'allowed_extensions' => [],
                'max_size_mb' => null,
                'is_mandatory_identity' => true,
            ],
            [
                'id' => 'cf_address',
                'name' => 'school_address',
                'label' => 'Alamat Sekolah / Madrasah',
                'type' => 'textarea',
                'placeholder' => 'Contoh: Jl. Raya Puger No. 20, Balung Kulon, Kec. Balung',
                'help_text' => 'Alamat lengkap jalan, desa/kelurahan, dan nomor bangunan sekolah/madrasah',
                'required' => true,
                'options' => [],
                'allowed_extensions' => [],
                'max_size_mb' => null,
                'is_mandatory_identity' => true,
            ],
            [
                'id' => 'cf_mentor',
                'name' => 'mentor_name',
                'label' => 'Nama Pendamping',
                'type' => 'text',
                'placeholder' => 'Contoh: Ustadz Ahmad Fauzi, S.Pd',
                'help_text' => 'Nama lengkap guru atau official pembina pendamping peserta',
                'required' => true,
                'options' => [],
                'allowed_extensions' => [],
                'max_size_mb' => null,
                'is_mandatory_identity' => true,
            ],
            [
                'id' => 'cf_wa',
                'name' => 'mentor_wa',
                'label' => 'Nomor WA / Kontak Pendamping',
                'type' => 'number',
                'placeholder' => 'Contoh: 081234567890',
                'help_text' => 'Nomor WhatsApp aktif untuk koordinasi teknis dan TM (hanya angka/nomor)',
                'required' => true,
                'options' => [],
                'allowed_extensions' => [],
                'max_size_mb' => null,
                'is_mandatory_identity' => true,
            ],
            [
                'id' => 'cf_email',
                'name' => 'mentor_email',
                'label' => 'Email Pendamping',
                'type' => 'email',
                'placeholder' => 'Contoh: pendamping@gmail.com / official@sekolah.sch.id',
                'help_text' => 'Alamat email aktif untuk tanda terima dan surat pengantar lomba',
                'required' => true,
                'options' => [],
                'allowed_extensions' => [],
                'max_size_mb' => null,
                'is_mandatory_identity' => true,
            ],
        ];
    }

    public static function makeParticipantRepeater(string $id, string $name, string $label, int $min, int $max, ?int $main = null, ?int $reserve = null): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'label' => $label,
            'type' => 'participant_repeater',
            'placeholder' => '',
            'help_text' => "Lengkapi data siswa delegasi ({$label})",
            'required' => true,
            'min_items' => $min,
            'max_items' => $max,
            'main_members' => $main ?? $max,
            'reserve_members' => $reserve ?? 0,
            'options' => [],
            'allowed_extensions' => [],
            'max_size_mb' => null,
            'sub_fields' => [
                'name' => ['active' => true, 'required' => true, 'label' => 'Nama Lengkap Siswa'],
                'gender' => ['active' => true, 'required' => true, 'label' => 'Jenis Kelamin (L/P)'],
                'birth_date' => ['active' => true, 'required' => false, 'label' => 'Tanggal Lahir Siswa'],
                'grade' => ['active' => true, 'required' => true, 'label' => 'Kelas / Tingkat'],
                'nisn' => ['active' => true, 'required' => true, 'label' => 'Nomor Induk Siswa Nasional (NISN)'],
                'custom_id' => ['active' => false, 'required' => false, 'label' => 'ID Siswa / No. Identitas (Teks)'],
                'wa_number' => ['active' => false, 'required' => false, 'label' => 'Nomor WhatsApp Siswa (Angka)'],
                'captain_option' => ['active' => false, 'required' => false, 'label' => 'Tandai Kapten Tim & Nomor WA Khusus Kapten'],
                'photo_formal' => ['active' => true, 'required' => false, 'label' => 'Pas Foto Formal (3x4)'],
                'photo_freestyle' => ['active' => false, 'required' => false, 'label' => 'Upload Foto Pose Gaya Bebas (Bisa >1 Foto)', 'multiple' => true],
                'custom_columns' => [],
            ],
        ];
    }

    public static function getPresets(): array
    {
        $mandatory = self::getMandatoryIdentityFields();

        // 1. Template 1 Siswa (Individu / Tunggal)
        $t1 = array_merge($mandatory, [
            self::makeParticipantRepeater('cf_peserta_1', 'biodata_peserta', 'Biodata Peserta (1 Siswa)', 1, 1, 1, 0),
            [
                'id' => 'cf_scan_kts',
                'name' => 'scan_kartu_pelajar',
                'label' => 'Scan / Foto Kartu Tanda Siswa (KTS)',
                'type' => 'file',
                'placeholder' => '',
                'help_text' => 'Unggah scan atau foto Kartu Pelajar / Surat Keterangan Siswa Aktif (PDF/JPG/PNG, maks 5MB)',
                'required' => true,
                'options' => [],
                'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
                'max_size_mb' => 5,
                'max_files' => 1,
            ],
        ]);

        // 2. Template 2 Siswa (Tim / Duo)
        $t2 = array_merge($mandatory, [
            [
                'id' => 'cf_nama_tim',
                'name' => 'nama_tim',
                'label' => 'Nama Tim / Duo',
                'type' => 'text',
                'placeholder' => 'Contoh: Tim Al-Fath Duo',
                'help_text' => 'Nama resmi tim delegasi',
                'required' => true,
                'options' => [],
                'allowed_extensions' => [],
                'max_size_mb' => null,
            ],
            self::makeParticipantRepeater('cf_peserta_2', 'biodata_tim', 'Biodata Anggota Tim (2 Siswa)', 2, 2, 2, 0),
            [
                'id' => 'cf_scan_kts',
                'name' => 'scan_kartu_pelajar',
                'label' => 'Scan / Foto Kartu Tanda Siswa (KTS)',
                'type' => 'file',
                'placeholder' => '',
                'help_text' => 'Unggah scan atau foto Kartu Pelajar peserta tim (PDF/JPG/PNG, maks 5MB)',
                'required' => true,
                'options' => [],
                'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
                'max_size_mb' => 5,
                'max_files' => 1,
            ],
        ]);

        // 3. Template 3 Siswa (Regu / Trio)
        $t3 = array_merge($mandatory, [
            [
                'id' => 'cf_nama_tim',
                'name' => 'nama_regu',
                'label' => 'Nama Regu / Tim',
                'type' => 'text',
                'placeholder' => 'Contoh: Regu Juara MTsN 1',
                'help_text' => 'Nama regu delegasi yang akan dipanggil saat perlombaan',
                'required' => true,
                'options' => [],
                'allowed_extensions' => [],
                'max_size_mb' => null,
            ],
            self::makeParticipantRepeater('cf_peserta_3', 'biodata_regu', 'Biodata Anggota Regu (3 Siswa)', 3, 3, 3, 0),
            [
                'id' => 'cf_scan_kts',
                'name' => 'scan_kartu_pelajar',
                'label' => 'Scan / Foto Kartu Tanda Siswa (KTS)',
                'type' => 'file',
                'placeholder' => '',
                'help_text' => 'Unggah scan atau foto Kartu Pelajar peserta regu (PDF/JPG/PNG, maks 5MB)',
                'required' => true,
                'options' => [],
                'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
                'max_size_mb' => 5,
                'max_files' => 1,
            ],
        ]);

        // 4. Template 4 Siswa (Tim 4 Anggota)
        $t4 = array_merge($mandatory, [
            [
                'id' => 'cf_nama_tim',
                'name' => 'nama_tim',
                'label' => 'Nama Tim',
                'type' => 'text',
                'placeholder' => 'Contoh: Tim Squad Empat',
                'help_text' => 'Nama resmi tim delegasi',
                'required' => true,
                'options' => [],
                'allowed_extensions' => [],
                'max_size_mb' => null,
            ],
            self::makeParticipantRepeater('cf_peserta_4', 'biodata_tim', 'Biodata Anggota Tim (4 Siswa)', 4, 4, 4, 0),
            [
                'id' => 'cf_scan_kts',
                'name' => 'scan_kartu_pelajar',
                'label' => 'Scan / Foto Kartu Tanda Siswa (KTS)',
                'type' => 'file',
                'placeholder' => '',
                'help_text' => 'Unggah scan atau foto Kartu Pelajar anggota tim (PDF/JPG/PNG, maks 5MB)',
                'required' => true,
                'options' => [],
                'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
                'max_size_mb' => 5,
                'max_files' => 1,
            ],
        ]);

        // 5. Template 5 Siswa (Tim 5 Anggota)
        $t5 = array_merge($mandatory, [
            [
                'id' => 'cf_nama_tim',
                'name' => 'nama_tim',
                'label' => 'Nama Tim',
                'type' => 'text',
                'placeholder' => 'Contoh: Tim Sinergi Lima',
                'help_text' => 'Nama resmi tim delegasi',
                'required' => true,
                'options' => [],
                'allowed_extensions' => [],
                'max_size_mb' => null,
            ],
            self::makeParticipantRepeater('cf_peserta_5', 'biodata_tim', 'Biodata Anggota Tim (5 Siswa)', 5, 5, 5, 0),
            [
                'id' => 'cf_scan_kts',
                'name' => 'scan_kartu_pelajar',
                'label' => 'Scan / Foto Kartu Tanda Siswa (KTS)',
                'type' => 'file',
                'placeholder' => '',
                'help_text' => 'Unggah berkas scan kartu tanda siswa seluruh anggota tim (PDF/JPG/PNG, maks 5MB)',
                'required' => true,
                'options' => [],
                'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
                'max_size_mb' => 5,
                'max_files' => 1,
            ],
        ]);

        // 6. Template 6 Siswa (5 Inti + 1 Cadangan)
        $t6 = array_merge($mandatory, [
            [
                'id' => 'cf_nama_tim',
                'name' => 'nama_tim',
                'label' => 'Nama Tim / Squad',
                'type' => 'text',
                'placeholder' => 'Contoh: Tim Garuda Squad',
                'help_text' => 'Nama tim resmi yang tercantum pada bagan turnamen',
                'required' => true,
                'options' => [],
                'allowed_extensions' => [],
                'max_size_mb' => null,
            ],
            self::makeParticipantRepeater('cf_peserta_6', 'biodata_tim', 'Biodata Skuad Tim (6 Siswa: 5 Inti + 1 Cadangan)', 5, 6, 5, 1),
            [
                'id' => 'cf_scan_kts',
                'name' => 'scan_kartu_pelajar',
                'label' => 'Scan / Foto Kartu Tanda Siswa (KTS)',
                'type' => 'file',
                'placeholder' => '',
                'help_text' => 'Unggah berkas foto kartu pelajar atau surat rekomendasi kepala madrasah (PDF/JPG/PNG, maks 5MB)',
                'required' => true,
                'options' => [],
                'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
                'max_size_mb' => 5,
                'max_files' => 1,
            ],
        ]);

        // 7. Template 10 Siswa (Grup / Tim 10 Siswa)
        $t10 = array_merge($mandatory, [
            [
                'id' => 'cf_nama_tim',
                'name' => 'nama_grup',
                'label' => 'Nama Grup / Tim',
                'type' => 'text',
                'placeholder' => 'Contoh: Grup Syauqul Qolbi',
                'help_text' => 'Nama resmi grup atau personil yang akan tampil',
                'required' => true,
                'options' => [],
                'allowed_extensions' => [],
                'max_size_mb' => null,
            ],
            self::makeParticipantRepeater('cf_peserta_10', 'biodata_grup', 'Biodata Personil Tim (Hingga 10 Siswa)', 6, 10, 6, 4),
            [
                'id' => 'cf_scan_kts',
                'name' => 'scan_kts_kolektif',
                'label' => 'Scan Kolektif Berkas / Rekomendasi Siswa (PDF)',
                'type' => 'file',
                'placeholder' => '',
                'help_text' => 'Unggah surat rekomendasi delegasi resmi atau kumpulan KTS personil dalam format PDF (maks 10MB)',
                'required' => true,
                'options' => [],
                'allowed_extensions' => ['pdf'],
                'max_size_mb' => 10,
                'max_files' => 1,
            ],
        ]);

        // 8. Template 12 Siswa (Roster Skuad 12 Siswa)
        $t12 = array_merge($mandatory, [
            [
                'id' => 'cf_nama_tim',
                'name' => 'nama_tim',
                'label' => 'Nama Tim / Skuad',
                'type' => 'text',
                'placeholder' => 'Contoh: Tim Juara MTsN 1',
                'help_text' => 'Nama resmi tim yang akan tercantum pada bagan turnamen',
                'required' => true,
                'options' => [],
                'allowed_extensions' => [],
                'max_size_mb' => null,
            ],
            self::makeParticipantRepeater('cf_peserta_12', 'roster_tim', 'Roster Skuad Tim (Hingga 12 Siswa: Inti & Cadangan)', 5, 12, 5, 7),
            [
                'id' => 'cf_scan_kts',
                'name' => 'scan_kts_kolektif',
                'label' => 'Scan Kolektif Kartu Pelajar Seluruh Pemain (PDF)',
                'type' => 'file',
                'placeholder' => '',
                'help_text' => 'Gabungkan foto kartu pelajar seluruh pemain dalam 1 berkas PDF resmi (maks 10MB)',
                'required' => true,
                'options' => [],
                'allowed_extensions' => ['pdf'],
                'max_size_mb' => 10,
                'max_files' => 1,
            ],
        ]);

        // 9. Template Multi Siswa (Jumlah Bebas / Dinamis)
        $t_dinamis = array_merge($mandatory, [
            self::makeParticipantRepeater('cf_peserta_dinamis', 'biodata_peserta', 'Biodata Siswa Delegasi (Jumlah Fleksibel)', 1, 20, 1, 0),
            [
                'id' => 'cf_scan_kts',
                'name' => 'scan_kartu_pelajar',
                'label' => 'Scan / Foto Kartu Tanda Siswa (KTS)',
                'type' => 'file',
                'placeholder' => '',
                'help_text' => 'Unggah scan atau foto Kartu Pelajar / Surat Keterangan Siswa Aktif (PDF/JPG/PNG, maks 5MB)',
                'required' => true,
                'options' => [],
                'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
                'max_size_mb' => 5,
                'max_files' => 1,
            ],
        ]);

        return [
            // Standard Generic Templates
            'template_1_siswa' => $t1,
            'template_2_siswa' => $t2,
            'template_3_siswa' => $t3,
            'template_4_siswa' => $t4,
            'template_5_siswa' => $t5,
            'template_6_siswa' => $t6,
            'template_10_siswa' => $t10,
            'template_12_siswa' => $t12,
            'template_dinamis' => $t_dinamis,

            // Backward-compatible Aliases
            'akademik' => $t1,
            'tahfidz' => $t1,
            'seni' => $t10,
            'esport' => $t6,
            'olahraga' => $t12,
            'umum' => $t_dinamis,
        ];
    }

    public function run(): void
    {
        $presets = self::getPresets();

        // 1. MIPA & IPS -> akademik
        Competition::whereIn('id', ['mipa', 'ips'])->update([
            'custom_fields' => $presets['akademik']
        ]);

        // 2. Albanjari, Singer, Storytelling -> seni
        Competition::whereIn('id', ['albanjari', 'singer', 'storytelling'])->update([
            'custom_fields' => $presets['seni']
        ]);

        // 3. MHQ / Tahfidz -> tahfidz
        Competition::where('id', 'like', '%mhq%')->orWhere('id', 'like', '%tahfidz%')->update([
            'custom_fields' => $presets['tahfidz']
        ]);

        // 4. Mobile Legends -> esport
        Competition::where('id', 'mobile-legends')->update([
            'custom_fields' => $presets['esport']
        ]);

        // 5. Volleyball Putra & Putri -> olahraga
        Competition::whereIn('id', ['volleyball-putra', 'volleyball-putri'])->update([
            'custom_fields' => $presets['olahraga']
        ]);

        // 6. Any other competition without custom fields gets 'umum'
        Competition::whereNull('custom_fields')->orWhere('custom_fields', '[]')->update([
            'custom_fields' => $presets['umum']
        ]);
    }
}
