@php
    $bgImage = \App\Models\Setting::get('admin_login_bg_image', '/assets/images/auth-one-bg.jpg');
    $bgOverlay = \App\Models\Setting::get('admin_login_bg_overlay_type', 'gradient_velzon');
    $bgOpacity = (int)\App\Models\Setting::get('admin_login_bg_overlay_opacity', 85);
    $bgBlur = (int)\App\Models\Setting::get('admin_login_bg_blur', 0);
    $showWave = filter_var(\App\Models\Setting::get('admin_login_show_wave', '1'), FILTER_VALIDATE_BOOLEAN);
    $particlesActive = filter_var(\App\Models\Setting::get('admin_login_particles_active', '1'), FILTER_VALIDATE_BOOLEAN);
    $loginTitle = \App\Models\Setting::get('admin_login_title', 'Selamat Datang Kembali !');
    $loginSubtitle = \App\Models\Setting::get('admin_login_subtitle', 'Masuk untuk melanjutkan ke Panel Semarak Aliyah.');
    $eventName = \App\Models\Setting::get('event_name', 'SEMARAK ALIYAH XIV 2026');
    $schoolName = \App\Models\Setting::get('school_name', 'MA Unggulan Wahid Hasyim Balung');
    $recaptchaEnabled = filter_var(\App\Models\Setting::get('admin_login_recaptcha_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
    $recaptchaSiteKey = \App\Models\Setting::get('recaptcha_site_key', env('RECAPTCHA_SITE_KEY', ''));
    $recaptchaVersion = \App\Models\Setting::get('recaptcha_version', 'v2_checkbox');
@endphp
<!doctype html>
<html lang="id" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <title>Masuk Panel Panitia | {{ $eventName }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Panel Admin Panitia {{ $eventName }} - {{ $schoolName }}" name="description" />
    <meta content="Panitia Semarak Aliyah" name="author" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    <link rel="shortcut icon" href="/assets/img/logo.png">

    <!-- Google Fonts (Poppins - Velzon Official Typography) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Laravel Vite Asset Bundling -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if($recaptchaEnabled && !empty($recaptchaSiteKey))
    <!-- Google reCAPTCHA API -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif

    <!-- Official Velzon Auth CSS Stylesheet & High-Contrast Readability Overrides -->
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
            --vz-border-color: #e9ebec;
            --vz-body-color: #212529;
            --vz-muted-color: #495057;
        }

        body {
            background-color: #f3f3f9 !important;
            font-family: 'Poppins', sans-serif !important;
            color: #212529 !important;
            font-size: 0.8125rem;
            margin: 0;
            padding: 0;
        }

        .auth-page-wrapper {
            min-height: 100vh;
            position: relative;
            background-color: #f3f3f9;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .auth-one-bg-position {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: 420px;
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
            background: linear-gradient(to right, rgba(54, 69, 116, {{ (float)$bgOpacity / 100 }}), rgba(64, 81, 137, {{ (float)$bgOpacity / 100 }})) !important;
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
            fill: #f3f3f9 !important;
            display: block;
        }

        .auth-page-content {
            position: relative;
            z-index: 2;
            width: 100%;
            padding-bottom: 2rem;
        }

        /* Velzon Header & Logo (Enlarged & Prominent) */
        .auth-logo {
            display: inline-flex !important;
            align-items: center !important;
            gap: 14px !important;
            text-decoration: none !important;
        }

        .auth-logo img {
            height: 64px !important;
            width: 64px !important;
            max-height: 64px !important;
            max-width: 64px !important;
            object-fit: contain !important;
            display: inline-block !important;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
        }

        .auth-logo-text {
            font-size: 1.45rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.05em !important;
            color: #ffffff !important;
            vertical-align: middle;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.35);
        }

        .auth-header-subtitle {
            color: rgba(255, 255, 255, 0.92) !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
            margin-top: 0.75rem;
        }

        /* Velzon Card */
        .card.card-bg-fill {
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08) !important;
            margin-bottom: 1.5rem;
        }

        .card-body {
            padding: 2.25rem !important;
        }

        /* High-Contrast Typography */
        .text-primary {
            color: #405189 !important;
        }

        .signin-title {
            color: #405189 !important;
            font-size: 1.25rem !important;
            font-weight: 700 !important;
        }

        .signin-subtitle {
            color: #334155 !important;
            font-size: 13.5px !important;
            font-weight: 500 !important;
            margin-top: 4px;
        }

        .forgot-password-link {
            color: #405189 !important;
            font-size: 12.5px !important;
            font-weight: 600 !important;
            text-decoration: underline !important;
            text-underline-offset: 2px;
            transition: color 0.15s ease-in-out;
        }

        .forgot-password-link:hover {
            color: #2b395d !important;
        }

        .form-label {
            margin-bottom: 0.45rem;
            font-size: 13px !important;
            font-weight: 600 !important;
            color: #1e293b !important;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 0.55rem 0.95rem;
            font-size: 0.85rem;
            font-weight: 500;
            line-height: 1.5;
            color: #0f172a !important;
            background-color: #fff;
            background-clip: padding-box;
            border: 1.5px solid #cbd5e1;
            border-radius: 0.35rem;
            transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        }

        .form-control::placeholder {
            color: #94a3b8 !important;
            font-weight: 400;
        }

        .form-control:focus {
            color: #0f172a !important;
            background-color: #fff;
            border-color: #6366f1;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.2);
        }

        /* Password Input Group */
        .auth-pass-inputgroup {
            position: relative;
        }

        .auth-pass-inputgroup input {
            border-top-right-radius: 0.35rem !important;
            border-bottom-right-radius: 0.35rem !important;
            padding-right: 2.75rem !important;
        }

        .password-addon {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            display: flex;
            align-items: center;
            padding: 0 12px;
            background: transparent;
            border: none;
            color: #475569 !important;
            cursor: pointer;
            z-index: 4;
            font-size: 1.1rem;
        }

        .password-addon:hover {
            color: #405189 !important;
        }

        /* Checkbox */
        .remember-label {
            font-size: 13px !important;
            font-weight: 500 !important;
            color: #334155 !important;
            cursor: pointer;
        }

        .form-check-input {
            width: 1.1em;
            height: 1.1em;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: #0ab39c !important;
            border-color: #0ab39c !important;
        }

        /* Velzon Buttons */
        .btn-success {
            background-color: #0ab39c !important;
            border-color: #0ab39c !important;
            color: #ffffff !important;
            font-weight: 600;
            padding: 0.65rem 1rem;
            font-size: 0.925rem;
            border-radius: 0.35rem;
            box-shadow: 0 2px 6px 0 rgba(10, 179, 156, 0.35);
            transition: all 0.2s ease-in-out;
        }

        .btn-success:hover, .btn-success:focus {
            background-color: #099885 !important;
            border-color: #088f7d !important;
            box-shadow: 0 4px 12px 0 rgba(10, 179, 156, 0.45);
        }

        .btn-primary {
            background-color: #405189 !important;
            border-color: #405189 !important;
            color: #ffffff !important;
        }

        .btn-primary:hover {
            background-color: #364574 !important;
            border-color: #33416e !important;
        }

        .btn-danger {
            background-color: #f06548 !important;
            border-color: #f06548 !important;
            color: #ffffff !important;
        }

        .btn-danger:hover {
            background-color: #cc563d !important;
            border-color: #c0513a !important;
        }

        .btn-icon {
            display: inline-flex !important;
            justify-content: center !important;
            align-items: center !important;
            height: 40px !important;
            width: 40px !important;
            padding: 0 !important;
            border-radius: 0.35rem !important;
        }

        /* Social Divider Title matching Velzon */
        .signin-other-title {
            position: relative;
            text-align: center;
            margin-top: 1.5rem;
            margin-bottom: 1.25rem;
        }

        .signin-other-title:after {
            content: "";
            position: absolute;
            width: 100%;
            height: 1px;
            left: 0;
            right: 0;
            top: 50%;
            background-color: #cbd5e1;
            z-index: 1;
        }

        .signin-other-title .title {
            background-color: #fff;
            display: inline-block;
            padding: 0 14px;
            position: relative;
            z-index: 2;
            color: #475569 !important;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 0 !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .account-hint {
            color: #334155 !important;
            font-size: 13.5px !important;
            font-weight: 500 !important;
        }

        .account-hint .hint-val {
            color: #405189 !important;
            font-weight: 700 !important;
        }

        /* 4 Login Method Tabs (Velzon Custom Tab Style) */
        .login-nav-tabs {
            display: flex;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            gap: 4px;
        }

        .login-tab-btn {
            flex: 1;
            padding: 8px 6px;
            font-size: 11.5px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.2s ease-in-out;
            white-space: nowrap;
        }

        .login-tab-btn:hover {
            color: #1e293b;
            background: rgba(255, 255, 255, 0.6);
        }

        .login-tab-btn.active {
            background: #ffffff;
            color: #405189;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
            font-weight: 700;
        }

        .login-tab-pane {
            display: none;
            animation: fadeIn 0.2s ease-in-out;
        }

        .login-tab-pane.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-callout {
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 12px;
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.45;
        }

        .login-callout-info {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
        }

        .login-callout-success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .otp-input {
            letter-spacing: 8px;
            font-size: 22px !important;
            font-weight: 700 !important;
            text-align: center;
            font-family: monospace;
            padding: 0.6rem 0.5rem;
        }

        .btn-google {
            background-color: #ffffff;
            border: 1.5px solid #cbd5e1;
            color: #1e293b;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 0.65rem 1rem;
            border-radius: 0.35rem;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .btn-google:hover {
            background-color: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
        }

        [data-bs-theme="dark"] .btn-google {
            background-color: #1e293b;
            border-color: #334155;
            color: #f8fafc;
        }

        [data-bs-theme="dark"] .btn-google:hover {
            background-color: #334155;
            border-color: #475569;
            color: #ffffff;
        }
    </style>
</head>

<body>

    <div class="auth-page-wrapper pt-5">
        <!-- auth page bg (Velzon Hero Image Overlay & Wave Shape) -->
        <div class="auth-one-bg-position auth-one-bg" id="auth-particles">
            <div class="bg-overlay"></div>

            @if($showWave)
            <div class="shape">
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1440 120">
                    <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
                </svg>
            </div>
            @endif
        </div>

        <!-- auth page content -->
        <div class="auth-page-content">
            <div class="container">
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
                <!-- end row -->

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-2 card-bg-fill">

                            <div class="card-body p-4">
                                <div class="text-center mt-1">
                                    <h5 class="signin-title">{{ $loginTitle }}</h5>
                                    <p class="signin-subtitle">{{ $loginSubtitle }}</p>
                                </div>

                                @if(session('info'))
                                    <div class="alert alert-info py-2 px-3 fs-13 mb-3 fw-medium">{{ session('info') }}</div>
                                @endif
                                @if($errors->any())
                                    <div class="alert alert-danger py-2 px-3 fs-13 mb-3 fw-medium">{{ $errors->first() }}</div>
                                @endif

                                @php
                                    $activeTab = 'default';
                                    if ($errors->has('sso_nip') || $errors->has('sso_password') || old('active_tab') === 'sso') {
                                        $activeTab = 'sso';
                                    } elseif ($errors->has('otp_phone') || $errors->has('otp_code') || old('active_tab') === 'otp') {
                                        $activeTab = 'otp';
                                    } elseif ($errors->has('google') || old('active_tab') === 'google') {
                                        $activeTab = 'google';
                                    }
                                @endphp

                                <!-- Navigasi 4 Metode Login -->
                                <div class="login-nav-tabs mt-3" role="tablist">
                                    <button type="button" class="login-tab-btn {{ $activeTab === 'default' ? 'active' : '' }}" onclick="switchLoginTab('default')" id="tab-btn-default">
                                        <i class="ri-user-line fs-14"></i>
                                        <span>Username</span>
                                    </button>
                                    <button type="button" class="login-tab-btn {{ $activeTab === 'sso' ? 'active' : '' }}" onclick="switchLoginTab('sso')" id="tab-btn-sso">
                                        <i class="ri-shield-keyhole-line fs-14 text-success"></i>
                                        <span>SSO Sipresensi</span>
                                    </button>
                                    <button type="button" class="login-tab-btn {{ $activeTab === 'otp' ? 'active' : '' }}" onclick="switchLoginTab('otp')" id="tab-btn-otp">
                                        <i class="ri-whatsapp-line fs-14 text-success"></i>
                                        <span>OTP WA</span>
                                    </button>
                                    <button type="button" class="login-tab-btn {{ $activeTab === 'google' ? 'active' : '' }}" onclick="switchLoginTab('google')" id="tab-btn-google">
                                        <i class="ri-google-fill fs-14 text-danger"></i>
                                        <span>Google</span>
                                    </button>
                                </div>

                                <div class="p-1">
                                    <!-- TAB 1: LOGIN DEFAULT (USERNAME / NIP / EMAIL + PASSWORD) -->
                                    <div class="login-tab-pane {{ $activeTab === 'default' ? 'active' : '' }}" id="pane-default">
                                        <form action="{{ route('login.post') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="active_tab" value="default">

                                            <div class="mb-3">
                                                <label for="username" class="form-label">Username / NIP / Email</label>
                                                <input type="text" name="email" class="form-control" id="username" placeholder="Masukkan NIP, username, atau email" value="{{ old('email') }}" required autofocus oninvalid="this.setCustomValidity('Harap isi username, NIP, atau email Anda.')" oninput="this.setCustomValidity('')">
                                            </div>

                                            <div class="mb-3">
                                                <div class="float-end">
                                                    <a href="javascript:void(0);" onclick="alert('Jika Anda belum memiliki kata sandi atau lupa, silakan gunakan login SSO Sipresensi atau OTP WhatsApp.');" class="forgot-password-link">Lupa sandi?</a>
                                                </div>
                                                <label class="form-label" for="password-input">Kata Sandi</label>
                                                <div class="position-relative auth-pass-inputgroup mb-3">
                                                    <input type="password" name="password" class="form-control pe-5 password-input" placeholder="Masukkan kata sandi" id="password-input" required oninvalid="this.setCustomValidity('Harap masukkan kata sandi Anda.')" oninput="this.setCustomValidity('')">
                                                    <button class="password-addon" type="button" id="password-addon" aria-label="Tampilkan atau sembunyikan kata sandi">
                                                        <i class="ri-eye-fill align-middle" id="eye-icon"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remember" id="auth-remember-check" checked>
                                                <label class="form-check-label remember-label" for="auth-remember-check">Ingat saya di perangkat ini</label>
                                            </div>

                                            @if($recaptchaEnabled)
                                                <div class="mt-3 mb-1 d-flex flex-column align-items-center justify-content-center">
                                                    @if(!empty($recaptchaSiteKey))
                                                        <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}" data-theme="light"></div>
                                                    @else
                                                        <div class="alert alert-warning py-2 px-3 fs-12 w-100 text-center mb-0">
                                                            <i class="ri-alert-line me-1"></i> Google reCAPTCHA aktif namun Kunci Situs (Site Key) belum diisi di Pengaturan Login Admin.
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif

                                            <div class="mt-4">
                                                <button class="btn btn-success w-100 fw-semibold" type="submit">
                                                    <i class="ri-login-box-line align-middle me-1 fs-15"></i> Masuk Panel
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- TAB 2: LOGIN SSO SIPRESENSI -->
                                    <div class="login-tab-pane {{ $activeTab === 'sso' ? 'active' : '' }}" id="pane-sso">
                                        <div class="login-callout login-callout-info mb-3">
                                            <i class="ri-information-line fs-16 flex-shrink-0 mt-0.5"></i>
                                            <div>
                                                <strong>SSO Guru Sipresensi:</strong><br>
                                                Gunakan <strong>NIP</strong> dan <strong>Password Akun Sipresensi</strong> MA Unggulan Wahid Hasyim. Kredensial diverifikasi langsung ke server Sipresensi.
                                            </div>
                                        </div>

                                        <form action="{{ route('login.sso') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="active_tab" value="sso">

                                            <div class="mb-3">
                                                <label for="sso_nip" class="form-label">NIP Guru / Username Sipresensi</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light text-muted"><i class="ri-id-card-line"></i></span>
                                                    <input type="text" name="sso_nip" class="form-control" id="sso_nip" placeholder="Contoh: 198001012005011001 atau 1001" value="{{ old('sso_nip') }}" required oninvalid="this.setCustomValidity('Harap masukkan NIP atau username Sipresensi.')" oninput="this.setCustomValidity('')">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label" for="sso_password">Password Akun Sipresensi</label>
                                                <div class="position-relative auth-pass-inputgroup mb-3">
                                                    <input type="password" name="sso_password" class="form-control pe-5 password-input" placeholder="Masukkan password Sipresensi" id="sso_password" required oninvalid="this.setCustomValidity('Harap masukkan password akun Sipresensi Anda.')" oninput="this.setCustomValidity('')">
                                                    <button class="password-addon" type="button" id="sso-password-addon" aria-label="Tampilkan atau sembunyikan kata sandi">
                                                        <i class="ri-eye-fill align-middle" id="sso-eye-icon"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remember" id="sso-remember-check" checked>
                                                <label class="form-check-label remember-label" for="sso-remember-check">Ingat saya di perangkat ini</label>
                                            </div>

                                            @if($recaptchaEnabled && !empty($recaptchaSiteKey))
                                                <div class="mt-3 mb-1 d-flex flex-column align-items-center justify-content-center">
                                                    <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}" data-theme="light"></div>
                                                </div>
                                            @endif

                                            <div class="mt-4">
                                                <button class="btn btn-primary w-100 fw-semibold" type="submit">
                                                    <i class="ri-shield-keyhole-line align-middle me-1 fs-15"></i> Masuk via SSO Sipresensi
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- TAB 3: LOGIN OTP WHATSAPP -->
                                    <div class="login-tab-pane {{ $activeTab === 'otp' ? 'active' : '' }}" id="pane-otp">
                                        <div class="login-callout login-callout-success mb-3">
                                            <i class="ri-whatsapp-line fs-16 flex-shrink-0 mt-0.5 text-success"></i>
                                            <div>
                                                <strong>Login Cepat Bebas Sandi:</strong><br>
                                                Masukkan nomor WhatsApp, NIP, atau Email panitia untuk menerima 6-digit kode OTP langsung di WhatsApp Anda.
                                            </div>
                                        </div>

                                        <!-- Step 1: Input Nomor / NIP -->
                                        <div id="otp-step-1">
                                            <div class="mb-3">
                                                <label for="otp-phone-input" class="form-label">Nomor WhatsApp / NIP Panitia</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light text-muted"><i class="ri-phone-line"></i></span>
                                                    <input type="text" class="form-control" id="otp-phone-input" placeholder="Contoh: 081234567890 atau NIP Guru">
                                                </div>
                                                <small class="text-muted fs-11 mt-1 d-block">Nomor HP / NIP harus sudah terdaftar pada akun panitia.</small>
                                            </div>

                                            <div id="otp-send-alert" class="alert d-none py-2 px-3 fs-12 mb-3"></div>

                                            <div class="mt-4">
                                                <button class="btn btn-success w-100 fw-semibold" type="button" id="btn-send-otp" onclick="handleSendOtp()">
                                                    <span id="btn-send-spinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                                                    <i class="ri-send-plane-fill align-middle me-1 fs-15" id="btn-send-icon"></i>
                                                    <span>Kirim Kode OTP WhatsApp</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Step 2: Input 6 Digit OTP -->
                                        <div id="otp-step-2" class="d-none">
                                            <div class="alert alert-success py-2 px-3 fs-12 mb-3" id="otp-sent-notice">
                                                <i class="ri-check-line me-1"></i>Kode OTP telah dikirimkan ke WhatsApp Anda.
                                            </div>

                                            <div class="mb-3 text-center">
                                                <label for="otp-code-input" class="form-label fw-bold">Masukkan 6 Digit Kode OTP</label>
                                                <input type="text" maxlength="6" class="form-control otp-input" id="otp-code-input" placeholder="------" autocomplete="one-time-code">
                                                <div class="d-flex justify-content-between align-items-center mt-2 fs-12 text-muted">
                                                    <span id="otp-timer-text"><i class="ri-time-line me-1"></i>Berlaku: <strong id="otp-countdown">05:00</strong></span>
                                                    <a href="javascript:void(0);" id="btn-resend-otp" class="text-decoration-underline text-primary fw-semibold d-none" onclick="handleSendOtp()">Kirim Ulang OTP</a>
                                                </div>
                                            </div>

                                            <div id="otp-verify-alert" class="alert d-none py-2 px-3 fs-12 mb-3"></div>

                                            <div class="mt-4 d-flex flex-column gap-2">
                                                <button class="btn btn-success w-100 fw-semibold" type="button" id="btn-verify-otp" onclick="handleVerifyOtp()">
                                                    <span id="btn-verify-spinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                                                    <i class="ri-login-circle-line align-middle me-1 fs-15" id="btn-verify-icon"></i>
                                                    <span>Verifikasi & Masuk Panel</span>
                                                </button>
                                                <button class="btn btn-light border w-100 btn-sm text-muted" type="button" onclick="resetOtpStep()">
                                                    <i class="ri-arrow-left-line me-1"></i>Ganti Nomor / NIP
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB 4: LOGIN DENGAN AKUN GOOGLE -->
                                    <div class="login-tab-pane {{ $activeTab === 'google' ? 'active' : '' }}" id="pane-google">
                                        <div class="login-callout login-callout-info mb-4">
                                            <i class="ri-google-fill fs-18 flex-shrink-0 mt-0.5 text-danger"></i>
                                            <div>
                                                <strong>Masuk Cepat Google:</strong><br>
                                                Gunakan akun Google resmi yang terdaftar sebagai panitia Semarak untuk langsung mengakses sistem dengan sekali klik.
                                            </div>
                                        </div>

                                        <!-- 1-Click Google OAuth Button -->
                                        <div class="py-2 mb-2">
                                            <a href="{{ route('admin.auth.google') }}" class="btn-google w-100 shadow-sm py-2 px-3 fs-14 fw-semibold">
                                                <svg width="20" height="20" viewBox="0 0 48 48" class="align-middle me-1">
                                                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                                                </svg>
                                                <span>Masuk Cepat dengan Google</span>
                                            </a>
                                        </div>

                                        <p class="text-center text-muted fs-11 mt-3 mb-1">
                                            <i class="ri-shield-check-line text-success align-middle me-1"></i>Otentikasi aman via Google Identity Services
                                        </p>
                                    </div>

                                    <div class="mt-4 text-center">
                                        <p class="mb-0 text-muted fs-12">
                                            Bukan Panitia? <a href="{{ config('portal.public_url') }}/pendaftar/login" class="fw-semibold text-primary text-decoration-underline">Masuk Portal Pendaftar di Sini</a>
                                        </p>
                                    </div>

                                    <div class="mt-4 text-center">
                                        <div class="signin-other-title">
                                            <h5 class="title">Pintasan Semarak</h5>
                                        </div>
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="{{ config('portal.public_url') }}" target="_blank" class="btn btn-primary btn-icon waves-effect waves-light" title="Web Publik"><i class="ri-earth-line fs-18"></i></a>
                                            <a href="{{ config('portal.public_url') }}/layanan-registrasi" target="_blank" class="btn btn-success btn-icon waves-effect waves-light" title="Form Pendaftaran"><i class="ri-user-add-line fs-18"></i></a>
                                            <a href="{{ config('portal.public_url') }}/assets/JUKNIS-SEMARAK-2026.pdf" target="_blank" class="btn btn-danger btn-icon waves-effect waves-light" title="Petunjuk Teknis"><i class="ri-file-pdf-line fs-18"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->

                    </div>
                </div>
                <!-- end row -->
            </div>
            <!-- end container -->
        </div>
        <!-- end auth page content -->

        <!-- footer -->
        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center">
                            <p class="mb-0 fs-12">
                                &copy; {{ date('Y') }} {{ $eventName }}. Dikelola dengan <i class="ri-heart-fill text-danger align-middle"></i> oleh Panitia Maswahas & MA Unggulan Wahid Hasyim
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        <!-- end Footer -->
    </div>
    <!-- end auth-page-wrapper -->

    <!-- JAVASCRIPT: Particles Simulation, Tabs & WhatsApp OTP Handler -->
    <script>
        // 1. Tab Switching Functionality
        function switchLoginTab(tabName) {
            const tabs = ['default', 'sso', 'otp', 'google'];
            tabs.forEach(t => {
                const btn = document.getElementById('tab-btn-' + t);
                const pane = document.getElementById('pane-' + t);
                if (btn) btn.classList.remove('active');
                if (pane) pane.classList.remove('active');
            });

            const activeBtn = document.getElementById('tab-btn-' + tabName);
            const activePane = document.getElementById('pane-' + tabName);
            if (activeBtn) activeBtn.classList.add('active');
            if (activePane) {
                activePane.classList.add('active');
                const firstInput = activePane.querySelector('input:not([type="hidden"])');
                if (firstInput) setTimeout(() => firstInput.focus(), 80);
            }
        }

        // 2. WhatsApp OTP State & Handlers
        let otpCountdownInterval = null;
        let currentPhoneOrNip = '';

        async function handleSendOtp() {
            const inputEl = document.getElementById('otp-phone-input');
            const alertEl = document.getElementById('otp-send-alert');
            const btnEl = document.getElementById('btn-send-otp');
            const spinnerEl = document.getElementById('btn-send-spinner');
            const iconEl = document.getElementById('btn-send-icon');

            const val = (inputEl.value || currentPhoneOrNip || '').trim();
            if (!val) {
                alertEl.className = 'alert alert-warning py-2 px-3 fs-12 mb-3';
                alertEl.textContent = 'Silakan masukkan nomor WhatsApp atau NIP panitia terlebih dahulu.';
                alertEl.classList.remove('d-none');
                inputEl.focus();
                return;
            }

            alertEl.classList.add('d-none');
            if (btnEl) btnEl.disabled = true;
            if (spinnerEl) spinnerEl.classList.remove('d-none');
            if (iconEl) iconEl.classList.add('d-none');

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch("{{ route('login.otp.send') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ phone_or_nip: val })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    currentPhoneOrNip = val;
                    document.getElementById('otp-step-1').classList.add('d-none');
                    document.getElementById('otp-step-2').classList.remove('d-none');
                    
                    const noticeEl = document.getElementById('otp-sent-notice');
                    if (noticeEl) {
                        noticeEl.innerHTML = `<i class="ri-check-line me-1"></i>${data.message}`;
                    }
                    
                    startOtpTimer(300);
                    const codeInput = document.getElementById('otp-code-input');
                    if (codeInput) {
                        codeInput.value = '';
                        setTimeout(() => codeInput.focus(), 100);
                    }
                } else {
                    alertEl.className = 'alert alert-danger py-2 px-3 fs-12 mb-3';
                    alertEl.textContent = data.message || 'Gagal mengirim kode OTP. Silakan periksa kembali data Anda.';
                    alertEl.classList.remove('d-none');
                }
            } catch (err) {
                alertEl.className = 'alert alert-danger py-2 px-3 fs-12 mb-3';
                alertEl.textContent = 'Terjadi kesalahan koneksi saat mengirim kode OTP: ' + err.message;
                alertEl.classList.remove('d-none');
            } finally {
                if (btnEl) btnEl.disabled = false;
                if (spinnerEl) spinnerEl.classList.add('d-none');
                if (iconEl) iconEl.classList.remove('d-none');
            }
        }

        async function handleVerifyOtp() {
            const codeInput = document.getElementById('otp-code-input');
            const alertEl = document.getElementById('otp-verify-alert');
            const btnEl = document.getElementById('btn-verify-otp');
            const spinnerEl = document.getElementById('btn-verify-spinner');
            const iconEl = document.getElementById('btn-verify-icon');

            const otpCode = (codeInput.value || '').trim();
            if (!otpCode || otpCode.length < 6) {
                alertEl.className = 'alert alert-warning py-2 px-3 fs-12 mb-3';
                alertEl.textContent = 'Masukkan 6-digit kode OTP secara lengkap.';
                alertEl.classList.remove('d-none');
                codeInput.focus();
                return;
            }

            alertEl.classList.add('d-none');
            if (btnEl) btnEl.disabled = true;
            if (spinnerEl) spinnerEl.classList.remove('d-none');
            if (iconEl) iconEl.classList.add('d-none');

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch("{{ route('login.otp.verify') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        phone_or_nip: currentPhoneOrNip,
                        otp_code: otpCode
                    })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    alertEl.className = 'alert alert-success py-2 px-3 fs-12 mb-3';
                    alertEl.textContent = data.message + ' Mengalihkan...';
                    alertEl.classList.remove('d-none');
                    if (otpCountdownInterval) clearInterval(otpCountdownInterval);
                    setTimeout(() => {
                        window.location.href = data.redirect || "{{ route('admin.dashboard') }}";
                    }, 600);
                } else {
                    alertEl.className = 'alert alert-danger py-2 px-3 fs-12 mb-3';
                    alertEl.textContent = data.message || 'Kode OTP tidak sesuai atau sudah kadaluwarsa.';
                    alertEl.classList.remove('d-none');
                }
            } catch (err) {
                alertEl.className = 'alert alert-danger py-2 px-3 fs-12 mb-3';
                alertEl.textContent = 'Terjadi kesalahan sistem saat verifikasi OTP: ' + err.message;
                alertEl.classList.remove('d-none');
            } finally {
                if (btnEl) btnEl.disabled = false;
                if (spinnerEl) spinnerEl.classList.add('d-none');
                if (iconEl) iconEl.classList.remove('d-none');
            }
        }

        function startOtpTimer(durationSeconds) {
            if (otpCountdownInterval) clearInterval(otpCountdownInterval);
            let timeLeft = durationSeconds;
            const timerEl = document.getElementById('otp-countdown');
            const resendBtn = document.getElementById('btn-resend-otp');
            const timerText = document.getElementById('otp-timer-text');

            if (resendBtn) resendBtn.classList.add('d-none');
            if (timerText) timerText.classList.remove('d-none');

            const updateDisplay = () => {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                if (timerEl) {
                    timerEl.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                }
                if (timeLeft <= 0) {
                    clearInterval(otpCountdownInterval);
                    if (resendBtn) resendBtn.classList.remove('d-none');
                    if (timerText) timerText.classList.add('d-none');
                }
                timeLeft--;
            };

            updateDisplay();
            otpCountdownInterval = setInterval(updateDisplay, 1000);
        }

        function resetOtpStep() {
            if (otpCountdownInterval) clearInterval(otpCountdownInterval);
            document.getElementById('otp-step-2').classList.add('d-none');
            document.getElementById('otp-step-1').classList.remove('d-none');
            const sendAlert = document.getElementById('otp-send-alert');
            const verifyAlert = document.getElementById('otp-verify-alert');
            if (sendAlert) sendAlert.classList.add('d-none');
            if (verifyAlert) verifyAlert.classList.add('d-none');
            const phoneInput = document.getElementById('otp-phone-input');
            if (phoneInput) setTimeout(() => phoneInput.focus(), 80);
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Default Password Toggle
            const passwordInput = document.getElementById('password-input');
            const passwordAddon = document.getElementById('password-addon');
            const eyeIcon = document.getElementById('eye-icon');

            if (passwordAddon && passwordInput && eyeIcon) {
                passwordAddon.addEventListener('click', () => {
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        eyeIcon.className = 'ri-eye-off-fill align-middle';
                    } else {
                        passwordInput.type = 'password';
                        eyeIcon.className = 'ri-eye-fill align-middle';
                    }
                });
            }

            // SSO Password Toggle
            const ssoPasswordInput = document.getElementById('sso_password');
            const ssoPasswordAddon = document.getElementById('sso-password-addon');
            const ssoEyeIcon = document.getElementById('sso-eye-icon');

            if (ssoPasswordAddon && ssoPasswordInput && ssoEyeIcon) {
                ssoPasswordAddon.addEventListener('click', () => {
                    if (ssoPasswordInput.type === 'password') {
                        ssoPasswordInput.type = 'text';
                        ssoEyeIcon.className = 'ri-eye-off-fill align-middle';
                    } else {
                        ssoPasswordInput.type = 'password';
                        ssoEyeIcon.className = 'ri-eye-fill align-middle';
                    }
                });
            }

            // Auto-submit OTP saat 6 digit terisi
            const otpCodeInput = document.getElementById('otp-code-input');
            if (otpCodeInput) {
                otpCodeInput.addEventListener('input', (e) => {
                    const cleaned = e.target.value.replace(/[^0-9]/g, '');
                    e.target.value = cleaned;
                    if (cleaned.length === 6) {
                        handleVerifyOtp();
                    }
                });
                otpCodeInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        handleVerifyOtp();
                    }
                });
            }

            // Velzon Signature Floating White Particles Canvas
            @if($particlesActive)
            const bgContainer = document.getElementById('auth-particles');
            if (bgContainer) {
                const canvas = document.createElement('canvas');
                canvas.className = 'particles-js-canvas-el';
                canvas.style.position = 'absolute';
                canvas.style.top = '0';
                canvas.style.left = '0';
                canvas.style.width = '100%';
                canvas.style.height = '100%';
                canvas.style.pointerEvents = 'none';
                canvas.style.zIndex = '1';
                bgContainer.appendChild(canvas);

                const ctx = canvas.getContext('2d');
                let width = canvas.width = bgContainer.offsetWidth;
                let height = canvas.height = bgContainer.offsetHeight;

                window.addEventListener('resize', () => {
                    width = canvas.width = bgContainer.offsetWidth;
                    height = canvas.height = bgContainer.offsetHeight;
                });

                const particles = [];
                for (let i = 0; i < 40; i++) {
                    particles.push({
                        x: Math.random() * width,
                        y: Math.random() * height,
                        r: Math.random() * 2 + 0.8,
                        vx: (Math.random() - 0.5) * 0.35,
                        vy: (Math.random() - 0.5) * 0.35,
                        alpha: Math.random() * 0.5 + 0.25
                    });
                }

                function draw() {
                    ctx.clearRect(0, 0, width, height);
                    ctx.fillStyle = '#ffffff';

                    particles.forEach(p => {
                        ctx.globalAlpha = p.alpha;
                        ctx.beginPath();
                        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                        ctx.fill();

                        p.x += p.vx;
                        p.y += p.vy;
                        if (p.x < 0) p.x = width;
                        if (p.x > width) p.x = 0;
                        if (p.y < 0) p.y = height;
                        if (p.y > height) p.y = 0;
                    });

                    requestAnimationFrame(draw);
                }
                draw();
            }
            @endif
        });
    </script>
</body>
</html>
