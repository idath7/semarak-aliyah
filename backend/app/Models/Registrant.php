<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Registrant extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Nama tabel khusus akun pendaftar
     *
     * @var string
     */
    protected $table = 'registrants';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'gender',
        'school_name',
        'school_district',
        'registrant_role',
        'google_id',
        'wa_otp_code',
        'wa_otp_expires_at',
        'is_active',
        'avatar',
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
     * Get the full URL to the registrant avatar.
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
            'is_active' => 'boolean',
            'wa_otp_expires_at' => 'datetime',
        ];
    }

    /**
     * Pendaftaran (registrations) yang ditautkan ke akun pendaftar ini.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'registrant_id');
    }

    /**
     * Pendaftar selalu memiliki identitas registrant.
     */
    public function isRegistrant(): bool
    {
        return true;
    }

    /**
     * Pendaftar tidak memiliki akses administratif ke Admin Panel.
     */
    public function hasAdminAccess(): bool
    {
        return false;
    }

    /**
     * Pendaftar bukan Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return false;
    }
}
