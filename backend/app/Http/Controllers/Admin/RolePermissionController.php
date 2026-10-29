<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
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
        // 0. Penyelarasan otomatis peran koordinator lomba
        User::syncAllCoordinatorRoles();

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
        $usersList = User::select('id', 'name', 'email', 'nip', 'role', 'role_id', 'competition_id', 'custom_permissions')
            ->orderBy('name')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'nip' => $u->nip,
                    'role' => $u->role,
                    'role_id' => $u->role_id,
                    'competition_id' => $u->competition_id,
                    'coordinated_competitions' => $u->getCoordinatedCompetitions(),
                    'custom_permissions' => $u->custom_permissions ?: new \stdClass(),
                    'all_permissions' => $u->allPermissions(),
                ];
            });

        // 4. Daftar cabang lomba untuk penugasan koordinator jika peran diubah
        $competitions = Competition::select('id', 'title', 'category_label')
            ->orderBy('title')
            ->get();

        // 5. Ringkasan Statistik
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
            'competitions' => $competitions,
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
    public function updateRole(Request $request, string|int $id)
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
    public function destroyRole(string|int $id)
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
     * Perbarui hak akses massal untuk beberapa peran sekaligus.
     */
    public function bulkUpdateRolePermissions(Request $request)
    {
        $validated = $request->validate([
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['exists:roles,id'],
            'action' => ['required', 'string', 'in:grant,revoke,sync'],
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ], [
            'role_ids.required' => 'Pilih minimal satu peran sasaran.',
            'role_ids.min' => 'Pilih minimal satu peran sasaran.',
            'action.required' => 'Aksi pengelolaan massal wajib ditentukan.',
        ]);

        $roleIds = $validated['role_ids'];
        $action = $validated['action'];
        $permissionIds = $validated['permission_ids'];

        $updatedCount = 0;
        DB::transaction(function () use ($roleIds, $action, $permissionIds, &$updatedCount) {
            foreach ($roleIds as $rId) {
                $role = Role::find($rId);
                if (!$role || $role->name === 'super_admin') {
                    // Super admin selalu mempertahankan seluruh akses
                    continue;
                }

                if ($action === 'grant') {
                    $role->permissions()->syncWithoutDetaching($permissionIds);
                } elseif ($action === 'revoke') {
                    $role->permissions()->detach($permissionIds);
                } elseif ($action === 'sync') {
                    $role->permissions()->sync($permissionIds);
                }

                $updatedCount++;
            }
        });

        $actionLabels = [
            'grant' => 'penambahan izin',
            'revoke' => 'pencabutan izin',
            'sync' => 'penyelarasan hak akses',
        ];

        return back()->with('success', "Operasi {$actionLabels[$action]} massal berhasil diterapkan pada {$updatedCount} peran.");
    }

    /**
     * Salin seluruh susunan hak akses dari satu peran acuan ke beberapa peran tujuan.
     */
    public function copyRolePermissions(Request $request)
    {
        $validated = $request->validate([
            'source_role_id' => ['required', 'exists:roles,id'],
            'target_role_ids' => ['required', 'array', 'min:1'],
            'target_role_ids.*' => ['exists:roles,id'],
        ], [
            'source_role_id.required' => 'Pilih peran sumber acuan.',
            'target_role_ids.required' => 'Pilih minimal satu peran tujuan.',
            'target_role_ids.min' => 'Pilih minimal satu peran tujuan.',
        ]);

        $sourceRole = Role::with('permissions')->findOrFail($validated['source_role_id']);
        $sourcePermIds = $sourceRole->permissions->pluck('id')->toArray();

        $updatedCount = 0;
        DB::transaction(function () use ($validated, $sourcePermIds, &$updatedCount) {
            foreach ($validated['target_role_ids'] as $targetId) {
                if ($targetId == $validated['source_role_id']) {
                    continue;
                }
                $targetRole = Role::find($targetId);
                if (!$targetRole || $targetRole->name === 'super_admin') {
                    continue;
                }

                $targetRole->permissions()->sync($sourcePermIds);
                $updatedCount++;
            }
        });

        return back()->with('success', "Susunan hak akses peran '{$sourceRole->label}' berhasil disalin ke {$updatedCount} peran target.");
    }

    /**
     * Perbarui hak akses khusus / override untuk banyak pengguna sekaligus.
     */
    public function bulkUpdateUserOverrides(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['exists:users,id'],
            'action' => ['required', 'string', 'in:grant,revoke,clear_perms,reset_all'],
            'permission_names' => ['nullable', 'array'],
            'permission_names.*' => ['string'],
        ], [
            'user_ids.required' => 'Pilih minimal satu panitia sasaran.',
            'user_ids.min' => 'Pilih minimal satu panitia sasaran.',
            'action.required' => 'Aksi pembaruan massal wajib ditentukan.',
        ]);

        $userIds = $validated['user_ids'];
        $action = $validated['action'];
        $permNames = $validated['permission_names'] ?? [];

        $updatedCount = 0;
        DB::transaction(function () use ($userIds, $action, $permNames, &$updatedCount) {
            foreach ($userIds as $uId) {
                $user = User::find($uId);
                if (!$user) continue;

                if ($action === 'reset_all') {
                    $user->custom_permissions = null;
                } else {
                    $custom = $user->custom_permissions ?: [];
                    if (!is_array($custom)) {
                        $custom = [];
                    }

                    foreach ($permNames as $pName) {
                        if ($action === 'grant') {
                            $custom[$pName] = true;
                        } elseif ($action === 'revoke') {
                            $custom[$pName] = false;
                        } elseif ($action === 'clear_perms') {
                            unset($custom[$pName]);
                        }
                    }

                    $user->custom_permissions = !empty($custom) ? $custom : null;
                }

                $user->save();
                $updatedCount++;
            }
        });

        return back()->with('success', "Hak akses khusus massal berhasil diterapkan pada {$updatedCount} panitia.");
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
                'competitions.view', 'competitions.manage',
                'tm.view', 'tm.manage',
                'form_builder.view', 'form_builder.manage',
                'juknis.view', 'juknis.manage',
                'finance.view', 'finance.manage',
                'content.view', 'content.sponsor', 'content.gallery', 'content.faq', 'content.customization',
                'users.view', 'users.manage', 'roles.manage',
                'settings.view', 'settings.system',
                'operational.gate', 'operational.verification', 'operational.jury', 'operational.tournament', 'operational.print',
            ],
            'ketua_panitia' => [
                'dashboard.view',
                'registrants.view', 'registrants.export',
                'schools.view',
                'competitions.view', 'competitions.manage',
                'tm.view', 'tm.manage',
                'form_builder.view', 'form_builder.manage',
                'juknis.view', 'juknis.manage',
                'finance.view',
                'content.view',
                'users.view',
                'settings.view',
                'operational.gate', 'operational.verification', 'operational.jury', 'operational.tournament', 'operational.print',
            ],
            'wakil_ketua_panitia' => [
                'dashboard.view',
                'registrants.view', 'registrants.export',
                'schools.view',
                'competitions.view', 'competitions.manage',
                'tm.view', 'tm.manage',
                'form_builder.view', 'form_builder.manage',
                'juknis.view', 'juknis.manage',
                'finance.view',
                'content.view',
                'users.view',
                'settings.view',
                'operational.gate', 'operational.verification', 'operational.jury', 'operational.tournament', 'operational.print',
            ],
            'sekretaris' => [
                'dashboard.view',
                'registrants.view', 'registrants.verify', 'registrants.export',
                'schools.view', 'schools.manage',
                'competitions.view', 'competitions.manage',
                'tm.view', 'tm.manage',
                'form_builder.view', 'form_builder.manage',
                'juknis.view', 'juknis.manage',
                'content.view',
                'operational.gate', 'operational.verification', 'operational.print',
            ],
            'kesekretariatan' => [
                'dashboard.view',
                'registrants.view', 'registrants.verify', 'registrants.export',
                'schools.view', 'schools.manage',
                'competitions.view', 'competitions.manage',
                'tm.view', 'tm.manage',
                'form_builder.view', 'form_builder.manage',
                'juknis.view', 'juknis.manage',
                'content.view',
                'operational.gate', 'operational.verification', 'operational.print',
            ],
            'koordinator_lomba' => [
                'dashboard.view',
                'registrants.view',
                'competitions.view',
                'competitions.manage',
                'tm.view',
                'tm.manage',
                'form_builder.view',
                'juknis.view',
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
                'competitions.view',
                'tm.view',
                'juknis.view',
                'content.view',
                'operational.gate',
            ],
            'juri' => [
                'dashboard.view',
                'competitions.view',
                'tm.view',
                'juknis.view',
                'operational.jury',
            ],
            'wasit' => [
                'dashboard.view',
                'competitions.view',
                'tm.view',
                'juknis.view',
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
    public function updateUserPermissions(Request $request, string|int $userId)
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'custom_permissions' => ['nullable', 'array'],
        ]);

        $custom = $validated['custom_permissions'] ?? null;
        if (empty($custom) || !is_array($custom)) {
            $user->custom_permissions = null;
        } else {
            // Filter nilai boolean true / false
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

    /**
     * Ubah peran utama seorang panitia secara langsung dari modul Hak Akses & Peran.
     */
    public function updateUserRole(Request $request, string|int $userId)
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
            'competition_id' => ['nullable', 'string', 'exists:competitions,id'],
        ], [
            'role.required' => 'Pilih peran baru untuk panitia ini.',
            'role.exists' => 'Peran yang dipilih tidak terdaftar dalam sistem.',
            'competition_id.exists' => 'Cabang lomba tidak ditemukan.',
        ]);

        $newRole = Role::where('name', $validated['role'])->firstOrFail();

        // Pencegahan super admin terakhir
        if ($user->role === 'super_admin' && $newRole->name !== 'super_admin') {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                return back()->with('error', 'Sistem harus memiliki setidaknya satu Super Admin aktif.');
            }
        }

        $user->role = $newRole->name;
        $user->role_id = $newRole->id;

        if (array_key_exists('competition_id', $validated)) {
            $user->competition_id = $validated['competition_id'];
        }

        $user->save();

        // Jalankan sinkronisasi koordinator agar relasi kompetisi selalu sinkron
        User::syncAllCoordinatorRoles();

        return back()->with('success', "Peran panitia '{$user->name}' berhasil diubah menjadi '{$newRole->label}'.");
    }

    /**
     * Jalankan sinkronisasi peran koordinator lomba secara manual dari UI.
     */
    public function syncCoordinators(Request $request)
    {
        $result = User::syncAllCoordinatorRoles();
        $count = $result['synced_count'] ?? 0;

        if ($count > 0) {
            return back()->with('success', "Penyelarasan berhasil: {$count} panitia yang ditugaskan di cabang lomba telah disesuaikan sebagai Koordinator Lomba.");
        }

        return back()->with('success', 'Seluruh peran koordinator lomba sudah selaras dan sesuai dengan penugasan.');
    }
}
