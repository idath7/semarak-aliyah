<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Live Stage Scoreboard | Semarak Aliyah XII 2026</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/assets/img/logo.png">

    <!-- Local Offline Fonts, RemixIcon, Boxicons & Bootstrap 5 CSS -->
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/vendor/remixicon/remixicon.css">
    <link rel="stylesheet" href="/assets/vendor/boxicons/css/boxicons.min.css">
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #090d16;
            color: #f8fafc;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Top Stage Header */
        .stage-header {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.95) 0%, rgba(9, 13, 22, 0.8) 100%);
            border-bottom: 2px solid rgba(251, 191, 36, 0.3);
            padding: 16px 28px;
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .stage-logo {
            width: 44px;
            height: 44px;
            object-fit: contain;
        }

        .stage-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            background: linear-gradient(90deg, #fbbf24 0%, #10b981 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Category Nav Tabs */
        .comp-pill-btn {
            background-color: rgba(30, 41, 59, 0.7);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 30px;
            padding: 6px 18px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .comp-pill-btn:hover {
            color: #ffffff;
            background-color: rgba(51, 65, 85, 0.8);
            border-color: rgba(251, 191, 36, 0.4);
        }
        .comp-pill-btn.active {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            color: #ffffff;
            border-color: #fbbf24;
            box-shadow: 0 0 15px rgba(251, 191, 36, 0.4);
        }

        /* Pulse Animation */
        .live-dot {
            width: 10px;
            height: 10px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 8px #10b981;
            animation: pulse-dot 1.5s infinite;
        }
        @keyframes pulse-dot {
            0% { transform: scale(0.9); opacity: 0.7; }
            50% { transform: scale(1.3); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.7; }
        }

        /* Podium Cards */
        .podium-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
            align-items: flex-end;
        }

        .podium-card {
            background: rgba(17, 24, 39, 0.85);
            border-radius: 16px;
            padding: 24px 20px;
            text-align: center;
            position: relative;
            backdrop-filter: blur(8px);
            border: 2px solid transparent;
            transition: transform 0.3s ease;
        }
        .podium-card:hover {
            transform: translateY(-6px);
        }

        /* 1st Place (Center / Taller) */
        .podium-gold {
            order: 2;
            border-color: #fbbf24;
            box-shadow: 0 0 35px rgba(251, 191, 36, 0.25);
            background: linear-gradient(180deg, rgba(30, 27, 75, 0.7) 0%, rgba(17, 24, 39, 0.95) 100%);
            padding-top: 36px;
            padding-bottom: 36px;
        }
        /* 2nd Place (Left) */
        .podium-silver {
            order: 1;
            border-color: #94a3b8;
            box-shadow: 0 0 25px rgba(148, 163, 184, 0.15);
        }
        /* 3rd Place (Right) */
        .podium-bronze {
            order: 3;
            border-color: #d97706;
            box-shadow: 0 0 25px rgba(217, 119, 6, 0.15);
        }

        .medal-badge {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 12px;
            position: relative;
        }
        .medal-gold { background: linear-gradient(135deg, #fbbf24, #d97706); color: #000; box-shadow: 0 0 16px rgba(251, 191, 36, 0.6); }
        .medal-silver { background: linear-gradient(135deg, #e2e8f0, #94a3b8); color: #000; box-shadow: 0 0 14px rgba(226, 232, 240, 0.5); }
        .medal-bronze { background: linear-gradient(135deg, #f59e0b, #78350f); color: #fff; box-shadow: 0 0 14px rgba(245, 158, 11, 0.5); }

        .podium-score {
            font-family: 'Orbitron', sans-serif;
            font-size: 3rem;
            font-weight: 900;
            line-height: 1;
            margin: 12px 0 6px;
        }
        .podium-gold .podium-score { color: #fbbf24; text-shadow: 0 0 20px rgba(251, 191, 36, 0.5); }
        .podium-silver .podium-score { color: #e2e8f0; text-shadow: 0 0 15px rgba(226, 232, 240, 0.4); }
        .podium-bronze .podium-score { color: #f59e0b; text-shadow: 0 0 15px rgba(245, 158, 11, 0.4); }

        .podium-school {
            font-size: 1.15rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 4px;
        }

        /* Leaderboard Table */
        .stage-table-box {
            background: rgba(17, 24, 39, 0.85);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.15);
            overflow: hidden;
            backdrop-filter: blur(8px);
        }

        .stage-table th {
            background: rgba(15, 23, 42, 0.95);
            color: #94a3b8;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 16px 20px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        }

        .stage-table td {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.08);
            color: #f1f5f9;
            vertical-align: middle;
        }

        .rank-pill {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
            color: #94a3b8;
        }

        .table-score {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            color: #10b981;
        }

        /* -------------------------------------------------------------
           STAGE BRACKET TREE ARCHITECTURE WITH GEOMETRIC CONNECTOR LINES
           ------------------------------------------------------------- */
        .stage-bracket-canvas {
            display: flex;
            align-items: stretch;
            gap: 40px;
            overflow-x: auto;
            padding: 24px 8px;
            min-height: 520px;
        }
        .stage-round-col {
            display: flex;
            flex-direction: column;
            width: 280px;
            flex-shrink: 0;
        }
        .stage-round-badge {
            text-align: center;
            padding: 10px 14px;
            margin-bottom: 24px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            border: 1px dashed rgba(245, 158, 11, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .stage-pairs-stream {
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            flex-grow: 1;
        }
        .stage-matchup-pair {
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            flex-grow: 1;
            position: relative;
            padding: 10px 0;
        }
        .stage-matchup-pair.with-spine::after {
            content: '';
            position: absolute;
            right: -20px;
            top: 25%;
            bottom: 25%;
            width: 2px;
            background: #475569;
            border-radius: 2px;
        }
        .stage-matchup-pair.with-spine::before {
            content: '';
            position: absolute;
            right: -40px;
            top: 50%;
            width: 20px;
            height: 2px;
            background: #475569;
            transform: translateY(-50%);
        }
        .stage-card-wrapper {
            position: relative;
            padding: 8px 0;
        }
        .stage-card-wrapper.has-outgoing::after {
            content: '';
            position: absolute;
            right: -20px;
            top: 50%;
            width: 20px;
            height: 2px;
            background: #475569;
            transform: translateY(-50%);
        }
        .stage-card-wrapper.has-incoming::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 50%;
            width: 20px;
            height: 2px;
            background: #475569;
            transform: translateY(-50%);
        }
        .stage-card-wrapper.final-connector::after {
            content: '';
            position: absolute;
            right: -40px;
            top: 50%;
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
            box-shadow: 0 0 14px rgba(245, 158, 11, 0.6);
            transform: translateY(-50%);
        }

        .stage-match-card {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.4);
            overflow: hidden;
            transition: transform 0.2s ease;
        }
        .stage-match-card.is-live {
            border-color: #ef4444 !important;
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.35);
        }
        .stage-match-header {
            padding: 8px 12px;
            background: rgba(30, 41, 59, 0.6);
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
        }
        .stage-team-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 12px;
            font-size: 13px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.06);
            color: #f1f5f9;
        }
        .stage-team-row:last-child {
            border-bottom: none;
        }
        .stage-team-row.is-winner {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            font-weight: 700;
        }
        .stage-team-row.is-loser {
            opacity: 0.45;
            text-decoration: line-through;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header class="stage-header">
        <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <img src="/assets/img/logo.png" alt="Logo" class="stage-logo">
                <div>
                    <h1 class="stage-title m-0">LIVE STAGE SCOREBOARD</h1>
                    <small class="text-secondary" style="font-size: 11px; letter-spacing: 0.5px;">
                        SEMARAK ALIYAH XII 2026 • MA UNGGULAN WAHID HASYIM BALUNG
                    </small>
                </div>
            </div>

            <!-- Arenas Tabs -->
            <div class="d-flex align-items-center gap-2 overflow-x-auto py-1">
                @foreach($competitions as $c)
                    <a href="{{ route('portal.scoreboard', ['competition' => $c->id]) }}" 
                       class="comp-pill-btn {{ ($selectedCompetition && $selectedCompetition->id == $c->id) ? 'active' : '' }}"
                       data-comp-id="{{ $c->id }}">
                        {{ $c->title }}
                    </a>
                @endforeach
            </div>

            <!-- Stage Control Tools -->
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2 bg-dark border border-secondary px-3 py-1 rounded-pill">
                    <span class="live-dot"></span>
                    <span class="fs-12 text-success fw-semibold">LIVE SYNC</span>
                </div>
                <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3" id="fullscreen-btn" title="Layar Penuh Proyektor">
                    <i class="ri-fullscreen-line me-1"></i> Fullscreen
                </button>
            </div>
        </div>
    </header>

    <!-- Main Stage Content -->
    <main class="flex-grow-1 p-4">
        <div class="container-fluid" style="max-width: 1400px;">

            @if($selectedCompetition)
                @if(!empty($isTournament))
                    <!-- TOURNAMENT KNOCKOUT STAGE VIEW (SISTEM GUGUR) -->
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div>
                            <span class="badge bg-danger text-white fw-bold fs-12 px-3 py-1 mb-1">
                                <i class="ri-sword-line me-1"></i> ARENA TURNAMEN • SISTEM GUGUR (KNOCKOUT)
                            </span>
                            <h2 class="fs-24 fw-bold text-white m-0">{{ $selectedCompetition->title }}</h2>
                        </div>
                        <div class="text-end">
                            <span class="text-muted fs-12 d-block">Status Turnamen</span>
                            <strong class="fs-16 text-warning">
                                {{ $tournamentMatches->where('status', 'completed')->count() }} / {{ count($tournamentMatches) }} Pertandingan Selesai
                            </strong>
                        </div>
                    </div>

                    @php
                        $ongoingMatch = $tournamentMatches->where('status', 'ongoing')->first();
                    @endphp

                    @if($ongoingMatch)
                        <!-- Live Match Stage Spotlight -->
                        <div class="card border-0 mb-4 shadow-lg text-white" style="background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.95)); border-radius: 16px; border: 2px solid #ef4444 !important;">
                            <div class="card-header bg-transparent border-bottom border-secondary d-flex justify-content-between align-items-center p-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="live-dot" style="background-color: #ef4444; box-shadow: 0 0 10px #ef4444;"></span>
                                    <strong class="text-danger fs-13">SEDANG TANDING DI ARENA (LIVE MATCH)</strong>
                                    <span class="badge bg-dark text-warning border border-warning fs-11">{{ $ongoingMatch->match_number }} • {{ $ongoingMatch->round_name }}</span>
                                </div>
                                <span class="fs-12 text-secondary">
                                    <i class="ri-map-pin-line text-danger me-1"></i> {{ $ongoingMatch->court ?? 'Lapangan Utama' }}
                                </span>
                            </div>
                            <div class="card-body p-4">
                                <div class="row align-items-center text-center">
                                    <div class="col-5">
                                        <h4 class="fs-20 fw-bold text-white mb-1">{{ $ongoingMatch->team1_display_name }}</h4>
                                        <div class="display-3 fw-bold text-danger font-monospace">{{ $ongoingMatch->team1_score }}</div>
                                        <span class="badge bg-secondary-subtle text-secondary fs-12">{{ $ongoingMatch->team1_set_scores ?: 'Set' }}</span>
                                    </div>
                                    <div class="col-2">
                                        <div class="avatar-sm mx-auto mb-1">
                                            <span class="avatar-title bg-danger text-white rounded-circle fs-14 fw-bold">VS</span>
                                        </div>
                                        <small class="text-muted fs-11 d-block">SKOR SET</small>
                                    </div>
                                    <div class="col-5">
                                        <h4 class="fs-20 fw-bold text-white mb-1">{{ $ongoingMatch->team2_display_name }}</h4>
                                        <div class="display-3 fw-bold text-info font-monospace">{{ $ongoingMatch->team2_score }}</div>
                                        <span class="badge bg-secondary-subtle text-secondary fs-12">{{ $ongoingMatch->team2_set_scores ?: 'Set' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Stage Tournament Bracket -->
                    <div class="stage-table-box p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fs-16 fw-bold text-white m-0">
                                <i class="ri-git-branch-line text-warning me-1"></i> Bagan Alur Pertandingan Sistem Gugur (Official Knockout Tree)
                            </h5>
                            <span class="text-secondary fs-12">Pemenang otomatis lolos ke babak selanjutnya hingga penobatan Juara 1</span>
                        </div>

                        <div class="stage-bracket-canvas">
                            @php
                                $totalStageRounds = $tournamentRounds->count();
                                $stageRoundIdx = 0;
                            @endphp

                            @foreach($tournamentRounds as $rName => $rMatches)
                                @php
                                    $stageRoundIdx++;
                                    $isStageFinal = ($stageRoundIdx === $totalStageRounds);
                                    $hasStagePrev = ($stageRoundIdx > 1);
                                @endphp

                                <div class="stage-round-col">
                                    <div class="stage-round-badge">
                                        @if($isStageFinal)
                                            <i class="ri-trophy-line text-warning"></i>
                                        @elseif($rName === 'Semi Final')
                                            <i class="ri-medal-line text-info"></i>
                                        @else
                                            <i class="ri-sword-line text-danger"></i>
                                        @endif
                                        <span>{{ $rName }}</span>
                                    </div>

                                    <div class="stage-pairs-stream">
                                        @foreach($rMatches->chunk(2) as $stagePair)
                                            @php
                                                $isSingleInStagePair = $stagePair->count() === 1;
                                            @endphp
                                            <div class="stage-matchup-pair {{ (!$isStageFinal && !$isSingleInStagePair) ? 'with-spine' : '' }}">
                                                @foreach($stagePair as $tMatch)
                                                    @php
                                                        $tIsLive = $tMatch->status === 'ongoing';
                                                        $tIsDone = $tMatch->status === 'completed';
                                                        $t1W = $tIsDone && $tMatch->winner_registration_id == $tMatch->team1_registration_id && $tMatch->winner_registration_id;
                                                        $t2W = $tIsDone && $tMatch->winner_registration_id == $tMatch->team2_registration_id && $tMatch->winner_registration_id;
                                                    @endphp

                                                    <div class="stage-card-wrapper {{ (!$isStageFinal) ? 'has-outgoing' : 'final-connector' }} {{ ($hasStagePrev) ? 'has-incoming' : '' }}">
                                                        <div class="stage-match-card {{ $tIsLive ? 'is-live' : '' }}">
                                                            <div class="stage-match-header">
                                                                <span class="text-warning fw-bold font-monospace">{{ $tMatch->match_number }}</span>
                                                                @if($tIsLive)
                                                                    <span class="badge bg-danger fs-10 px-2 py-0"><span class="live-dot" style="width: 6px; height: 6px;"></span> LIVE</span>
                                                                @elseif($tIsDone)
                                                                    <span class="badge bg-success fs-10 px-2 py-0">SELESAI</span>
                                                                @else
                                                                    <span class="text-muted fs-10">{{ $tMatch->scheduled_time ?? 'Menunggu' }}</span>
                                                                @endif
                                                            </div>

                                                            <div class="stage-team-row {{ $t1W ? 'is-winner' : ($t2W ? 'is-loser' : '') }}">
                                                                <span class="text-truncate" style="max-width: 190px;" title="{{ $tMatch->team1_display_name }}">
                                                                    @if($t1W) 🏆 @endif {{ $tMatch->team1_display_name }}
                                                                </span>
                                                                <span class="badge {{ $t1W ? 'bg-success' : 'bg-dark border border-secondary' }} fs-12 font-monospace">{{ $tMatch->team1_score }}</span>
                                                            </div>

                                                            <div class="stage-team-row {{ $t2W ? 'is-winner' : ($t1W ? 'is-loser' : '') }}">
                                                                <span class="text-truncate" style="max-width: 190px;" title="{{ $tMatch->team2_display_name }}">
                                                                    @if($t2W) 🏆 @endif {{ $tMatch->team2_display_name }}
                                                                </span>
                                                                <span class="badge {{ $t2W ? 'bg-success' : 'bg-dark border border-secondary' }} fs-12 font-monospace">{{ $tMatch->team2_score }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <!-- Grand Champion Card on Stage -->
                            @php
                                $finalM = $tournamentMatches->where('round_order', $tournamentMatches->max('round_order'))->first();
                                $champ = $finalM && $finalM->status === 'completed' ? $finalM->winner : null;
                            @endphp
                            <div style="width: 260px; flex-shrink: 0;" class="d-flex flex-column justify-content-center text-center">
                                <div class="stage-round-badge" style="background: rgba(245, 158, 11, 0.2); border-color: #f59e0b;">
                                    🏆 JUARA 1
                                </div>
                                <div class="p-4 rounded-4 border border-warning" style="background: linear-gradient(145deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.95)); box-shadow: 0 0 35px rgba(245, 158, 11, 0.25);">
                                    <div class="avatar-lg mx-auto mb-3 text-warning fs-40">
                                        <i class="ri-trophy-fill"></i>
                                    </div>
                                    @if($champ)
                                        <span class="badge bg-warning text-dark fw-bold fs-11 px-3 py-1 rounded-pill mb-2">CHAMPION</span>
                                        <h4 class="fs-18 fw-bold text-white mb-1">{{ $champ->school_name }}</h4>
                                        <small class="text-muted d-block mt-1">Official: {{ $champ->mentor_name }}</small>
                                        <div class="mt-2 text-warning fs-13">
                                            <i class="ri-star-fill"></i> <i class="ri-star-fill"></i> <i class="ri-star-fill"></i>
                                        </div>
                                    @else
                                        <span class="badge bg-dark text-secondary border border-secondary fs-11 px-3 py-1 rounded-pill mb-2">MENUNGGU FINAL</span>
                                        <h6 class="fs-13 text-secondary mb-1">Juara Turnamen</h6>
                                        <small class="text-muted fs-11 d-block">{{ $selectedCompetition->title }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- REGULAR INDIVIDUAL PERFORMANCE / EXAM SCOREBOARD (SENI & AKADEMIK) -->
                    <!-- Current Arena Subheader -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <span class="badge bg-warning text-dark fw-bold fs-12 px-3 py-1 mb-1">
                                ARENA AKTIF: {{ strtoupper($selectedCompetition->id) }}
                            </span>
                            <h2 class="fs-22 fw-bold text-white m-0">{{ $selectedCompetition->title }}</h2>
                        </div>
                        <div class="text-end">
                            <span class="text-muted fs-12 d-block">Jumlah Peserta Tampil / Dinilai</span>
                            <strong class="fs-16 text-warning" id="live-total-scored">{{ count($leaderboard) }} Kontingen</strong>
                        </div>
                    </div>

                @if(count($leaderboard) > 0)
                    <!-- Top 3 Podium -->
                    @php
                        $first = $leaderboard->get(0);
                        $second = $leaderboard->get(1);
                        $third = $leaderboard->get(2);
                    @endphp

                    <div class="podium-container">
                        <!-- 2nd Place (Silver) -->
                        <div class="podium-card podium-silver">
                            @if($second)
                                <div class="medal-badge medal-silver">
                                    <i class="ri-medal-fill"></i>
                                </div>
                                <span class="badge bg-secondary text-white fs-11 px-3 py-1 mb-2">RUNNER UP (JUARA 2)</span>
                                <div class="podium-score">{{ number_format($second->total_score, 1) }}</div>
                                <div class="podium-school text-truncate">{{ $second->registration->school_name ?? '-' }}</div>
                                <div class="fs-12 text-muted">{{ $second->registration->mentor_name ?? '-' }} • <i class="ri-timer-line"></i> {{ $second->performance_duration }}</div>
                            @else
                                <div class="text-muted py-5">Belum ada data Juara 2</div>
                            @endif
                        </div>

                        <!-- 1st Place (Gold) -->
                        <div class="podium-card podium-gold">
                            @if($first)
                                <div class="medal-badge medal-gold">
                                    <i class="ri-vip-crown-fill"></i>
                                </div>
                                <span class="badge bg-warning text-dark fs-12 px-3 py-1 fw-bold mb-2">CHAMPION (JUARA 1)</span>
                                <div class="podium-score">{{ number_format($first->total_score, 1) }}</div>
                                <div class="podium-school text-truncate fs-18 fw-bold">{{ $first->registration->school_name ?? '-' }}</div>
                                <div class="fs-13 text-warning-subtle">{{ $first->registration->mentor_name ?? '-' }} • <i class="ri-timer-line"></i> {{ $first->performance_duration }}</div>
                            @else
                                <div class="text-muted py-5">Belum ada data Juara 1</div>
                            @endif
                        </div>

                        <!-- 3rd Place (Bronze) -->
                        <div class="podium-card podium-bronze">
                            @if($third)
                                <div class="medal-badge medal-bronze">
                                    <i class="ri-medal-fill"></i>
                                </div>
                                <span class="badge bg-danger-subtle text-danger fs-11 px-3 py-1 mb-2">JUARA 3</span>
                                <div class="podium-score">{{ number_format($third->total_score, 1) }}</div>
                                <div class="podium-school text-truncate">{{ $third->registration->school_name ?? '-' }}</div>
                                <div class="fs-12 text-muted">{{ $third->registration->mentor_name ?? '-' }} • <i class="ri-timer-line"></i> {{ $third->performance_duration }}</div>
                            @else
                                <div class="text-muted py-5">Belum ada data Juara 3</div>
                            @endif
                        </div>
                    </div>

                    <!-- Full Leaderboard Table (Rank 4 and onwards, or complete list) -->
                    <div class="stage-table-box mb-5">
                        <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                            <h5 class="fs-15 fw-bold m-0 text-white">
                                <i class="ri-trophy-line text-warning me-2"></i> Klasemen Seluruh Kontingen Arena
                            </h5>
                            <span class="fs-12 text-muted">Diperbarui secara real-time dari konsol dewan juri</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table stage-table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;" class="text-center">Peringkat</th>
                                        <th>Kode</th>
                                        <th>Asal Sekolah / Pangkalan</th>
                                        <th>Official / Pembina</th>
                                        <th>Durasi Tampil</th>
                                        <th>Dewan Juri</th>
                                        <th class="text-end">Total Nilai</th>
                                    </tr>
                                </thead>
                                <tbody id="leaderboard-body">
                                    @foreach($leaderboard as $idx => $score)
                                        <tr>
                                            <td class="text-center">
                                                @if($idx === 0)
                                                    <span class="rank-pill" style="background: #fbbf24; color: #000; border-color: #f59e0b;">1</span>
                                                @elseif($idx === 1)
                                                    <span class="rank-pill" style="background: #e2e8f0; color: #000; border-color: #cbd5e1;">2</span>
                                                @elseif($idx === 2)
                                                    <span class="rank-pill" style="background: #d97706; color: #fff; border-color: #b45309;">3</span>
                                                @else
                                                    <span class="rank-pill">{{ $idx + 1 }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-dark border border-secondary text-secondary fs-11">
                                                    {{ $score->registration->reg_code ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="fw-bold fs-14 text-white">
                                                {{ $score->registration->school_name ?? '-' }}
                                            </td>
                                            <td class="text-secondary fs-13">
                                                {{ $score->registration->mentor_name ?? '-' }}
                                            </td>
                                            <td class="text-secondary fs-13">
                                                <i class="ri-timer-line me-1 text-warning"></i> {{ $score->performance_duration }}
                                            </td>
                                            <td class="text-secondary fs-13">
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $score->jury_name }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="table-score">{{ number_format($score->total_score, 1) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="stage-table-box p-5 text-center my-4">
                        <i class="ri-scales-3-line text-warning" style="font-size: 64px;"></i>
                        <h4 class="mt-3 text-white">Menunggu Penilaian Juri</h4>
                        <p class="text-muted" style="max-width: 500px; margin: 0 auto;">
                            Belum ada skor yang dikunci oleh dewan juri pada cabang lomba ini. Klasemen panggung akan muncul secara otomatis saat juri menyimpan nilai.
                        </p>
                    </div>
                @endif
            @endif
        @else
            <div class="stage-table-box p-5 text-center my-4">
                <p class="text-muted">Pilih cabang lomba dari navigasi di atas untuk memproyeksikan klasemen.</p>
            </div>
        @endif

        </div>
    </main>

    <!-- Auto-polling script for Stage Projector -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentCompId = "{{ $selectedCompetition->id ?? '' }}";

            // Fullscreen toggle
            const fullscreenBtn = document.getElementById('fullscreen-btn');
            if (fullscreenBtn) {
                fullscreenBtn.addEventListener('click', () => {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen().catch(err => {
                            console.error(`Error attempting to enable fullscreen: ${err.message}`);
                        });
                        fullscreenBtn.innerHTML = '<i class="ri-fullscreen-exit-line me-1"></i> Exit Fullscreen';
                    } else {
                        document.exitFullscreen();
                        fullscreenBtn.innerHTML = '<i class="ri-fullscreen-line me-1"></i> Fullscreen';
                    }
                });
            }

            // Polling every 5 seconds for live scores
            if (currentCompId) {
                setInterval(async () => {
                    try {
                        const res = await fetch(`/portal/live-scoreboard?competition=${currentCompId}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            if (data.status === 'success' && data.leaderboard) {
                                const totalScoredEl = document.getElementById('live-total-scored');
                                if (totalScoredEl) {
                                    totalScoredEl.innerText = `${data.leaderboard.length} Kontingen`;
                                }
                            }
                        }
                    } catch (e) {
                        console.log('Live sync tick error:', e);
                    }
                }, 5000);
            }
        });
    </script>
</body>
</html>
