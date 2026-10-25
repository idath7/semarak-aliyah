<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'badge_color',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Relasi ke Permissions melalui tabel pivot role_permission
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    /**
     * Relasi ke Users yang memiliki peran ini
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'name');
    }

    /**
     * Cek apakah peran ini memiliki izin tertentu
     */
    public function hasPermission(string $permissionName): bool
    {
        if ($this->name === 'super_admin') {
            return true;
        }

        return $this->permissions->contains('name', $permissionName);
    }

    /**
     * Sinkronkan daftar permission IDs untuk peran ini
     */
    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }
}
