<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     * Ensure only admin and committee staff (non-registrants) can access admin and operational panels.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('web')->check() || Auth::guard('web')->user() instanceof \App\Models\Registrant) {
            if ($request->expectsJson() || $request->header('X-Inertia')) {
                return redirect()->route('login')->withErrors([
                    'identifier' => 'Hanya akun panitia dan pengelola yang berhak mengakses Panel Admin. Pendaftar tidak diperbolehkan login di Admin Panel.',
                ]);
            }
            return redirect()->route('login');
        }

        $user = Auth::guard('web')->user();

        // Jika panitia wajib buat/ganti password saat pertama kali login
        if ($user && ($user->must_change_password || empty($user->password))) {
            if (!$request->is('admin/buat-password', 'logout')) {
                if ($request->header('X-Inertia')) {
                    return \Inertia\Inertia::location(route('admin.password.first'));
                }
                return redirect()->route('admin.password.first');
            }
        }

        return $next($request);
    }
}
