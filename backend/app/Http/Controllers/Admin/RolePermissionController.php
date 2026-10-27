<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RolePermissionController extends Controller
{
    /**
     * Tampilkan halaman Manajemen Hak Akses & Matriks Peran Panitia.
     */
    public function index(Request $request)
    {
        // 1. Ambil semua peran beserta relasi permissions dan jumlah anggota panitia
        $roles = Role::with(['permissions:id,name'])
            ->withCount('users')
            ->orderBy('id')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $role->label,
                    'badge_color' => $role->badge_color,
                    'description' => $role->description,
                    'is_system' => $role->is_system,
                    'users_count' => $role->users_count,
                    'permission_ids' => $role->permissions->pluck('id')->toArray(),
                    'permission_names' => $role->permissions->pluck('name')->toArray(),
                ];
            });

        // 2. Ambil semua hak akses dikelompokkan berdasarkan kategori modul
        $allPermissions = Permission::orderBy('id')->get();
        $permissionsGrouped = $allPermissions->groupBy('group');

        // 3. Ambil daftar panitia untuk modal hak akses khusus pengguna
        $usersList = User::select('id', 'name', 'email', 'nip', 'role', 'role_id', 'custom_permissions')
            ->orderBy('name')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'nip' => $u->nip,
                    'role' => $u->role,
                    'custom_permissions' => $u->custom_permissions ?: new \stdClass(),
                    'all_permissions' => $u->allPermissions(),
                ];
            });

        // 4. Ringkasan Statistik
        $metrics = [
            'total_roles' => $roles->count(),
            'total_permissions' => $allPermissions->count(),
            'total_users' => User::count(),
            'custom_overrides_count' => User::whereNotNull('custom_permissions')->count(),
        ];

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'permissionsGrouped' => $permissionsGrouped,
            'allPermissions' => $allPermissions,
            'usersList' => $usersList,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Perbarui matriks hak akses untuk peran tertentu (atau batch).
     */
    public function updateMatrix(Request $request)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        // Super Admin selalu memiliki semua hak akses
        if ($role->name === 'super_admin') {
            $allPermIds = Permission::pluck('id')->toArray();
            $role->permissions()->sync($allPermIds);
            return back()->with('success', 'Peran Super Admin selalu memiliki seluruh hak akses sistem secara penuh.');
        }

        $role->permissions()->sync($validated['permission_ids']);

        return back()->with('success', "Matriks hak akses untuk peran '{$role->label}' berhasil diperbarui.");
    }

    /**
     * Tambah peran kustom baru.
     */
    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:roles,name'],
            'label' => ['required', 'string', 'max:100'],
            'badge_color' => ['required', 'string', 'in:primary,secondary,success,danger,warning,info,dark'],
            'description' => ['nullable', 'string', 'max:500'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ], [
            'name.required' => 'Slug pengenal peran wajib diisi.',
            'name.unique' => 'Slug pengenal peran ini sudah digunakan.',
            'name.alpha_dash' => 'Slug peran hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'label.required' => 'Nama judul peran wajib diisi.',
        ]);

        $role = Role::create([
            'name' => strtolower($validated['name']),
            'label' => $validated['label'],
            'badge_color' => $validated['badge_color'],
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        if (!empty($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }

        return back()->with('success', "Peran baru '{$role->label}' berhasil ditambahkan ke sistem.");
    }

    /**
     * Perbarui data peran (label, badge, deskripsi).
     */
    public function updateRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'badge_color' => ['required', 'string', 'in:primary,secondary,success,danger,warning,info,dark'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $role->update($validated);

        return back()->with('success', "Peran '{$role->label}' berhasil diperbarui.");
    }

    /**
     * Hapus peran kustom (peran sistem tidak dapat dihapus).
     */
    public function destroyRole($id)
    {
        $role = Role::findOrFail($id);

        if ($role->is_system) {
            return back()->with('error', "Peran '{$role->label}' adalah peran bawaan sistem dan tidak dapat dihapus.");
        }

        $assignedCount = User::where('role', $role->name)->orWhere('role_id', $role->id)->count();
        if ($assignedCount > 0) {
            return back()->with('error', "Peran '{$role->label}' masih digunakan oleh {$assignedCount} panitia. Silakan alihkan peran pengguna tersebut terlebih dahulu.");
        }

        $role->permissions()->detach();
        $role->delete();

        return back()->with('success', "Peran '{$role->label}' berhasil dihapus.");
    }

    /**
     * Reset seluruh hak akses peran bawaan ke standar awal sistem.
     */
    public function resetDefaults()
    {
        $allPermissions = Permission::pluck('id', 'name')->toArray();
        $allRoles = Role::pluck('id', 'name')->toArray();

        $rolePermissionMap = [
            'super_admin' => array_keys($allPermissions),
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
            'bendahara' => [
                'dashboard.view',
                'registrants.view', 'registrants.export',
                'finance.view', 'finance.manage',
            ],
            'panitia' => [
                'dashboard.view',
                'registrants.view',
                'schools.view',
                'operational.gate',
            ],
            'juri' => [
                'dashboard.view',
                'operational.jury',
            ],
            'wasit' => [
                'dashboard.view',
                'operational.tournament',
            ],
        ];

        DB::transaction(function () use ($rolePermissionMap, $allRoles, $allPermissions) {
            foreach ($rolePermissionMap as $roleName => $permNames) {
                if (!isset($allRoles[$roleName])) continue;
                $role = Role::find($allRoles[$roleName]);
                if (!$role) continue;

                $permIds = [];
                foreach ($permNames as $pName) {
                    if (isset($allPermissions[$pName])) {
                        $permIds[] = $allPermissions[$pName];
                    }
                }
                $role->permissions()->sync($permIds);
            }
        });

        return back()->with('success', 'Seluruh hak akses peran bawaan berhasil dikembalikan ke standar awal sistem.');
    }

    /**
     * Perbarui hak akses khusus / override untuk pengguna tertentu.
     */
    public function updateUserPermissions(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'custom_permissions' => ['nullable', 'array'],
        ]);

        $custom = $validated['custom_permissions'] ?? null;
        if (empty($custom) || !is_array($custom)) {
            $user->custom_permissions = null;
        } else {
            // Filter nilai null / default
            $filtered = [];
            foreach ($custom as $permKey => $val) {
                if ($val === true || $val === false) {
                    $filtered[$permKey] = $val;
                }
            }
            $user->custom_permissions = !empty($filtered) ? $filtered : null;
        }

        $user->save();

        return back()->with('success', "Hak akses khusus untuk panitia '{$user->name}' berhasil disimpan.");
    }
}
