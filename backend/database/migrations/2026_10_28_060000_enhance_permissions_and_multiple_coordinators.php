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
        $now = now();

        // 1. Tambah 6 Hak Akses "Lihat Saja (Read-Only)" Baru
        $newPermissions = [
            [
                'name' => 'competitions.view',
                'label' => 'Melihat Daftar & Kuota Cabang Lomba (Lihat Saja)',
                'group' => 'Manajemen Lomba & TM',
                'description' => 'Melihat daftar seluruh cabang lomba, kuota pendaftar, dan jadwal pelaksanaan tanpa hak mengubah.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'tm.view',
                'label' => 'Melihat Jadwal & Data Technical Meeting (Lihat Saja)',
                'group' => 'Manajemen Lomba & TM',
                'description' => 'Melihat agenda TM, panduan teknis, dan bagan/urutan undian tanpa hak mengubah pengaturan.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'form_builder.view',
                'label' => 'Melihat Susunan Formulir Pendaftaran (Lihat Saja)',
                'group' => 'Manajemen Lomba & TM',
                'description' => 'Melihat pratinjau kolom pertanyaan dan biodata formulir tiap cabang lomba.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'juknis.view',
                'label' => 'Melihat & Mengunduh Juknis Lomba (Lihat Saja)',
                'group' => 'Manajemen Lomba & TM',
                'description' => 'Melihat dan mengunduh buku panduan teknis juknis lomba.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'content.view',
                'label' => 'Melihat Manajemen Konten Web Publik (Lihat Saja)',
                'group' => 'Kelola Web Publik',
                'description' => 'Melihat daftar sponsor, galeri dokumentasi, FAQ, dan layout web publik.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'settings.view',
                'label' => 'Melihat Ringkasan Pengaturan Sistem (Lihat Saja)',
                'group' => 'Pengaturan Sistem',
                'description' => 'Melihat profil sekolah dan informasi konfigurasi sistem tanpa wewenang mengubah data.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($newPermissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm['name']],
                $perm
            );
        }

        // 2. Hubungkan permission baru ke peran-peran terkait
        $roles = DB::table('roles')->pluck('id', 'name')->toArray();
        $permissions = DB::table('permissions')->pluck('id', 'name')->toArray();

        $rolePermissionMap = [
            'super_admin' => array_keys($permissions),
            'admin' => array_keys($permissions),
            'ketua_panitia' => [
                'dashboard.view', 'registrants.view', 'registrants.export', 'schools.view',
                'competitions.view', 'competitions.manage', 'tm.view', 'tm.manage',
                'form_builder.view', 'form_builder.manage', 'juknis.view', 'juknis.manage',
                'finance.view', 'content.view', 'users.view', 'settings.view',
                'operational.gate', 'operational.verification', 'operational.jury', 'operational.tournament', 'operational.print',
            ],
            'wakil_ketua_panitia' => [
                'dashboard.view', 'registrants.view', 'registrants.export', 'schools.view',
                'competitions.view', 'competitions.manage', 'tm.view', 'tm.manage',
                'form_builder.view', 'form_builder.manage', 'juknis.view', 'juknis.manage',
                'finance.view', 'content.view', 'users.view', 'settings.view',
                'operational.gate', 'operational.verification', 'operational.jury', 'operational.tournament', 'operational.print',
            ],
            'sekretaris' => [
                'dashboard.view', 'registrants.view', 'registrants.verify', 'registrants.export',
                'schools.view', 'schools.manage',
                'competitions.view', 'competitions.manage', 'tm.view', 'tm.manage',
                'form_builder.view', 'form_builder.manage', 'juknis.view', 'juknis.manage',
                'content.view', 'settings.view',
                'operational.gate', 'operational.verification', 'operational.print',
            ],
            'kesekretariatan' => [
                'dashboard.view', 'registrants.view', 'registrants.verify', 'registrants.export',
                'schools.view', 'schools.manage',
                'competitions.view', 'competitions.manage', 'tm.view', 'tm.manage',
                'form_builder.view', 'form_builder.manage', 'juknis.view', 'juknis.manage',
                'content.view', 'settings.view',
                'operational.gate', 'operational.verification', 'operational.print',
            ],
            'koordinator_lomba' => [
                'dashboard.view', 'registrants.view',
                'competitions.view', 'competitions.manage',
                'tm.view', 'tm.manage',
                'form_builder.view',
                'juknis.view', 'juknis.manage',
                'operational.jury', 'operational.tournament',
            ],
            'panitia' => [
                'dashboard.view', 'registrants.view', 'schools.view',
                'competitions.view', 'tm.view', 'juknis.view',
                'operational.gate',
            ],
            'bendahara' => [
                'dashboard.view', 'registrants.view', 'registrants.export',
                'finance.view', 'finance.manage',
            ],
            'juri' => [
                'dashboard.view', 'competitions.view', 'tm.view', 'juknis.view',
                'operational.jury',
            ],
            'wasit' => [
                'dashboard.view', 'competitions.view', 'tm.view', 'juknis.view',
                'operational.tournament',
            ],
        ];

        foreach ($rolePermissionMap as $roleName => $permList) {
            if (!isset($roles[$roleName])) continue;
            $roleId = $roles[$roleName];

            foreach ($permList as $pName) {
                if (isset($permissions[$pName])) {
                    $pId = $permissions[$pName];
                    DB::table('role_permission')->updateOrInsert(
                        ['role_id' => $roleId, 'permission_id' => $pId],
                        []
                    );
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $names = [
            'competitions.view',
            'tm.view',
            'form_builder.view',
            'juknis.view',
            'content.view',
            'settings.view',
        ];

        $permIds = DB::table('permissions')->whereIn('name', $names)->pluck('id');
        DB::table('role_permission')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('name', $names)->delete();
    }
};
