<!doctype html>
<html lang="id" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <title>Gate Check-in Barcode | Velzon - Semarak Aliyah XII 2026</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Panel Check-in Meja Registrasi Scanner Barcode USB HID" name="description" />
    <meta content="MA Wahid Hasyim" name="author" />
    <link rel="shortcut icon" href="/assets/img/logo.png">
    <!-- Local Offline Fonts & Icons -->
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/vendor/remixicon/remixicon.css">
    <link rel="stylesheet" href="/assets/vendor/boxicons/css/boxicons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .scanner-card {
            background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
            border-radius: 12px;
            color: #ffffff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-left: 5px solid #0ab39c;
        }

        .scanner-input {
            font-size: 1.35rem;
            letter-spacing: 0.08em;
            font-weight: 700;
            background-color: #ffffff;
            color: #111827;
            border: 3px solid #0ab39c;
            height: 60px;
            border-radius: 8px;
            padding: 0 20px;
        }

        .scanner-input:focus {
            box-shadow: 0 0 0 4px rgba(10, 179, 156, 0.35);
            border-color: #0ab39c;
        }

        .scanner-pulse {
            animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }

        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
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
                    <span class="badge bg-success-subtle text-success fs-12 px-3 py-1">
                        <i class="ri-radar-line align-middle me-1"></i> Scanner USB HID Aktif
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
                    <div class="col-md-8">
                        <h4 class="fs-18 fw-bold mb-1">Gate Check-in Meja Registrasi Hari-H</h4>
                        <p class="text-muted mb-0 fs-13">Scan barcode ID Card peserta atau masukkan kode registrasi untuk mencatat kehadiran fisik secara real-time.</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-2 mt-md-0">
                        <span class="badge bg-light text-dark border px-3 py-2 fs-12">
                            <i class="ri-time-line text-primary me-1"></i> Mode Meja Registrasi
                        </span>
                    </div>
                </div>

                <!-- 4 KPI Presence Cards -->
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-uppercase fw-semibold text-muted fs-11 mb-1">Total Pendaftar</p>
                                        <h4 class="fs-22 fw-bold mb-0" id="kpi-total">{{ $totalPeserta }} <span class="fs-12 text-muted fw-normal">Peserta</span></h4>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded fs-20">
                                            <i class="ri-team-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-success">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-uppercase fw-semibold text-success fs-11 mb-1">Sudah Hadir (Checked-in)</p>
                                        <h4 class="fs-22 fw-bold mb-0 text-success" id="kpi-hadir">{{ $hadirCount }} <span class="fs-12 text-muted fw-normal">Peserta</span></h4>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle text-success rounded fs-20">
                                            <i class="ri-checkbox-circle-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-uppercase fw-semibold text-muted fs-11 mb-1">Belum Hadir</p>
                                        <h4 class="fs-22 fw-bold mb-0 text-warning" id="kpi-belum">{{ $belumHadirCount }} <span class="fs-12 text-muted fw-normal">Peserta</span></h4>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-warning-subtle text-warning rounded fs-20">
                                            <i class="ri-user-unfollow-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-uppercase fw-semibold text-muted fs-11 mb-1">Persentase Kehadiran</p>
                                        <h4 class="fs-22 fw-bold mb-0 text-primary" id="kpi-persen">{{ $persenHadir }}%</h4>
                                        <div class="progress mt-2" style="height: 6px; width: 140px;">
                                            <div class="progress-bar bg-success" id="kpi-progress-bar" style="width: {{ $persenHadir }}%"></div>
                                        </div>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded fs-20">
                                            <i class="ri-pie-chart-2-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hero Barcode Scanner Station -->
                <div class="card scanner-card mb-4 p-4">
                    <div class="row align-items-center">
                        <div class="col-lg-7">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="avatar-sm bg-success rounded-circle d-flex align-items-center justify-content-center scanner-pulse">
                                    <i class="ri-barcode-box-line fs-26 text-white"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-white">Area Scan Barcode / USB Scanner</h5>
                                    <small class="text-white-50">Arahkan scanner ke barcode ID Card atau ketik kode registrasi, lalu tekan Enter.</small>
                                </div>
                            </div>

                            <form id="barcode-scan-form" onsubmit="event.preventDefault(); submitScan();">
                                <div class="input-group">
                                    <input type="text" id="barcode-input" class="form-control scanner-input text-uppercase" placeholder="Scan Barcode / Masukkan Kode..." autocomplete="off" autofocus>
                                    <button class="btn btn-success px-4 fw-bold" type="submit" id="btn-submit-scan">
                                        <i class="ri-check-line align-middle me-1 fs-18"></i> CHECK-IN
                                    </button>
                                </div>
                            </form>
                            <small class="text-white-50 mt-2 d-block">
                                <i class="ri-information-line me-1"></i> Scanner input selalu aktif otomatis (auto-focus). Coba contoh: <strong>REG-SMRK-001</strong>
                            </small>
                        </div>

                        <!-- Live Scan Feedback Monitor -->
                        <div class="col-lg-5 mt-4 mt-lg-0">
                            <div class="p-3 rounded bg-dark bg-opacity-50 border border-secondary" id="scan-monitor" style="min-height: 140px;">
                                <div class="text-center text-muted py-4" id="monitor-placeholder">
                                    <i class="ri-rfid-line fs-32 text-white-50 d-block mb-1"></i>
                                    <span class="fs-13 text-white-50">Menunggu scan barcode peserta...</span>
                                </div>

                                <!-- Dynamic Success/Error Result Box -->
                                <div id="monitor-result" style="display: none;">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-success fs-12 px-2 py-1" id="res-badge">CHECK-IN SUKSES</span>
                                        <span class="text-white-50 fs-11 ms-auto" id="res-time">-</span>
                                    </div>
                                    <h6 class="text-white fw-bold fs-15 mb-1" id="res-name">-</h6>
                                    <p class="text-warning fs-12 mb-1 fw-semibold" id="res-school">-</p>
                                    <div class="fs-12 text-white-50">
                                        Lomba: <span class="text-white" id="res-lomba">-</span> | Peserta: <span class="text-white" id="res-peserta">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Check-ins Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title">Log Kehadiran Peserta Hari-H (Terbaru)</h4>
                            <small class="text-muted">Pencatatan waktu kehadiran fisik di meja registrasi.</small>
                        </div>
                        <a href="{{ route('admin.export') }}" class="btn btn-sm btn-outline-success">
                            <i class="ri-file-excel-line"></i> Export Kehadiran
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Waktu Check-in</th>
                                        <th>Kode Registrasi</th>
                                        <th>Asal Sekolah</th>
                                        <th>Guru Pendamping</th>
                                        <th>Cabang Lomba</th>
                                        <th>Nama Peserta</th>
                                        <th>Status Bayar</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-checkins-body">
                                    @forelse($recentCheckins as $chk)
                                        <tr>
                                            <td>
                                                <span class="badge bg-success-subtle text-success fs-12">
                                                    <i class="ri-time-line me-1"></i> {{ $chk->checkin_at ? $chk->checkin_at->format('H:i:s') : '-' }} WIB
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-primary border fw-semibold">{{ $chk->reg_code }}</span>
                                            </td>
                                            <td class="fw-bold">{{ $chk->school_name }}</td>
                                            <td>{{ $chk->mentor_name }}</td>
                                            <td><span class="badge bg-primary-subtle text-primary">{{ $chk->competition_title }}</span></td>
                                            <td><small class="text-muted">{{ $chk->participants_names }}</small></td>
                                            <td>
                                                <span class="badge {{ $chk->payment_status === 'Terverifikasi' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                                    {{ $chk->payment_status }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <form action="{{ route('admin.gate.undo', $chk->id) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2 fs-11" title="Batalkan Check-in jika salah scan">
                                                        Batal
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="no-checkins-row">
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                Belum ada peserta yang melakukan check-in hari ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            2026 © MA Unggulan Wahid Hasyim Balung — Gate Check-in System.
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            Semarak Aliyah XII & XIV Platform
                        </div>
                    </div>
                </div>
            </footer>
        </main>
    </div>

    <!-- Scanner Script & Audio Feedback Synthesizer -->
    <script>
        // Audio Beep Feedback (Web Audio API Synthesizer)
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        function playBeep(success = true) {
            try {
                if (audioCtx.state === 'suspended') audioCtx.resume();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);

                if (success) {
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5 note
                    osc.frequency.setValueAtTime(1320, audioCtx.currentTime + 0.08); // E6 note
                    gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.25);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.25);
                } else {
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(220, audioCtx.currentTime); // low buzz
                    gain.gain.setValueAtTime(0.4, audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.35);
                }
            } catch(e) { console.log(e); }
        }

        const barcodeInput = document.getElementById('barcode-input');
        const monitorPlaceholder = document.getElementById('monitor-placeholder');
        const monitorResult = document.getElementById('monitor-result');

        // Always keep scanner input focused
        window.addEventListener('click', (e) => {
            if (!['BUTTON', 'A', 'INPUT'].includes(e.target.tagName)) {
                if (barcodeInput) barcodeInput.focus();
            }
        });

        async function submitScan() {
            const code = barcodeInput.value.trim();
            if (!code) return;

            try {
                const response = await fetch("{{ route('admin.gate.scan') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ code: code })
                });

                const res = await response.json();

                if (response.ok && res.status === 'success') {
                    playBeep(true);

                    // Show Result in Monitor
                    monitorPlaceholder.style.display = 'none';
                    monitorResult.style.display = 'block';

                    document.getElementById('res-badge').className = res.already ? 'badge bg-warning fs-12 px-2 py-1' : 'badge bg-success fs-12 px-2 py-1';
                    document.getElementById('res-badge').innerText = res.already ? 'SUDAH PERNAH HADIR' : 'CHECK-IN SUKSES';
                    document.getElementById('res-time').innerText = res.data.checkin_at;
                    document.getElementById('res-name').innerText = res.data.mentor_name;
                    document.getElementById('res-school').innerText = res.data.school_name;
                    document.getElementById('res-lomba').innerText = res.data.competition_title;
                    document.getElementById('res-peserta').innerText = res.data.participants_names;

                    // Update Table prepend
                    if (!res.already) {
                        const noRow = document.getElementById('no-checkins-row');
                        if (noRow) noRow.remove();

                        const tbody = document.getElementById('recent-checkins-body');
                        const tr = document.createElement('tr');
                        tr.className = 'table-success';
                        tr.innerHTML = `
                            <td><span class="badge bg-success-subtle text-success fs-12"><i class="ri-time-line me-1"></i> Baru saja</span></td>
                            <td><span class="badge bg-light text-primary border fw-semibold">${res.data.reg_code}</span></td>
                            <td class="fw-bold">${res.data.school_name}</td>
                            <td>${res.data.mentor_name}</td>
                            <td><span class="badge bg-primary-subtle text-primary">${res.data.competition_title}</span></td>
                            <td><small class="text-muted">${res.data.participants_names}</small></td>
                            <td><span class="badge bg-success-subtle text-success">${res.data.payment_status}</span></td>
                            <td class="text-end"><span class="badge bg-success">Hadir</span></td>
                        `;
                        tbody.insertBefore(tr, tbody.firstChild);

                        // Update KPIs
                        const hadirEl = document.getElementById('kpi-hadir');
                        const belumEl = document.getElementById('kpi-belum');
                        const persenEl = document.getElementById('kpi-persen');
                        const progEl = document.getElementById('kpi-progress-bar');
                        const total = parseInt(document.getElementById('kpi-total').innerText, 10);

                        let currentHadir = parseInt(hadirEl.innerText, 10) + 1;
                        let currentBelum = Math.max(0, total - currentHadir);
                        let currentPersen = total > 0 ? Math.round((currentHadir / total) * 100) : 0;

                        hadirEl.innerHTML = `${currentHadir} <span class="fs-12 text-muted fw-normal">Peserta</span>`;
                        belumEl.innerHTML = `${currentBelum} <span class="fs-12 text-muted fw-normal">Peserta</span>`;
                        persenEl.innerText = `${currentPersen}%`;
                        progEl.style.width = `${currentPersen}%`;
                    }
                } else {
                    playBeep(false);
                    alert(res.message || 'Gagal memproses check-in');
                }
            } catch (err) {
                playBeep(false);
                alert('Terjadi kesalahan jaringan atau server offline: ' + err.message);
            } finally {
                barcodeInput.value = '';
                barcodeInput.focus();
            }
        }
    </script>
</body>
</html>
