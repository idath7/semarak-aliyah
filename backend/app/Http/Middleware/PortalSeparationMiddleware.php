<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PortalSeparationMiddleware
{
    /**
     * Handle an incoming request and enforce port/domain separation between
     * Public Web (Port 8000 / public domain) and Admin Portal (Port 8001 / admin domain).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Bypass assets, static storage, Vite dev server, and health check
        if ($request->is('assets/*', 'storage/*', 'build/*', 'up', '@vite/*', '__vite_ping*') ||
            $request->ajax() && !$request->pjax() && !$request->header('X-Inertia')) {
            return $next($request);
        }

        $port = (int) $request->getPort();
        $host = $request->getHost();

        $adminPort = (int) config('portal.admin_port', 8001);
        $publicPort = (int) config('portal.public_port', 8000);

        $adminDomain = config('portal.admin_domain');
        $publicDomain = config('portal.public_domain');

        $portalType = $request->header('X-Portal-Type');

        // Check channel based on header X-Portal-Type, domain (in production), or port (in local)
        $isAdminChannel = ($portalType === 'admin')
            || ($adminDomain && str_contains($host, $adminDomain))
            || ($port === $adminPort);

        $isPublicChannel = ($portalType === 'public')
            || ($publicDomain && str_contains($host, $publicDomain))
            || ($port === $publicPort);

        $isAdminRoute = $request->is('login', 'logout', 'admin', 'admin/*', 'operasional', 'operasional/*');
        $isPublicOnlyRoute = $request->is('layanan-registrasi', 'layanan-registrasi/*');

        // ---------------------------------------------------------
        // A. REQUEST DI PORTAL ADMIN (Port 8001 / admin domain)
        // ---------------------------------------------------------
        if ($isAdminChannel) {
            // Jika mengakses root '/' pada portal admin, alihkan langsung ke dashboard atau login
            if ($request->is('/')) {
                if (Auth::check()) {
                    return redirect()->route('admin.dashboard');
                }
                return redirect()->route('login');
            }

            // Jika mengakses pendaftaran publik di portal admin, alihkan ke web publik
            if ($isPublicOnlyRoute) {
                $publicBase = $publicDomain
                    ? ($request->getScheme() . '://' . $publicDomain)
                    : ($request->getScheme() . '://' . $host . ($publicPort === 80 || $publicPort === 443 ? '' : ':' . $publicPort));
                return redirect(rtrim($publicBase, '/') . '/' . ltrim($request->path(), '/'));
            }

            return $next($request);
        }

        // ---------------------------------------------------------
        // B. REQUEST DI WEB PUBLIK (Port 8000 / public domain)
        // ---------------------------------------------------------
        if ($isPublicChannel) {
            // Jika ada yang mencoba mengakses halaman admin atau login dari web publik
            if ($isAdminRoute) {
                $adminBase = $adminDomain
                    ? ($request->getScheme() . '://' . $adminDomain)
                    : ($request->getScheme() . '://' . $host . ($adminPort === 80 || $adminPort === 443 ? '' : ':' . $adminPort));
                $target = rtrim($adminBase, '/') . '/' . ltrim($request->path(), '/');

                // Jika ada query string (misal: ?tab=...), sertakan
                if ($query = $request->getQueryString()) {
                    $target .= '?' . $query;
                }

                return redirect($target);
            }

            return $next($request);
        }

        return $next($request);
    }
}
