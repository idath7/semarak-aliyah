<!doctype html>
<html lang="id" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <title>Penjurian Digital Juri (Arena) | Velzon - Semarak Aliyah XII 2026</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Panel Penjurian Digital, Rubrik Penilaian dan Stopwatch Arena" name="description" />
    <meta content="MA Wahid Hasyim" name="author" />
    <link rel="shortcut icon" href="/assets/img/logo.png">

    <!-- Google Fonts (Poppins - Velzon Official Typography) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .arena-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border-radius: 12px;
            overflow: hidden;
        }
        .arena-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.08);
        }
        .arena-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
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
                        <i class="ri-scales-3-line align-middle me-1"></i> Penjurian Digital Hari-H
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <i class="ri-arrow-left-line"></i> Dashboard
                    </a>
                    <a href="{{ route('portal.scoreboard') }}" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3">
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

                <!-- Page Header -->
                <div class="row mb-4 align-items-center">
                    <div class="col-md-8">
                        <h4 class="fs-18 fw-bold mb-1">Hub Arena Penjurian Digital (Dewan Juri)</h4>
                        <p class="text-muted mb-0 fs-13">Pilih arena cabang lomba untuk memulai proses scoring peserta dengan rubrik terstandar, stopwatch panggung, dan kunci nilai.</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-2 mt-md-0">
                        <a href="{{ route('portal.scoreboard') }}" target="_blank" class="btn btn-dark btn-sm rounded-pill px-3">
                            <i class="ri-tv-line me-1 text-warning"></i> Buka Layar Proyektor (Scoreboard)
                        </a>
                    </div>
                </div>

                <!-- Arena Cards Grid -->
                <div class="row g-3 mb-4">
                    @php
                        $icons = [
                            'albanjari' => ['icon' => 'ri-music-2-fill', 'color' => 'bg-success-subtle text-success'],
                            'tahfidz' => ['icon' => 'ri-book-read-fill', 'color' => 'bg-info-subtle text-info'],
                            'singing' => ['icon' => 'ri-mic-fill', 'color' => 'bg-danger-subtle text-danger'],
                            'storytelling' => ['icon' => 'ri-chat-voice-fill', 'color' => 'bg-warning-subtle text-warning'],
                            'mipa' => ['icon' => 'ri-flask-fill', 'color' => 'bg-primary-subtle text-primary'],
                            'futsal' => ['icon' => 'ri-football-fill', 'color' => 'bg-success-subtle text-success'],
                            'kaligrafi' => ['icon' => 'ri-brush-fill', 'color' => 'bg-secondary-subtle text-secondary'],
                            'puisi' => ['icon' => 'ri-quill-pen-fill', 'color' => 'bg-dark-subtle text-dark']
                        ];
                    @endphp

                    @foreach($competitions as $comp)
                        @php
                            $cfg = $icons[$comp->id] ?? ['icon' => 'ri-trophy-fill', 'color' => 'bg-primary-subtle text-primary'];
                            $pesertaCount = \App\Models\Registration::where('competition_id', $comp->id)->count();
                            $scoredCount = \App\Models\JuryScore::where('competition_id', $comp->id)->count();
                        @endphp
                        <div class="col-md-6 col-xl-3">
                            <div class="card arena-card shadow-sm border-0 h-100">
                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="arena-icon-box {{ $cfg['color'] }}">
                                            <i class="{{ $cfg['icon'] }}"></i>
                                        </div>
                                        <span class="badge bg-light text-muted border fs-11">
                                            {{ $comp->category ?? 'Umum' }}
                                        </span>
                                    </div>

                                    <h5 class="fs-15 fw-bold mb-1 text-dark">{{ $comp->title }}</h5>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <small class="text-muted fs-12">Kuota: {{ $comp->max_quota }} Kontingen</small>
                                        @if($comp->isTournament())
                                            <span class="badge bg-danger-subtle text-danger border border-danger fs-11" title="Pertandingan Menang-Kalah Sistem Gugur">
                                                <i class="ri-sword-line me-1"></i> Sistem Gugur
                                            </span>
                                        @elseif($comp->is_custom_rubric)
                                            <span class="badge bg-warning-subtle text-warning border border-warning fs-11" title="Kriteria telah dikustomisasi">
                                                <i class="ri-edit-2-line me-1"></i> Kustom ({{ ($comp->rubric_type ?? 'percentage') === 'point' ? 'Poin' : '%' }})
                                            </span>
                                        @else
                                            <span class="badge bg-primary-subtle text-primary fs-11" title="Kriteria baku standar Juknis">
                                                <i class="ri-shield-check-line me-1"></i> Juknis (%)
                                            </span>
                                        @endif
                                    </div>

                                    @if($comp->isTournament())
                                        <!-- Tournament Match Information (Sistem Gugur) -->
                                        <div class="p-3 mb-3 bg-danger-subtle rounded border border-danger-subtle">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <small class="text-danger fs-11 fw-bold">
                                                    <i class="ri-sword-line me-1"></i> Format Pertandingan:
                                                </small>
                                                <span class="badge bg-danger fs-10">Sistem Gugur (Knockout)</span>
                                            </div>
                                            <p class="fs-12 text-dark mb-2">
                                                Pertandingan 1 vs 1 (Voli / E-Sport). Tim menang melaju ke babak selanjutnya.
                                            </p>
                                            <div class="d-flex justify-content-between fs-11 text-muted">
                                                <span>Total Laga: <strong>{{ $comp->matches_count ?? 0 }} Match</strong></span>
                                                <span class="text-success fw-semibold"><i class="ri-check-line"></i> {{ $comp->completed_matches_count ?? 0 }} Selesai</span>
                                            </div>
                                        </div>

                                        <!-- Progress Matches -->
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-between fs-11 text-muted mb-1">
                                                <span>Progress Turnamen:</span>
                                                <span class="fw-bold text-dark">{{ $comp->completed_matches_count ?? 0 }} / {{ $comp->matches_count ?? 0 }} Laga</span>
                                            </div>
                                            <div class="progress mb-3" style="height: 6px;">
                                                @php
                                                    $matchPct = ($comp->matches_count ?? 0) > 0 ? round((($comp->completed_matches_count ?? 0) / $comp->matches_count) * 100) : 0;
                                                @endphp
                                                <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $matchPct }}%;"></div>
                                            </div>

                                            <div class="d-grid gap-2">
                                                <a href="{{ route('admin.tournament.arena', $comp->id) }}" class="btn btn-danger btn-sm rounded-pill fw-bold shadow-sm">
                                                    <i class="ri-trophy-line me-1"></i> Arena & Bagan Turnamen (Sistem Gugur)
                                                </a>
                                                <a href="{{ route('portal.scoreboard', ['competition' => $comp->id]) }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill fs-11">
                                                    <i class="ri-line-chart-line me-1"></i> Bagan Live Scoreboard
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        <!-- Rubric Criteria Chips Preview (Lomba Seni / Akademik) -->
                                        <div class="p-2 mb-3 bg-light rounded border">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <small class="text-muted fs-11 fw-semibold">Rubrik Penjurian ({{ count($comp->active_rubric) }} Aspek):</small>
                                                @if(($comp->rubric_type ?? 'percentage') === 'point')
                                                    <span class="badge bg-info-subtle text-info fs-10 fw-bold">Skor Poin (Maks {{ array_sum($comp->active_rubric) }})</span>
                                                @else
                                                    <span class="badge bg-dark-subtle text-dark fs-10">Total 100%</span>
                                                @endif
                                            </div>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($comp->active_rubric as $cName => $cWeight)
                                                    <span class="badge bg-white text-dark border fs-11 text-truncate" style="max-width: 100%;" title="{{ $cName }} ({{ $cWeight }}{{ ($comp->rubric_type ?? 'percentage') === 'point' ? ' Poin' : '%' }})">
                                                        {{ Str::limit($cName, 18) }}: <strong class="text-primary">{{ $cWeight }}{{ ($comp->rubric_type ?? 'percentage') === 'point' ? ' Poin' : '%' }}</strong>
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>

                                        <!-- Progress Scoring -->
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-between fs-11 text-muted mb-1">
                                                <span>Progress Penilaian:</span>
                                                <span class="fw-bold text-dark">{{ $scoredCount }} / {{ $pesertaCount }} Peserta</span>
                                            </div>
                                            <div class="progress mb-3" style="height: 6px;">
                                                @php
                                                    $pct = $pesertaCount > 0 ? round(($scoredCount / $pesertaCount) * 100) : 0;
                                                @endphp
                                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pct }}%;"></div>
                                            </div>

                                            <div class="d-grid gap-2">
                                                <a href="{{ route('admin.jury.arena', $comp->id) }}" class="btn btn-primary btn-sm rounded-pill fw-semibold">
                                                    <i class="ri-play-circle-line me-1"></i> Masuk Arena Penjurian
                                                </a>
                                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold btn-kustomisasi-rubrik" 
                                                        data-comp-id="{{ $comp->id }}"
                                                        data-comp-title="{{ $comp->title }}"
                                                        onclick="openRubricModal('{{ $comp->id }}', '{{ addslashes($comp->title) }}')">
                                                    <i class="ri-equalizer-line me-1"></i> Kustomisasi Rubrik Penjurian
                                                </button>
                                                <a href="{{ route('portal.scoreboard', ['competition' => $comp->id]) }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill fs-11">
                                                    <i class="ri-line-chart-line me-1"></i> Leaderboard Arena
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Recent Scores Table -->
                <div class="card shadow-sm border-0">
                    <div class="card-header border-bottom bg-white d-flex justify-content-between align-items-center p-3">
                        <h5 class="fs-14 fw-bold mb-0 text-dark">
                            <i class="ri-history-line text-primary me-1"></i> Rekap Penilaian Terbaru Juri
                        </h5>
                        <span class="badge bg-info-subtle text-info fs-11">8 Penilaian Terakhir</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light fs-12">
                                    <tr>
                                        <th>Waktu Input</th>
                                        <th>Cabang Lomba</th>
                                        <th>Kontingen / Sekolah</th>
                                        <th>Official / Pembina</th>
                                        <th>Nama Juri</th>
                                        <th>Durasi Tampil</th>
                                        <th class="text-center">Total Skor</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="fs-13">
                                    @forelse($recentScores as $s)
                                        <tr>
                                            <td class="text-muted fs-11">{{ $s->created_at ? $s->created_at->format('H:i:s') : '-' }} WIB</td>
                                            <td><span class="badge bg-primary-subtle text-primary fw-semibold">{{ $s->competition?->title ?? $s->competition_id }}</span></td>
                                            <td class="fw-bold">{{ $s->registration?->school_name ?? '-' }}</td>
                                            <td>{{ $s->registration?->mentor_name ?? '-' }}</td>
                                            <td><span class="badge bg-light text-dark border">{{ $s->jury_name }}</span></td>
                                            <td><i class="ri-timer-line me-1 text-muted"></i>{{ $s->performance_duration ?? '-' }}</td>
                                            <td class="text-center"><span class="badge bg-success fs-13 px-3 py-1 fw-bold">{{ number_format($s->total_score, 1) }}</span></td>
                                            <td class="text-center">
                                                @if($s->is_locked)
                                                    <span class="badge bg-danger-subtle text-danger"><i class="ri-lock-fill me-1"></i>Terkunci</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning">Draft</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted p-4">
                                                <i class="ri-file-list-3-line fs-32 text-muted mb-2 d-block"></i>
                                                Belum ada nilai yang diinput oleh dewan juri. Masuk ke arena lomba di atas untuk mulai menilai.
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

    <!-- Include Rubric Customizer Modal -->
    @include('admin.partials.rubric-customizer-modal')

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
