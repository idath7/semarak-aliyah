<!doctype html>
<html lang="id" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <title>Arena Penjurian: {{ $competition->title }} | Velzon - Semarak Aliyah XII 2026</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Panel Penilaian Juri Arena, Rubrik Penilaian dan Stopwatch Panggung" name="description" />
    <meta content="MA Wahid Hasyim" name="author" />
    <link rel="shortcut icon" href="/assets/img/logo.png">

    <!-- Google Fonts (Poppins - Velzon Official Typography) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .stopwatch-box {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 12px;
            color: #ffffff;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            border-left: 5px solid #f59e0b;
        }
        .stopwatch-display {
            font-family: 'Courier New', Courier, monospace;
            font-size: 2.8rem;
            font-weight: 800;
            letter-spacing: 2px;
            color: #fbbf24;
            text-shadow: 0 0 12px rgba(251, 191, 36, 0.4);
        }
        .participant-card-item {
            cursor: pointer;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        .participant-card-item:hover {
            background-color: rgba(64, 81, 137, 0.05);
        }
        .participant-card-item.active {
            background-color: rgba(64, 81, 137, 0.08);
            border-left-color: #405189;
        }
        .total-score-badge {
            font-size: 2.2rem;
            font-weight: 800;
            color: #0ab39c;
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
                    <span class="badge bg-warning-subtle text-warning fs-12 px-3 py-1">
                        <i class="ri-scales-3-line align-middle me-1"></i> Arena: {{ $competition->title }}
                    </span>
                    <span class="badge bg-info-subtle text-info border fs-11 px-2 py-1 d-none d-md-inline-block">
                        {{ $competition->category_label ?? strtoupper($competition->category ?? 'Umum') }}
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.jury.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="ri-arrow-left-line"></i> Pilih Arena Lain
                    </a>
                    <a href="{{ route('portal.scoreboard', ['competition' => $competition->id]) }}" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3">
                        <i class="ri-broadcast-line me-1"></i> Live Scoreboard
                    </a>
                    <button type="button" class="btn btn-topbar" id="theme-toggle">
                        <i class='bx bx-moon fs-22'></i>
                    </button>
                    <div class="dropdown ms-1">
                        <button class="btn btn-sm p-0 d-flex align-items-center gap-2 border-0 bg-transparent" type="button" data-bs-toggle="dropdown">
                            <img src="/assets/img/logo.png" alt="Admin" class="rounded-circle" style="width: 36px; height: 36px; object-fit: contain; background: #111827; padding: 2px;">
                            <span class="fw-semibold text-dark fs-13 d-none d-xl-block">{{ Auth::user()->name ?? 'Dewan Juri' }}</span>
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

                <!-- Arena Title Banner -->
                <div class="row mb-3 align-items-center">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-primary fs-12 px-2 py-1">{{ strtoupper($competition->id) }}</span>
                            <h4 class="fs-18 fw-bold mb-0">{{ $competition->title }}</h4>
                            @if(!empty($isCustomRubric))
                                <span class="badge bg-warning text-dark fs-11">
                                    <i class="ri-edit-line me-1"></i> Rubrik Kustom ({{ ($rubricType ?? 'percentage') === 'point' ? 'Skor/Poin' : 'Prosentase' }})
                                </span>
                            @else
                                <span class="badge bg-success-subtle text-success fs-11">
                                    <i class="ri-award-line me-1"></i> Juknis Resmi: {{ $competition->title }}
                                </span>
                            @endif
                        </div>
                        <p class="text-muted mb-0 fs-13 mt-1">{{ $competition->description ?? 'Gunakan rubrik terbobot khusus di bawah ini untuk menilai kontingen secara objektif dan real-time.' }}</p>
                    </div>
                    <div class="col-md-5 text-md-end mt-2 mt-md-0 d-flex justify-content-md-end align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-sm btn-kustomisasi-rubrik" 
                                data-comp-id="{{ $competition->id }}"
                                data-comp-title="{{ $competition->title }}"
                                onclick="openRubricModal('{{ $competition->id }}', '{{ addslashes($competition->title) }}')">
                            <i class="ri-sound-module-line me-1"></i> Kustomisasi Rubrik Cabang Ini
                        </button>
                        <span class="badge bg-light text-dark border px-3 py-2 fs-12">
                            <i class="ri-team-line text-primary me-1"></i> Total {{ count($registrations) }} Kontingen
                        </span>
                    </div>
                </div>

                <div class="row">
                    <!-- Left: Participant Queue -->
                    <div class="col-lg-4">
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header border-bottom bg-white p-3">
                                <h6 class="fs-14 fw-bold mb-0 text-dark">
                                    <i class="ri-user-star-line text-primary me-1"></i> Daftar Kontingen / Peserta
                                </h6>
                            </div>
                            <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                                @forelse($registrations as $reg)
                                    @php
                                        $hasScore = $reg->juryScores->where('competition_id', $competition->id)->first();
                                    @endphp
                                    <a href="{{ route('admin.jury.arena', ['competitionId' => $competition->id, 'participant' => $reg->id]) }}" 
                                       class="participant-card-item d-block p-3 border-bottom text-decoration-none text-dark {{ ($selectedParticipant && $selectedParticipant->id == $reg->id) ? 'active' : '' }}">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <span class="badge bg-light text-primary border fw-bold fs-11">{{ $reg->reg_code }}</span>
                                            @if($hasScore)
                                                <span class="badge bg-success-subtle text-success fs-11">
                                                    <i class="ri-checkbox-circle-fill me-1"></i> Skor: {{ number_format($hasScore->total_score, 1) }}
                                                </span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning fs-11">
                                                    <i class="ri-time-line me-1"></i> Belum Dinilai
                                                </span>
                                            @endif
                                        </div>
                                        <h6 class="fs-13 fw-semibold mb-1 text-truncate">{{ $reg->school_name }}</h6>
                                        <div class="d-flex justify-content-between align-items-center fs-11 text-muted">
                                            <span><i class="ri-user-line me-1"></i>{{ $reg->mentor_name }}</span>
                                            @if($reg->checkin_status == 'Hadir')
                                                <span class="badge bg-success-subtle text-success"><i class="ri-map-pin-user-line me-1"></i>Hadir di Lokasi</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Belum Check-in</span>
                                            @endif
                                        </div>
                                    </a>
                                @empty
                                    <div class="p-4 text-center text-muted">
                                        <p class="fs-13 mb-0">Belum ada peserta terdaftar pada cabang ini.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Right: Digital Scoring Panel + Stopwatch -->
                    <div class="col-lg-8">
                        @if($selectedParticipant)
                            <!-- 1. Stage Stopwatch Card -->
                            <div class="stopwatch-box p-3 mb-4">
                                <div class="row align-items-center">
                                    <div class="col-md-5 text-center text-md-start mb-2 mb-md-0">
                                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-1">
                                            <i class="ri-timer-flash-line fs-20 text-warning"></i>
                                            <span class="fs-13 fw-semibold text-uppercase text-light">Stopwatch Panggung Arena</span>
                                        </div>
                                        <div class="stopwatch-display" id="stopwatch-timer">00:00.0</div>
                                    </div>
                                    <div class="col-md-7 text-center text-md-end">
                                        <div class="d-inline-flex gap-2">
                                            <button type="button" class="btn btn-warning px-3 fw-bold" id="sw-start-btn">
                                                <i class="ri-play-fill me-1"></i> Mulai
                                            </button>
                                            <button type="button" class="btn btn-outline-light px-3" id="sw-pause-btn" disabled>
                                                <i class="ri-pause-fill me-1"></i> Jeda
                                            </button>
                                            <button type="button" class="btn btn-outline-danger px-3" id="sw-reset-btn">
                                                <i class="ri-refresh-line me-1"></i> Reset
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Scoring Rubric Form -->
                            <div class="card shadow-sm border-0">
                                <div class="card-header border-bottom bg-white d-flex justify-content-between align-items-center p-3">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge bg-primary fs-12 px-2 py-1">{{ $selectedParticipant->reg_code }}</span>
                                            <h5 class="fs-16 fw-bold mb-0 text-dark">{{ $selectedParticipant->school_name }}</h5>
                                        </div>
                                        <span class="fs-12 text-muted">
                                            Official: <strong class="text-dark">{{ $selectedParticipant->mentor_name }}</strong> ({{ $selectedParticipant->mentor_phone }})
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted d-block fs-11">TOTAL SKOR AKHIR</small>
                                        <span class="total-score-badge" id="display-total-score">
                                            {{ $existingScore ? number_format($existingScore->total_score, 1) : '0.0' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="card-body p-4">
                                    <form method="POST" action="{{ route('admin.jury.score') }}" id="jury-score-form">
                                        @csrf
                                        <input type="hidden" name="registration_id" value="{{ $selectedParticipant->id }}">
                                        <input type="hidden" name="competition_id" value="{{ $competition->id }}">
                                        <input type="hidden" name="performance_duration" id="input-duration" value="{{ $existingScore->performance_duration ?? '00:00' }}">

                                        <!-- Jury Info & Time Display -->
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label fs-12 fw-semibold">Nama / ID Dewan Juri</label>
                                                <input type="text" name="jury_name" class="form-control form-control-sm" 
                                                       value="{{ $existingScore->jury_name ?? Auth::user()->name ?? 'Dewan Juri 1' }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fs-12 fw-semibold">Durasi Tampil Tercatat</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-light"><i class="ri-time-line"></i></span>
                                                    <input type="text" id="display-duration-field" class="form-control" 
                                                           value="{{ $existingScore->performance_duration ?? '00:00' }}" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Rubric Criteria Header & Custom Adder -->
                                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                                            <div>
                                                <h6 class="fs-13 fw-bold text-dark text-uppercase m-0">
                                                    <i class="ri-calculator-line text-primary me-1"></i> Rubrik Penilaian: {{ $competition->title }}
                                                </h6>
                                                <small class="text-muted fs-11">
                                                    @if(!empty($isCustomRubric))
                                                        <span class="text-warning fw-semibold"><i class="ri-checkbox-circle-fill me-1"></i>Rubrik Kustom Aktif</span> (Tersimpan permanen).
                                                    @else
                                                        Setiap kriteria disesuaikan dengan juknis resmi cabang lomba terkait.
                                                    @endif
                                                </small>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <button type="button" class="btn btn-sm btn-primary px-3 shadow-sm btn-kustomisasi-rubrik" 
                                                        data-comp-id="{{ $competition->id }}"
                                                        data-comp-title="{{ $competition->title }}"
                                                        onclick="openRubricModal('{{ $competition->id }}', '{{ addslashes($competition->title) }}')">
                                                    <i class="ri-settings-4-line me-1"></i> Kustomisasi Rubrik & Bobot
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btn-add-criterion">
                                                    <i class="ri-add-line me-1"></i> Tambah Kriteria Cepat
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Rubric Criteria Inputs -->
                                        <div class="rubric-list mb-4" id="rubric-list-container">
                                            @php
                                                $savedScores = $existingScore ? ($existingScore->criteria_scores ?? []) : [];
                                            @endphp

                                            @foreach($criteria as $criterionName => $weight)
                                                @php
                                                    $currVal = $savedScores[$criterionName] ?? 0;
                                                @endphp
                                                <div class="p-3 mb-3 border rounded bg-light rubric-item-box" data-name="{{ $criterionName }}">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <strong class="fs-13 text-dark criterion-title">{{ $criterionName }}</strong>
                                                            <span class="badge bg-primary-subtle text-primary fs-11 weight-badge">
                                                                {{ ($rubricType ?? 'percentage') === 'point' ? 'Maks: ' . $weight . ' Poin' : 'Bobot: ' . $weight . '%' }}
                                                            </span>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <input type="number" 
                                                                   name="scores[{{ $criterionName }}]" 
                                                                   class="form-control form-control-sm text-end fw-bold rubric-number-input" 
                                                                   style="width: 85px;" 
                                                                   min="0" max="{{ $weight }}" step="0.5" 
                                                                   value="{{ $currVal }}" 
                                                                   data-weight="{{ $weight }}">
                                                            <span class="fs-12 text-muted">/ {{ $weight }} {{ ($rubricType ?? 'percentage') === 'point' ? 'Poin' : '' }}</span>
                                                            <button type="button" class="btn btn-sm text-danger p-0 ms-1 btn-delete-criterion" title="Hapus Kriteria Ini">
                                                                <i class="ri-delete-bin-line fs-14"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <input type="range" 
                                                           class="form-range rubric-range-input" 
                                                           min="0" max="{{ $weight }}" step="0.5" 
                                                           value="{{ $currVal }}">
                                                </div>
                                            @endforeach
                                        </div>

                                        <!-- Notes / Comments -->
                                        <div class="mb-4">
                                            <label class="form-label fs-12 fw-semibold">Catatan Evaluasi / Rekomendasi Juri</label>
                                            <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Catatan kelebihan atau saran teknis penampilan peserta...">{{ $existingScore->notes ?? '' }}</textarea>
                                        </div>

                                        <!-- Lock Switch & Submit Button -->
                                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded border">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="is_locked" value="1" id="lock-switch" {{ ($existingScore && $existingScore->is_locked) ? 'checked' : 'checked' }}>
                                                <label class="form-check-label fs-12 fw-semibold text-dark" for="lock-switch">
                                                    <i class="ri-lock-line me-1 text-danger"></i> Kunci Nilai Juri (Tampil di Live Scoreboard)
                                                </label>
                                            </div>

                                            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">
                                                <i class="ri-save-3-line me-1"></i> Simpan Penilaian
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header border-bottom bg-white p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fs-14 fw-bold mb-0 text-dark">
                                            <i class="ri-file-list-3-line text-primary me-1"></i> Rubrik Penjurian: {{ $competition->title }}
                                        </h6>
                                        @if(!empty($isCustomRubric))
                                            <span class="badge bg-warning text-dark fs-11">Rubrik Kustom</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success fs-11">Juknis Resmi</span>
                                        @endif
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-primary px-3 shadow-sm btn-kustomisasi-rubrik" 
                                                data-comp-id="{{ $competition->id }}"
                                                data-comp-title="{{ $competition->title }}"
                                                onclick="openRubricModal('{{ $competition->id }}', '{{ addslashes($competition->title) }}')">
                                            <i class="ri-sound-module-line me-1"></i> Edit & Kustomisasi Kriteria Sekarang
                                        </button>
                                        <span class="badge bg-primary-subtle text-primary fs-11">
                                            {{ ($rubricType ?? 'percentage') === 'point' ? 'Total Poin Maks: ' . array_sum($criteria) . ' Poin' : 'Total Bobot: 100%' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body p-4">
                                    <div class="alert alert-info border-0 shadow-none d-flex align-items-center gap-2 mb-4">
                                        <i class="ri-information-line fs-20 text-info"></i>
                                        <div class="fs-13">
                                            Berikut adalah rincian kriteria penilaian baku khusus untuk cabang <strong>{{ $competition->title }}</strong> (Kategori: {{ $competition->category_label ?? strtoupper($competition->category) }}). Silakan pilih kontingen peserta di panel kiri untuk mulai menginput skor.
                                        </div>
                                    </div>

                                    <div class="table-responsive border rounded">
                                        <table class="table table-hover align-middle mb-0 fs-13">
                                            <thead class="table-light fs-12">
                                                <tr>
                                                    <th style="width: 50px;" class="text-center">No</th>
                                                    <th>Kriteria & Aspek Penilaian</th>
                                                    <th class="text-center" style="width: 170px;">
                                                        {{ ($rubricType ?? 'percentage') === 'point' ? 'Batas Skor Maksimal' : 'Bobot Nilai Maksimal' }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $num = 1; @endphp
                                                @foreach($criteria as $cName => $cWeight)
                                                    <tr>
                                                        <td class="text-center fw-semibold text-muted">{{ $num++ }}</td>
                                                        <td class="fw-bold text-dark">
                                                            {{ $cName }}
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-primary fs-12 px-3 py-1">
                                                                {{ $cWeight }} {{ ($rubricType ?? 'percentage') === 'point' ? 'Poin' : 'Poin (' . $cWeight . '%)' }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
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

    <!-- Arena Stopwatch & Dynamic Score Calculator Scripts -->
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

            // 1. Dynamic Rubric Calculator
            const container = document.getElementById('rubric-list-container');
            const displayTotal = document.getElementById('display-total-score');

            function calculateTotal() {
                let sum = 0;
                const numInputs = document.querySelectorAll('.rubric-number-input');
                numInputs.forEach(input => {
                    sum += parseFloat(input.value) || 0;
                });
                if (displayTotal) {
                    displayTotal.innerText = sum.toFixed(1);
                }
            }

            function bindRubricRowEvents(row) {
                const numIn = row.querySelector('.rubric-number-input');
                const rangeIn = row.querySelector('.rubric-range-input');
                const delBtn = row.querySelector('.btn-delete-criterion');

                if (numIn && rangeIn) {
                    numIn.addEventListener('input', () => {
                        rangeIn.value = numIn.value;
                        calculateTotal();
                    });
                    rangeIn.addEventListener('input', () => {
                        numIn.value = rangeIn.value;
                        calculateTotal();
                    });
                }

                if (delBtn) {
                    delBtn.addEventListener('click', () => {
                        if (confirm('Hapus kriteria penilaian ini?')) {
                            row.remove();
                            calculateTotal();
                        }
                    });
                }
            }

            // Bind existing rows
            document.querySelectorAll('.rubric-item-box').forEach(row => {
                bindRubricRowEvents(row);
            });

            // Add Custom Criterion
            const addBtn = document.getElementById('btn-add-criterion');
            if (addBtn && container) {
                addBtn.addEventListener('click', () => {
                    const isPoint = '{{ $rubricType ?? "percentage" }}' === 'point';
                    const promptUnit = isPoint ? 'Poin Maksimal (Contoh: 100)' : 'Bobot Persen (Contoh: 25)';
                    const defaultWeight = isPoint ? '100' : '25';

                    const name = prompt('Masukkan Nama Kriteria Baru (Contoh: "Kreativitas Properti" atau "Variasi Nada"):');
                    if (!name || !name.trim()) return;

                    const weightStr = prompt(`Masukkan ${promptUnit} kriteria ini:`, defaultWeight);
                    const weight = parseFloat(weightStr) || (isPoint ? 100 : 25);

                    const badgeText = isPoint ? `Maks: ${weight} Poin` : `Bobot: ${weight}%`;
                    const suffixText = isPoint ? `/ ${weight} Poin` : `/ ${weight}`;

                    const div = document.createElement('div');
                    div.className = 'p-3 mb-3 border rounded bg-light rubric-item-box';
                    div.setAttribute('data-name', name.trim());
                    div.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <strong class="fs-13 text-dark criterion-title">${name.trim()}</strong>
                                <span class="badge bg-primary-subtle text-primary fs-11 weight-badge">${badgeText}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <input type="number" 
                                       name="scores[${name.trim()}]" 
                                       class="form-control form-control-sm text-end fw-bold rubric-number-input" 
                                       style="width: 85px;" 
                                       min="0" max="${weight}" step="0.5" 
                                       value="0" 
                                       data-weight="${weight}">
                                <span class="fs-12 text-muted">${suffixText}</span>
                                <button type="button" class="btn btn-sm text-danger p-0 ms-1 btn-delete-criterion" title="Hapus Kriteria Ini">
                                    <i class="ri-delete-bin-line fs-14"></i>
                                </button>
                            </div>
                        </div>
                        <input type="range" 
                               class="form-range rubric-range-input" 
                               min="0" max="${weight}" step="0.5" 
                               value="0">
                    `;
                    container.appendChild(div);
                    bindRubricRowEvents(div);
                    calculateTotal();
                });
            }

            // 2. Stopwatch Logic
            let startTime = 0;
            let elapsedTime = 0;
            let timerInterval = null;
            let isRunning = false;

            const timerDisplay = document.getElementById('stopwatch-timer');
            const startBtn = document.getElementById('sw-start-btn');
            const pauseBtn = document.getElementById('sw-pause-btn');
            const resetBtn = document.getElementById('sw-reset-btn');
            const inputDuration = document.getElementById('input-duration');
            const displayDurationField = document.getElementById('display-duration-field');

            function formatTime(ms) {
                const totalSeconds = Math.floor(ms / 1000);
                const minutes = Math.floor(totalSeconds / 60);
                const seconds = totalSeconds % 60;
                const tenths = Math.floor((ms % 1000) / 100);

                const mStr = String(minutes).padStart(2, '0');
                const sStr = String(seconds).padStart(2, '0');
                return `${mStr}:${sStr}.${tenths}`;
            }

            function updateTimer() {
                const current = Date.now();
                const total = elapsedTime + (current - startTime);
                if (timerDisplay) {
                    timerDisplay.innerText = formatTime(total);
                }
            }

            if (startBtn && pauseBtn && resetBtn) {
                startBtn.addEventListener('click', () => {
                    if (!isRunning) {
                        isRunning = true;
                        startTime = Date.now();
                        timerInterval = setInterval(updateTimer, 50);
                        startBtn.disabled = true;
                        pauseBtn.disabled = false;
                    }
                });

                pauseBtn.addEventListener('click', () => {
                    if (isRunning) {
                        isRunning = false;
                        clearInterval(timerInterval);
                        elapsedTime += Date.now() - startTime;
                        startBtn.disabled = false;
                        pauseBtn.disabled = true;

                        const timeStr = formatTime(elapsedTime);
                        if (inputDuration) inputDuration.value = timeStr;
                        if (displayDurationField) displayDurationField.value = timeStr;
                    }
                });

                resetBtn.addEventListener('click', () => {
                    isRunning = false;
                    clearInterval(timerInterval);
                    elapsedTime = 0;
                    if (timerDisplay) timerDisplay.innerText = '00:00.0';
                    startBtn.disabled = false;
                    pauseBtn.disabled = true;
                    if (inputDuration) inputDuration.value = '00:00';
                    if (displayDurationField) displayDurationField.value = '00:00';
                });
            }
        });
    </script>

    <!-- Modal Kustomisasi Rubrik Penjurian Partial -->
    @include('admin.partials.rubric-customizer-modal')
</body>
</html>
