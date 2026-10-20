<!doctype html>
<html lang="id" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <title>Dashboard Panitia | Velzon - Semarak Aliyah XII 2026</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Panel Admin Panitia Semarak Aliyah XII 2026 - MA Unggulan Wahid Hasyim Balung" name="description" />
    <meta content="MA Wahid Hasyim" name="author" />

    <!-- Favicon -->
    <link rel="shortcut icon" href="/assets/img/logo.png">

    <!-- Google Fonts (Poppins - Velzon Official Typography) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Laravel Vite Asset Bundling -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div id="layout-wrapper">

        <!-- Top Header Navigation (Velzon Topbar) -->
        <header id="page-topbar">
            <div class="navbar-header">
                <div class="d-flex align-items-center gap-3">
                    <!-- Hamburger Sidebar Toggle Button -->
                    <button type="button" class="btn btn-topbar" id="sidebar-toggle" aria-label="Toggle Navigation">
                        <i class="ri-menu-2-line fs-20"></i>
                    </button>

                    <!-- Topbar Search Bar with Shortcut -->
                    <form class="app-search d-none d-md-block" onsubmit="event.preventDefault();">
                        <div class="position-relative">
                            <input type="text" class="form-control" id="table-search-input" placeholder="Cari nama, sekolah, ID..." autocomplete="off">
                            <span class="ri-search-line search-widget-icon"></span>
                            <span class="search-shortcut">/</span>
                        </div>
                    </form>

                    <div class="d-none d-lg-block">
                        <span class="badge bg-primary-subtle text-primary fs-12 px-2 py-1">
                            <i class="ri-flashlight-line align-middle me-1"></i> Edisi XII • 2026
                        </span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <!-- External Public Web Link -->
                    <a href="{{ config('portal.public_url', 'http://localhost:8000') }}" target="_blank" class="btn btn-sm btn-outline-primary d-none d-sm-inline-flex align-items-center gap-1 rounded-pill px-3">
                        <i class="ri-external-link-line"></i> Web Publik
                    </a>

                    <!-- Web Apps Dropdown -->
                    <div class="dropdown">
                        <button type="button" class="btn btn-topbar" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Aplikasi Terkait">
                            <i class='bx bx-category-alt fs-22'></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0">
                            <div class="p-3 border-bottom border-light">
                                <h6 class="m-0 fw-semibold fs-14">Aplikasi & Pintasan</h6>
                            </div>
                            <div class="p-2">
                                <div class="row g-0">
                                    <div class="col-4">
                                        <a class="dropdown-item text-center py-3 rounded" href="{{ config('portal.public_url', 'http://localhost:8000') }}" target="_blank">
                                            <i class="ri-earth-line fs-24 text-primary d-block mb-1"></i>
                                            <span class="fs-12 text-muted">Landing Page</span>
                                        </a>
                                    </div>
                                    <div class="col-4">
                                        <a class="dropdown-item text-center py-3 rounded" href="{{ config('portal.public_url', 'http://localhost:8000') }}/layanan-registrasi" target="_blank">
                                            <i class="ri-user-add-line fs-24 text-success d-block mb-1"></i>
                                            <span class="fs-12 text-muted">Form Registrasi</span>
                                        </a>
                                    </div>
                                    <div class="col-4">
                                        <a class="dropdown-item text-center py-3 rounded" href="/assets/doc/JUKNIS-SEMARAK-2026.pdf" target="_blank">
                                            <i class="ri-file-pdf-line fs-24 text-danger d-block mb-1"></i>
                                            <span class="fs-12 text-muted">Juknis PDF</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fullscreen Toggle Button -->
                    <button type="button" class="btn btn-topbar d-none d-sm-inline-flex" id="fullscreen-toggle" title="Layar Penuh">
                        <i class='bx bx-fullscreen fs-22'></i>
                    </button>

                    <!-- Theme Toggle (Dark / Light) -->
                    <button type="button" class="btn btn-topbar" id="theme-toggle" title="Ganti Tema">
                        <i class='bx bx-moon fs-22'></i>
                    </button>

                    <!-- Export Excel Shortcut -->
                    <a href="{{ route('admin.export') }}" class="btn btn-sm btn-success d-none d-md-inline-flex align-items-center gap-1 rounded-pill px-3">
                        <i class="ri-file-excel-2-line"></i> Export Data
                    </a>

                    <!-- User Profile Dropdown -->
                    <div class="dropdown ms-1">
                        <button class="btn btn-sm p-0 d-flex align-items-center gap-2 border-0 bg-transparent" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="/assets/img/logo.png" alt="Admin Avatar" class="rounded-circle" style="width: 36px; height: 36px; object-fit: contain; background: #111827; padding: 2px;">
                            <div class="d-none d-xl-block text-start">
                                <span class="d-block fw-semibold text-dark fs-13">{{ Auth::user()->name ?? 'Panitia Semarak' }}</span>
                                <span class="d-block text-muted fs-11">Superadmin Panitia</span>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-sm">
                            <div class="px-3 py-2 border-bottom">
                                <h6 class="mb-0 fs-13 fw-semibold">{{ Auth::user()->name ?? 'Panitia Semarak XII' }}</h6>
                                <small class="text-muted">{{ Auth::user()->email ?? 'admin@maswahas.sch.id' }}</small>
                            </div>
                            <a class="dropdown-item py-2" href="{{ route('admin.dashboard') }}">
                                <i class="ri-dashboard-line text-muted me-2 align-middle"></i> Dashboard
                            </a>
                            <a class="dropdown-item py-2" href="{{ route('admin.export') }}">
                                <i class="ri-download-cloud-line text-muted me-2 align-middle"></i> Download Data
                            </a>
                            <div class="dropdown-divider"></div>
                            <form action="{{ route('logout') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 text-danger border-0 bg-transparent w-100 text-start">
                                    <i class="ri-logout-box-line text-danger me-2 align-middle"></i> Keluar (Logout)
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Sidebar Navigation (Velzon Dark Menu) -->
        @include('admin.partials.sidebar')

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="page-content">

                <!-- Alert Messages -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="ri-check-double-line align-middle me-2 fs-16"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Page Header Greeting & Breadcrumb -->
                <div class="row mb-4 align-items-center">
                    <div class="col-md-7">
                        <h4 class="fs-18 fw-bold mb-1">Dashboard Panitia Semarak Aliyah XII</h4>
                        <p class="text-muted mb-0 fs-13">
                            MA Unggulan Wahid Hasyim Balung, Jember • Pantau pendaftaran, verifikasi peserta, dan kuota lomba secara real-time.
                        </p>
                    </div>
                    <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end align-items-center gap-2">
                        <span class="badge bg-light text-dark border px-3 py-2 fs-12">
                            <i class="ri-calendar-line text-primary me-1"></i> {{ date('d F Y') }}
                        </span>
                        <a href="{{ route('admin.export') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
                            <i class="ri-download-cloud-2-line"></i> Unduh Data
                        </a>
                    </div>
                </div>

                <!-- 4 Velzon Metric Widget Cards -->
                <div class="row">
                    <!-- Widget 1: Total Pendaftar -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Total Pendaftar</p>
                                        <h4 class="fs-22 fw-bold mb-0">{{ $stats['total_registrations'] }} <span class="fs-12 text-muted fw-normal">Peserta</span></h4>
                                        <span class="badge bg-success-subtle text-success fs-11 mt-2">
                                            <i class="ri-arrow-up-line align-middle"></i> +12% dari minggu lalu
                                        </span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded fs-20">
                                            <i class="ri-user-star-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Widget 2: Total Biaya Masuk -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Total Dana Masuk</p>
                                        <h4 class="fs-22 fw-bold mb-0">Rp {{ number_format($stats['total_fee'], 0, ',', '.') }}</h4>
                                        <span class="badge bg-info-subtle text-info fs-11 mt-2">
                                            <i class="ri-shield-check-line align-middle"></i> Terverifikasi Panitia
                                        </span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle text-success rounded fs-20">
                                            <i class="ri-wallet-3-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Widget 3: Kuota Terisi -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Tingkat Keterisian Kuota</p>
                                        <h4 class="fs-22 fw-bold mb-0">{{ $stats['quota_filled_percentage'] }}%</h4>
                                        <div class="progress mt-2" style="height: 6px; width: 140px;">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $stats['quota_filled_percentage'] }}%" aria-valuenow="{{ $stats['quota_filled_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-warning-subtle text-warning rounded fs-20">
                                            <i class="ri-pie-chart-2-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Widget 4: Menunggu Verifikasi -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Menunggu Verifikasi</p>
                                        <h4 class="fs-22 fw-bold mb-0 text-danger">{{ $stats['pending_registrations'] }} <span class="fs-12 text-muted fw-normal">Peserta</span></h4>
                                        <span class="badge bg-danger-subtle text-danger fs-11 mt-2">
                                            <i class="ri-error-warning-line align-middle"></i> Perlu Tindakan
                                        </span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-danger-subtle text-danger rounded fs-20">
                                            <i class="ri-time-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section (Velzon ApexCharts) -->
                <div class="row">
                    <div class="col-xl-8">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Grafik Tren Pendaftaran Peserta (Harian)</h4>
                                <span class="badge bg-light text-muted border">Oktober 2026</span>
                            </div>
                            <div class="card-body">
                                <div id="registration-trends-chart"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Distribusi Kategori Lomba</h4>
                            </div>
                            <div class="card-body">
                                <div id="category-distribution-chart"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cabang Lomba Quota Section -->
                <div class="row" id="section-kuota">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <h4 class="card-title">Status Kuota 8 Cabang Lomba</h4>
                                    <small class="text-muted">Kelola ketersediaan kuota pendaftaran secara real-time untuk website publik.</small>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    @foreach($competitions as $comp)
                                        <div class="col-xl-3 col-md-6">
                                            <div class="p-3 border rounded bg-light bg-opacity-25 h-100 d-flex flex-column justify-content-between">
                                                <div>
                                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                                        <span class="badge bg-primary-subtle text-primary fs-11">{{ $comp->category_label ?? $comp->category }}</span>
                                                        <span class="badge {{ $comp->status === 'open' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} fs-11">
                                                            {{ $comp->status === 'open' ? 'Buka' : 'Ditutup' }}
                                                        </span>
                                                    </div>
                                                    <h6 class="fs-14 fw-bold mb-1">{{ $comp->title }}</h6>
                                                    <p class="text-muted fs-12 mb-2">Rp {{ number_format($comp->fee, 0, ',', '.') }} / peserta</p>
                                                    
                                                    <div class="d-flex justify-content-between fs-12 text-muted mb-1">
                                                        <span>Terisi: <strong>{{ $comp->current_quota }}</strong> / {{ $comp->max_quota }}</span>
                                                        <span>{{ round(($comp->current_quota / max(1, $comp->max_quota)) * 100) }}%</span>
                                                    </div>
                                                    <div class="progress mb-3" style="height: 6px;">
                                                        <div class="progress-bar {{ $comp->status === 'open' ? 'bg-primary' : 'bg-secondary' }}" style="width: {{ min(100, round(($comp->current_quota / max(1, $comp->max_quota)) * 100)) }}%"></div>
                                                    </div>
                                                </div>

                                                <form action="{{ route('admin.toggle-quota', $comp->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm w-100 {{ $comp->status === 'open' ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                        <i class="ri-toggle-line align-middle me-1"></i>
                                                        {{ $comp->status === 'open' ? 'Tutup Pendaftaran' : 'Buka Pendaftaran' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Registration Table Section -->
                <div class="row" id="section-pendaftar">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header flex-column flex-md-row gap-3">
                                <div>
                                    <h4 class="card-title">Daftar Registrasi Peserta Terbaru</h4>
                                    <small class="text-muted">Total terdata: <span id="visible-count-badge" class="fw-bold text-primary">{{ count($registrations) }}</span> peserta.</small>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <!-- Filter Status -->
                                    <select class="form-select form-select-sm" id="table-filter-status" style="width: auto;">
                                        <option value="all">Semua Status</option>
                                        <option value="Terverifikasi">Terverifikasi</option>
                                        <option value="Menunggu Verifikasi">Menunggu Verifikasi</option>
                                    </select>

                                    <!-- Filter Cabang Lomba -->
                                    <select class="form-select form-select-sm" id="table-filter-lomba" style="width: auto;">
                                        <option value="all">Semua Lomba</option>
                                        @foreach($competitions as $c)
                                            <option value="{{ $c->id }}">{{ $c->title }}</option>
                                        @endforeach
                                    </select>

                                    <a href="{{ route('admin.export') }}" class="btn btn-sm btn-outline-success">
                                        <i class="ri-file-excel-line align-middle"></i> Export
                                    </a>
                                </div>
                            </div>

                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>ID Registrasi</th>
                                                <th>Peserta / Pendamping</th>
                                                <th>Asal Sekolah</th>
                                                <th>Cabang Lomba</th>
                                                <th>Biaya</th>
                                                <th>Status</th>
                                                <th>Tgl Daftar</th>
                                                <th class="text-end">Aksi Panitia</th>
                                            </tr>
                                        </thead>
                                        <tbody id="registrations-table-body">
                                            @forelse($registrations as $reg)
                                                <tr data-name="{{ strtolower($reg->mentor_name . ' ' . $reg->participants_names) }}"
                                                    data-school="{{ strtolower($reg->school_name) }}"
                                                    data-id="{{ strtolower($reg->reg_code) }}"
                                                    data-status="{{ $reg->payment_status }}"
                                                    data-lomba="{{ $reg->competition_id }}">
                                                    <td>
                                                        <span class="badge bg-light text-primary border fw-semibold">
                                                            {{ $reg->reg_code }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold">{{ $reg->mentor_name }}</div>
                                                        <small class="text-muted d-block" style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $reg->participants_names }}">
                                                            {{ $reg->participants_names }}
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="fw-semibold">{{ $reg->school_name }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary-subtle text-primary">
                                                            {{ $reg->competition_title }}
                                                        </span>
                                                    </td>
                                                    <td class="fw-semibold">
                                                        Rp {{ number_format($reg->total_fee, 0, ',', '.') }}
                                                    </td>
                                                    <td>
                                                        @if($reg->payment_status === 'Terverifikasi')
                                                            <span class="badge bg-success-subtle text-success">
                                                                <i class="ri-checkbox-circle-line align-middle me-1"></i> Terverifikasi
                                                            </span>
                                                        @else
                                                            <span class="badge bg-warning-subtle text-warning">
                                                                <i class="ri-time-line align-middle me-1"></i> Menunggu Verifikasi
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ $reg->created_at ? $reg->created_at->format('d M Y, H:i') : '-' }}
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-inline-flex gap-2">
                                                            <!-- WhatsApp Shortcut Button -->
                                                            @php
                                                                $waMsg = "Halo Bapak/Ibu {$reg->mentor_name} ({$reg->school_name}), kami dari Panitia SEMARAK ALIYAH XII 2026 MA Unggulan Wahid Hasyim Balung. Terkait pendaftaran Anda (Kode: {$reg->reg_code}) pada cabang {$reg->competition_title}: ";
                                                                $waUrl = \App\Models\Setting::getWaUrl($reg->mentor_wa, $waMsg);
                                                            @endphp
                                                            <a href="{{ $waUrl }}" target="_blank" class="btn-whatsapp" title="Hubungi Guru Pendamping via WhatsApp">
                                                                <i class="ri-whatsapp-line"></i> WA
                                                            </a>

                                                            <!-- Toggle Verification Button -->
                                                            <form action="{{ route('admin.verify', $reg->id) }}" method="POST" style="display:inline;">
                                                                @csrf
                                                                @if($reg->payment_status === 'Terverifikasi')
                                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Batalkan Verifikasi">
                                                                        <i class="ri-close-circle-line"></i> Batal
                                                                    </button>
                                                                @else
                                                                    <button type="submit" class="btn btn-sm btn-success" title="Verifikasi Pendaftaran">
                                                                        <i class="ri-check-line"></i> Verifikasi
                                                                    </button>
                                                                @endif
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="text-center py-4 text-muted">
                                                        Belum ada pendaftaran yang masuk.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>

                                    <div id="no-filter-results" class="text-center py-4 text-muted" style="display: none;">
                                        <i class="ri-search-eye-line fs-28 d-block mb-1 text-muted"></i>
                                        Tidak ada data yang cocok dengan kriteria filter atau pencarian Anda.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer (Velzon Layout) -->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            2026 © MA Unggulan Wahid Hasyim Balung — Panitia Semarak Aliyah XII.
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            Template Powered by <strong>Velzon</strong> • Laravel & Vite
                        </div>
                    </div>
                </div>
            </footer>
        </main>
    </div>
</body>
</html>
