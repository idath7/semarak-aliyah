<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'nip',
        'password',
        'must_change_password',
        'role',
        'role_id',
        'division',
        'phone',
        'competition_id',
        'is_active',
        'avatar',
        'registrant_role',
        'gender',
        'school_name',
        'school_district',
        'google_id',
        'wa_otp_code',
        'wa_otp_expires_at',
        'custom_permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'wa_otp_code',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'avatar_url',
    ];

    /**
     * Get the full URL to the user avatar.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (empty($this->avatar)) {
            return null;
        }
        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://') || str_starts_with($this->avatar, 'data:')) {
            return $this->avatar;
        }
        if (str_starts_with($this->avatar, '/storage/')) {
            return asset(ltrim($this->avatar, '/'));
        }
        if (str_starts_with($this->avatar, 'storage/')) {
            return asset($this->avatar);
        }
        return asset('storage/' . ltrim($this->avatar, '/'));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
            'wa_otp_expires_at' => 'datetime',
            'custom_permissions' => 'array',
        ];
    }

    /**
     * Relasi ke Master Peran (Role).
     */
    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Cabang Lomba yang dinaungi (jika role Juri / Wasit / PJ Lomba).
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class, 'competition_id');
    }

    /**
     * Registrations submitted by this registrant account.
     */
    public function registrations()
    {
        return $this->hasMany(Registration::class, 'user_id');
    }

    /**
     * Check if user is a registrant (pendaftar / peserta).
     * Always returns false for User model as registrant accounts are now separated in the registrants table.
     */
    public function isRegistrant(): bool
    {
        return false;
    }

    /**
     * Check if user is Super Admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user has administrative rights
     */
    public function hasAdminAccess(): bool
    {
        return in_array($this->role, [
            'super_admin', 'admin', 'bendahara', 'kesekretariatan',
            'ketua_panitia', 'wakil_ketua_panitia', 'sekretaris', 'koordinator_lomba'
        ]);
    }

    /**
     * Ambil seluruh nama hak akses aktif pengguna (gabungan peran + custom overrides).
     *
     * @return array<string>
     */
    public function allPermissions(): array
    {
        if ($this->isSuperAdmin()) {
            return Permission::pluck('name')->toArray();
        }

        $role = $this->roleRelation ?: Role::where('name', $this->role)->first();
        $permissions = $role ? $role->permissions()->pluck('name')->toArray() : [];

        if (!empty($this->custom_permissions) && is_array($this->custom_permissions)) {
            foreach ($this->custom_permissions as $perm => $allowed) {
                if ($allowed && !in_array($perm, $permissions)) {
                    $permissions[] = $perm;
                } elseif (!$allowed) {
                    $permissions = array_values(array_diff($permissions, [$perm]));
                }
            }
        }

        return $permissions;
    }

    /**
     * Periksa apakah pengguna memiliki hak akses tertentu.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!empty($this->custom_permissions) && is_array($this->custom_permissions) && array_key_exists($permission, $this->custom_permissions)) {
            return (bool)$this->custom_permissions[$permission];
        }

        $role = $this->roleRelation ?: Role::where('name', $this->role)->first();
        return $role ? $role->hasPermission($permission) : false;
    }

    /**
     * Periksa apakah pengguna dapat mengakses salah satu atau seluruh izin.
     */
    public function canAccess(string|array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (is_array($permissions)) {
            foreach ($permissions as $perm) {
                if ($this->hasPermission($perm)) {
                    return true;
                }
            }
            return false;
        }

        return $this->hasPermission($permissions);
    }

    /**
     * Cek apakah pengguna merupakan koordinator untuk cabang lomba tertentu.
     */
    public function isCoordinatorFor(string|int|null $competitionId): bool
    {
        if (empty($competitionId)) {
            return false;
        }

        if ((string)$this->competition_id === (string)$competitionId) {
            return true;
        }

        $competition = Competition::find($competitionId);
        if (!$competition) {
            return false;
        }

        return $competition->isUserCoordinator($this->id);
    }

    /**
     * Ambil seluruh ID cabang lomba yang dikoordinasikan oleh pengguna ini.
     *
     * @return array<string>
     */
    public function getCoordinatedCompetitionIds(): array
    {
        $ids = [];
        if (!empty($this->competition_id)) {
            $ids[] = (string)$this->competition_id;
        }

        $competitions = Competition::whereNotNull('coordinators')
            ->orWhere('coordinator_id', $this->id)
            ->get();

        foreach ($competitions as $comp) {
            if ($comp->isUserCoordinator($this->id)) {
                $ids[] = (string)$comp->id;
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Ambil seluruh data cabang lomba yang dikoordinasikan oleh pengguna ini.
     *
     * @return array<array{id: string|int, title: string, category_label: string}>
     */
    public function getCoordinatedCompetitions(): array
    {
        $ids = $this->getCoordinatedCompetitionIds();
        if (empty($ids)) {
            return [];
        }

        return Competition::whereIn('id', $ids)
            ->select('id', 'title', 'category_label')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'category_label' => $c->category_label,
                ];
            })
            ->toArray();
    }

    /**
     * Penyelarasan otomatis peran koordinator lomba antara tabel competitions dan users.
     * Memastikan setiap pengguna yang ditugaskan sebagai koordinator lomba memiliki peran
     * 'koordinator_lomba' dan role_id yang benar, serta menyelaraskan struktur data JSON.
     *
     * @return array{synced_count: int, updated_users: array}
     */
    public static function syncAllCoordinatorRoles(): array
    {
        $coordRole = Role::where('name', 'koordinator_lomba')->first();
        if (!$coordRole) {
            $coordRole = Role::create([
                'name' => 'koordinator_lomba',
                'label' => 'Koordinator Lomba',
                'badge_color' => 'warning',
                'description' => 'Penanggung jawab teknis cabang lomba spesifik & TM',
                'is_system' => true,
            ]);
        }

        $competitions = Competition::all();
        $allUsers = static::all();

        // Indeks nomor telepon dan nama untuk auto-link koordinator tanpa user_id
        $usersByPhone = [];
        $usersByName = [];
        foreach ($allUsers as $u) {
            if (!empty($u->phone)) {
                $cleanPhone = preg_replace('/\D/', '', (string)$u->phone);
                if (!empty($cleanPhone)) {
                    $usersByPhone[$cleanPhone] = $u;
                    if (str_starts_with($cleanPhone, '0')) {
                        $usersByPhone['62' . substr($cleanPhone, 1)] = $u;
                    } elseif (str_starts_with($cleanPhone, '62')) {
                        $usersByPhone['0' . substr($cleanPhone, 2)] = $u;
                    }
                }
            }
            if (!empty($u->name)) {
                $usersByName[strtolower(trim($u->name))] = $u;
            }
        }

        $coordUserMap = []; // userId => [comp_id => comp_title]

        foreach ($competitions as $comp) {
            $dirtyComp = false;
            $coords = is_array($comp->coordinators) ? $comp->coordinators : [];

            foreach ($coords as &$c) {
                // Selalu pastikan role penugasan koordinator adalah 'Koordinator Lomba'
                if (empty($c['role']) || in_array(strtolower(trim((string)$c['role'])), ['panitia pelaksana', 'panitia', 'koordinator'])) {
                    $c['role'] = 'Koordinator Lomba';
                    $dirtyComp = true;
                }

                // Coba auto-link user_id jika kosong menggunakan nomor telepon atau nama
                if (empty($c['user_id'])) {
                    $matchedUser = null;
                    if (!empty($c['contact'])) {
                        $cPhone = preg_replace('/\D/', '', (string)$c['contact']);
                        if (isset($usersByPhone[$cPhone])) {
                            $matchedUser = $usersByPhone[$cPhone];
                        }
                    }
                    if (!$matchedUser && !empty($c['name'])) {
                        $cName = strtolower(trim((string)$c['name']));
                        if (!str_starts_with($cName, 'koordinator ') && isset($usersByName[$cName])) {
                            $matchedUser = $usersByName[$cName];
                        }
                    }

                    if ($matchedUser) {
                        $c['user_id'] = $matchedUser->id;
                        $dirtyComp = true;
                        if (empty($comp->coordinator_id)) {
                            $comp->coordinator_id = $matchedUser->id;
                            $dirtyComp = true;
                        }
                    }
                }

                if (!empty($c['user_id']) && is_numeric($c['user_id'])) {
                    $coordUserMap[(int)$c['user_id']][$comp->id] = $comp->title;
                }
            }
            unset($c);

            if ($dirtyComp) {
                $comp->coordinators = $coords;
                $comp->save();
            }

            if (!empty($comp->coordinator_id)) {
                $coordUserMap[(int)$comp->coordinator_id][$comp->id] = $comp->title;
            }
        }

        // Cek juga pengguna yang memiliki competition_id di tabel users
        foreach ($allUsers as $u) {
            if (!empty($u->competition_id)) {
                $c = $competitions->firstWhere('id', $u->competition_id);
                if ($c) {
                    $coordUserMap[(int)$u->id][$c->id] = $c->title;

                    // Pastikan kompetisi juga mencatat user ini di coordinators atau coordinator_id
                    if (!$c->isUserCoordinator($u->id)) {
                        $cCoords = is_array($c->coordinators) ? $c->coordinators : [];
                        $hasEntry = false;
                        foreach ($cCoords as $item) {
                            if (!empty($item['user_id']) && (int)$item['user_id'] === (int)$u->id) {
                                $hasEntry = true;
                                break;
                            }
                        }
                        if (!$hasEntry) {
                            $cCoords[] = [
                                'user_id' => $u->id,
                                'name' => $u->name,
                                'contact' => $u->phone ?: '',
                                'role' => 'Koordinator Lomba',
                            ];
                            $c->coordinators = $cCoords;
                        }
                        if (empty($c->coordinator_id)) {
                            $c->coordinator_id = $u->id;
                        }
                        $c->save();
                    }
                }
            }
        }

        $updatedCount = 0;
        $updatedDetails = [];

        foreach ($coordUserMap as $uId => $comps) {
            $user = static::find($uId);
            if (!$user) {
                continue;
            }

            $userChanged = false;
            $primaryCompId = array_key_first($comps);

            // Jika competition_id pengguna masih kosong, isi dengan cabang lomba pertama
            if (empty($user->competition_id) && $primaryCompId) {
                $user->competition_id = $primaryCompId;
                $userChanged = true;
            }

            // Jika peran saat ini adalah 'panitia' atau kosong, perbarui ke 'koordinator_lomba'
            if (in_array($user->role, ['panitia', 'koordinator_lomba', null, ''], true)) {
                if ($user->role !== 'koordinator_lomba') {
                    $user->role = 'koordinator_lomba';
                    $userChanged = true;
                }
                if ($coordRole && (int)$user->role_id !== (int)$coordRole->id) {
                    $user->role_id = $coordRole->id;
                    $userChanged = true;
                }
            }

            if ($userChanged) {
                $user->save();
                $updatedCount++;
                $updatedDetails[] = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'competitions' => array_values($comps),
                ];
            }
        }

        return [
            'synced_count' => $updatedCount,
            'updated_users' => $updatedDetails,
        ];
    }
}
