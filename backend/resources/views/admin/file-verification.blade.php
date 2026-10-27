<!doctype html>
<html lang="id" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <title>Review Berkas Peserta (Split-Pane) | Velzon - Semarak Aliyah XII 2026</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Review & Verifikasi Dokumen Pendaftaran Peserta Semarak Aliyah" name="description" />
    <meta content="MA Wahid Hasyim" name="author" />
    <link rel="shortcut icon" href="/assets/img/logo.png">
    <!-- Local Offline Fonts & Icons -->
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/vendor/remixicon/remixicon.css">
    <link rel="stylesheet" href="/assets/vendor/boxicons/css/boxicons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .split-list-item {
            cursor: pointer;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        .split-list-item:hover {
            background-color: rgba(64, 81, 137, 0.05);
        }
        .split-list-item.active {
            background-color: rgba(64, 81, 137, 0.08);
            border-left-color: #405189;
        }
        .doc-preview-box {
            background-color: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            min-height: 280px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .wa-btn {
            background-color: #25D366;
            color: #ffffff;
            border: none;
            transition: all 0.2s;
        }
        .wa-btn:hover {
            background-color: #1ebe57;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.35);
        }
    </style>
</head>

<body>
    <div id="layout-wrapper">

        <!-- Topbar -->
        <header id="page-topbar">
            <div class="navbar-header">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-topbar" id="sidebar-toggle">
                        <i class="ri-menu-2-line fs-20"></i>
                    </button>
                    <span class="badge bg-primary-subtle text-primary fs-12 px-3 py-1">
                        <i class="ri-file-search-line align-middle me-1"></i> Modul Verifikasi Berkas
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <i class="ri-arrow-left-line"></i> Dashboard
                    </a>
                    <button type="button" class="btn btn-topbar" id="theme-toggle">
                        <i class='bx bx-moon fs-22'></i>
                    </button>
                    <div class="dropdown ms-1">
                        <button class="btn btn-sm p-0 d-flex align-items-center gap-2 border-0 bg-transparent" type="button" data-bs-toggle="dropdown">
                            <img src="/assets/img/logo.png" alt="Admin" class="rounded-circle" style="width: 36px; height: 36px; object-fit: contain; background: #111827; padding: 2px;">
                            <span class="fw-semibold text-dark fs-13 d-none d-xl-block">{{ Auth::user()->name ?? 'Panitia' }}</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-sm">
                            <form action="{{ route('logout') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger py-2">
                                    <i class="ri-logout-box-line me-2"></i> Keluar (Logout)
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Sidebar Navigation -->
        @include('admin.partials.sidebar')

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-content">

                <!-- Alert Messages -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="ri-check-double-line align-middle me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Page Header -->
                <div class="row mb-3 align-items-center">
                    <div class="col-md-7">
                        <h4 class="fs-18 fw-bold mb-1">Verifikasi & Review Berkas Dokumen (Split-Pane)</h4>
                        <p class="text-muted mb-0 fs-13">Tinjau kelengkapan berkas pendaftaran (Surat Tugas, Bukti Transfer, Kartu Pelajar) dan update status verifikasi.</p>
                    </div>
                    <div class="col-md-5 text-md-end mt-2 mt-md-0">
                        <div class="d-inline-flex gap-2">
                            <span class="badge bg-warning-subtle text-warning border px-3 py-2 fs-12">
                                <i class="ri-time-line me-1"></i> {{ $stats['pending'] }} Menunggu
                            </span>
                            <span class="badge bg-success-subtle text-success border px-3 py-2 fs-12">
                                <i class="ri-checkbox-circle-line me-1"></i> {{ $stats['verified'] }} Lolos
                            </span>
                            <span class="badge bg-danger-subtle text-danger border px-3 py-2 fs-12">
                                <i class="ri-close-circle-line me-1"></i> {{ $stats['rejected'] }} Ditolak
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Split-Pane Container -->
                <div class="row">
                    <!-- Left Pane: List of Registrations -->
                    <div class="col-lg-5 col-xl-4">
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header border-bottom p-3">
                                <form method="GET" action="{{ route('admin.verification.index') }}" class="d-flex flex-column gap-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0"><i class="ri-search-line"></i></span>
                                        <input type="text" id="filter-search-input" class="form-control bg-light border-start-0" placeholder="Cari sekolah / kode...">
                                    </div>
                                    <div class="d-flex gap-2">
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="">Semua Status</option>
                                            <option value="Menunggu Verifikasi" {{ request('status') == 'Menunggu Verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                                            <option value="Terverifikasi" {{ request('status') == 'Terverifikasi' ? 'selected' : '' }}>Terverifikasi</option>
                                            <option value="Ditolak / Revisi" {{ request('status') == 'Ditolak / Revisi' ? 'selected' : '' }}>Ditolak / Revisi</option>
                                        </select>
                                        <select name="competition" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="">Semua Lomba</option>
                                            @foreach($competitions as $c)
                                                <option value="{{ $c->id }}" {{ request('competition') == $c->id ? 'selected' : '' }}>{{ $c->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </form>
                            </div>

                            <div class="card-body p-0" style="max-height: 650px; overflow-y: auto;" id="participant-list-container">
                                @forelse($registrations as $r)
                                    <a href="{{ route('admin.verification.index', array_merge(request()->query(), ['select' => $r->id])) }}" 
                                       class="split-list-item d-block p-3 border-bottom text-decoration-none text-dark {{ ($selectedRegistration && $selectedRegistration->id == $r->id) ? 'active' : '' }}"
                                       data-search="{{ strtolower($r->school_name . ' ' . $r->reg_code . ' ' . $r->mentor_name) }}">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <span class="badge bg-light text-primary border fw-bold fs-11">{{ $r->reg_code }}</span>
                                            @if($r->payment_status == 'Terverifikasi')
                                                <span class="badge bg-success-subtle text-success fs-10"><i class="ri-checkbox-circle-fill me-1"></i>Terverifikasi</span>
                                            @elseif($r->payment_status == 'Ditolak / Revisi')
                                                <span class="badge bg-danger-subtle text-danger fs-10"><i class="ri-close-circle-fill me-1"></i>Ditolak</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning fs-10"><i class="ri-time-fill me-1"></i>Menunggu</span>
                                            @endif
                                        </div>
                                        <h6 class="fs-13 fw-semibold mb-1 text-truncate">{{ $r->school_name }}</h6>
                                        <div class="d-flex justify-content-between align-items-center fs-11 text-muted">
                                            <span><i class="ri-user-line me-1"></i>{{ $r->mentor_name }}</span>
                                            <span class="badge bg-secondary-subtle text-secondary">{{ $r->competition_id }}</span>
                                        </div>
                                    </a>
                                @empty
                                    <div class="p-4 text-center text-muted">
                                        <i class="ri-file-unknow-line fs-36 text-muted mb-2"></i>
                                        <p class="fs-13 mb-0">Tidak ada data pendaftar sesuai filter.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Right Pane: Document Review & Verification Decision -->
                    <div class="col-lg-7 col-xl-8">
                        @if($selectedRegistration)
                            <div class="card shadow-sm border-0">
                                <div class="card-header border-bottom bg-white d-flex justify-content-between align-items-center p-3">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge bg-primary fs-12 px-2 py-1">{{ $selectedRegistration->reg_code }}</span>
                                            <h5 class="fs-16 fw-bold mb-0 text-dark">{{ $selectedRegistration->school_name }}</h5>
                                        </div>
                                        <span class="fs-12 text-muted">
                                            <i class="ri-calendar-line me-1"></i> Terdaftar pada: {{ $selectedRegistration->created_at ? $selectedRegistration->created_at->format('d M Y, H:i') : '-' }} WIB
                                        </span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        @php
                                            $mentorNumber = $selectedRegistration->mentor_wa ?? $selectedRegistration->mentor_phone;
                                            $waMsgText = "Halo Bapak/Ibu {$selectedRegistration->mentor_name} dari {$selectedRegistration->school_name}.\n\nKami dari Panitia Semarak Aliyah XII 2026 MA Unggulan Wahid Hasyim Balung menginfokan status pendaftaran berkas {$selectedRegistration->reg_code} untuk cabang lomba {$selectedRegistration->competition_id}:\n\nStatus: {$selectedRegistration->payment_status}\nCatatan: " . ($selectedRegistration->verification_notes ?? 'Berkas Anda telah ditinjau panitia.') . "\n\nTerima kasih.";
                                            $waUrl = \App\Models\Setting::getWaUrl($mentorNumber, $waMsgText);
                                        @endphp
                                        <a href="{{ $waUrl }}" target="_blank" class="btn btn-sm wa-btn d-flex align-items-center gap-1 rounded-pill px-3">
                                            <i class="ri-whatsapp-line fs-14"></i> Hubungi Pembina
                                        </a>
                                    </div>
                                </div>

                                <div class="card-body p-4">
                                    <!-- Meta Info Grid -->
                                    <div class="row g-3 mb-4">
                                        <div class="col-sm-6 col-md-3">
                                            <div class="p-2 border rounded bg-light">
                                                <small class="text-muted d-block fs-11">Pembina / Official</small>
                                                <span class="fw-semibold fs-13 text-dark">{{ $selectedRegistration->mentor_name }}</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-md-3">
                                            <div class="p-2 border rounded bg-light">
                                                <small class="text-muted d-block fs-11">Nomor WhatsApp</small>
                                                <span class="fw-semibold fs-13 text-dark">{{ $selectedRegistration->mentor_phone }}</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-md-3">
                                            <div class="p-2 border rounded bg-light">
                                                <small class="text-muted d-block fs-11">Cabang Lomba</small>
                                                <span class="fw-semibold fs-13 text-primary">{{ strtoupper($selectedRegistration->competition_id) }}</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-md-3">
                                            <div class="p-2 border rounded bg-light">
                                                <small class="text-muted d-block fs-11">Status Saat Ini</small>
                                                <span class="fw-bold fs-13 {{ $selectedRegistration->payment_status == 'Terverifikasi' ? 'text-success' : ($selectedRegistration->payment_status == 'Ditolak / Revisi' ? 'text-danger' : 'text-warning') }}">
                                                    {{ $selectedRegistration->payment_status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Registered Participants List -->
                                    <div class="mb-4">
                                        <h6 class="fs-13 fw-bold text-dark text-uppercase mb-2">
                                            <i class="ri-team-line text-primary me-1"></i> Data Anggota / Peserta yang Didaftarkan:
                                        </h6>
                                        <div class="table-responsive border rounded">
                                            <table class="table table-sm table-bordered mb-0 fs-12">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width: 40px;" class="text-center">No</th>
                                                        <th>Nama Peserta</th>
                                                        <th>Nomor Identitas / NISN</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $participants = is_array($selectedRegistration->participants) 
                                                            ? $selectedRegistration->participants 
                                                            : json_decode($selectedRegistration->participants, true) ?? [];
                                                    @endphp
                                                    @forelse($participants as $idx => $p)
                                                        <tr>
                                                            <td class="text-center fw-semibold">{{ $idx + 1 }}</td>
                                                            <td class="fw-bold text-dark">{{ is_array($p) ? ($p['name'] ?? '-') : $p }}</td>
                                                            <td><span class="badge bg-light text-secondary border">{{ is_array($p) ? ($p['nisn'] ?? $p['id_number'] ?? '-') : '-' }}</span></td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="3" class="text-center text-muted">Belum ada rincian nama anggota.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Documents Inspection Tabs -->
                                    <div class="mb-4">
                                        <h6 class="fs-13 fw-bold text-dark text-uppercase mb-3">
                                            <i class="ri-attachment-line text-primary me-1"></i> Berkas Lampiran Terunggah:
                                        </h6>

                                        <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" data-bs-toggle="tab" href="#tab-bukti-bayar" role="tab">
                                                    <i class="ri-bank-card-line me-1"></i> Bukti Pembayaran
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" href="#tab-surat-tugas" role="tab">
                                                    <i class="ri-file-text-line me-1"></i> Surat Tugas Sekolah
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" href="#tab-kartu-pelajar" role="tab">
                                                    <i class="ri-id-card-line me-1"></i> Kartu Pelajar / NISN
                                                </a>
                                            </li>
                                        </ul>

                                        <div class="tab-content">
                                            <!-- Tab 1: Bukti Transfer -->
                                            <div class="tab-pane active" id="tab-bukti-bayar" role="tabpanel">
                                                <div class="doc-preview-box">
                                                    @if($selectedRegistration->payment_receipt)
                                                        <img src="{{ $selectedRegistration->payment_receipt }}" alt="Bukti Transfer" class="img-fluid rounded shadow-sm mb-3" style="max-height: 380px;">
                                                        <div>
                                                            <a href="{{ $selectedRegistration->payment_receipt }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="ri-external-link-line me-1"></i> Buka Ukuran Penuh
                                                            </a>
                                                        </div>
                                                    @else
                                                        <i class="ri-file-warning-line fs-48 text-warning mb-2"></i>
                                                        <h6 class="fw-semibold">Bukti Pembayaran Belum Diunggah</h6>
                                                        <p class="text-muted fs-12 mb-0">Peserta mendaftar melalui jalur non-tunai atau belum mengunggah struk transfer.</p>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Tab 2: Surat Tugas -->
                                            <div class="tab-pane" id="tab-surat-tugas" role="tabpanel">
                                                <div class="doc-preview-box">
                                                    <i class="ri-file-pdf-fill fs-48 text-danger mb-2"></i>
                                                    <h6 class="fw-semibold">Surat Rekomendasi / Tugas Kepala Sekolah</h6>
                                                    <p class="text-muted fs-12 mb-3">Dokumen pengesahan kontingen resmi dari sekolah asal pendaftar.</p>
                                                    <span class="badge bg-success-subtle text-success fs-12 px-3 py-2">
                                                        <i class="ri-check-line me-1"></i> Dokumen Terlampir dan Sah
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Tab 3: Kartu Pelajar -->
                                            <div class="tab-pane" id="tab-kartu-pelajar" role="tabpanel">
                                                <div class="doc-preview-box">
                                                    <i class="ri-profile-line fs-48 text-primary mb-2"></i>
                                                    <h6 class="fw-semibold">Scan Kartu Pelajar / Surat Keterangan Siswa Aktif</h6>
                                                    <p class="text-muted fs-12 mb-3">Verifikasi keaslian status peserta tingkat SMP/MTs sederajat se-Jawa Timur.</p>
                                                    <span class="badge bg-info-subtle text-info fs-12 px-3 py-2">
                                                        <i class="ri-shield-check-line me-1"></i> Terverifikasi Sesuai Jenjang
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Decision Action Form -->
                                    <div class="p-3 bg-light rounded border">
                                        <h6 class="fs-13 fw-bold text-dark text-uppercase mb-3">
                                            <i class="ri-shield-check-line text-success me-1"></i> Form Keputusan Verifikator:
                                        </h6>
                                        <form method="POST" action="{{ route('admin.verification.status', $selectedRegistration->id) }}">
                                            @csrf
                                            <div class="row g-3">
                                                <div class="col-md-5">
                                                    <label class="form-label fs-12 fw-semibold">Status Validasi</label>
                                                    <select name="status" class="form-select form-select-sm" required>
                                                        <option value="Terverifikasi" {{ $selectedRegistration->payment_status == 'Terverifikasi' ? 'selected' : '' }}>
                                                            ✅ Terverifikasi (Lolos & Sah)
                                                        </option>
                                                        <option value="Menunggu Verifikasi" {{ $selectedRegistration->payment_status == 'Menunggu Verifikasi' ? 'selected' : '' }}>
                                                            ⏳ Menunggu Verifikasi
                                                        </option>
                                                        <option value="Ditolak / Revisi" {{ $selectedRegistration->payment_status == 'Ditolak / Revisi' ? 'selected' : '' }}>
                                                            ❌ Ditolak / Perlu Revisi Berkas
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-md-7">
                                                    <label class="form-label fs-12 fw-semibold">Catatan Verifikator (Akan Dilihat Pendaftar)</label>
                                                    <input type="text" name="notes" class="form-control form-control-sm" 
                                                           value="{{ $selectedRegistration->verification_notes ?? '' }}" 
                                                           placeholder="Contoh: Berkas lengkap, nomor rekening dan stempel valid.">
                                                </div>
                                                <div class="col-12 text-end">
                                                    <button type="submit" class="btn btn-primary btn-sm px-4">
                                                        <i class="ri-save-line me-1"></i> Simpan Keputusan Verifikasi
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        @else
                            <div class="card shadow-sm border-0">
                                <div class="card-body p-5 text-center text-muted">
                                    <i class="ri-cursor-line fs-48 text-muted mb-3"></i>
                                    <h5>Pilih Peserta dari Daftar Kiri</h5>
                                    <p class="fs-13 text-muted">Klik salah satu sekolah atau kode pendaftaran di panel kiri untuk membuka lembar review dokumen.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </main>

        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        2026 © MA Unggulan Wahid Hasyim Balung.
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        Semarak Aliyah XII • Official Operational System
                    </div>
                </div>
            </div>
        </footer>

    </div>

    <!-- Client-side filter search for left pane -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('filter-search-input');
            const listItems = document.querySelectorAll('.split-list-item');

            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const term = e.target.value.toLowerCase().trim();
                    listItems.forEach(item => {
                        const hay = item.getAttribute('data-search') || '';
                        if (hay.includes(term)) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }

            // Theme toggle
            const themeToggleBtn = document.getElementById('theme-toggle');
            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', () => {
                    const html = document.documentElement;
                    const currentTheme = html.getAttribute('data-bs-theme');
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    html.setAttribute('data-bs-theme', newTheme);
                    const icon = themeToggleBtn.querySelector('i');
                    if (icon) {
                        icon.className = newTheme === 'dark' ? 'bx bx-sun fs-22' : 'bx bx-moon fs-22';
                    }
                });
            }

            // Sidebar toggle
            const sidebarToggleBtn = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('app-sidebar');
            if (sidebarToggleBtn && sidebar) {
                sidebarToggleBtn.addEventListener('click', () => {
                    document.body.classList.toggle('sidebar-enable');
                });
            }
        });
    </script>
</body>
</html>
