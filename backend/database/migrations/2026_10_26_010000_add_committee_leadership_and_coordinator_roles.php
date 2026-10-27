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
        // 1. Tambah coordinator_id pada tabel competitions jika belum ada
        if (Schema::hasTable('competitions') && !Schema::hasColumn('competitions', 'coordinator_id')) {
            Schema::table('competitions', function (Blueprint $table) {
                $table->foreignId('coordinator_id')
                    ->nullable()
                    ->after('contact_person')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        // 2. Daftarkan 4 peran baku kepanitiaan terkait Lomba & TM
        $now = now();
        $rolesToAdd = [
            [
                'name' => 'ketua_panitia',
                'label' => 'Ketua Panitia',
                'badge_color' => 'dark',
                'description' => 'Pimpinan umum kepanitiaan, wewenang monitoring & pengawasan seluruh cabang lomba dan TM.',
                'is_system' => true,
            ],
            [
                'name' => 'wakil_ketua_panitia',
                'label' => 'Wakil Ketua Panitia',
                'badge_color' => 'primary',
                'description' => 'Wakil pimpinan kepanitiaan, pendampingan teknis dan kelancaran seluruh cabang lomba dan TM.',
                'is_system' => true,
            ],
            [
                'name' => 'sekretaris',
                'label' => 'Sekretaris Pelaksana',
                'badge_color' => 'secondary',
                'description' => 'Administrasi kepanitiaan, kelola cabang lomba, juknis lomba, form builder, dan hasil pengundian TM.',
                'is_system' => true,
            ],
            [
                'name' => 'koordinator_lomba',
                'label' => 'Koordinator Lomba',
                'badge_color' => 'warning',
                'description' => 'Penanggung jawab teknis cabang lomba spesifik dan pelaksanaan TM cabang lomba yang ditugaskan.',
                'is_system' => true,
            ],
        ];

        foreach ($rolesToAdd as $rData) {
            $existing = DB::table('roles')->where('name', $rData['name'])->first();
            if (!$existing) {
                DB::table('roles')->insert(array_merge($rData, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            } else {
                DB::table('roles')->where('name', $rData['name'])->update([
                    'label' => $rData['label'],
                    'badge_color' => $rData['badge_color'],
                    'description' => $rData['description'],
                    'updated_at' => $now,
                ]);
            }
        }

        // 3. Petakan permissions untuk peran-peran baru
        $allPermissions = DB::table('permissions')->pluck('id', 'name')->toArray();
        $allRoles = DB::table('roles')->pluck('id', 'name')->toArray();

        $rolePermissionMap = [
            'ketua_panitia' => [
                'dashboard.view',
                'registrants.view', 'registrants.export',
                'schools.view',
                'competitions.manage', 'tm.manage', 'form_builder.manage', 'juknis.manage',
                'finance.view',
                'users.view',
                'operational.gate', 'operational.verification', 'operational.jury', 'operational.tournament', 'operational.print',
            ],
            'wakil_ketua_panitia' => [
                'dashboard.view',
                'registrants.view', 'registrants.export',
                'schools.view',
                'competitions.manage', 'tm.manage', 'form_builder.manage', 'juknis.manage',
                'finance.view',
                'users.view',
                'operational.gate', 'operational.verification', 'operational.jury', 'operational.tournament', 'operational.print',
            ],
            'sekretaris' => [
                'dashboard.view',
                'registrants.view', 'registrants.verify', 'registrants.export',
                'schools.view', 'schools.manage',
                'competitions.manage', 'tm.manage', 'form_builder.manage', 'juknis.manage',
                'operational.gate', 'operational.verification', 'operational.print',
            ],
            'kesekretariatan' => [
                'dashboard.view',
                'registrants.view', 'registrants.verify', 'registrants.export',
                'schools.view', 'schools.manage',
                'competitions.manage', 'tm.manage', 'form_builder.manage', 'juknis.manage',
                'operational.gate', 'operational.verification', 'operational.print',
            ],
            'koordinator_lomba' => [
                'dashboard.view',
                'registrants.view',
                'competitions.manage',
                'tm.manage',
                'juknis.manage',
                'operational.jury',
                'operational.tournament',
            ],
        ];

        foreach ($rolePermissionMap as $roleName => $permNames) {
            if (!isset($allRoles[$roleName])) continue;
            $roleId = $allRoles[$roleName];

            foreach ($permNames as $pName) {
                if (!isset($allPermissions[$pName])) continue;
                $permId = $allPermissions[$pName];

                $exists = DB::table('role_permission')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permId)
                    ->exists();

                if (!$exists) {
                    DB::table('role_permission')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('competitions') && Schema::hasColumn('competitions', 'coordinator_id')) {
            Schema::table('competitions', function (Blueprint $table) {
                $table->dropForeign(['coordinator_id']);
                $table->dropColumn('coordinator_id');
            });
        }
    }
};
