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
        return in_array($this->role, ['super_admin', 'admin', 'bendahara', 'kesekretariatan']);
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
}
