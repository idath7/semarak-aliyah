<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * Check if authenticated user has required permission(s).
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = Auth::guard('web')->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super Admin memiliki akses mutlak ke semua fitur
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Cek apakah user memiliki minimal salah satu izin yang dibutuhkan
        $hasAccess = false;
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            $permStr = implode(', ', $permissions);
            if ($request->expectsJson() || $request->header('X-Inertia')) {
                return redirect()->route('admin.dashboard')->with('error', "Akses Ditolak: Akun Anda tidak memiliki hak akses ({$permStr}) untuk modul tersebut.");
            }
            abort(403, 'Akses Ditolak: Anda tidak memiliki hak akses untuk membuka halaman ini.');
        }

        return $next($request);
    }
}
