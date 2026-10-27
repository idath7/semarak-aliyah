@php
    $bgImage = \App\Models\Setting::get('admin_login_bg_image', '/assets/images/auth-one-bg.jpg');
    $bgOverlay = \App\Models\Setting::get('admin_login_bg_overlay_type', 'gradient_velzon');
    $bgOpacity = (int)\App\Models\Setting::get('admin_login_bg_overlay_opacity', 85);
    $bgBlur = (int)\App\Models\Setting::get('admin_login_bg_blur', 0);
    $showWave = filter_var(\App\Models\Setting::get('admin_login_show_wave', '1'), FILTER_VALIDATE_BOOLEAN);
    $eventName = \App\Models\Setting::get('event_name', 'SEMARAK ALIYAH XIV 2026');
    $schoolName = \App\Models\Setting::get('school_name', 'MA Unggulan Wahid Hasyim Balung');

    // Inisial Nama User
    $words = explode(' ', trim($user->name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $w) {
        $initials .= mb_strtoupper(mb_substr($w, 0, 1));
    }
    if (empty($initials)) {
        $initials = 'P';
    }

    $isMandatory = $isMandatory ?? ($user->must_change_password || empty($user->password));
@endphp
<!doctype html>
<html lang="id" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <meta name="color-scheme" content="light dark">
    <title>Buat Kata Sandi Baru | {{ $eventName }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Buat Kata Sandi Baru Panitia {{ $eventName }} - {{ $schoolName }}" name="description" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    <link rel="shortcut icon" href="/assets/img/logo.png">

    <!-- Local Offline Fonts & Icons (100% Offline-First) -->
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/vendor/remixicon/remixicon.css">
    <link rel="stylesheet" href="/assets/vendor/boxicons/css/boxicons.min.css">

    <!-- Laravel Vite Bundling -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --vz-primary: #405189;
            --vz-primary-rgb: 64, 81, 137;
            --vz-success: #0ab39c;
            --vz-success-rgb: 10, 179, 156;
            --vz-info: #299cdb;
            --vz-warning: #f7b84b;
            --vz-danger: #f06548;
            --vz-body-bg: #f3f3f9;
            --vz-card-bg: #ffffff;
            --vz-border-color: #e2e8f0;
            --vz-body-color: #0f172a;
            --vz-muted-color: #475569;
        }

        [data-bs-theme="dark"] {
            --vz-body-bg: #0b1120;
            --vz-card-bg: #1e293b;
            --vz-border-color: #334155;
            --vz-body-color: #f8fafc;
            --vz-muted-color: #94a3b8;
        }

        body {
            background-color: var(--vz-body-bg) !important;
            font-family: 'Poppins', sans-serif !important;
            color: var(--vz-body-color) !important;
            margin: 0;
            padding: 0;
            transition: background-color 0.25s ease, color 0.25s ease;
        }

        .auth-page-wrapper {
            min-height: 100vh;
            position: relative;
            background-color: var(--vz-body-bg);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Hero Banner Background & Overlay */
        .auth-one-bg-position {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: 440px;
            overflow: hidden;
            z-index: 0;
        }

        .auth-one-bg {
            background-image: url("{{ $bgImage }}") !important;
            background-position: center !important;
            background-size: cover !important;
            background-repeat: no-repeat !important;
            @if($bgBlur > 0)
            filter: blur({{ $bgBlur }}px);
            transform: scale(1.03);
            @endif
        }

        .auth-one-bg .bg-overlay {
            position: absolute;
            height: 100%;
            width: 100%;
            right: 0;
            bottom: 0;
            left: 0;
            top: 0;
            @if($bgOverlay === 'dark')
            background: rgba(15, 23, 42, {{ (float)$bgOpacity / 100 }}) !important;
            @elseif($bgOverlay === 'emerald')
            background: linear-gradient(135deg, rgba(6, 78, 59, {{ (float)$bgOpacity / 100 }}), rgba(4, 120, 87, {{ (float)$bgOpacity / 100 }})) !important;
            @elseif($bgOverlay === 'gold')
            background: linear-gradient(135deg, rgba(120, 53, 15, {{ (float)$bgOpacity / 100 }}), rgba(180, 83, 9, {{ (float)$bgOpacity / 100 }})) !important;
            @elseif($bgOverlay === 'none')
            background: transparent !important;
            @else
            background: linear-gradient(135deg, rgba(54, 69, 116, {{ (float)$bgOpacity / 100 }}), rgba(10, 179, 156, {{ (float)$bgOpacity / 100 }})) !important;
            @endif
        }

        .auth-one-bg .shape {
            position: absolute;
            bottom: 0;
            right: 0;
            left: 0;
            z-index: 1;
            pointer-events: none;
        }

        .auth-one-bg .shape > svg {
            width: 100%;
            height: auto;
            fill: var(--vz-body-bg) !important;
            display: block;
            transition: fill 0.25s ease;
        }

        .auth-page-content {
            position: relative;
            z-index: 2;
            width: 100%;
            padding-bottom: 2.5rem;
        }

        /* Topbar Controls (Theme Switcher & Quick Navigation) */
        .auth-topbar-controls {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-auth-control {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #ffffff !important;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .btn-auth-control:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateY(-2px);
            color: #ffffff !important;
        }

        /* Velzon Header & Branding */
        .auth-logo {
            display: inline-flex !important;
            align-items: center !important;
            gap: 14px !important;
            text-decoration: none !important;
        }

        .auth-logo img {
            height: 60px !important;
            width: 60px !important;
            max-height: 60px !important;
            max-width: 60px !important;
            object-fit: contain !important;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
        }

        .auth-logo-text {
            font-size: 1.45rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.04em !important;
            color: #ffffff !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.35);
        }

        .auth-header-subtitle {
            color: rgba(255, 255, 255, 0.92) !important;
            font-size: 14.5px !important;
            font-weight: 600 !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
            margin-top: 0.6rem;
        }

        /* Main Card Container */
        .card.card-bg-fill {
            background-color: var(--vz-card-bg) !important;
            border: 1px solid var(--vz-border-color) !important;
            border-radius: 0.75rem !important;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.12), 0 4px 12px -2px rgba(15, 23, 42, 0.08) !important;
            overflow: hidden;
            transition: all 0.25s ease;
        }

        .card-body {
            padding: 2.25rem !important;
        }

        @media (max-width: 576px) {
            .card-body {
                padding: 1.5rem !important;
            }
        }

        /* Top Accent Bar on Card */
        .card-accent-bar {
            height: 4px;
            background: linear-gradient(90deg, #405189 0%, #0ab39c 100%);
            width: 100%;
        }

        /* Avatar Header Badge */
        .avatar-pass-badge {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(10, 179, 156, 0.15) 0%, rgba(64, 81, 137, 0.15) 100%);
            border: 2px solid rgba(10, 179, 156, 0.4);
            color: #0ab39c;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            box-shadow: 0 4px 12px rgba(10, 179, 156, 0.15);
            transition: transform 0.2s ease;
        }

        [data-bs-theme="dark"] .avatar-pass-badge {
            background: linear-gradient(135deg, rgba(10, 179, 156, 0.25) 0%, rgba(64, 81, 137, 0.25) 100%);
            border-color: rgba(10, 179, 156, 0.6);
            color: #38ef7d;
            box-shadow: 0 0 16px rgba(10, 179, 156, 0.3);
        }

        .page-main-title {
            color: #1e293b;
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        [data-bs-theme="dark"] .page-main-title {
            color: #f8fafc !important;
        }

        .page-main-subtitle {
            color: #475569;
            font-size: 13.5px;
            line-height: 1.55;
            margin-bottom: 1.25rem;
        }

        [data-bs-theme="dark"] .page-main-subtitle {
            color: #cbd5e1 !important;
        }

        /* Verified Panitia Profile Strip */
        .panitia-profile-strip {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.6rem;
            padding: 0.85rem 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            transition: all 0.2s ease;
        }

        [data-bs-theme="dark"] .panitia-profile-strip {
            background: rgba(15, 23, 42, 0.6);
            border-color: #334155;
        }

        .panitia-avatar-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #405189 0%, #0ab39c 100%);
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(64, 81, 137, 0.3);
        }

        .panitia-info-name {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 220px;
        }

        [data-bs-theme="dark"] .panitia-info-name {
            color: #f8fafc !important;
        }

        .panitia-info-meta {
            font-size: 11.5px;
            color: #64748b;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
        }

        [data-bs-theme="dark"] .panitia-info-meta {
            color: #94a3b8 !important;
        }

        /* High-Contrast Badges */
        .badge-role-admin {
            background-color: #eff6ff !important;
            color: #1d4ed8 !important;
            border: 1px solid #bfdbfe !important;
            font-weight: 700 !important;
            font-size: 11px !important;
            letter-spacing: 0.5px;
            padding: 4px 8px;
            border-radius: 4px;
        }

        [data-bs-theme="dark"] .badge-role-admin {
            background-color: rgba(37, 99, 235, 0.2) !important;
            color: #93c5fd !important;
            border-color: rgba(96, 165, 250, 0.4) !important;
        }

        /* Form Labels & Controls */
        .form-label {
            font-size: 13px !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            margin-bottom: 0.45rem;
            display: flex;
            align-items: center;
        }

        [data-bs-theme="dark"] .form-label {
            color: #f1f5f9 !important;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 0.65rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.5;
            color: #0f172a !important;
            background-color: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 0.45rem;
            transition: all 0.2s ease-in-out;
        }

        .form-control::placeholder {
            color: #94a3b8 !important;
            font-weight: 400;
        }

        .form-control:focus {
            color: #0f172a !important;
            background-color: #ffffff;
            border-color: #0ab39c;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(10, 179, 156, 0.2);
        }

        [data-bs-theme="dark"] .form-control {
            background-color: #0f172a !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }

        [data-bs-theme="dark"] .form-control::placeholder {
            color: #64748b !important;
        }

        [data-bs-theme="dark"] .form-control:focus {
            background-color: #0f172a !important;
            border-color: #0ab39c !important;
            color: #ffffff !important;
            box-shadow: 0 0 0 0.2rem rgba(10, 179, 156, 0.3) !important;
        }

        /* Password Addon */
        .auth-pass-inputgroup {
            position: relative;
        }

        .auth-pass-inputgroup input {
            padding-right: 3rem !important;
        }

        .password-addon {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 14px;
            background: transparent;
            border: none;
            color: #64748b !important;
            cursor: pointer;
            z-index: 4;
            font-size: 1.15rem;
            transition: color 0.15s ease-in-out;
        }

        .password-addon:hover {
            color: #0ab39c !important;
        }

        [data-bs-theme="dark"] .password-addon {
            color: #94a3b8 !important;
        }

        [data-bs-theme="dark"] .password-addon:hover {
            color: #38ef7d !important;
        }

        /* Dynamic Password Strength Bar */
        .strength-meter-wrapper {
            margin-top: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .strength-meter-bar {
            height: 6px;
            width: 100%;
            background-color: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            display: flex;
        }

        [data-bs-theme="dark"] .strength-meter-bar {
            background-color: #334155;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
            border-radius: 999px;
        }

        .strength-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11.5px;
            margin-top: 4px;
            color: #64748b;
        }

        [data-bs-theme="dark"] .strength-label {
            color: #94a3b8;
        }

        .strength-text {
            font-weight: 700;
        }

        /* Live Checklist Pills */
        .pass-rules-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.45rem;
            padding: 0.65rem 0.85rem;
            margin-bottom: 1rem;
        }

        [data-bs-theme="dark"] .pass-rules-box {
            background: rgba(15, 23, 42, 0.4);
            border-color: #334155;
        }

        .rule-item {
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
            color: #64748b;
            transition: color 0.2s ease;
        }

        .rule-item:last-child {
            margin-bottom: 0;
        }

        [data-bs-theme="dark"] .rule-item {
            color: #94a3b8;
        }

        .rule-item.valid {
            color: #0ab39c !important;
            font-weight: 600;
        }

        [data-bs-theme="dark"] .rule-item.valid {
            color: #38ef7d !important;
        }

        .rule-icon {
            font-size: 14px;
        }

        /* Buttons */
        .btn-success {
            background-color: #0ab39c !important;
            border-color: #0ab39c !important;
            color: #ffffff !important;
            font-weight: 600;
            padding: 0.75rem 1.25rem;
            font-size: 0.95rem;
            border-radius: 0.45rem;
            box-shadow: 0 4px 12px rgba(10, 179, 156, 0.3);
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }

        .btn-success:hover, .btn-success:focus {
            background-color: #099885 !important;
            border-color: #088f7d !important;
            box-shadow: 0 6px 16px rgba(10, 179, 156, 0.45);
            transform: translateY(-1px);
        }

        .btn-secondary-action {
            color: #64748b !important;
            font-size: 12.5px !important;
            font-weight: 500;
            text-decoration: none !important;
            transition: color 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
        }

        .btn-secondary-action:hover {
            color: #0f172a !important;
            text-decoration: underline !important;
        }

        [data-bs-theme="dark"] .btn-secondary-action {
            color: #94a3b8 !important;
        }

        [data-bs-theme="dark"] .btn-secondary-action:hover {
            color: #f8fafc !important;
        }

        /* Alert Callouts */
        .alert-info-custom {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            border-radius: 0.45rem;
            padding: 0.65rem 0.85rem;
            font-size: 12.5px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.5;
        }

        [data-bs-theme="dark"] .alert-info-custom {
            background-color: rgba(30, 64, 175, 0.15);
            border-color: rgba(96, 165, 250, 0.3);
            color: #93c5fd;
        }

        .alert-danger-custom {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 0.45rem;
            padding: 0.65rem 0.85rem;
            font-size: 12.5px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.5;
        }

        [data-bs-theme="dark"] .alert-danger-custom {
            background-color: rgba(185, 28, 28, 0.2);
            border-color: rgba(248, 113, 113, 0.35);
            color: #fca5a5;
        }

        /* Footer */
        .auth-footer {
            color: var(--vz-muted-color);
            font-size: 12px;
            text-align: center;
            padding: 1.5rem 1rem;
        }
    </style>
</head>

<body>
    <div class="auth-page-wrapper">
        <!-- Topbar Theme Switcher -->
        <div class="auth-topbar-controls">
            <button type="button" class="btn-auth-control" id="theme-toggle-btn" onclick="toggleTheme()" title="Ganti Mode Terang / Gelap" aria-label="Toggle Dark Mode">
                <i class="ri-moon-line" id="theme-toggle-icon"></i>
            </button>
        </div>

        <!-- Hero Background Banner -->
        <div class="auth-one-bg-position auth-one-bg" id="auth-particles">
            <div class="bg-overlay"></div>
            @if($showWave)
            <div class="shape">
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 1440 120">
                    <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
                </svg>
            </div>
            @endif
        </div>

        <!-- Auth Page Content -->
        <div class="auth-page-content">
            <div class="container">
                <!-- Branding Header -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center mt-sm-4 mb-4">
                            <div>
                                <a href="/" class="auth-logo">
                                    <img src="/assets/img/logo.png" alt="Logo Semarak">
                                    <span class="auth-logo-text">{{ $eventName }}</span>
                                </a>
                            </div>
                            <p class="auth-header-subtitle">Panel Admin Panitia • {{ $schoolName }}</p>
                        </div>
                    </div>
                </div>

                <!-- Main Card Box -->
                <div class="row justify-content-center">
                    <div class="col-md-9 col-lg-7 col-xl-5">
                        <div class="card card-bg-fill">
                            <div class="card-accent-bar"></div>
                            <div class="card-body">
                                
                                <!-- Icon & Header Title -->
                                <div class="text-center">
                                    <div class="avatar-pass-badge">
                                        <i class="ri-shield-keyhole-line fs-32"></i>
                                    </div>

                                    @if($isMandatory)
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1 mb-2 fs-11 fw-semibold">
                                        <i class="ri-alert-line me-1"></i>Aktivasi Akun Pertama Kali
                                    </span>
                                    @else
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 mb-2 fs-11 fw-semibold">
                                        <i class="ri-shield-user-line me-1"></i>Keamanan Akun Panitia
                                    </span>
                                    @endif

                                    <h4 class="page-main-title">Buat Kata Sandi Baru</h4>
                                    <p class="page-main-subtitle">
                                        @if($isMandatory)
                                        Halo <strong>{{ $user->name }}</strong>, akun Anda telah aktif. Silakan tentukan kata sandi baru untuk login mandiri ke sistem.
                                        @else
                                        Halo <strong>{{ $user->name }}</strong>, silakan buat kata sandi baru untuk mempermudah login Anda selanjutnya.
                                        @endif
                                    </p>
                                </div>

                                <!-- Ringkasan Akun Panitia -->
                                <div class="panitia-profile-strip">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="panitia-avatar-circle">
                                            {{ $initials }}
                                        </div>
                                        <div>
                                            <div class="panitia-info-name" title="{{ $user->name }}">
                                                {{ $user->name }}
                                            </div>
                                            <div class="panitia-info-meta">
                                                <span><i class="ri-mail-line align-middle me-0.5"></i>{{ $user->email }}</span>
                                                @if($user->nip)
                                                <span>•</span>
                                                <span><i class="ri-id-card-line align-middle me-0.5"></i>{{ $user->nip }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge-role-admin">
                                            {{ strtoupper($user->role) }}
                                        </span>
                                    </div>
                                </div>

                                @if(session('info'))
                                    <div class="alert-info-custom mb-3">
                                        <i class="ri-information-line fs-16 flex-shrink-0 mt-0.5"></i>
                                        <div>{{ session('info') }}</div>
                                    </div>
                                @endif

                                @if($errors->any())
                                    <div class="alert-danger-custom mb-3">
                                        <i class="ri-error-warning-line fs-16 flex-shrink-0 mt-0.5"></i>
                                        <div>{{ $errors->first() }}</div>
                                    </div>
                                @endif

                                <!-- Form Buat Sandi -->
                                <form action="{{ route('admin.password.first.store') }}" method="POST" id="form-buat-password">
                                    @csrf

                                    <!-- Input 1: Kata Sandi Baru -->
                                    <div class="mb-3">
                                        <label class="form-label" for="pass-input">
                                            <i class="ri-lock-2-line text-primary me-1 fs-15"></i>
                                            <span>Kata Sandi Baru <span class="text-danger">*</span></span>
                                        </label>
                                        <div class="auth-pass-inputgroup">
                                            <input 
                                                type="password" 
                                                name="password" 
                                                class="form-control" 
                                                id="pass-input" 
                                                placeholder="Minimal 6 karakter" 
                                                required 
                                                autofocus
                                                autocomplete="new-password"
                                                oninput="checkPasswordStrength()"
                                            />
                                            <button class="password-addon" type="button" onclick="togglePassVisibility('pass-input', 'eye-icon-1')" aria-label="Tampilkan / sembunyikan kata sandi">
                                                <i class="ri-eye-line" id="eye-icon-1"></i>
                                            </button>
                                        </div>

                                        <!-- Password Strength Indicator -->
                                        <div class="strength-meter-wrapper" id="strength-wrapper" style="display: none;">
                                            <div class="strength-meter-bar">
                                                <div class="strength-fill" id="strength-fill"></div>
                                            </div>
                                            <div class="strength-label">
                                                <span>Kekuatan Kata Sandi:</span>
                                                <span class="strength-text" id="strength-text">-</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Input 2: Konfirmasi Kata Sandi -->
                                    <div class="mb-3">
                                        <label class="form-label" for="pass-confirm-input">
                                            <i class="ri-lock-password-line text-success me-1 fs-15"></i>
                                            <span>Ulangi Kata Sandi Baru <span class="text-danger">*</span></span>
                                        </label>
                                        <div class="auth-pass-inputgroup">
                                            <input 
                                                type="password" 
                                                name="password_confirmation" 
                                                class="form-control" 
                                                id="pass-confirm-input" 
                                                placeholder="Ketik ulang kata sandi baru" 
                                                required
                                                autocomplete="new-password"
                                                oninput="checkPasswordStrength()"
                                            />
                                            <button class="password-addon" type="button" onclick="togglePassVisibility('pass-confirm-input', 'eye-icon-2')" aria-label="Tampilkan / sembunyikan konfirmasi kata sandi">
                                                <i class="ri-eye-line" id="eye-icon-2"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Live Password Rules Checklist -->
                                    <div class="pass-rules-box">
                                        <div class="rule-item" id="rule-length">
                                            <i class="ri-checkbox-blank-circle-line rule-icon" id="rule-icon-length"></i>
                                            <span>Minimal 6 karakter</span>
                                        </div>
                                        <div class="rule-item" id="rule-combo">
                                            <i class="ri-checkbox-blank-circle-line rule-icon" id="rule-icon-combo"></i>
                                            <span>Kombinasi huruf dan angka disarankan</span>
                                        </div>
                                        <div class="rule-item" id="rule-match">
                                            <i class="ri-checkbox-blank-circle-line rule-icon" id="rule-icon-match"></i>
                                            <span>Konfirmasi kata sandi cocok</span>
                                        </div>
                                    </div>

                                    <!-- Submit Action -->
                                    <div class="d-grid mt-4">
                                        <button class="btn btn-success" type="submit" id="btn-submit-pass">
                                            <i class="ri-shield-check-line align-middle me-1 fs-16"></i>
                                            <span id="btn-submit-text">Simpan Kata Sandi & Masuk Dashboard</span>
                                        </button>
                                    </div>
                                </form>

                                <!-- Secondary Action -->
                                <div class="mt-4 pt-2 text-center border-top">
                                    @if($isMandatory)
                                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-link btn-secondary-action">
                                                <i class="ri-logout-box-r-line"></i>
                                                <span>Bukan akun Anda? Keluar / Ganti Akun</span>
                                            </button>
                                        </form>
                                    @else
                                        <div class="d-flex justify-content-center align-items-center gap-3">
                                            <a href="{{ route('admin.dashboard') }}" class="btn btn-link btn-secondary-action">
                                                <i class="ri-arrow-left-line"></i>
                                                <span>Kembali ke Dashboard</span>
                                            </a>
                                            <span class="text-muted">•</span>
                                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-link btn-secondary-action">
                                                    <i class="ri-logout-box-r-line"></i>
                                                    <span>Keluar</span>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer -->
        <footer class="auth-footer">
            &copy; {{ date('Y') }} {{ $eventName }} • {{ $schoolName }}
        </footer>
    </div>

    <!-- Client-Side Scripts (Eye Toggle, Strength Meter, Dark Mode) -->
    <script>
        // 1. Password Visibility Toggle
        function togglePassVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'ri-eye-off-line';
            } else {
                input.type = 'password';
                icon.className = 'ri-eye-line';
            }
        }

        // 2. Dynamic Password Strength & Requirements Validation
        function checkPasswordStrength() {
            const pass = document.getElementById('pass-input').value;
            const confirm = document.getElementById('pass-confirm-input').value;
            const wrapper = document.getElementById('strength-wrapper');
            const fill = document.getElementById('strength-fill');
            const text = document.getElementById('strength-text');

            const ruleLength = document.getElementById('rule-length');
            const iconLength = document.getElementById('rule-icon-length');
            const ruleCombo = document.getElementById('rule-combo');
            const iconCombo = document.getElementById('rule-icon-combo');
            const ruleMatch = document.getElementById('rule-match');
            const iconMatch = document.getElementById('rule-icon-match');

            if (pass.length > 0) {
                wrapper.style.display = 'block';
            } else {
                wrapper.style.display = 'none';
            }

            // Rule 1: Min 6 characters
            const isLengthOk = pass.length >= 6;
            updateRuleState(ruleLength, iconLength, isLengthOk);

            // Rule 2: Letters + Numbers
            const hasLetters = /[a-zA-Z]/.test(pass);
            const hasNumbers = /[0-9]/.test(pass);
            const isComboOk = hasLetters && hasNumbers;
            updateRuleState(ruleCombo, iconCombo, isComboOk);

            // Rule 3: Passwords match
            const isMatchOk = pass.length > 0 && pass === confirm;
            updateRuleState(ruleMatch, iconMatch, isMatchOk);

            // Strength calculation
            let score = 0;
            if (pass.length >= 6) score += 25;
            if (pass.length >= 10) score += 25;
            if (hasLetters && hasNumbers) score += 25;
            if (/[^a-zA-Z0-9]/.test(pass)) score += 25;

            if (pass.length === 0) {
                fill.style.width = '0%';
                text.textContent = '-';
                text.style.color = '#64748b';
            } else if (score <= 25) {
                fill.style.width = '25%';
                fill.style.backgroundColor = '#ef4444'; // Red
                text.textContent = 'Kurang (Terlalu Pendek)';
                text.style.color = '#ef4444';
            } else if (score <= 50) {
                fill.style.width = '50%';
                fill.style.backgroundColor = '#f59e0b'; // Amber
                text.textContent = 'Cukup';
                text.style.color = '#f59e0b';
            } else if (score <= 75) {
                fill.style.width = '75%';
                fill.style.backgroundColor = '#3b82f6'; // Blue
                text.textContent = 'Baik & Aman';
                text.style.color = '#3b82f6';
            } else {
                fill.style.width = '100%';
                fill.style.backgroundColor = '#10b981'; // Emerald
                text.textContent = 'Sangat Kuat';
                text.style.color = '#10b981';
            }
        }

        function updateRuleState(elem, iconElem, isValid) {
            if (!elem || !iconElem) return;
            if (isValid) {
                elem.classList.add('valid');
                iconElem.className = 'ri-checkbox-circle-fill rule-icon';
            } else {
                elem.classList.remove('valid');
                iconElem.className = 'ri-checkbox-blank-circle-line rule-icon';
            }
        }

        // 3. Theme Toggle (Light / Dark)
        function initTheme() {
            const saved = localStorage.getItem('semarak_admin_theme') || 'light';
            applyTheme(saved);
        }

        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            applyTheme(next);
            localStorage.setItem('semarak_admin_theme', next);
        }

        function applyTheme(theme) {
            document.documentElement.setAttribute('data-bs-theme', theme);
            const icon = document.getElementById('theme-toggle-icon');
            if (icon) {
                if (theme === 'dark') {
                    icon.className = 'ri-sun-line';
                } else {
                    icon.className = 'ri-moon-line';
                }
            }
        }

        // 4. Form Submit Spinner
        document.getElementById('form-buat-password').addEventListener('submit', function (e) {
            const btn = document.getElementById('btn-submit-pass');
            const text = document.getElementById('btn-submit-text');
            btn.disabled = true;
            text.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menyimpan kata sandi...';
        });

        // Jalankan inisialisasi tema saat halaman dimuat
        document.addEventListener('DOMContentLoaded', initTheme);
    </script>
</body>
</html>
