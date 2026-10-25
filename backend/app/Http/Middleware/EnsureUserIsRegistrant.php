<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsRegistrant
{
    /**
     * Handle an incoming request.
     * Ensure only registered participants (pendaftar / peserta) can access the registrant portal.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $registrant = Auth::guard('pendaftar')->user();
        if (!$registrant) {
            if ($request->expectsJson() || $request->header('X-Inertia')) {
                return redirect()->route('pendaftar.login')->withErrors([
                    'login' => 'Portal ini khusus untuk akun pendaftar / peserta lomba. Silakan masuk terlebih dahulu.',
                ]);
            }
            return redirect()->route('pendaftar.login');
        }

        return $next($request);
    }
}
