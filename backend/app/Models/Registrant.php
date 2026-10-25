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
