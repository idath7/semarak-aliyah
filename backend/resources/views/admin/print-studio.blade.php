<!doctype html>
<html lang="id" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <title>Print Studio (ID Card & Sertifikat) | Velzon - Semarak Aliyah XII 2026</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Cetak Massal ID Card Peserta Berbarcode dan Sertifikat Penghargaan" name="description" />
    <meta content="MA Wahid Hasyim" name="author" />
    <link rel="shortcut icon" href="/assets/img/logo.png">

    <!-- Google Fonts (Poppins - Velzon Official Typography) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .card-preview-thumb {
            width: 120px;
            height: 180px;
            background: linear-gradient(135deg, #111827 0%, #1e3a8a 100%);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin: 0 auto;
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
                    <span class="badge bg-secondary-subtle text-secondary fs-12 px-3 py-1">
                        <i class="ri-printer-line align-middle me-1"></i> Print Studio & Cetak Dokumen
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

                <!-- Page Header -->
                <div class="row mb-4 align-items-center">
                    <div class="col-md-7">
                        <h4 class="fs-18 fw-bold mb-1">Print Studio & Manajemen Percetakan Dokumen</h4>
                        <p class="text-muted mb-0 fs-13">Cetak kartu tanda peserta (ID Card) dengan barcode USB scanner otomatis, serta cetak sertifikat penghargaan resmi.</p>
                    </div>
                    <div class="col-md-5 text-md-end mt-2 mt-md-0">
                        <div class="d-inline-flex gap-2">
                            <a href="{{ route('admin.print.idcards') }}" target="_blank" class="btn btn-success btn-sm rounded-pill px-3">
                                <i class="ri-printer-fill me-1"></i> Cetak Semua ID Card (Batch)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Showcase Highlights -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4 d-flex align-items-center gap-3">
                                <div class="card-preview-thumb">
                                    <i class="ri-barcode-box-line fs-32 text-warning mb-1"></i>
                                    <span class="fs-11 fw-bold text-uppercase">ID CARD</span>
                                    <small class="fs-9 text-white-50">REG-XXXXXX</small>
                                </div>
                                <div>
                                    <h5 class="fs-15 fw-bold mb-1 text-dark">ID Card Peserta & Barcode Check-in</h5>
                                    <p class="text-muted fs-12 mb-3">Format standar lanyard ukuran 8.5 x 13 cm, dilengkapi logo sekolah, nama peserta, asal pangkalan dan barcode yang kompatibel dengan scanner meja gate check-in.</p>
                                    <a href="{{ route('admin.print.idcards') }}" target="_blank" class="btn btn-sm btn-primary rounded-pill px-3">
                                        <i class="ri-printer-line me-1"></i> Buka Antrian Cetak ID Card
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4 d-flex align-items-center gap-3">
                                <div class="card-preview-thumb" style="background: linear-gradient(135deg, #14532d 0%, #064e3b 100%);">
                                    <i class="ri-award-fill fs-32 text-warning mb-1"></i>
                                    <span class="fs-11 fw-bold text-uppercase">SERTIFIKAT</span>
                                    <small class="fs-9 text-white-50">A4 Landscape</small>
                                </div>
                                <div>
                                    <h5 class="fs-15 fw-bold mb-1 text-dark">Sertifikat Penghargaan & Partisipasi</h5>
                                    <p class="text-muted fs-12 mb-3">Format resmi A4 Landscape dengan ornamen emas, tanda tangan Kepala Madrasah, verifikasi digital, dan kustomisasi predikat juara per kontingen.</p>
                                    <span class="badge bg-success-subtle text-success fs-12 px-3 py-1">
                                        <i class="ri-check-line me-1"></i> Siap Cetak dari Tabel di Bawah
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Participants Table for Printing -->
                <div class="card shadow-sm border-0">
                    <div class="card-header border-bottom bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 p-3">
                        <h5 class="fs-14 fw-bold mb-0 text-dark">
                            <i class="ri-file-list-2-line text-primary me-1"></i> Pilih Peserta untuk Dicetak
                        </h5>
                        <form method="GET" action="{{ route('admin.print.index') }}" class="d-flex gap-2">
                            <select name="competition" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Semua Cabang Lomba</option>
                                @foreach($competitions as $c)
                                    <option value="{{ $c->id }}" {{ request('competition') == $c->id ? 'selected' : '' }}>{{ $c->title }}</option>
                                @endforeach
                            </select>
                            @if(request('competition'))
                                <a href="{{ route('admin.print.idcards', ['competition' => request('competition')]) }}" target="_blank" class="btn btn-sm btn-outline-success text-nowrap">
                                    <i class="ri-printer-line me-1"></i> Cetak Cabang Ini
                                </a>
                            @endif
                        </form>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light fs-12">
                                    <tr>
                                        <th>Kode Registrasi</th>
                                        <th>Barcode</th>
                                        <th>Asal Sekolah / Pangkalan</th>
                                        <th>Official / Pembina</th>
                                        <th>Cabang Lomba</th>
                                        <th class="text-center">Status Berkas</th>
                                        <th class="text-end">Aksi Cetak Dokumen</th>
                                    </tr>
                                </thead>
                                <tbody class="fs-13">
                                    @forelse($registrations as $r)
                                        <tr>
                                            <td>
                                                <span class="badge bg-light text-primary border fw-bold fs-12">{{ $r->reg_code }}</span>
                                            </td>
                                            <td>
                                                <code class="text-dark fw-bold fs-11">{{ $r->barcode ?? $r->reg_code }}</code>
                                            </td>
                                            <td class="fw-bold">{{ $r->school_name }}</td>
                                            <td>{{ $r->mentor_name }}</td>
                                            <td><span class="badge bg-primary-subtle text-primary">{{ $r->competition_id }}</span></td>
                                            <td class="text-center">
                                                @if($r->payment_status == 'Terverifikasi')
                                                    <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-fill me-1"></i>Terverifikasi</span>
                                                @elseif($r->payment_status == 'Ditolak / Revisi')
                                                    <span class="badge bg-danger-subtle text-danger"><i class="ri-close-circle-fill me-1"></i>Ditolak</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning"><i class="ri-time-fill me-1"></i>Menunggu</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('admin.print.idcards', ['id' => $r->id]) }}" target="_blank" class="btn btn-outline-primary" title="Cetak ID Card">
                                                        <i class="ri-id-card-line me-1"></i> ID Card
                                                    </a>
                                                    <button type="button" class="btn btn-outline-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ri-award-line me-1"></i> Sertifikat
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end shadow-sm">
                                                        <a class="dropdown-item" href="{{ route('admin.print.certificate', ['id' => $r->id, 'rank' => 'Peserta']) }}" target="_blank">
                                                            <i class="ri-user-star-line me-2 text-muted"></i> Sertifikat Peserta
                                                        </a>
                                                        <a class="dropdown-item" href="{{ route('admin.print.certificate', ['id' => $r->id, 'rank' => 'Juara 1']) }}" target="_blank">
                                                            <i class="ri-trophy-fill me-2 text-warning"></i> Sertifikat Juara 1
                                                        </a>
                                                        <a class="dropdown-item" href="{{ route('admin.print.certificate', ['id' => $r->id, 'rank' => 'Juara 2']) }}" target="_blank">
                                                            <i class="ri-trophy-line me-2 text-secondary"></i> Sertifikat Juara 2
                                                        </a>
                                                        <a class="dropdown-item" href="{{ route('admin.print.certificate', ['id' => $r->id, 'rank' => 'Juara 3']) }}" target="_blank">
                                                            <i class="ri-trophy-line me-2 text-amber"></i> Sertifikat Juara 3
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted p-4">
                                                Tidak ada pendaftar ditemukan.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
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
