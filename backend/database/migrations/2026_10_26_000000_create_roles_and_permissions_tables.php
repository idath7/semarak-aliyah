<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel Peran (Roles)
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Slug: super_admin, admin, panitia, kesekretariatan, bendahara, juri, wasit, custom
            $table->string('label'); // Nama Tampil: Super Admin, Administrator, dll.
            $table->string('badge_color')->default('primary'); // danger, primary, info, secondary, success, warning, dark
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // Peran bawaan sistem yang tidak boleh dihapus
            $table->timestamps();
        });

        // 2. Tabel Hak Akses / Izin (Permissions)
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Slug: dashboard.view, registrants.view, dll.
            $table->string('label'); // Nama izin: Melihat Data Pendaftar
            $table->string('group'); // Kategori grup: Dashboard, Pendaftaran, Lomba & TM, Keuangan, Konten, Pengguna, Pengaturan, Operasional
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 3. Tabel Pivot Peran - Izin (Role-Permission Matrix)
        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->primary(['role_id', 'permission_id']);
        });

        // 4. Tambahkan custom_permissions dan role_id pada tabel users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'custom_permissions')) {
                $table->json('custom_permissions')->nullable()->after('role_id');
            }
        });

        // 5. Seed Data Baku Awal (Default Roles & Permissions)
        $this->seedInitialRolesAndPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'custom_permissions')) {
                $table->dropColumn('custom_permissions');
            }
            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropConstrainedForeignId('role_id');
            }
        });

        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }

    /**
     * Seed initial roles, permissions, and assign defaults.
     */
    protected function seedInitialRolesAndPermissions(): void
    {
        $now = now();

        // A. Peran Baku Sistem (System Roles)
        $rolesData = [
            [
                'name' => 'super_admin',
                'label' => 'Super Admin',
                'badge_color' => 'danger',
                'description' => 'Akses penuh tanpa batas ke seluruh konfigurasi sistem, database, hak akses, dan operasional lapangan.',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'admin',
                'label' => 'Administrator',
                'badge_color' => 'primary',
                'description' => 'Akses pengelolaan pendaftaran, data sekolah, cabang lomba, TM, konten web publik, dan manajemen panitia.',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'kesekretariatan',
                'label' => 'Kesekretariatan',
                'badge_color' => 'secondary',
                'description' => 'Verifikasi berkas persyaratan pendaftar, pencetakan ID Card & sertifikat, ekspor data, serta kelola juknis lomba.',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'bendahara',
                'label' => 'Bendahara & Keuangan',
                'badge_color' => 'success',
                'description' => 'Validasi bukti pembayaran pendaftaran lomba, pengelolaan biaya registrasi, paket pembayaran, dan rekap keuangan.',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'panitia',
                'label' => 'Panitia Pelaksana',
                'badge_color' => 'info',
                'description' => 'Panitia umum lapangan, akses monitoring pendaftar, sekolah terdaftar, dan gate check-in kehadiran peserta.',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'juri',
                'label' => 'Dewan Juri',
                'badge_color' => 'warning',
                'description' => 'Akses arena penilaian dewan juri, input nilai kriteria & rubrik skor untuk cabang lomba yang ditugaskan.',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'wasit',
                'label' => 'Wasit / Match Official',
                'badge_color' => 'dark',
                'description' => 'Pengadil pertandingan turnamen, update skor live match, dan pengaturan bagan pertandingan olahraga / e-sport.',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('roles')->insert($rolesData);

        // B. Daftar Hak Akses Baku (Permissions)
        $permissionsData = [
            // 1. Dashboard
            [
                'name' => 'dashboard.view',
                'label' => 'Melihat Dashboard Utama & Statistik',
                'group' => 'Dashboard & Ringkasan',
                'description' => 'Melihat ringkasan metrik pendaftaran, grafik peserta, dan status sistem.',
            ],

            // 2. Pendaftaran & Peserta
            [
                'name' => 'registrants.view',
                'label' => 'Melihat Data Pendaftar Masuk',
                'group' => 'Administrasi Pendaftaran',
                'description' => 'Melihat tabel pendaftar, detail delegasi peserta, berkas, dan kartu pendaftaran.',
            ],
            [
                'name' => 'registrants.verify',
                'label' => 'Verifikasi Berkas & Status Pendaftar',
                'group' => 'Administrasi Pendaftaran',
                'description' => 'Menyetujui, menolak, atau meminta perbaikan berkas persyaratan peserta.',
            ],
            [
                'name' => 'registrants.delete',
                'label' => 'Menghapus Data Pendaftar',
                'group' => 'Administrasi Pendaftaran',
                'description' => 'Menghapus data pendaftaran peserta dari database.',
            ],
            [
                'name' => 'registrants.export',
                'label' => 'Ekspor Data Pendaftar (Excel / CSV)',
                'group' => 'Administrasi Pendaftaran',
                'description' => 'Mengunduh rekapitulasi data pendaftar dalam format spreadsheet Excel/CSV.',
            ],

            // 3. Database Sekolah
            [
                'name' => 'schools.view',
                'label' => 'Melihat Database Sekolah & Sekolah Terdaftar',
                'group' => 'Database Sekolah',
                'description' => 'Melihat daftar master sekolah/madrasah dan sekolah yang telah mendaftarkan kontingen.',
            ],
            [
                'name' => 'schools.manage',
                'label' => 'Mengelola Master Data Sekolah',
                'group' => 'Database Sekolah',
                'description' => 'Menambah, mengubah, menghapus, serta impor/ekspor data master sekolah.',
            ],

            // 4. Manajemen Lomba & TM
            [
                'name' => 'competitions.manage',
                'label' => 'Mengelola Cabang Lomba & Kuota',
                'group' => 'Manajemen Lomba & TM',
                'description' => 'Mengatur nama lomba, kuota tim, biaya dasar, tanggal pelaksanaan, dan PJ lomba.',
            ],
            [
                'name' => 'tm.manage',
                'label' => 'Mengelola Technical Meeting & Pengundian',
                'group' => 'Manajemen Lomba & TM',
                'description' => 'Mengatur agenda TM, tautan zoom/meet, serta pengundian nomor urut peserta.',
            ],
            [
                'name' => 'form_builder.manage',
                'label' => 'Mengonfigurasi Formulir Builder Lomba',
                'group' => 'Manajemen Lomba & TM',
                'description' => 'Menambah, mengubah kolom isian khusus, dan preset formulir pendaftaran dinamis.',
            ],
            [
                'name' => 'juknis.manage',
                'label' => 'Mengelola Buku Panduan Juknis Lomba',
                'group' => 'Manajemen Lomba & TM',
                'description' => 'Mengunggah file PDF dan memperbarui juknis umum maupun spesifik cabang lomba.',
            ],

            // 5. Keuangan & Biaya
            [
                'name' => 'finance.view',
                'label' => 'Melihat Laporan Keuangan & Biaya',
                'group' => 'Keuangan & Biaya',
                'description' => 'Melihat rekap biaya pendaftaran, transaksi pembayaran, dan bukti transfer.',
            ],
            [
                'name' => 'finance.manage',
                'label' => 'Mengelola Tarif Biaya & Paket Registrasi',
                'group' => 'Keuangan & Biaya',
                'description' => 'Mengubah biaya cabang lomba, paket pendaftaran, diskon, dan rekening pembayaran.',
            ],

            // 6. Kelola Web Publik
            [
                'name' => 'content.sponsor',
                'label' => 'Mengelola Mitra & Sponsorship Slider',
                'group' => 'Kelola Web Publik',
                'description' => 'Menambah logo sponsor, mengatur level sponsor, dan urutan tampilan.',
            ],
            [
                'name' => 'content.gallery',
                'label' => 'Mengelola Galeri Dokumentasi Kegiatan',
                'group' => 'Kelola Web Publik',
                'description' => 'Mengunggah foto kegiatan, mengatur slider 2-baris, dan takarir foto.',
            ],
            [
                'name' => 'content.faq',
                'label' => 'Mengelola Tanya Jawab (FAQ)',
                'group' => 'Kelola Web Publik',
                'description' => 'Menambah dan mengubah pertanyaan umum serta jawaban untuk calon pendaftar.',
            ],
            [
                'name' => 'content.customization',
                'label' => 'Kustomisasi Layout & Teks Berjalan (Ticker)',
                'group' => 'Kelola Web Publik',
                'description' => 'Mengatur efek visual web, layout landing page, dan pengumuman teks berjalan.',
            ],

            // 7. Pengguna & Hak Akses
            [
                'name' => 'users.view',
                'label' => 'Melihat Daftar Pengguna & Panitia',
                'group' => 'Manajemen Pengguna & Hak Akses',
                'description' => 'Melihat akun panitia, kontak, NIP, peran, dan status aktif.',
            ],
            [
                'name' => 'users.manage',
                'label' => 'Mengelola Akun Panitia (Tambah/Edit/Sinkron)',
                'group' => 'Manajemen Pengguna & Hak Akses',
                'description' => 'Membuat akun panitia baru, mengubah profil, reset kata sandi, dan sinkron Sipresensi.',
            ],
            [
                'name' => 'roles.manage',
                'label' => 'Mengelola Hak Akses & Matriks Peran',
                'group' => 'Manajemen Pengguna & Hak Akses',
                'description' => 'Mengatur matriks izin untuk setiap peran panitia dan mengonfigurasi peran baru.',
            ],

            // 8. Pengaturan Sistem
            [
                'name' => 'settings.system',
                'label' => 'Mengelola Pengaturan Sistem Lengkap',
                'group' => 'Pengaturan Sistem',
                'description' => 'Akses pengaturan profil sekolah, mode perawatan, SEO OpenGraph, login admin, dan reCAPTCHA.',
            ],
            [
                'name' => 'settings.integrations',
                'label' => 'Mengelola Integrasi Gateway (WA & Sipresensi)',
                'group' => 'Pengaturan Sistem',
                'description' => 'Mengonfigurasi OpenWA Gateway, log pengiriman WhatsApp, dan kredensial API Sipresensi.',
            ],

            // 9. Operasional Lapangan
            [
                'name' => 'operational.gate',
                'label' => 'Akses Gate Check-in & Scan Kehadiran',
                'group' => 'Operasional Lapangan',
                'description' => 'Memindai barcode/QR code peserta di pintu masuk venue kegiatan.',
            ],
            [
                'name' => 'operational.verification',
                'label' => 'Akses Meja Verifikasi Berkas Lapangan',
                'group' => 'Operasional Lapangan',
                'description' => 'Pengecekan berkas fisik langsung pada meja registrasi ulang hari H.',
            ],
            [
                'name' => 'operational.jury',
                'label' => 'Akses Arena Penilaian Dewan Juri',
                'group' => 'Operasional Lapangan',
                'description' => 'Menginput nilai peserta cabang lomba seni, keagamaan, dan akademik.',
            ],
            [
                'name' => 'operational.tournament',
                'label' => 'Akses Arena Wasit & Turnamen Pertandingan',
                'group' => 'Operasional Lapangan',
                'description' => 'Mengatur pertandingan turnamen olahraga / e-sport dan skor babak gugur.',
            ],
            [
                'name' => 'operational.print',
                'label' => 'Akses Studio Cetak ID Card & Sertifikat',
                'group' => 'Operasional Lapangan',
                'description' => 'Mencetak kartu tanda peserta, ID card official kontingen, dan piagam sertifikat.',
            ],
        ];

        foreach ($permissionsData as $perm) {
            $perm['created_at'] = $now;
            $perm['updated_at'] = $now;
            DB::table('permissions')->insert($perm);
        }

        // C. Petakan Hak Akses Awal untuk Setiap Peran Baku
        $allPermissions = DB::table('permissions')->pluck('id', 'name')->toArray();
        $allRoles = DB::table('roles')->pluck('id', 'name')->toArray();

        $rolePermissionMap = [
            // Super Admin: Semua izin
            'super_admin' => array_keys($allPermissions),

            // Administrator: Hampir seluruh izin kecuali integrasi sistem tingkat tinggi
            'admin' => [
                'dashboard.view',
                'registrants.view', 'registrants.verify', 'registrants.delete', 'registrants.export',
                'schools.view', 'schools.manage',
                'competitions.manage', 'tm.manage', 'form_builder.manage', 'juknis.manage',
                'finance.view', 'finance.manage',
                'content.sponsor', 'content.gallery', 'content.faq', 'content.customization',
                'users.view', 'users.manage', 'roles.manage',
                'settings.system',
                'operational.gate', 'operational.verification', 'operational.jury', 'operational.tournament', 'operational.print',
            ],

            // Kesekretariatan: Pendaftaran, berkas, sekolah, juknis, cetak ID Card
            'kesekretariatan' => [
                'dashboard.view',
                'registrants.view', 'registrants.verify', 'registrants.export',
                'schools.view', 'schools.manage',
                'juknis.manage',
                'operational.gate', 'operational.verification', 'operational.print',
            ],

            // Bendahara: Biaya, paket registrasi, pendaftar, laporan keuangan
            'bendahara' => [
                'dashboard.view',
                'registrants.view', 'registrants.export',
                'finance.view', 'finance.manage',
            ],

            // Panitia Umum: Dashboard, lihat pendaftar, sekolah, gate checkin
            'panitia' => [
                'dashboard.view',
                'registrants.view',
                'schools.view',
                'operational.gate',
            ],

            // Dewan Juri: Arena juri & penilaian
            'juri' => [
                'dashboard.view',
                'operational.jury',
            ],

            // Wasit: Arena turnamen & skor pertandingan
            'wasit' => [
                'dashboard.view',
                'operational.tournament',
            ],
        ];

        $pivotInserts = [];
        foreach ($rolePermissionMap as $roleName => $permNames) {
            if (!isset($allRoles[$roleName])) continue;
            $roleId = $allRoles[$roleName];

            foreach ($permNames as $pName) {
                if (isset($allPermissions[$pName])) {
                    $pivotInserts[] = [
                        'role_id' => $roleId,
                        'permission_id' => $allPermissions[$pName],
                    ];
                }
            }
        }

        DB::table('role_permission')->insert($pivotInserts);

        // D. Sinkronkan role_id pada tabel users yang sudah ada
        foreach ($allRoles as $roleSlug => $roleId) {
            DB::table('users')->where('role', $roleSlug)->update(['role_id' => $roleId]);
        }
    }
};
