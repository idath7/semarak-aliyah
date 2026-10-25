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
        ];
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
}
