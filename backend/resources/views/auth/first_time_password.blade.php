@php
    $bgImage = \App\Models\Setting::get('admin_login_bg_image', '/assets/images/auth-one-bg.jpg');
    $bgOverlay = \App\Models\Setting::get('admin_login_bg_overlay_type', 'gradient_velzon');
    $bgOpacity = (int)\App\Models\Setting::get('admin_login_bg_overlay_opacity', 85);
    $bgBlur = (int)\App\Models\Setting::get('admin_login_bg_blur', 0);
    $showWave = filter_var(\App\Models\Setting::get('admin_login_show_wave', '1'), FILTER_VALIDATE_BOOLEAN);
    $eventName = \App\Models\Setting::get('event_name', 'SEMARAK ALIYAH XIV 2026');
    $schoolName = \App\Models\Setting::get('school_name', 'MA Unggulan Wahid Hasyim Balung');
@endphp
<!doctype html>
<html lang="id" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <title>Buat Kata Sandi Baru | {{ $eventName }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Buat Kata Sandi Baru Panitia {{ $eventName }}" name="description" />
    <link rel="shortcut icon" href="/assets/img/logo.png">
    <!-- Local Offline Fonts & Icons -->
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/vendor/remixicon/remixicon.css">
    <link rel="stylesheet" href="/assets/vendor/boxicons/css/boxicons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            background-color: #f3f3f9 !important;
            font-family: 'Poppins', sans-serif !important;
            color: #212529 !important;
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
            background: linear-gradient(135deg, rgba(64, 81, 137, 0.92) 0%, rgba(10, 179, 156, 0.88) 100%) !important;
        }
        .auth-page-content {
            position: relative;
            z-index: 10;
        }
        .card-bg-fill {
            background-color: #ffffff !important;
            border: 1px solid #e9ebec !important;
            border-radius: 0.75rem !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        }
        .btn-success {
            background-color: #0ab39c !important;
            border-color: #0ab39c !important;
            color: #ffffff !important;
            font-weight: 600;
            padding: 0.65rem 1rem;
            border-radius: 0.35rem;
        }
        .btn-success:hover {
            background-color: #099885 !important;
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
        }
    </style>
</head>

<body>
    <div class="auth-page-wrapper pt-5">
        <div class="auth-one-bg-position auth-one-bg">
            <div class="bg-overlay"></div>
            @if($showWave)
            <div class="shape">
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 1440 120">
                    <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
                </svg>
            </div>
            @endif
        </div>

        <div class="auth-page-content">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center mt-sm-3 mb-4">
                            <div class="d-inline-flex align-items-center gap-2">
                                <img src="/assets/img/logo.png" alt="Logo Semarak" height="42">
                                <span class="fs-22 fw-bold text-white tracking-wide">{{ $eventName }}</span>
                            </div>
                            <p class="text-white-50 fs-13 mt-1 mb-0">Panel Admin Panitia • {{ $schoolName }}</p>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-2 card-bg-fill">
                            <div class="card-body p-4">
                                <div class="text-center mt-1">
                                    <div class="avatar-md bg-success-subtle text-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
                                        <i class="ri-lock-password-line fs-28"></i>
                                    </div>
                                    <h5 class="fs-18 fw-bold text-dark mb-1">Buat Kata Sandi Baru</h5>
                                    <p class="text-muted fs-12 mb-3">
                                        Selamat datang, <strong>{{ $user->name }}</strong>. Akun Anda telah aktif, silakan buat kata sandi baru untuk login mandiri ke sistem.
                                    </p>
                                </div>

                                <!-- Ringkasan Akun -->
                                <div class="p-2.5 bg-light rounded-3 border mb-3 fs-12">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Nama:</span>
                                        <strong class="text-dark">{{ $user->name }}</strong>
                                    </div>
                                    @if($user->nip)
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">NIP Guru:</span>
                                        <span class="font-monospace text-dark fw-semibold">{{ $user->nip }}</span>
                                    </div>
                                    @endif
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Email Akun:</span>
                                        <span class="font-monospace text-dark">{{ $user->email }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Peran:</span>
                                        <span class="badge bg-primary-subtle text-primary">{{ strtoupper($user->role) }}</span>
                                    </div>
                                </div>

                                @if(session('info'))
                                    <div class="alert alert-info py-2 px-3 fs-12 mb-3 fw-medium">
                                        <i class="ri-information-line me-1"></i>{{ session('info') }}
                                    </div>
                                @endif

                                @if($errors->any())
                                    <div class="alert alert-danger py-2 px-3 fs-12 mb-3 fw-medium">
                                        <i class="ri-error-warning-line me-1"></i>{{ $errors->first() }}
                                    </div>
                                @endif

                                <form action="{{ route('admin.password.first.store') }}" method="POST">
                                    @csrf

                                    <!-- Kata Sandi Baru -->
                                    <div class="mb-3">
                                        <label class="form-label fs-12 fw-semibold text-dark" for="pass-input">
                                            Kata Sandi Baru <span class="text-danger">*</span>
                                        </label>
                                        <div class="position-relative">
                                            <input 
                                                type="password" 
                                                name="password" 
                                                class="form-control pe-5" 
                                                id="pass-input" 
                                                placeholder="Minimal 6 karakter" 
                                                required 
                                                autofocus
                                                oninvalid="this.setCustomValidity('Harap masukkan kata sandi baru.')"
                                                oninput="this.setCustomValidity('')"
                                            />
                                            <button class="password-addon" type="button" onclick="togglePass('pass-input', 'eye-1')">
                                                <i class="ri-eye-fill align-middle" id="eye-1"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted fs-11">Minimal 6 karakter (kombinasi huruf & angka disarankan).</small>
                                    </div>

                                    <!-- Konfirmasi Kata Sandi -->
                                    <div class="mb-4">
                                        <label class="form-label fs-12 fw-semibold text-dark" for="pass-confirm-input">
                                            Ulangi Kata Sandi Baru <span class="text-danger">*</span>
                                        </label>
                                        <div class="position-relative">
                                            <input 
                                                type="password" 
                                                name="password_confirmation" 
                                                class="form-control pe-5" 
                                                id="pass-confirm-input" 
                                                placeholder="Ulangi kata sandi baru" 
                                                required
                                                oninvalid="this.setCustomValidity('Harap ulangi kata sandi baru Anda.')"
                                                oninput="this.setCustomValidity('')"
                                            />
                                            <button class="password-addon" type="button" onclick="togglePass('pass-confirm-input', 'eye-2')">
                                                <i class="ri-eye-fill align-middle" id="eye-2"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button class="btn btn-success fw-semibold py-2" type="submit">
                                            <i class="ri-shield-keyhole-line me-1"></i> Simpan Kata Sandi & Masuk Dashboard
                                        </button>
                                    </div>
                                </form>

                                <div class="mt-3 text-center">
                                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-link text-muted fs-12 text-decoration-none">
                                            <i class="ri-logout-box-r-line me-1"></i>Keluar / Ganti Akun Lain
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="text-center py-3 text-muted fs-11">
            &copy; {{ date('Y') }} {{ $eventName }} • {{ $schoolName }}
        </footer>
    </div>

    <script>
        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'ri-eye-off-fill align-middle';
            } else {
                input.type = 'password';
                icon.className = 'ri-eye-fill align-middle';
            }
        }
    </script>
</body>
</html>
