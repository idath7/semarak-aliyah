<!DOCTYPE html>
<html lang="id" data-layout="vertical" data-topbar="{{ (request()->is('admin*') || request()->is('operasional*') || request()->is('login*') || request()->is('pendaftar*')) ? 'dark' : 'light' }}" data-sidebar="dark" data-sidebar-size="lg" data-bs-theme="{{ (request()->is('admin*') || request()->is('operasional*') || request()->is('login*') || request()->is('pendaftar*')) ? 'dark' : 'light' }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <meta name="color-scheme" content="light dark" />
    <link rel="icon" type="image/png" href="/assets/img/logo.png" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @php
        $schoolName = \App\Models\Setting::get('school_name', 'MA Unggulan Wahid Hasyim Balung');
        $schoolShortName = \App\Models\Setting::get('school_short_name', 'MA Wahid Hasyim');
        $eventName = \App\Models\Setting::get('event_name', 'SEMARAK ALIYAH XIV 2026');
        $preloaderActive = \App\Models\Setting::get('preloader_active', '1') === '1';
        $preloaderText = \App\Models\Setting::get('preloader_text', 'Memuat keseruan Semarak Aliyah...');
        $seoTitle = \App\Models\Setting::get('seo_meta_title', $eventName . ' - ' . $schoolName);
        $seoDesc = \App\Models\Setting::get('seo_meta_description', 'Ajang kompetisi tahunan bergengsi siswa/i SMP/MTs sederajat se-Kabupaten Jember. Raih piala juara, piagam resmi, uang pembinaan jutaan rupiah, dan beasiswa pendidikan.');
        $seoKeywords = \App\Models\Setting::get('seo_meta_keywords', 'semarak aliyah, ma wahid hasyim balung, olimpiade mipa jember, olimpiade ips jember, storytelling, festival albanjari, singer lagu islami, voli jember, mobile legends');
        $seoAuthor = \App\Models\Setting::get('seo_author', 'Panitia ' . $eventName . ' • ' . $schoolName);
        $seoRobots = \App\Models\Setting::get('seo_robots', 'index, follow');
        $seoThemeColor = \App\Models\Setting::get('seo_theme_color', '#8b5cf6');

        $isHttps = request()->isSecure() || request()->header('x-forwarded-proto') === 'https' || str_starts_with(config('app.url'), 'https://');
        
        $currentUrl = url()->current();
        if ($isHttps && str_starts_with($currentUrl, 'http://')) {
            $currentUrl = 'https://' . substr($currentUrl, 7);
        }

        $seoCanonical = \App\Models\Setting::get('seo_canonical_url', $currentUrl);
        if ($isHttps && str_starts_with($seoCanonical, 'http://')) {
            $seoCanonical = 'https://' . substr($seoCanonical, 7);
        }

        $ogTitle = \App\Models\Setting::get('seo_og_title', $seoTitle);
        $ogDesc = \App\Models\Setting::get('seo_og_description', $seoDesc);
        
        $rawOgImage = \App\Models\Setting::get('seo_og_image', '/assets/img/og-thumbnail.jpg');
        $ogImage = $rawOgImage;
        if (!str_starts_with($ogImage, 'http')) {
            $ogImage = url($ogImage);
        }
        if ($isHttps && str_starts_with($ogImage, 'http://')) {
            $ogImage = 'https://' . substr($ogImage, 7);
        }
        $ogImageSecure = str_replace('http://', 'https://', $ogImage);

        // Specs image untuk card WhatsApp & Facebook
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

        $twitterCard = \App\Models\Setting::get('seo_twitter_card', 'summary_large_image');
    @endphp

    <title inertia>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}" />
    <meta name="keywords" content="{{ $seoKeywords }}" />
    <meta name="author" content="{{ $seoAuthor }}" />
    <meta name="robots" content="{{ $seoRobots }}" />
    <meta name="theme-color" content="{{ $seoThemeColor }}" />
    <link rel="canonical" href="{{ $seoCanonical }}" />

    <!-- Open Graph / Facebook / WhatsApp -->
    <meta property="og:type" content="website" />
    <meta property="og:locale" content="id_ID" />
    <meta property="og:site_name" content="{{ $eventName }}" />
    <meta property="og:url" content="{{ $currentUrl }}" />
    <meta property="og:title" content="{{ $ogTitle }}" />
    <meta property="og:description" content="{{ $ogDesc }}" />
    <meta property="og:image" content="{{ $ogImage . $ogImageVersion }}" />
    <meta property="og:image:secure_url" content="{{ $ogImageSecure . $ogImageVersion }}" />
    <meta property="og:image:type" content="{{ $ogImageType }}" />
    <meta property="og:image:width" content="{{ $ogImageWidth }}" />
    <meta property="og:image:height" content="{{ $ogImageHeight }}" />
    <meta property="og:image:alt" content="{{ $ogTitle }}" />

    <!-- Twitter / X -->
    <meta name="twitter:card" content="{{ $twitterCard }}" />
    <meta name="twitter:url" content="{{ $currentUrl }}" />
    <meta name="twitter:title" content="{{ $ogTitle }}" />
    <meta name="twitter:description" content="{{ $ogDesc }}" />
    <meta name="twitter:image" content="{{ $ogImage . $ogImageVersion }}" />

    <!-- Local Offline Fonts & Icons (Available Offline-First) -->
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/vendor/remixicon/remixicon.css">
    <link rel="stylesheet" href="/assets/vendor/boxicons/css/boxicons.min.css">

    @if(request()->is('admin*') || request()->is('operasional*') || request()->is('login*') || request()->is('pendaftar*'))
        <!-- Admin, Operasional & Pendaftar Portal Fonts & Assets (Synchronous Theme Restorer) -->
        <script>
            (function() {
                try {
                    var isPendaftar = window.location.pathname.startsWith('/pendaftar');
                    var t = isPendaftar 
                        ? (localStorage.getItem('semarak_theme') || localStorage.getItem('velzon_theme_v2') || 'dark')
                        : (localStorage.getItem('velzon_theme_v2') || localStorage.getItem('semarak_theme') || 'dark');
                    if (t !== 'dark' && t !== 'light') {
                        t = 'dark';
                    }
                    document.documentElement.setAttribute('data-bs-theme', t);
                    document.documentElement.setAttribute('data-topbar', t === 'dark' ? 'dark' : 'light');
                    document.documentElement.setAttribute('data-sidebar', 'dark');
                    document.documentElement.style.colorScheme = t;
                    if (t === 'dark') {
                        document.documentElement.style.backgroundColor = '#0b0f19';
                    } else {
                        document.documentElement.style.backgroundColor = '#f3f3f9';
                    }
                } catch(e) {}
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            [data-bs-theme="dark"] body {
                background-color: #0b0f19 !important;
                color: #f1f5f9 !important;
            }
            [data-bs-theme="dark"] body::before,
            [data-bs-theme="dark"] body::after {
                display: none !important;
            }
            [data-bs-theme="light"] body {
                background-color: #f3f3f9 !important;
                color: #212529 !important;
            }
        </style>
    @else
        <!-- Public Portal Fonts & Stylesheets (Synchronous in head to eliminate FOUC and page transition flash) -->
        <script>
            (function() {
                try {
                    var t = localStorage.getItem('semarak_theme') || 'light';
                    if (t !== 'dark' && t !== 'light') {
                        t = 'light';
                    }
                    document.documentElement.setAttribute('data-bs-theme', t);
                    document.documentElement.setAttribute('data-topbar', t === 'dark' ? 'dark' : 'light');
                    document.documentElement.style.colorScheme = t;
                    if (t === 'dark') {
                        document.documentElement.style.backgroundColor = '#0b0f19';
                    } else {
                        document.documentElement.style.backgroundColor = '#ffffff';
                    }
                } catch(e) {}
            })();
        </script>
        <link rel="stylesheet" href="/assets/css/style.css?v=3.26" />
        <!-- Preload critical above-the-fold assets for instant rendering -->
        <link rel="preload" as="image" href="/assets/img/logo.webp" type="image/webp" fetchpriority="high">
        <link rel="preload" as="image" href="/assets/img/pamflet.webp" type="image/webp" fetchpriority="high">
        @vite(['resources/js/app.js'])

        @php
            $customCss = \App\Models\Setting::get('custom_css');
            $customHeadScript = \App\Models\Setting::get('custom_head_script');
            $customBodyScript = \App\Models\Setting::get('custom_body_script');

            if (!empty($customCss)) {
                echo '<' . 'style id="custom-portal-css">' . $customCss . '</' . 'style>';
            }
        @endphp

        @if(!empty($customHeadScript))
            {!! $customHeadScript !!}
        @endif

        @if($preloaderActive)
            <style id="public-preloader-style">
                #public-preloader {
                    position: fixed;
                    inset: 0;
                    z-index: 99999999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 100vw;
                    height: 100vh;
                    overflow: hidden;
                    user-select: none;
                    -webkit-user-select: none;
                    transition: opacity 0.48s cubic-bezier(0.16, 1, 0.3, 1), 
                                transform 0.48s cubic-bezier(0.16, 1, 0.3, 1), 
                                visibility 0.48s ease;
                    will-change: opacity, transform;
                }
                body.preloader-active {
                    overflow: hidden !important;
                }
                [data-bs-theme="light"] #public-preloader,
                :root:not([data-bs-theme="dark"]) #public-preloader {
                    background-color: #ffffff;
                    color: #0f172a;
                }
                [data-bs-theme="light"] .preloader-glow-1,
                :root:not([data-bs-theme="dark"]) .preloader-glow-1 {
                    background: radial-gradient(circle, rgba(139, 92, 246, 0.12) 0%, rgba(236, 72, 153, 0.06) 45%, transparent 70%);
                }
                [data-bs-theme="light"] .preloader-glow-2,
                :root:not([data-bs-theme="dark"]) .preloader-glow-2 {
                    background: radial-gradient(circle, rgba(249, 115, 22, 0.08) 0%, transparent 65%);
                }
                [data-bs-theme="light"] .preloader-logo-badge,
                :root:not([data-bs-theme="dark"]) .preloader-logo-badge {
                    background: #ffffff;
                    border: 1.5px solid rgba(139, 92, 246, 0.2);
                    box-shadow: 0 16px 36px -10px rgba(139, 92, 246, 0.22), 0 0 0 1px rgba(226, 232, 240, 0.85);
                }
                [data-bs-theme="light"] .preloader-badge-tag,
                :root:not([data-bs-theme="dark"]) .preloader-badge-tag {
                    background: rgba(139, 92, 246, 0.08);
                    border: 1px solid rgba(139, 92, 246, 0.22);
                    color: #7c3aed;
                }
                [data-bs-theme="light"] .preloader-school-title,
                :root:not([data-bs-theme="dark"]) .preloader-school-title {
                    color: #1e293b;
                }
                [data-bs-theme="light"] .preloader-progress-track,
                :root:not([data-bs-theme="dark"]) .preloader-progress-track {
                    background: rgba(226, 232, 240, 0.85);
                }
                [data-bs-theme="light"] .preloader-status-text,
                :root:not([data-bs-theme="dark"]) .preloader-status-text {
                    color: #475569;
                }

                [data-bs-theme="dark"] #public-preloader {
                    background-color: #0b0f19;
                    color: #f8fafc;
                }
                [data-bs-theme="dark"] .preloader-glow-1 {
                    background: radial-gradient(circle, rgba(139, 92, 246, 0.24) 0%, rgba(236, 72, 153, 0.12) 45%, transparent 70%);
                }
                [data-bs-theme="dark"] .preloader-glow-2 {
                    background: radial-gradient(circle, rgba(249, 115, 22, 0.12) 0%, transparent 65%);
                }
                [data-bs-theme="dark"] .preloader-logo-badge {
                    background: #111827;
                    border: 1.5px solid rgba(139, 92, 246, 0.38);
                    box-shadow: 0 18px 40px -8px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.08);
                }
                [data-bs-theme="dark"] .preloader-badge-tag {
                    background: rgba(139, 92, 246, 0.2);
                    border: 1px solid rgba(139, 92, 246, 0.45);
                    color: #c4b5fd;
                }
                [data-bs-theme="dark"] .preloader-school-title {
                    color: #f1f5f9;
                }
                [data-bs-theme="dark"] .preloader-progress-track {
                    background: rgba(30, 41, 59, 0.85);
                }
                [data-bs-theme="dark"] .preloader-status-text {
                    color: #cbd5e1;
                }

                .preloader-glow-orb {
                    position: absolute;
                    border-radius: 50%;
                    pointer-events: none;
                    filter: blur(40px);
                    transform: translateZ(0);
                }
                .preloader-glow-1 {
                    width: 460px;
                    height: 460px;
                    top: 25%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    animation: preloaderPulseGlow 4s ease-in-out infinite alternate;
                }
                .preloader-glow-2 {
                    width: 320px;
                    height: 320px;
                    bottom: 10%;
                    right: 15%;
                    opacity: 0.7;
                }
                .preloader-stage {
                    position: relative;
                    z-index: 2;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                    padding: 24px;
                    max-width: 440px;
                    width: 100%;
                    animation: preloaderCardEnter 0.5s cubic-bezier(0.16, 1, 0.3, 1) backwards;
                }
                .preloader-logo-area {
                    position: relative;
                    width: 120px;
                    height: 120px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 22px;
                }
                .preloader-orbit-outer {
                    position: absolute;
                    width: 120px;
                    height: 120px;
                    border-radius: 50%;
                    border: 2.5px solid transparent;
                    border-top-color: #8b5cf6;
                    border-right-color: #ec4899;
                    border-bottom-color: #f97316;
                    animation: preloaderSpin 2.4s cubic-bezier(0.68, -0.2, 0.27, 1.2) infinite;
                }
                .preloader-orbit-inner {
                    position: absolute;
                    width: 104px;
                    height: 104px;
                    border-radius: 50%;
                    border: 1.5px dashed rgba(139, 92, 246, 0.4);
                    animation: preloaderSpinRev 3.5s linear infinite;
                }
                .preloader-logo-halo {
                    position: absolute;
                    width: 86px;
                    height: 86px;
                    border-radius: 50%;
                    background: radial-gradient(circle, rgba(139, 92, 246, 0.4) 0%, rgba(236, 72, 153, 0.18) 50%, transparent 70%);
                    animation: preloaderHalo 2.4s ease-in-out infinite;
                }
                .preloader-logo-badge {
                    position: relative;
                    z-index: 3;
                    width: 86px;
                    height: 86px;
                    border-radius: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    animation: preloaderFloat 2.6s ease-in-out infinite;
                }
                .preloader-logo-img {
                    width: 54px;
                    height: 54px;
                    object-fit: contain;
                    display: block;
                    filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
                }
                .preloader-brand-box {
                    margin-bottom: 24px;
                }
                .preloader-badge-tag {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    padding: 5px 14px;
                    border-radius: 999px;
                    font-size: 0.78rem;
                    font-weight: 700;
                    letter-spacing: 0.05em;
                    text-transform: uppercase;
                    margin-bottom: 8px;
                }
                .preloader-sparkle {
                    font-size: 0.72rem;
                    color: #f59e0b;
                    animation: preloaderSparkle 1.8s infinite ease-in-out;
                }
                .preloader-school-title {
                    font-size: 1.05rem;
                    font-weight: 700;
                    line-height: 1.35;
                    margin: 0;
                    letter-spacing: -0.01em;
                }
                .preloader-progress-wrap {
                    width: 100%;
                    max-width: 240px;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 10px;
                }
                .preloader-progress-track {
                    width: 100%;
                    height: 4.5px;
                    border-radius: 999px;
                    overflow: hidden;
                    position: relative;
                }
                .preloader-progress-bar {
                    position: absolute;
                    left: 0;
                    top: 0;
                    bottom: 0;
                    width: 10%;
                    background: linear-gradient(90deg, #8b5cf6 0%, #ec4899 45%, #f97316 80%, #10b981 100%);
                    border-radius: 999px;
                    transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
                    overflow: hidden;
                }
                .preloader-shimmer {
                    position: absolute;
                    inset: 0;
                    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.65), transparent);
                    transform: translateX(-100%);
                    animation: preloaderShimmer 1.5s infinite linear;
                }
                .preloader-status-text {
                    font-size: 0.8rem;
                    font-weight: 500;
                    display: inline-flex;
                    align-items: center;
                    gap: 2px;
                    letter-spacing: 0.01em;
                }
                .preloader-dot-pulse span {
                    display: inline-block;
                    font-weight: 700;
                    animation: preloaderDot 1.4s infinite ease-in-out both;
                }
                .preloader-dot-pulse span:nth-child(1) { animation-delay: 0s; }
                .preloader-dot-pulse span:nth-child(2) { animation-delay: 0.2s; }
                .preloader-dot-pulse span:nth-child(3) { animation-delay: 0.4s; }

                #public-preloader.preloader-fade-out {
                    opacity: 0;
                    visibility: hidden;
                    transform: scale(1.04);
                    pointer-events: none;
                }

                @keyframes preloaderSpin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                @keyframes preloaderSpinRev {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(-360deg); }
                }
                @keyframes preloaderFloat {
                    0%, 100% { transform: translateY(0px); }
                    50% { transform: translateY(-4px); }
                }
                @keyframes preloaderHalo {
                    0%, 100% { transform: scale(0.95); opacity: 0.5; }
                    50% { transform: scale(1.08); opacity: 0.9; }
                }
                @keyframes preloaderPulseGlow {
                    0% { transform: translate(-50%, -50%) scale(0.95); opacity: 0.7; }
                    100% { transform: translate(-50%, -50%) scale(1.08); opacity: 1; }
                }
                @keyframes preloaderShimmer {
                    0% { transform: translateX(-100%); }
                    100% { transform: translateX(200%); }
                }
                @keyframes preloaderDot {
                    0%, 80%, 100% { opacity: 0.2; transform: translateY(0); }
                    40% { opacity: 1; transform: translateY(-2px); }
                }
                @keyframes preloaderSparkle {
                    0%, 100% { transform: scale(1); opacity: 0.8; }
                    50% { transform: scale(1.25); opacity: 1; }
                }
                @keyframes preloaderCardEnter {
                    0% { opacity: 0; transform: translateY(12px) scale(0.96); }
                    100% { opacity: 1; transform: translateY(0) scale(1); }
                }
            </style>
        @endif
    @endif

    @inertiaHead
