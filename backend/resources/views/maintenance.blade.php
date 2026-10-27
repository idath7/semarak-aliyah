<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $seoTitle ?? ($title . ' | ' . $eventName) }}</title>
    <link rel="icon" type="image/png" href="/assets/img/logo.png">

    <!-- Primary Meta Tags (Mengikuti Pengaturan SEO Admin) -->
    <meta name="title" content="{{ $seoTitle ?? ($title . ' | ' . $eventName) }}">
    <meta name="description" content="{{ $seoDesc ?? $message }}">
    @if(!empty($seoKeywords))
    <meta name="keywords" content="{{ $seoKeywords }}">
    @endif
    @if(!empty($seoAuthor))
    <meta name="author" content="{{ $seoAuthor }}">
    @endif
    <meta name="robots" content="{{ $seoRobots ?? 'index, follow' }}">
    <meta name="theme-color" content="{{ $seoThemeColor ?? '#8b5cf6' }}">
    <link rel="canonical" href="{{ $seoCanonical ?? ($currentUrl ?? url()->current()) }}">

    <!-- Open Graph / Facebook / WhatsApp (Sesuai Konfigurasi SEO Terkini) -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="{{ $eventName }}">
    <meta property="og:url" content="{{ $currentUrl ?? url()->current() }}">
    <meta property="og:title" content="{{ $ogTitle ?? ($seoTitle ?? ($title . ' | ' . $eventName)) }}">
    <meta property="og:description" content="{{ $ogDesc ?? ($seoDesc ?? $message) }}">
    <meta property="og:image" content="{{ ($ogImage ?? url('/assets/img/og-thumbnail.jpg')) . ($ogImageVersion ?? '') }}">
    <meta property="og:image:secure_url" content="{{ ($ogImageSecure ?? url('/assets/img/og-thumbnail.jpg')) . ($ogImageVersion ?? '') }}">
    <meta property="og:image:type" content="{{ $ogImageType ?? 'image/jpeg' }}">
    <meta property="og:image:width" content="{{ $ogImageWidth ?? '1200' }}">
    <meta property="og:image:height" content="{{ $ogImageHeight ?? '630' }}">
    <meta property="og:image:alt" content="{{ $ogTitle ?? $eventName }}">

    <!-- Twitter / X -->
    <meta name="twitter:card" content="{{ $twitterCard ?? 'summary_large_image' }}">
    <meta name="twitter:url" content="{{ $currentUrl ?? url()->current() }}">
    <meta name="twitter:title" content="{{ $ogTitle ?? ($seoTitle ?? ($title . ' | ' . $eventName)) }}">
    <meta name="twitter:description" content="{{ $ogDesc ?? ($seoDesc ?? $message) }}">
    <meta name="twitter:image" content="{{ ($ogImage ?? url('/assets/img/og-thumbnail.jpg')) . ($ogImageVersion ?? '') }}">

    <!-- Structured Data (JSON-LD) untuk Search Engine Google -->
    <script type="application/ld+json">
    {!! json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'WebSite',
      'name' => $eventName ?? 'SEMARAK ALIYAH XIV 2026',
      'url' => $currentUrl ?? url()->current(),
      'description' => $seoDesc ?? ($message ?? ''),
      'publisher' => [
        '@type' => 'Organization',
        'name' => $schoolName ?? 'MA Unggulan Wahid Hasyim Balung',
      ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
    
    <!-- Local Offline Fonts & Icons -->
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/vendor/remixicon/remixicon.css">

    <style>
        :root {
            --theme-pink: #ec4899;
            --theme-orange: #f97316;
            --theme-green: #10b981;
            --theme-purple: #8b5cf6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #090e1c;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient Glow Background */
        .ambient-glow {
            position: fixed;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.25;
            pointer-events: none;
            z-index: 0;
        }

        .glow-1 {
            top: -80px;
            left: -80px;
            background: radial-gradient(circle, #8b5cf6 0%, #ec4899 100%);
        }

        .glow-2 {
            bottom: -80px;
            right: -80px;
            background: radial-gradient(circle, #f97316 0%, #10b981 100%);
        }

        .maintenance-card {
            position: relative;
            z-index: 10;
            max-width: 620px;
            width: 100%;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 28px;
            padding: 44px 36px;
            text-align: center;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7), 0 0 40px rgba(139, 92, 246, 0.12);
            animation: fadeInCard 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeInCard {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 8px 18px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 9999px;
            margin-bottom: 24px;
        }

        .brand-badge img {
            height: 28px;
            width: auto;
            object-fit: contain;
        }

        .brand-text {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            color: #ffffff;
        }

        .edition-badge {
            background: linear-gradient(135deg, #ec4899, #f97316);
            color: #ffffff;
            font-size: 0.62rem;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 9999px;
        }

        .illustration-wrap {
            position: relative;
            width: 90px;
            height: 90px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.15), rgba(139, 92, 246, 0.2));
            border: 1.5px solid rgba(249, 115, 22, 0.35);
        }

        .illustration-wrap i {
            font-size: 2.8rem;
            background: linear-gradient(135deg, #f97316 0%, #ec4899 50%, #8b5cf6 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: rotateGear 12s linear infinite;
        }

        @keyframes rotateGear {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .pulse-ring {
            position: absolute;
            inset: -6px;
            border-radius: 50%;
            border: 2px dashed rgba(249, 115, 22, 0.4);
            animation: pulseRing 3s ease-out infinite;
        }

        @keyframes pulseRing {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.08); opacity: 0.3; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: #fbbf24;
            font-size: 0.76rem;
            font-weight: 700;
            border-radius: 9999px;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #fbbf24;
            box-shadow: 0 0 8px #fbbf24;
            animation: blinkDot 1.5s infinite;
        }

        @keyframes blinkDot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .maintenance-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(1.15rem, 2.5vw, 1.35rem);
            font-weight: 700;
            color: #ffffff;
            line-height: 1.35;
            margin-bottom: 12px;
            letter-spacing: -0.01em;
        }

        .maintenance-desc {
            font-size: 0.92rem;
            line-height: 1.65;
            color: #cbd5e1;
            margin-bottom: 24px;
        }

        .estimate-box {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 28px;
            font-size: 0.86rem;
            color: #e2e8f0;
        }

        .estimate-box i {
            color: #38bdf8;
            font-size: 1.2rem;
        }

        .btn-group-action {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            max-width: 540px;
            margin: 0 auto;
        }

        .btn-wa-hotline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            background: #10b981;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.82rem;
            border-radius: 9999px;
            text-decoration: none;
            transition: all 0.25s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.35);
        }

        .btn-wa-hotline:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
            color: #ffffff;
        }

        /* Countdown Component Styling */
        .countdown-container {
            margin: 24px auto 26px;
            max-width: 480px;
            width: 100%;
        }

        .countdown-heading {
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .countdown-heading i {
            color: #f97316;
            font-size: 0.95rem;
        }

        .countdown-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .countdown-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 14px 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .countdown-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #ec4899, #f97316, #8b5cf6);
            opacity: 0.85;
        }

        .countdown-value {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: clamp(1.5rem, 3.8vw, 2.1rem);
            line-height: 1.1;
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-variant-numeric: tabular-nums;
        }

        .countdown-label {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: #f97316;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .countdown-ended-message {
            display: none;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.35);
            border-radius: 14px;
            padding: 12px 18px;
            color: #34d399;
            font-size: 0.85rem;
            font-weight: 700;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
        }

        .footer-note {
            margin-top: 32px;
            font-size: 0.76rem;
            color: #64748b;
        }

        @media (max-width: 576px) {
            .maintenance-card {
                padding: 30px 20px;
                border-radius: 20px;
            }
            .countdown-grid {
                gap: 8px;
            }
            .countdown-card {
                padding: 10px 4px;
                border-radius: 12px;
            }
            .countdown-value {
                font-size: 1.35rem;
            }
            .countdown-label {
                font-size: 0.58rem;
            }
            .btn-group-action {
                flex-direction: column;
                width: 100%;
            }
            .btn-wa-hotline {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="ambient-glow glow-1"></div>
    <div class="ambient-glow glow-2"></div>

    <div class="maintenance-card">
        <!-- Brand Badge -->
        <div class="brand-badge">
            <img src="/assets/img/logo.png" alt="Logo Semarak Aliyah">
            <span class="brand-text">SEMARAK ALIYAH <span class="edition-badge">XIV</span></span>
        </div>

        <!-- Animated Maintenance Illustration -->
        <div class="illustration-wrap">
            <div class="pulse-ring"></div>
            <i class="ri-settings-5-fill"></i>
        </div>

        <div class="status-pill">
            <span class="status-dot"></span>
            <span>Mode Pemeliharaan Aktif</span>
        </div>

        <h1 class="maintenance-title">{{ $title }}</h1>
        <p class="maintenance-desc">{{ $message }}</p>

        <!-- Real-Time Countdown Timer Component -->
        <div class="countdown-container" id="countdownSection">
            <div class="countdown-heading">
                <i class="ri-timer-flash-line"></i>
                <span>Hitung Mundur Selesai Pemeliharaan</span>
            </div>
            <div class="countdown-grid" id="countdownGrid">
                <div class="countdown-card">
                    <span class="countdown-value" id="cdDays">00</span>
                    <span class="countdown-label">Hari</span>
                </div>
                <div class="countdown-card">
                    <span class="countdown-value" id="cdHours">00</span>
                    <span class="countdown-label">Jam</span>
                </div>
                <div class="countdown-card">
                    <span class="countdown-value" id="cdMinutes">00</span>
                    <span class="countdown-label">Menit</span>
                </div>
                <div class="countdown-card">
                    <span class="countdown-value" id="cdSeconds">00</span>
                    <span class="countdown-label">Detik</span>
                </div>
            </div>
            <div class="countdown-ended-message" id="cdEndedMsg">
                <i class="ri-refresh-line" style="animation: rotateGear 2s linear infinite;"></i>
                <span>Waktu estimasi selesai. Sistem sedang sinkronisasi akhir untuk segera dibuka kembali.</span>
            </div>
        </div>

        @if(!empty($estimatedEnd))
            <div class="estimate-box">
                <i class="ri-time-line"></i>
                <span>Estimasi Selesai: <strong>{{ $estimatedEnd }}</strong></span>
            </div>
        @endif

        <div class="btn-group-action">
            @php
                $contacts = $waContacts ?? [];
                if (empty($contacts) && !empty($emergencyWa)) {
                    $contacts = [['label' => 'Hubungi Hotline Panitia', 'number' => $emergencyWa]];
                }
            @endphp
            @foreach($contacts as $idx => $wa)
                @php
                    $rawNum = $wa['number'] ?? '';
                    $cleanWa = \App\Models\Setting::formatWaNumber($rawNum, '');
                    $waLabel = !empty($wa['label']) ? $wa['label'] : (count($contacts) > 1 ? 'Hotline Panitia ' . ($idx + 1) : 'Hubungi Hotline Panitia');
                    $waUrl = \App\Models\Setting::getWaUrl($rawNum, 'Halo Panitia Semarak Aliyah 2026, saya ingin bertanya seputar informasi pendaftaran', '');
                @endphp
                @if(!empty($cleanWa))
                    <a 
                        href="{{ $waUrl }}" 
                        target="_blank" 
                        rel="noopener" 
                        class="btn-wa-hotline"
                    >
                        <i class="ri-whatsapp-line fs-18"></i>
                        <span>{{ $waLabel }} ({{ $rawNum }})</span>
                    </a>
                @endif
            @endforeach
        </div>

        <div class="footer-note">
            {{ $schoolName }} • {{ $eventName }}
        </div>
    </div>

    <script>
    (function() {
        var rawTarget = "{{ $countdownTarget ?? '' }}";
        var cdEnded = document.getElementById('cdEndedMsg');
        var cdGrid = document.getElementById('countdownGrid');
        var daysEl = document.getElementById('cdDays');
        var hoursEl = document.getElementById('cdHours');
        var minsEl = document.getElementById('cdMinutes');
        var secsEl = document.getElementById('cdSeconds');

        var targetTime = null;
        if (rawTarget && rawTarget.trim() !== '') {
            var parsed = new Date(rawTarget).getTime();
            if (!isNaN(parsed)) {
                targetTime = parsed;
            }
        }

        // Fallback target: jika belum diatur, gunakan 2 jam ke depan dari sesi pertama
        if (!targetTime) {
            var saved = localStorage.getItem('semarak_mt_target_ts');
            if (saved && !isNaN(parseInt(saved)) && parseInt(saved) > Date.now()) {
                targetTime = parseInt(saved);
            } else {
                targetTime = Date.now() + (2 * 60 * 60 * 1000);
                try { localStorage.setItem('semarak_mt_target_ts', targetTime); } catch(e) {}
            }
        }

        function padZero(num) {
            return num < 10 ? '0' + num : num;
        }

        function updateCountdown() {
            var now = Date.now();
            var diff = targetTime - now;

            if (diff <= 0) {
                daysEl.innerText = '00';
                hoursEl.innerText = '00';
                minsEl.innerText = '00';
                secsEl.innerText = '00';
                if (cdEnded) cdEnded.style.display = 'inline-flex';
                return;
            }

            var d = Math.floor(diff / (1000 * 60 * 60 * 24));
            var h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            var s = Math.floor((diff % (1000 * 60)) / 1000);

            daysEl.innerText = padZero(d);
            hoursEl.innerText = padZero(h);
            minsEl.innerText = padZero(m);
            secsEl.innerText = padZero(s);
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    })();
    </script>
</body>
</html>
