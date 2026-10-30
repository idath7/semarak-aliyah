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
        $now = now();

        // 1. Tambah permission registered_schools.delete
        $permData = [
            'name' => 'registered_schools.delete',
            'label' => 'Menghapus Data Sekolah Terdaftar',
            'group' => 'Database Sekolah',
            'description' => 'Menghapus data kontingen sekolah terdaftar beserta seluruh delegasi lombanya dari sistem.',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('permissions')->updateOrInsert(
            ['name' => 'registered_schools.delete'],
            $permData
        );

        // 2. Hubungkan permission ke peran super_admin dan admin
        $permId = DB::table('permissions')->where('name', 'registered_schools.delete')->value('id');
        $superAdminRoleId = DB::table('roles')->where('name', 'super_admin')->value('id');
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');

        if ($permId && $superAdminRoleId) {
            DB::table('role_permission')->updateOrInsert([
                'role_id' => $superAdminRoleId,
                'permission_id' => $permId,
            ]);
        }

        if ($permId && $adminRoleId) {
            DB::table('role_permission')->updateOrInsert([
                'role_id' => $adminRoleId,
                'permission_id' => $permId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permId = DB::table('permissions')->where('name', 'registered_schools.delete')->value('id');
        if ($permId) {
            DB::table('role_permission')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }
    }
};

