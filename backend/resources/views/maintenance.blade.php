<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} | {{ $eventName }}</title>
    <link rel="icon" type="image/png" href="/assets/img/logo.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Syne:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.css" rel="stylesheet" />

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
            font-family: 'Syne', sans-serif;
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
            font-family: 'Outfit', sans-serif;
            font-size: clamp(1.4rem, 4vw, 1.85rem);
            font-weight: 800;
            color: #ffffff;
            line-height: 1.25;
            margin-bottom: 14px;
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
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-wa-hotline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            background: #10b981;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.86rem;
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

        .btn-login-panitia {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #cbd5e1;
            font-weight: 600;
            font-size: 0.86rem;
            border-radius: 9999px;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .btn-login-panitia:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.3);
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
            .btn-group-action {
                flex-direction: column;
                width: 100%;
            }
            .btn-wa-hotline, .btn-login-panitia {
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
            <span class="brand-text">SEMARAK ALIYAH <span class="edition-badge">XII</span></span>
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

        @if(!empty($estimatedEnd))
            <div class="estimate-box">
                <i class="ri-time-line"></i>
                <span>Estimasi Selesai: <strong>{{ $estimatedEnd }}</strong></span>
            </div>
        @endif

        <div class="btn-group-action">
            @php
                $cleanWa = preg_replace('/[^0-9]/', '', $emergencyWa);
                if (str_starts_with($cleanWa, '0')) {
                    $cleanWa = '62' . substr($cleanWa, 1);
                }
            @endphp
            @if(!empty($emergencyWa))
                <a 
                    href="https://wa.me/{{ $cleanWa }}?text=Halo%20Panitia%20Semarak%20Aliyah%202026%2C%20saya%20ingin%20bertanya%20seputar%20informasi%20pendaftaran" 
                    target="_blank" 
                    rel="noopener" 
                    class="btn-wa-hotline"
                >
                    <i class="ri-whatsapp-line fs-18"></i>
                    <span>Hubungi Hotline Panitia</span>
                </a>
            @endif

            <a href="/login" class="btn-login-panitia">
                <i class="ri-lock-2-line"></i>
                <span>Masuk Panel Panitia</span>
            </a>
        </div>

        <div class="footer-note">
            {{ $schoolName }} • Semarak Aliyah XII 2026
        </div>
    </div>
</body>
</html>
