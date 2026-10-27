<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'role',
        'action',
        'activity_type',
        'title',
        'description',
        'highlight',
        'badge_icon',
        'badge_class',
        'ip_address',
        'user_agent',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an activity log entry safely.
     */
    public static function record(string $action, string $title, ?string $description = null, array $extra = []): ?self
    {
        try {
            $user = $extra['user'] ?? Auth::guard('web')->user();
            $userId = $extra['user_id'] ?? ($user ? $user->id : null);
            $userName = $extra['user_name'] ?? ($user ? $user->name : 'Sistem / Tamu');
            $role = $extra['role'] ?? ($user ? ($user->role ?: 'panitia') : 'system');
            
            $activityType = $extra['activity_type'] ?? (
                in_array($action, ['login', 'logout']) ? 'auth' : 'panitia'
            );

            $badgeIcon = $extra['badge_icon'] ?? match ($action) {
                'login'           => 'ri-login-box-line',
                'logout'          => 'ri-logout-box-r-line',
                'password_change' => 'ri-lock-password-line',
                'verification'    => 'ri-shield-check-line',
                'checkin'         => 'ri-qr-code-line',
                'settings_update' => 'ri-settings-4-line',
                'scoring'         => 'ri-trophy-line',
                'school_update'   => 'ri-community-line',
                'user_update'     => 'ri-user-settings-line',
                default           => 'ri-user-star-line',
            };

            $badgeClass = $extra['badge_class'] ?? match ($action) {
                'login'           => 'bg-success-subtle text-success',
                'logout'          => 'bg-danger-subtle text-danger',
                'password_change' => 'bg-warning-subtle text-warning',
                'verification'    => 'bg-info-subtle text-info',
                'checkin'         => 'bg-primary-subtle text-primary',
                'settings_update' => 'bg-secondary-subtle text-secondary',
                'scoring'         => 'bg-warning-subtle text-warning',
                default           => 'bg-primary-subtle text-primary',
            };

            $highlight = $extra['highlight'] ?? ($user ? $user->name : null);
            $ip = $extra['ip_address'] ?? (request() ? request()->ip() : null);
            $userAgent = $extra['user_agent'] ?? (request() ? request()->userAgent() : null);

            return self::create([
                'user_id'       => $userId,
                'user_name'     => $userName,
                'role'          => $role,
                'action'        => $action,
                'activity_type' => $activityType,
                'title'         => $title,
                'description'   => $description,
                'highlight'     => $highlight,
                'badge_icon'    => $badgeIcon,
                'badge_class'   => $badgeClass,
                'ip_address'    => $ip,
                'user_agent'    => $userAgent,
                'properties'    => $extra['properties'] ?? null,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ActivityLog error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Helper to log Panitia Login.
     */
    public static function logLogin(User $user, string $method = 'Password', ?string $details = null): ?self
    {
        $division = $user->division ? " ({$user->division})" : "";
        $desc = "Panitia {$user->name}{$division} berhasil masuk ke Admin Portal via {$method}.";
        if ($details) {
            $desc .= " " . $details;
        }

        return self::record('login', 'Panitia Masuk (Login)', $desc, [
            'user'          => $user,
            'highlight'     => $user->name,
            'activity_type' => 'auth',
            'properties'    => [
                'method' => $method,
                'email'  => $user->email,
                'nip'    => $user->nip,
                'role'   => $user->role,
            ],
        ]);
    }

    /**
     * Helper to log Panitia Logout.
     */
    public static function logLogout(User $user): ?self
    {
        $division = $user->division ? " ({$user->division})" : "";
        $desc = "Panitia {$user->name}{$division} telah keluar dari sesi Admin Portal.";

        return self::record('logout', 'Panitia Keluar (Logout)', $desc, [
            'user'          => $user,
            'highlight'     => $user->name,
            'activity_type' => 'auth',
            'properties'    => [
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);
    }
}
