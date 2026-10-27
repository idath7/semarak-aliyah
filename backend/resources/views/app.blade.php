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
    @endif

    @inertiaHead
</head>
<body class="font-sans antialiased min-h-screen">
    @inertia

    @if(!request()->is('admin*') && !request()->is('operasional*') && !request()->is('login*'))
        @if(!empty($customBodyScript))
            {!! $customBodyScript !!}
        @endif
    @endif
</body>
</html>
