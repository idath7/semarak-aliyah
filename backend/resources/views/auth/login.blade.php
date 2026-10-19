<!doctype html>
<html lang="id" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <title>Sign In | Velzon - Semarak Aliyah XII 2026</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Panel Admin Panitia Semarak Aliyah XII 2026 - MA Unggulan Wahid Hasyim Balung" name="description" />
    <meta content="Themesbrand" name="author" />

    <!-- Favicon -->
    <link rel="shortcut icon" href="/assets/img/logo.png">

    <!-- Google Fonts (Poppins - Velzon Official Typography) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Laravel Vite Asset Bundling -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

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
            background-image: url("/assets/images/auth-one-bg.jpg");
            background-position: center;
            background-size: cover;
        }

        .auth-one-bg .bg-overlay {
            background: linear-gradient(to right, rgba(54, 69, 116, 0.92), rgba(64, 81, 137, 0.95));
            position: absolute;
            height: 100%;
            width: 100%;
            right: 0;
            bottom: 0;
            left: 0;
            top: 0;
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

        /* Footer */
        .footer {
            position: relative;
            padding: 20px 0;
            color: #64748b;
            font-weight: 500;
        }
    </style>
</head>

<body>

    <div class="auth-page-wrapper pt-5">
        <!-- auth page bg (Velzon Official Hero Image Overlay & Wave Shape) -->
        <div class="auth-one-bg-position auth-one-bg" id="auth-particles">
            <div class="bg-overlay"></div>

            <div class="shape">
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1440 120">
                    <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
                </svg>
            </div>
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
                                    <span class="auth-logo-text">SEMARAK ALIYAH XII</span>
                                </a>
                            </div>
                            <p class="auth-header-subtitle">Panel Admin Panitia • MA Unggulan Wahid Hasyim Balung</p>
                        </div>
                    </div>
                </div>
                <!-- end row -->

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-2 card-bg-fill">

                            <div class="card-body p-4">
                                <div class="text-center mt-1">
                                    <h5 class="signin-title">Welcome Back !</h5>
                                    <p class="signin-subtitle">Sign in to continue to Semarak Aliyah.</p>
                                </div>

                                @if(session('info'))
                                    <div class="alert alert-info py-2 px-3 fs-13 mb-3 fw-medium">{{ session('info') }}</div>
                                @endif
                                @if($errors->any())
                                    <div class="alert alert-danger py-2 px-3 fs-13 mb-3 fw-medium">{{ $errors->first() }}</div>
                                @endif

                                <div class="p-2 mt-3">
                                    <form action="{{ route('login.post') }}" method="POST">
                                        @csrf

                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username / Email</label>
                                            <input type="text" name="email" class="form-control" id="username" placeholder="Masukkan email / username" value="{{ old('email', 'admin@maswahas.sch.id') }}" required autofocus>
                                        </div>

                                        <div class="mb-3">
                                            <div class="float-end">
                                                <a href="javascript:void(0);" onclick="alert('Kata sandi panitia default: admin123');" class="forgot-password-link">Forgot password?</a>
                                            </div>
                                            <label class="form-label" for="password-input">Password</label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input type="password" name="password" class="form-control pe-5 password-input" placeholder="Masukkan password" id="password-input" value="admin123" required>
                                                <button class="password-addon" type="button" id="password-addon" aria-label="Toggle Password Visibility">
                                                    <i class="ri-eye-fill align-middle" id="eye-icon"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remember" id="auth-remember-check" checked>
                                            <label class="form-check-label remember-label" for="auth-remember-check">Remember me</label>
                                        </div>

                                        <div class="mt-4">
                                            <button class="btn btn-success w-100 fw-semibold" type="submit">
                                                <i class="ri-login-box-line align-middle me-1 fs-15"></i> Sign In
                                            </button>
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
                                    </form>
                                </div>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->

                        <div class="mt-3 text-center">
                            <p class="mb-0 account-hint">Akun Panitia : <span class="hint-val">admin@maswahas.sch.id</span> (Password : <span class="hint-val">admin123</span>)</p>
                        </div>

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
                                &copy; {{ date('Y') }} SEMARAK ALIYAH XII. Crafted with <i class="ri-heart-fill text-danger align-middle"></i> by Panitia Maswahas & Themesbrand Velzon
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        <!-- end Footer -->
    </div>
    <!-- end auth-page-wrapper -->

    <!-- JAVASCRIPT: Particles Simulation & Password Toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Password Show/Hide Toggle matching Velzon
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

            // Velzon Signature Floating White Particles Canvas
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
        });
    </script>
</body>
</html>
