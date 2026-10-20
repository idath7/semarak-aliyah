<!DOCTYPE html>
<html lang="id" data-bs-theme="{{ (request()->is('admin*') || request()->is('operasional*') || request()->is('login*')) ? 'dark' : 'light' }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
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
        $seoCanonical = \App\Models\Setting::get('seo_canonical_url', url()->current());
        $seoThemeColor = \App\Models\Setting::get('seo_theme_color', '#8b5cf6');
        $ogTitle = \App\Models\Setting::get('seo_og_title', $seoTitle);
        $ogDesc = \App\Models\Setting::get('seo_og_description', $seoDesc);
        $ogImage = \App\Models\Setting::get('seo_og_image', '/assets/img/pamflet.jpg');
        if (!str_starts_with($ogImage, 'http')) {
            $ogImage = url($ogImage);
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
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:title" content="{{ $ogTitle }}" />
    <meta property="og:description" content="{{ $ogDesc }}" />
    <meta property="og:image" content="{{ $ogImage }}" />
    <meta property="og:site_name" content="{{ $eventName }}" />

    <!-- Twitter / X -->
    <meta name="twitter:card" content="{{ $twitterCard }}" />
    <meta name="twitter:url" content="{{ url()->current() }}" />
    <meta name="twitter:title" content="{{ $ogTitle }}" />
    <meta name="twitter:description" content="{{ $ogDesc }}" />
    <meta name="twitter:image" content="{{ $ogImage }}" />

    @if(request()->is('admin*') || request()->is('operasional*') || request()->is('login*'))
        <!-- Admin & Operasional Command Center Fonts & Assets -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&family=Orbitron:wght@600;700;800;900&family=Syne:wght@700;800;900&family=Oswald:wght@600;700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.css" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <!-- Public Portal Fonts & Stylesheets (Synchronous in head to eliminate FOUC and page transition flash) -->
        <script>
            (function() {
                try {
                    var t = localStorage.getItem('semarak_theme');
                    if (t === 'dark') {
                        document.documentElement.setAttribute('data-bs-theme', 'dark');
                        document.documentElement.style.colorScheme = 'dark';
                        document.documentElement.style.backgroundColor = '#0b0f19';
                    }
                } catch(e) {}
            })();
        </script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800;900&family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Oswald:wght@600;700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.css" rel="stylesheet" />
        <link rel="stylesheet" href="/assets/css/style.css?v=3.24" />
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