</head>
<body class="font-sans antialiased min-h-screen">
    @if(!request()->is('admin*') && !request()->is('operasional*') && !request()->is('login*') && !request()->is('pendaftar*') && $preloaderActive)
        <!-- ======================================================================
             PUBLIC PORTAL PRELOADER (First Access & Page Refresh Animation)
             ====================================================================== -->
        <div id="public-preloader" class="public-preloader" aria-hidden="true">
            <div class="preloader-backdrop"></div>
            <div class="preloader-glow-orb preloader-glow-1"></div>
            <div class="preloader-glow-orb preloader-glow-2"></div>
            
            <div class="preloader-stage">
                <!-- Dual Orbital Rings & Floating Logo Badge -->
                <div class="preloader-logo-area">
                    <div class="preloader-orbit-outer"></div>
                    <div class="preloader-orbit-inner"></div>
                    <div class="preloader-logo-halo"></div>
                    <div class="preloader-logo-badge">
                        <picture>
                            <source srcset="/assets/img/logo.webp" type="image/webp">
                            <img src="/assets/img/logo.png" alt="{{ $eventName }}" class="preloader-logo-img" width="54" height="54" fetchpriority="high" />
                        </picture>
                    </div>
                </div>

                <!-- Brand Typography -->
                <div class="preloader-brand-box">
                    <div class="preloader-badge-tag">
                        <span class="preloader-sparkle">✦</span>
                        <span>{{ $eventName }}</span>
                        <span class="preloader-sparkle">✦</span>
                    </div>
                    <h1 class="preloader-school-title">{{ $schoolName }}</h1>
                </div>

                <!-- Sleek Progress Track & Status -->
                <div class="preloader-progress-wrap">
                    <div class="preloader-progress-track">
                        <div class="preloader-progress-bar">
                            <div class="preloader-shimmer"></div>
                        </div>
                    </div>
                    <div class="preloader-status-text">
                        <span class="preloader-status-msg">{{ $preloaderText }}</span>
                        <span class="preloader-dot-pulse">
                            <span>.</span><span>.</span><span>.</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <script id="public-preloader-script">
            (function() {
                var preloader = document.getElementById('public-preloader');
                if (!preloader) return;

                document.body.classList.add('preloader-active');
                var startTime = performance.now();
                var minDuration = 650;
                var isDismissed = false;
                var progressBar = preloader.querySelector('.preloader-progress-bar');
                var progress = 18;
                if (progressBar) progressBar.style.width = progress + '%';

                var progressTimer = setInterval(function() {
                    if (isDismissed) {
                        clearInterval(progressTimer);
                        return;
                    }
                    if (progress < 85) {
                        progress += Math.floor(Math.random() * 12) + 6;
                        if (progress > 85) progress = 85;
                        if (progressBar) progressBar.style.width = progress + '%';
                    }
                }, 110);

                function dismissPreloader() {
                    if (isDismissed) return;
                    isDismissed = true;
                    clearInterval(progressTimer);

                    var elapsed = performance.now() - startTime;
                    var remaining = Math.max(0, minDuration - elapsed);

                    setTimeout(function() {
                        if (progressBar) {
                            progressBar.style.transition = 'width 0.22s cubic-bezier(0.4, 0, 0.2, 1)';
                            progressBar.style.width = '100%';
                        }

                        setTimeout(function() {
                            preloader.classList.add('preloader-fade-out');

                            setTimeout(function() {
                                preloader.style.display = 'none';
                                if (preloader.parentNode) {
                                    preloader.parentNode.removeChild(preloader);
                                }
                                document.body.classList.remove('preloader-active');
                            }, 500);
                        }, 180);
                    }, remaining);
                }

                if (document.readyState === 'complete') {
                    dismissPreloader();
                } else {
                    window.addEventListener('load', dismissPreloader);
                }

                window.addEventListener('semarak:page-ready', dismissPreloader);
                document.addEventListener('inertia:finish', dismissPreloader);

                setTimeout(dismissPreloader, 2500);
            })();
        </script>
    @endif

    @inertia

    @if(!request()->is('admin*') && !request()->is('operasional*') && !request()->is('login*'))
        @if(!empty($customBodyScript))
            {!! $customBodyScript !!}
        @endif
    @endif
</body>
</html>
