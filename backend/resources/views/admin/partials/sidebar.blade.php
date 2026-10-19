@php
    $pendingReviewCount = \App\Models\Registration::where('payment_status', 'Menunggu Verifikasi')->count();
@endphp

<!-- Sidebar Navigation (Themesbrand Velzon Official Standard for Admin Pendaftaran) -->
<aside class="app-menu navbar-menu" id="app-sidebar">
    <!-- Brand Logo Box -->
    <div class="navbar-brand-box">
        <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center justify-content-center gap-2">
            <img src="/assets/img/logo.png" alt="Logo" height="34" style="object-fit: contain;">
            <div class="d-flex flex-column text-start">
                <span class="fs-16 fw-bold text-white lh-1" style="letter-spacing: 1.5px; font-family: 'Poppins', sans-serif;">SEMARAK</span>
                <span class="text-warning fw-bold fs-10" style="letter-spacing: 2px;">ALIYAH XII</span>
            </div>
        </a>
    </div>

    <!-- Quick Switcher: Admin Pendaftaran vs Pusat Komando Operasional -->
    <div class="px-3 pt-2 pb-2 border-bottom border-dark border-opacity-50">
        <div class="nav nav-pills nav-justified p-1 bg-black bg-opacity-40 rounded-3 border border-secondary border-opacity-25" id="sidebarModeGroup">
            <a href="{{ route('admin.dashboard') }}" 
               class="nav-link rounded-3 py-1-5 fs-12 fw-semibold active bg-primary text-white shadow-sm" 
               title="Panel Administrasi Pendaftaran (Velzon)">
                <i class="ri-file-list-3-line me-1"></i> Pendaftaran
            </a>
            <a href="{{ route('operasional') }}" 
               class="nav-link rounded-3 py-1-5 fs-12 fw-semibold text-warning text-center" 
               title="Buka Pusat Komando Operasional Lapangan (Dark UI)">
                <i class="ri-flashlight-line me-1"></i> Operasional
            </a>
        </div>
    </div>

    <!-- Scrollable Menu -->
    <div class="sidebar-menu-wrapper" data-simplebar id="scrollbar">
        <ul class="navbar-nav" id="navbar-nav">

            <!-- 1. Menu Pendaftaran & Administrasi -->
            <li class="menu-title"><span>Menu Pendaftaran</span></li>

            <li class="nav-item">
                <a class="nav-link menu-link active" 
                   href="#sidebarDashboards" 
                   data-bs-toggle="collapse" 
                   role="button" 
                   aria-expanded="true" 
                   aria-controls="sidebarDashboards">
                    <i class="ri-dashboard-2-line"></i>
                    <span>Dashboards</span>
                </a>
                <div class="collapse menu-dropdown show" id="sidebarDashboards">
                    <ul class="nav nav-sm flex-column">
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard') }}" class="nav-link active" id="nav-dash-utama">
                                Dashboard Utama
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard') }}#section-pendaftar" class="nav-link" id="nav-dash-pendaftar">
                                Data Pendaftar Masuk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard') }}#section-kuota" class="nav-link" id="nav-dash-kuota">
                                Cabang Lomba & Kuota
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Laporan & Dokumen Pendaftaran -->
            <li class="menu-title"><span>Laporan & Dokumen</span></li>

            <li class="nav-item">
                <a class="nav-link menu-link" href="{{ route('admin.export') }}">
                    <i class="ri-file-excel-2-line text-success"></i>
                    <span>Export Excel (CSV)</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-link" href="/assets/doc/JUKNIS-SEMARAK-2026.pdf" target="_blank">
                    <i class="ri-file-pdf-line text-danger"></i>
                    <span>Juknis Resmi PDF</span>
                    <i class="ri-external-link-line fs-14 ms-auto text-muted"></i>
                </a>
            </li>

            <!-- 2. Operasional Lapangan (Dark UI Dedicated) -->
            <li class="menu-title"><span class="text-warning">Operasional Lapangan (Dark UI)</span></li>

            <li class="nav-item">
                <a class="nav-link menu-link fw-bold text-warning" href="{{ route('operasional') }}" style="background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 6px; margin: 4px 12px; padding: 10px 14px;">
                    <i class="ri-dashboard-3-line text-warning"></i>
                    <span>Pusat Komando (Dark UI)</span>
                    <span class="badge bg-warning text-dark ms-auto fs-10">Buka</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-link" href="/operasional?tab=gate">
                    <i class="ri-qr-scan-2-line text-success"></i>
                    <span>Gate Check-in Barcode</span>
                    <span class="badge bg-success ms-auto fs-10">Live</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-link" href="/operasional?tab=berkas">
                    <i class="ri-file-search-line text-info"></i>
                    <span>Review & Verifikasi Berkas</span>
                    @if($pendingReviewCount > 0)
                        <span class="badge badge-pill bg-danger ms-auto fs-10">{{ $pendingReviewCount }}</span>
                    @endif
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-link" href="/operasional?tab=turnamen">
                    <i class="ri-trophy-line text-warning"></i>
                    <span>Bagan Turnamen Gugur</span>
                    <span class="badge bg-warning text-dark ms-auto fs-10">Arena</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-link" href="/operasional?tab=juri">
                    <i class="ri-scales-3-line text-warning"></i>
                    <span>Penjurian Digital Juri</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-link" href="/operasional?tab=print">
                    <i class="ri-printer-line text-primary"></i>
                    <span>Print Studio (ID Card)</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-link" href="{{ route('public.scoreboard') }}" target="_blank">
                    <i class="ri-broadcast-line text-info"></i>
                    <span>Live Scoreboard Layar</span>
                    <i class="ri-external-link-line fs-14 ms-auto text-muted"></i>
                </a>
            </li>

            <!-- 3. Portal Eksternal & Akun -->
            <li class="menu-title"><span>Portal Terpadu</span></li>

            <li class="nav-item">
                <a class="nav-link menu-link" href="{{ route('public.home') }}" target="_blank">
                    <i class="ri-global-line text-info"></i>
                    <span>Laman Publik Semarak</span>
                    <i class="ri-external-link-line fs-14 ms-auto text-muted"></i>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-link" href="https://maswahas.sch.id" target="_blank">
                    <i class="ri-building-line text-secondary"></i>
                    <span>Portal Maswahas</span>
                    <i class="ri-external-link-line fs-14 ms-auto text-muted"></i>
                </a>
            </li>

            <li class="menu-title"><span>Akun</span></li>

            <li class="nav-item">
                <form action="{{ route('logout') }}" method="POST" id="sidebar-logout-form" class="m-0">
                    @csrf
                    <button type="submit" class="nav-link menu-link text-danger border-0 bg-transparent w-100 text-start">
                        <i class="ri-logout-box-r-line text-danger"></i>
                        <span>Keluar Akun</span>
                    </button>
                </form>
            </li>
        </ul>
    </div>
</aside>
