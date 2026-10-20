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
            $countdownTarget = Setting::get('maintenance_countdown_target', '');
            $estimatedEnd = Setting::get('maintenance_estimated_end', '');

            if (!empty($countdownTarget)) {
                try {
                    $carbonDate = \Carbon\Carbon::parse(str_replace('T', ' ', $countdownTarget))->locale('id');
                    $estimatedEnd = $carbonDate->translatedFormat('d F Y, H:i') . ' WIB';
                } catch (\Exception $e) {}
            }

            $rawWa = Setting::get('maintenance_emergency_wa', '0838 5007 0767');
            $rawWaList = Setting::get('maintenance_emergency_wa_list', null);
            $waContacts = [];

            if (!empty($rawWaList)) {
                $decoded = json_decode($rawWaList, true);
                if (is_array($decoded)) {
                    $waContacts = array_values(array_filter($decoded, fn($w) => !empty($w['number'])));
                }
            }

            if (empty($waContacts) && !empty($rawWa)) {
                $parts = preg_split('/[\r\n,;\/]+/', $rawWa);
                foreach ($parts as $idx => $part) {
                    $trimmed = trim($part);
                    if (!empty($trimmed)) {
                        $waContacts[] = [
                            'label' => count($parts) > 1 ? 'Hotline Panitia ' . ($idx + 1) : 'Hubungi Hotline Panitia',
                            'number' => $trimmed,
                        ];
                    }
                }
            }

            if (empty($waContacts)) {
                $waContacts[] = [
                    'label' => 'Hubungi Hotline Panitia',
                    'number' => '0838 5007 0767',
                ];
            }

            // SEO & OpenGraph settings for rich preview (WhatsApp, Facebook, Twitter, Telegram, etc.)
            $schoolName = Setting::get('school_name', 'MA Unggulan Wahid Hasyim Balung');
            $eventName = Setting::get('event_name', 'SEMARAK ALIYAH XIV 2026');
            $title = Setting::get('maintenance_title', 'Situs Sedang Dalam Pemeliharaan Berkala');
            $message = Setting::get('maintenance_message', 'Mohon maaf atas ketidaknyamanan ini. Saat ini sistem Semarak Aliyah XIV 2026 sedang dalam proses peningkatan performa dan sinkronisasi data teknis. Website publik akan segera kembali online.');

            $seoTitle = Setting::get('seo_meta_title', $eventName . ' - ' . $schoolName);
            $seoDesc = Setting::get('seo_meta_description', 'Ajang kompetisi tahunan bergengsi siswa/i SMP/MTs sederajat se-Kabupaten Jember. Raih piala juara, piagam resmi, uang pembinaan jutaan rupiah, dan beasiswa pendidikan.');
            $seoKeywords = Setting::get('seo_meta_keywords', 'semarak aliyah, ma wahid hasyim balung, olimpiade mipa jember, olimpiade ips jember, storytelling, festival albanjari, singer lagu islami, voli jember, mobile legends');
            $seoAuthor = Setting::get('seo_author', 'Panitia ' . $eventName . ' • ' . $schoolName);
            $seoRobots = Setting::get('seo_robots', 'index, follow');
            $seoThemeColor = Setting::get('seo_theme_color', '#8b5cf6');

            $isHttps = $request->isSecure() || $request->header('x-forwarded-proto') === 'https' || str_starts_with(config('app.url'), 'https://');
            $currentUrl = url()->current();
            if ($isHttps && str_starts_with($currentUrl, 'http://')) {
                $currentUrl = 'https://' . substr($currentUrl, 7);
            }

            $seoCanonical = Setting::get('seo_canonical_url', $currentUrl);
            if ($isHttps && str_starts_with($seoCanonical, 'http://')) {
                $seoCanonical = 'https://' . substr($seoCanonical, 7);
            }

            $ogTitle = Setting::get('seo_og_title', $seoTitle);
            $ogDesc = Setting::get('seo_og_description', $seoDesc);

            $rawOgImage = Setting::get('seo_og_image', '/assets/img/og-thumbnail.jpg');
            $ogImage = $rawOgImage;
            if (!str_starts_with($ogImage, 'http')) {
                $ogImage = url($ogImage);
            }
            if ($isHttps && str_starts_with($ogImage, 'http://')) {
                $ogImage = 'https://' . substr($ogImage, 7);
            }
            $ogImageSecure = str_replace('http://', 'https://', $ogImage);

            $parsedPath = parse_url($rawOgImage, PHP_URL_PATH) ?: $rawOgImage;
            $localFilePath = public_path(ltrim($parsedPath, '/'));
            $ogImageWidth = '1200';
            $ogImageHeight = '630';
            $ogImageType = 'image/jpeg';
            $ogImageVersion = '';

            if (file_exists($localFilePath)) {
                $imgInfo = @getimagesize($localFilePath);
                if ($imgInfo && !empty($imgInfo[0]) && !empty($imgInfo[1])) {
                    $ogImageWidth = (string)$imgInfo[0];
                    $ogImageHeight = (string)$imgInfo[1];
                    $ogImageType = $imgInfo['mime'] ?? 'image/jpeg';
                }
                $ogImageVersion = '?v=' . filemtime($localFilePath);
            }

            $twitterCard = Setting::get('seo_twitter_card', 'summary_large_image');

            $data = [
                'title' => $title,
                'message' => $message,
                'estimatedEnd' => $estimatedEnd,
                'countdownTarget' => $countdownTarget,
                'emergencyWa' => $waContacts[0]['number'] ?? '0838 5007 0767',
                'waContacts' => $waContacts,
                'schoolName' => $schoolName,
                'eventName' => $eventName,
                'currentUrl' => $currentUrl,
                'seoTitle' => $seoTitle,
                'seoDesc' => $seoDesc,
                'seoKeywords' => $seoKeywords,
                'seoAuthor' => $seoAuthor,
                'seoRobots' => $seoRobots,
                'seoThemeColor' => $seoThemeColor,
                'seoCanonical' => $seoCanonical,
                'ogTitle' => $ogTitle,
                'ogDesc' => $ogDesc,
                'ogImage' => $ogImage,
                'ogImageSecure' => $ogImageSecure,
                'ogImageWidth' => $ogImageWidth,
                'ogImageHeight' => $ogImageHeight,
                'ogImageType' => $ogImageType,
                'ogImageVersion' => $ogImageVersion,
                'twitterCard' => $twitterCard,
            ];

            // Deteksi bot / scraper media sosial (WhatsApp, Facebook, Twitter, Telegram, LinkedIn, Pinterest, Discord, dll.)
            // Scraper media sosial WAJIB menerima HTTP status 200 OK agar dapat memproses OpenGraph meta tag dan memunculkan thumbnail preview.
            $userAgent = $request->header('User-Agent', '');
            $isSocialBot = (bool) preg_match('/(facebookexternalhit|WhatsApp|Facebot|Twitterbot|TelegramBot|LinkedInBot|Pinterest|Slackbot|Discordbot)/i', $userAgent);

            $statusCode = $isSocialBot ? 200 : 503;
            $response = response()->view('maintenance', $data, $statusCode);

            if ($statusCode === 503) {
                $response->header('Retry-After', '3600');
            }

            return $response;
        }

        return $next($request);
    }
}
