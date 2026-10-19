<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     * Check if maintenance mode is enabled and display maintenance view for public visitors.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Always allow assets, build artifacts, storage, and health checks
        if ($request->is('assets/*', 'storage/*', 'build/*', 'up', '@vite/*', '__vite_ping*') ||
            ($request->ajax() && !$request->pjax() && !$request->header('X-Inertia'))) {
            return $next($request);
        }

        // 2. Always allow admin, login, logout, and operasional command center
        if ($request->is('login', 'logout', 'admin', 'admin/*', 'operasional', 'operasional/*')) {
            return $next($request);
        }

        // 3. Always allow authenticated staff / admin users
        if ($request->hasSession() && Auth::check()) {
            return $next($request);
        }

        // 4. Secret key bypass support (e.g. ?bypass=secret or session bypass)
        $secretKey = Setting::get('maintenance_secret_key', 'semarak2026bypass');
        if ($request->query('bypass') === $secretKey) {
            if ($request->hasSession()) {
                $request->session()->put('maintenance_bypass', true);
            }
            return redirect($request->url());
        }

        if ($request->hasSession() && $request->session()->get('maintenance_bypass') === true) {
            return $next($request);
        }

        // 5. Check if maintenance mode is active
        $isMaintenance = Setting::get('maintenance_mode', '0') === '1';

        if ($isMaintenance) {
            $data = [
                'title' => Setting::get('maintenance_title', 'Situs Sedang Dalam Pemeliharaan Berkala'),
                'message' => Setting::get('maintenance_message', 'Mohon maaf atas ketidaknyamanan ini. Saat ini sistem Semarak Aliyah XIV 2026 sedang dalam proses peningkatan performa dan sinkronisasi data teknis. Website publik akan segera kembali online.'),
                'estimatedEnd' => Setting::get('maintenance_estimated_end', '19 Oktober 2026, 12:00 WIB'),
                'countdownTarget' => Setting::get('maintenance_countdown_target', ''),
                'emergencyWa' => Setting::get('maintenance_emergency_wa', '0838 5007 0767'),
                'schoolName' => Setting::get('school_name', 'MA Unggulan Wahid Hasyim Balung'),
                'eventName' => Setting::get('event_name', 'SEMARAK ALIYAH XIV 2026'),
            ];

            return response()->view('maintenance', $data, 503);
        }

        return $next($request);
    }
}
