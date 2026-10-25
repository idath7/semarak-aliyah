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
            [
                'id' => 'cf_allin_code',
                'name' => 'allin_code',
                'label' => 'Kode Paket Komplit (All-In)',
                'type' => 'text',
                'placeholder' => 'Contoh: SA26-XXXXXX (Otomatis jika sekolah terverifikasi All-In)',
                'help_text' => 'Masukkan kode paket jika sekolah Anda mendaftar Paket Komplit agar pendaftaran cabang mandiri ini bebas biaya (Rp 0).',
                'required' => false,
                'options' => [],
                'allowed_extensions' => [],
                'max_size_mb' => null,
                'is_mandatory_identity' => true,
            ],
        ];
    }

    public static function getPresets(): array
    {
        $mandatory = self::getMandatoryIdentityFields();

        return [
            'akademik' => array_merge($mandatory, [
                [
                    'id' => 'cf_participants',
                    'name' => 'participants_names',
                    'label' => 'Nama Siswa Peserta & Kelas',
                    'type' => 'textarea',
                    'placeholder' => "1. Ahmad Fauzi (Kelas 9A)\n2. Siti Nurhaliza (Kelas 8B)",
                    'help_text' => 'Tuliskan nama lengkap peserta delegasi dan tingkat kelas',
                    'required' => true,
                    'options' => [],
                    'allowed_extensions' => [],
                    'max_size_mb' => null,
                ],
                [
                    'id' => 'cf_nisn',
                    'name' => 'nisn_peserta',
                    'label' => 'Nomor Induk Siswa Nasional (NISN)',
                    'type' => 'text',
                    'placeholder' => 'Contoh: 0081234567',
                    'help_text' => 'NISN resmi peserta delegasi terdaftar di Kemendikbud/Kemenag',
                    'required' => true,
                    'options' => [],
                    'allowed_extensions' => [],
                    'max_size_mb' => null,
                ],
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
                ],
            ]),

            'tahfidz' => array_merge($mandatory, [
                [
                    'id' => 'cf_participants',
                    'name' => 'participants_names',
                    'label' => 'Nama Lengkap Santri / Peserta',
                    'type' => 'text',
                    'placeholder' => 'Contoh: Muhammad Rayhan Al-Fath',
                    'help_text' => 'Nama lengkap peserta sesuai akta / KTS',
                    'required' => true,
                    'options' => [],
                    'allowed_extensions' => [],
                    'max_size_mb' => null,
                ],
                [
                    'id' => 'cf_juz',
                    'name' => 'juz_diujikan',
                    'label' => 'Golongan Juz yang Diujikan',
                    'type' => 'radio',
                    'placeholder' => '',
                    'help_text' => 'Pilih paket golongan hafalan Al-Qur\'an yang akan diujikan',
                    'required' => true,
                    'options' => ['Juz 1 s.d. 5', 'Juz 26 s.d. 30'],
                    'allowed_extensions' => [],
                    'max_size_mb' => null,
                ],
                [
                    'id' => 'cf_surat_tashih',
                    'name' => 'surat_tashih_hafalan',
                    'label' => 'Surat Keterangan / Rekomendasi Tahfidz',
                    'type' => 'file',
                    'placeholder' => '',
                    'help_text' => 'Surat rekomendasi dari guru tahfidz/madrasah asal yang menyatakan capaian juz (PDF/JPG)',
                    'required' => true,
                    'options' => [],
                    'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
                    'max_size_mb' => 5,
                ],
                [
                    'id' => 'cf_link_murottal',
                    'name' => 'link_murottal_drive',
                    'label' => 'Link Rekaman Murottal (Google Drive / YouTube)',
                    'type' => 'url',
                    'placeholder' => 'https://drive.google.com/...',
                    'help_text' => 'Tautan video/audio rekaman bacaan murottal berdurasi 1-2 menit (Pastikan link diset publik)',
                    'required' => false,
                    'options' => [],
                    'allowed_extensions' => [],
                    'max_size_mb' => null,
                ],
            ]),

            'seni' => array_merge($mandatory, [
                [
                    'id' => 'cf_nama_grup',
                    'name' => 'nama_grup_atau_panggung',
                    'label' => 'Nama Grup / Nama Panggung',
                    'type' => 'text',
                    'placeholder' => 'Contoh: Syauqul Qolbi / Al-Fath Nada',
                    'help_text' => 'Nama grup musik atau panggung yang akan dipanggil saat tampil',
                    'required' => true,
                    'options' => [],
                    'allowed_extensions' => [],
                    'max_size_mb' => null,
                ],
                [
                    'id' => 'cf_participants',
                    'name' => 'participants_names',
                    'label' => 'Daftar Nama Anggota Kontingen & Vokal',
                    'type' => 'textarea',
                    'placeholder' => "1. Vokal Utama: Rizky\n2. Backing Vokal: Hasan, Ali\n3. Pemain Perkusi: Fajar, Dimas...",
                    'help_text' => 'Sebutkan nama lengkap seluruh personil grup yang akan tampil',
                    'required' => true,
                    'options' => [],
                    'allowed_extensions' => [],
                    'max_size_mb' => null,
                ],
                [
                    'id' => 'cf_judul_lagu',
                    'name' => 'judul_lagu_atau_karya',
                    'label' => 'Judul Lagu / Naskah yang Ditampilkan',
                    'type' => 'text',
                    'placeholder' => 'Contoh: Ya Habibal Qolbi & Jingle Semarak',
                    'help_text' => 'Judul karya, lagu wajib/pilihan yang akan dibawakan di panggung',
                    'required' => true,
                    'options' => [],
                    'allowed_extensions' => [],
                    'max_size_mb' => null,
                ],
                [
                    'id' => 'cf_file_minus_one',
                    'name' => 'file_minus_one_atau_naskah',
                    'label' => 'Unggah File Minus One / Naskah Karya (Jika Ada)',
                    'type' => 'file',
                    'placeholder' => '',
                    'help_text' => 'File instrumen audio (MP3/WAV) atau dokumen naskah (PDF/DOCX, maks 10MB)',
                    'required' => false,
                    'options' => [],
                    'allowed_extensions' => ['mp3', 'wav', 'pdf', 'docx'],
                    'max_size_mb' => 10,
                ],
            ]),

            'esport' => array_merge($mandatory, [
                [
                    'id' => 'cf_nama_squad',
                    'name' => 'nama_squad_ml',
                    'label' => 'Nama Squad / Tim Mobile Legends',
                    'type' => 'text',
                    'placeholder' => 'Contoh: Wahas Legion',
                    'help_text' => 'Nama resmi squad turnamen Mobile Legends',
                    'required' => true,
                    'options' => [],
                    'allowed_extensions' => [],
                    'max_size_mb' => null,
                ],
                [
                    'id' => 'cf_id_server',
                    'name' => 'id_server_kapten',
                    'label' => 'User ID & Server ID Kapten',
                    'type' => 'text',
                    'placeholder' => 'Contoh: 12345678 (2045)',
                    'help_text' => 'ID Mobile Legends kapten tim beserta zona server di dalam kurung',
                    'required' => true,
                    'options' => [],
                    'allowed_extensions' => [],
                    'max_size_mb' => null,
                ],
                [
                    'id' => 'cf_nickname',
                    'name' => 'nickname_kapten',
                    'label' => 'Nickname In-Game Kapten',
                    'type' => 'text',
                    'placeholder' => 'Contoh: Wahas•Aero',
                    'help_text' => 'Nickname akun kapten tim persis seperti di dalam game',
                    'required' => true,
                    'options' => [],
                    'allowed_extensions' => [],
                    'max_size_mb' => null,
                ],
                [
                    'id' => 'cf_participants',
                    'name' => 'participants_names',
                    'label' => 'Roster Anggota Tim (Nickname & ID Game)',
                    'type' => 'textarea',
                    'placeholder' => "1. Nickname (ID: 111111 [2222])\n2. Nickname (ID: 333333 [4444])\n3. Nickname (ID: 555555 [6666])\n4. Nickname (ID: 777777 [8888])\n5. Nickname (ID: 999999 [0000])\nCadangan: Nickname (ID: 121212 [3434])",
                    'help_text' => 'Tuliskan nama in-game dan ID untuk 5 pemain inti + 1 cadangan',
                    'required' => true,
                    'options' => [],
                    'allowed_extensions' => [],
                    'max_size_mb' => null,
                ],
                [
                    'id' => 'cf_screenshot_profil',
                    'name' => 'screenshot_profil_kapten',
                    'label' => 'Screenshot Profil Akun ML Kapten',
                    'type' => 'file',
                    'placeholder' => '',
                    'help_text' => 'Unggah tangkapan layar (screenshot) profil game kapten menampilkan ID dan Statistik (JPG/PNG)',
                    'required' => true,
                    'options' => [],
                    'allowed_extensions' => ['jpg', 'jpeg', 'png'],
                    'max_size_mb' => 5,
                ],
            ]),

            'olahraga' => array_merge($mandatory, [
                [
                    'id' => 'cf_nama_tim',
                    'name' => 'nama_tim_voli',
                    'label' => 'Nama Tim Bola Voli',
                    'type' => 'text',
                    'placeholder' => 'Contoh: Tim Voli Garuda MTsN 1',
                    'help_text' => 'Nama tim resmi yang akan tercantum pada bagan turnamen',
                    'required' => true,
                    'options' => [],
                    'allowed_extensions' => [],
                    'max_size_mb' => null,
                ],
                [
                    'id' => 'cf_participants',
                    'name' => 'participants_names',
                    'label' => 'Daftar Pemain (Nomor Punggung & Posisi)',
                    'type' => 'textarea',
                    'placeholder' => "1. Rizky (No. 4 - Tosser/Setter)\n2. Bayu (No. 7 - Outside Hitter)\n3. Dimas (No. 10 - Middle Blocker)\n4. Fajar (No. 2 - Libero)\n...",
                    'help_text' => 'Sebutkan nomor punggung jersey dan posisi utama pemain',
                    'required' => true,
                    'options' => [],
                    'allowed_extensions' => [],
                    'max_size_mb' => null,
                ],
                [
                    'id' => 'cf_scan_kts',
                    'name' => 'scan_kts_kolektif',
                    'label' => 'Scan Kolektif Kartu Pelajar Seluruh Pemain (PDF)',
                    'type' => 'file',
                    'placeholder' => '',
                    'help_text' => 'Gabungkan foto kartu pelajar atau surat rekomendasi kepala sekolah dalam 1 berkas PDF (maks 10MB)',
                    'required' => true,
                    'options' => [],
                    'allowed_extensions' => ['pdf'],
                    'max_size_mb' => 10,
                ],
            ]),

            'umum' => array_merge($mandatory, [
                [
                    'id' => 'cf_participants',
                    'name' => 'participants_names',
                    'label' => 'Nama Peserta Delegasi',
                    'type' => 'textarea',
                    'placeholder' => 'Tuliskan nama lengkap peserta',
                    'help_text' => 'Nama peserta lomba',
                    'required' => true,
                    'options' => [],
                    'allowed_extensions' => [],
                    'max_size_mb' => null,
                ],
            ])
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
