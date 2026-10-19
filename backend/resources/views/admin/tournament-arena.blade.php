<!doctype html>
<html lang="id" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <title>Arena Turnamen Sistem Gugur: {{ $competition->title }} | Velzon - Semarak Aliyah XII 2026</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Panel Pertandingan & Bagan Turnamen Sistem Gugur (Knockout Bracket) Resmi" name="description" />
    <meta content="MA Wahid Hasyim Balung" name="author" />
    <link rel="shortcut icon" href="/assets/img/logo.png">

    <!-- Google Fonts (Poppins & Orbitron for Electronic Scoreboard) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700;800;900&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --vz-bracket-line: #cbd5e1;
            --vz-bracket-line-active: #ef4444;
            --vz-bracket-line-win: #10b981;
            --vz-bracket-line-gold: #f59e0b;
        }
        [data-bs-theme="dark"] {
            --vz-bracket-line: #334155;
            --vz-bracket-line-active: #f87171;
            --vz-bracket-line-win: #34d399;
            --vz-bracket-line-gold: #fbbf24;
        }

        body {
            font-family: 'Poppins', sans-serif;
        }

        /* Electronic Scoreboard Font */
        .font-digital {
            font-family: 'Orbitron', monospace, sans-serif;
            font-variant-numeric: tabular-nums;
            letter-spacing: -1px;
        }

        /* -------------------------------------------------------------
           BRACKET TREE ARCHITECTURE WITH GEOMETRIC CONNECTOR LINES
           ------------------------------------------------------------- */
        .bracket-tree-canvas {
            display: flex;
            align-items: stretch;
            gap: 40px;
            overflow-x: auto;
            padding: 24px 16px;
            min-height: 640px;
            scroll-behavior: smooth;
            background: #f8fafc;
            border-radius: 12px;
        }
        [data-bs-theme="dark"] .bracket-tree-canvas {
            background: #0b1120;
        }

        .bracket-tree-canvas::-webkit-scrollbar {
            height: 8px;
        }
        .bracket-tree-canvas::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 4px;
        }
        .bracket-tree-canvas::-webkit-scrollbar-thumb {
            background: rgba(64, 81, 137, 0.25);
            border-radius: 4px;
        }
        [data-bs-theme="dark"] .bracket-tree-canvas::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
        }

        .bracket-round-col {
            display: flex;
            flex-direction: column;
            width: 275px;
            flex-shrink: 0;
        }

        /* Round Column Header Pill */
        .bracket-round-pill {
            text-align: center;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 9px 14px;
            border-radius: 30px;
            margin-bottom: 24px;
            background: linear-gradient(135deg, rgba(64, 81, 137, 0.08), rgba(64, 81, 137, 0.15));
            color: #405189;
            border: 1px solid rgba(64, 81, 137, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        [data-bs-theme="dark"] .bracket-round-pill {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
            color: #93c5fd;
            border-color: rgba(148, 163, 184, 0.25);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .bracket-pairs-stream {
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            flex-grow: 1;
        }

        /* Matchup Pair Container (Grouping 2 matches feeding into next round) */
        .bracket-matchup-pair {
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            flex-grow: 1;
            position: relative;
            padding: 10px 0;
        }

        /* Vertical Spine Bracket Line connecting Top Match and Bottom Match */
        .bracket-matchup-pair.with-spine::after {
            content: '';
            position: absolute;
            right: -20px;
            top: 25%;
            bottom: 25%;
            width: 2px;
            background: var(--vz-bracket-line);
            border-radius: 2px;
            transition: all 0.25s ease;
        }

        /* Horizontal Bridge extending from Spine to Next Round */
        .bracket-matchup-pair.with-spine::before {
            content: '';
            position: absolute;
            right: -40px;
            top: 50%;
            width: 20px;
            height: 2px;
            background: var(--vz-bracket-line);
            transform: translateY(-50%);
            transition: all 0.25s ease;
        }

        /* Match Card Wrapper (Holds the card and horizontal tails) */
        .bracket-card-wrapper {
            position: relative;
            padding: 8px 0;
        }

        /* Outgoing Horizontal Line to the Right Spine */
        .bracket-card-wrapper.has-outgoing::after {
            content: '';
            position: absolute;
            right: -20px;
            top: 50%;
            width: 20px;
            height: 2px;
            background: var(--vz-bracket-line);
            transform: translateY(-50%);
            transition: all 0.25s ease;
        }

        /* Incoming Horizontal Line from Previous Round */
        .bracket-card-wrapper.has-incoming::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 50%;
            width: 20px;
            height: 2px;
            background: var(--vz-bracket-line);
            transform: translateY(-50%);
            transition: all 0.25s ease;
        }

        /* Grand Final to Champion Golden Beam */
        .bracket-card-wrapper.final-connector::after {
            content: '';
            position: absolute;
            right: -40px;
            top: 50%;
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
            box-shadow: 0 0 12px rgba(245, 158, 11, 0.6);
            transform: translateY(-50%);
        }

        /* -------------------------------------------------------------
           PREMIUM TOURNAMENT MATCH CARD DESIGN
           ------------------------------------------------------------- */
        .bracket-match-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            overflow: hidden;
            position: relative;
        }

        [data-bs-theme="dark"] .bracket-match-card {
            background: rgba(30, 41, 59, 0.7);
            border-color: rgba(148, 163, 184, 0.18);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(8px);
        }

        .bracket-match-card:hover {
            transform: translateY(-3px);
            border-color: #405189;
            box-shadow: 0 12px 24px -4px rgba(64, 81, 137, 0.16);
        }

        [data-bs-theme="dark"] .bracket-match-card:hover {
            border-color: #60a5fa;
            box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.6);
        }

        /* Active / Selected Match Card in Scorekeeper */
        .bracket-match-card.is-selected {
            border: 2px solid #0ab39c !important;
            box-shadow: 0 0 0 4px rgba(10, 179, 156, 0.18), 0 8px 20px rgba(0,0,0,0.08) !important;
        }
        [data-bs-theme="dark"] .bracket-match-card.is-selected {
            border-color: #2dd4bf !important;
            box-shadow: 0 0 0 4px rgba(45, 212, 191, 0.25), 0 8px 24px rgba(0,0,0,0.4) !important;
        }

        /* Live Match Card (Pulsing Red) */
        .bracket-match-card.is-live {
            border-color: #ef4444 !important;
            animation: pulse-live-card 2s infinite;
        }
        @keyframes pulse-live-card {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5); }
            70% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        /* Match Card Header Meta */
        .bracket-match-meta {
            padding: 7px 12px;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            font-weight: 600;
        }
        [data-bs-theme="dark"] .bracket-match-meta {
            background: rgba(15, 23, 42, 0.6);
            border-bottom-color: rgba(148, 163, 184, 0.1);
        }

        .bracket-match-num {
            font-weight: 800;
            color: #334155;
            letter-spacing: 0.3px;
        }
        [data-bs-theme="dark"] .bracket-match-num {
            color: #cbd5e1;
        }

        /* Team Row */
        .bracket-team-slot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 12px;
            font-size: 12px;
            border-bottom: 1px solid #f8fafc;
            transition: background 0.15s ease;
        }
        [data-bs-theme="dark"] .bracket-team-slot {
            border-bottom-color: rgba(148, 163, 184, 0.06);
        }
        .bracket-team-slot:last-child {
            border-bottom: none;
        }

        /* Winner & Loser Row Status */
        .bracket-team-slot.is-winner {
            background: rgba(16, 185, 129, 0.08);
            font-weight: 700;
            color: #059669;
        }
        [data-bs-theme="dark"] .bracket-team-slot.is-winner {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
        }

        .bracket-team-slot.is-loser {
            opacity: 0.5;
            text-decoration: line-through;
        }

        .bracket-team-identity {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow: hidden;
            flex-grow: 1;
            padding-right: 8px;
        }

        .bracket-school-avatar {
            width: 22px;
            height: 22px;
            border-radius: 6px;
            background: rgba(64, 81, 137, 0.12);
            color: #405189;
            font-size: 10px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            text-transform: uppercase;
        }
        [data-bs-theme="dark"] .bracket-school-avatar {
            background: rgba(255, 255, 255, 0.1);
            color: #93c5fd;
        }
        .bracket-team-slot.is-winner .bracket-school-avatar {
            background: #10b981;
            color: #ffffff;
        }

        .bracket-team-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 175px;
            font-size: 12px;
        }

        .bracket-score-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            height: 24px;
            padding: 0 6px;
            font-weight: 800;
            font-size: 12px;
            border-radius: 6px;
            background: #f1f5f9;
            color: #334155;
            flex-shrink: 0;
        }
        [data-bs-theme="dark"] .bracket-score-pill {
            background: rgba(15, 23, 42, 0.7);
            color: #f1f5f9;
            border: 1px solid rgba(148, 163, 184, 0.15);
        }

        .bracket-team-slot.is-winner .bracket-score-pill {
            background: #10b981;
            color: #ffffff;
            border: none;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.35);
        }

        /* -------------------------------------------------------------
           CHAMPION SHOWCASE PODIUM (SANG JUARA 1)
           ------------------------------------------------------------- */
        .bracket-champion-podium {
            width: 270px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .champion-trophy-pedestal {
            background: linear-gradient(145deg, #ffffff, #fffbeb);
            border: 2px solid #fbbf24;
            border-radius: 16px;
            padding: 24px 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.15);
            position: relative;
            overflow: hidden;
        }
        [data-bs-theme="dark"] .champion-trophy-pedestal {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.95));
            border-color: #f59e0b;
            box-shadow: 0 0 35px rgba(245, 158, 11, 0.22);
        }

        .champion-aura-glow {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(251, 191, 36, 0.4) 0%, rgba(251, 191, 36, 0) 70%);
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            pointer-events: none;
            animation: pulse-aura 3s infinite alternate ease-in-out;
        }
        @keyframes pulse-aura {
            0% { transform: translateX(-50%) scale(0.9); opacity: 0.6; }
            100% { transform: translateX(-50%) scale(1.3); opacity: 1; }
        }

        .champion-cup-badge {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fbbf24, #d97706);
            color: #ffffff;
            font-size: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 18px rgba(217, 119, 6, 0.35);
            margin-bottom: 14px;
            position: relative;
            z-index: 1;
        }

        /* -------------------------------------------------------------
           ELECTRONIC STADIUM SCOREKEEPER CONTROL DESK
           ------------------------------------------------------------- */
        .stadium-desk-panel {
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.15);
            color: #ffffff;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .stadium-corner-box {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(148, 163, 184, 0.15);
            border-radius: 14px;
            padding: 16px 12px;
            text-align: center;
            transition: all 0.25s ease;
            position: relative;
        }

        .stadium-corner-box.is-leading {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.08);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.15);
        }

        .stadium-score-led {
            font-size: 3.6rem;
            font-weight: 900;
            line-height: 1;
            color: #f8fafc;
            text-shadow: 0 0 15px rgba(255, 255, 255, 0.25);
            user-select: none;
            margin: 8px 0;
        }
        .stadium-corner-box.is-leading .stadium-score-led {
            color: #34d399;
            text-shadow: 0 0 20px rgba(52, 211, 153, 0.5);
        }

        .stadium-pad-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
            border: none;
        }
        .stadium-pad-btn:active {
            transform: scale(0.92);
        }

        /* Interactive Winner Decision Cards (No Plain Radio) */
        .winner-select-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            background: #ffffff;
            color: #1e293b;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        [data-bs-theme="dark"] .winner-select-card {
            background: rgba(30, 41, 59, 0.6);
            border-color: rgba(148, 163, 184, 0.2);
            color: #ffffff;
            box-shadow: none;
        }
        .winner-select-card:hover {
            border-color: #f59e0b;
            background: rgba(245, 158, 11, 0.08);
            transform: translateY(-1px);
        }
        .winner-select-card.is-chosen {
            border-color: #10b981 !important;
            background: rgba(16, 185, 129, 0.12) !important;
            box-shadow: 0 0 16px rgba(16, 185, 129, 0.25);
        }
        .winner-team-title {
            color: #1e293b;
            font-size: 12px;
            font-weight: 700;
        }
        [data-bs-theme="dark"] .winner-team-title {
            color: #f8fafc;
        }

        /* Segmented Match Status Control */
        .status-segmented-bar {
            display: flex;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 4px;
            gap: 4px;
        }
        [data-bs-theme="dark"] .status-segmented-bar {
            background: rgba(15, 23, 42, 0.8);
            border-color: rgba(148, 163, 184, 0.2);
        }
        .status-segment-opt {
            flex: 1;
            text-align: center;
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0;
            color: #64748b;
            user-select: none;
        }
        [data-bs-theme="dark"] .status-segment-opt {
            color: #94a3b8;
        }
        .status-segment-opt input {
            display: none;
        }
        .status-segment-opt.is-active-scheduled {
            background: #ffffff;
            color: #1e293b;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
        }
        [data-bs-theme="dark"] .status-segment-opt.is-active-scheduled {
            background: #334155;
            color: #ffffff;
            box-shadow: none;
        }
        .status-segment-opt.is-active-live {
            background: #ef4444;
            color: #ffffff;
            box-shadow: 0 0 12px rgba(239, 68, 68, 0.5);
        }
        .status-segment-opt.is-active-completed {
            background: #10b981;
            color: #ffffff;
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.4);
        }

        /* Live Radar Beacon */
        .live-pulse-beacon {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #ef4444;
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            animation: pulse-beacon 1.5s infinite;
            margin-right: 4px;
            vertical-align: middle;
        }
        @keyframes pulse-beacon {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 7px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        /* Fullscreen Bracket Expansion Transition */
        .bracket-view-fullscreen {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1050;
            background: #0f172a !important;
            padding: 24px !important;
            overflow: auto;
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
                    <span class="badge bg-danger-subtle text-danger fs-12 px-3 py-1 fw-bold">
                        <i class="ri-trophy-line align-middle me-1"></i> Arena Turnamen • Sistem Gugur (Knockout)
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.jury.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="ri-arrow-left-line me-1"></i> Hub Penjurian
                    </a>
                    <a href="{{ route('portal.scoreboard', ['competition' => $competition->id]) }}" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-sm">
                        <i class="ri-broadcast-line me-1"></i> Live Scoreboard Panggung
                    </a>
                    <button type="button" class="btn btn-icon btn-topbar rounded-circle" id="theme-toggle">
                        <i class="bx bx-moon fs-22"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Sidebar Navigation (Velzon Standard) -->
        @include('admin.partials.sidebar')

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-content container-fluid">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
                        <i class="ri-checkbox-circle-fill align-middle me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
                        <i class="ri-error-warning-fill align-middle me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Tournament Arena Hero Banner -->
                <div class="card shadow-lg border-0 mb-4 overflow-hidden position-relative" 
                     style="background: linear-gradient(135deg, #0b1120 0%, #1e1b4b 50%, #0f172a 100%); border-radius: 16px;">
                    <div class="card-body p-4 text-white position-relative" style="z-index: 2;">
                        <div class="row align-items-center">
                            <div class="col-lg-7">
                                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                    <span class="badge bg-danger fs-12 px-3 py-1 fw-bold shadow-sm">
                                        <i class="ri-sword-line me-1"></i> SISTEM GUGUR (KNOCKOUT)
                                    </span>
                                    <span class="badge bg-warning text-dark fs-12 px-3 py-1 fw-bold shadow-sm">
                                        {{ strtoupper($competition->category) }}
                                    </span>
                                    <span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-25 fs-12 px-3 py-1">
                                        <i class="ri-building-line me-1"></i> MA Unggulan Wahid Hasyim Balung
                                    </span>
                                </div>
                                <h2 class="fs-24 fw-bold text-white mb-1">
                                    {{ $competition->icon }} {{ $competition->title }}
                                </h2>
                                <p class="text-white-50 fs-13 mb-0">
                                    Bagan Pertandingan Sistem Gugur Resmi. Tim pemenang langsung melaju ke babak berikutnya hingga perebutan Piala Juara 1 di Grand Final.
                                </p>
                            </div>

                            <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                                <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                                    <button type="button" class="btn btn-warning btn-sm fw-bold px-3 shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalGenerateBracket">
                                        <i class="ri-shuffle-line me-1"></i> Undi / Acak Bagan Otomatis
                                    </button>
                                    <span class="badge bg-dark bg-opacity-50 text-white border border-secondary px-3 py-2 fs-12 rounded-pill">
                                        <i class="ri-team-line text-warning me-1"></i> {{ count($teams) }} Tim Terdaftar
                                    </span>
                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-3 py-2 fs-12 rounded-pill">
                                        <i class="ri-check-double-line me-1"></i> {{ $completedMatchesCount }} / {{ $totalMatchesCount }} Laga Selesai
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row" id="arena-main-row">
                    <!-- Left: Operator / Wasit Scorekeeper Stadium Console (col-xl-4) -->
                    <div class="col-xl-4 col-lg-5 mb-4" id="scorekeeper-col">
                        <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                            <div class="card-header border-bottom bg-white p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-xs">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-14">
                                            <i class="ri-remote-control-line"></i>
                                        </span>
                                    </div>
                                    <h6 class="fs-14 fw-bold mb-0 text-dark">
                                        Lembar Wasit & Papan Skor
                                    </h6>
                                </div>
                                @if($selectedMatch)
                                    @if($selectedMatch->status === 'ongoing')
                                        <span class="badge bg-danger text-white fs-11 px-2 py-1">
                                            <span class="live-pulse-beacon"></span> LIVE ARENA
                                        </span>
                                    @elseif($selectedMatch->status === 'completed')
                                        <span class="badge bg-success fs-11 px-2 py-1">
                                            <i class="ri-check-line me-1"></i> SELESAI
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted border fs-11 px-2 py-1">
                                            <i class="ri-time-line me-1"></i> TERJADWAL
                                        </span>
                                    @endif
                                @endif
                            </div>

                            <div class="card-body p-3 p-md-4">
                                @if($selectedMatch)
                                    <form action="{{ route('admin.tournament.match.score', $selectedMatch->id) }}" method="POST" id="scorekeeper-form">
                                        @csrf
                                        
                                        <!-- Match Identification Header -->
                                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                            <div>
                                                <span class="badge bg-primary fs-12 px-2 py-1 font-monospace">{{ $selectedMatch->match_number }}</span>
                                                <strong class="fs-13 text-dark ms-1">{{ $selectedMatch->round_name }}</strong>
                                            </div>
                                            <div class="text-muted fs-11">
                                                <i class="ri-map-pin-line text-danger me-1"></i> {{ $selectedMatch->court ?? 'Lapangan Utama' }}
                                                • <i class="ri-time-line text-info me-1"></i> {{ $selectedMatch->scheduled_time ?? 'Hari-H' }}
                                            </div>
                                        </div>

                                        <!-- Electronic Stadium Scoreboard Console -->
                                        <div class="stadium-desk-panel p-3 mb-3">
                                            <div class="row align-items-center g-2">
                                                <!-- Team 1 (Sudut Merah) -->
                                                <div class="col-5">
                                                    <div class="stadium-corner-box {{ $selectedMatch->winner_registration_id == $selectedMatch->team1_registration_id && $selectedMatch->winner_registration_id ? 'is-leading' : '' }}" id="stadium-box-team1">
                                                        <span class="badge bg-danger text-white fs-10 px-2 py-0 mb-1 rounded-pill">SUDUT MERAH</span>
                                                        <h6 class="fs-12 fw-bold text-white text-truncate mb-0" title="{{ $selectedMatch->team1_display_name }}">
                                                            {{ $selectedMatch->team1_display_name }}
                                                        </h6>

                                                        <!-- Big LED Score Digit -->
                                                        <div class="stadium-score-led font-digital" id="display-team1-score">
                                                            {{ $selectedMatch->team1_score }}
                                                        </div>
                                                        <input type="hidden" name="team1_score" id="input-team1-score" value="{{ $selectedMatch->team1_score }}">

                                                        <!-- Quick Incrementor / Decrementor Buttons -->
                                                        <div class="d-flex justify-content-center gap-2 mb-2">
                                                            <button type="button" class="btn btn-dark stadium-pad-btn border border-secondary text-white" 
                                                                    onclick="changeScore('team1', -1)" title="Kurang 1">-</button>
                                                            <button type="button" class="btn btn-danger stadium-pad-btn shadow-sm" 
                                                                    onclick="changeScore('team1', 1)" title="Tambah 1">+</button>
                                                        </div>

                                                        <input type="text" name="team1_set_scores" class="form-control form-control-sm text-center fs-11 bg-dark text-white border-secondary" 
                                                               placeholder="Poin: 25, 25" value="{{ $selectedMatch->team1_set_scores }}">
                                                    </div>
                                                </div>

                                                <!-- VS Indicator & Set Counter -->
                                                <div class="col-2 text-center">
                                                    <div class="avatar-xs mx-auto mb-1">
                                                        <span class="avatar-title bg-danger text-white rounded-circle fs-11 fw-bold shadow">VS</span>
                                                    </div>
                                                    <small class="text-white-50 fs-10 fw-bold font-digital d-block">SKOR</small>
                                                    <div class="mt-2">
                                                        <button type="button" class="btn btn-outline-light btn-xs py-0 px-1 fs-10" onclick="autoDetectWinner()" title="Set Pemenang Otomatis dari Skor Tertinggi">
                                                            <i class="ri-magic-line"></i> Auto
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Team 2 (Sudut Biru) -->
                                                <div class="col-5">
                                                    <div class="stadium-corner-box {{ $selectedMatch->winner_registration_id == $selectedMatch->team2_registration_id && $selectedMatch->winner_registration_id ? 'is-leading' : '' }}" id="stadium-box-team2">
                                                        <span class="badge bg-info text-white fs-10 px-2 py-0 mb-1 rounded-pill">SUDUT BIRU</span>
                                                        <h6 class="fs-12 fw-bold text-white text-truncate mb-0" title="{{ $selectedMatch->team2_display_name }}">
                                                            {{ $selectedMatch->team2_display_name }}
                                                        </h6>

                                                        <!-- Big LED Score Digit -->
                                                        <div class="stadium-score-led font-digital" id="display-team2-score">
                                                            {{ $selectedMatch->team2_score }}
                                                        </div>
                                                        <input type="hidden" name="team2_score" id="input-team2-score" value="{{ $selectedMatch->team2_score }}">

                                                        <!-- Quick Incrementor / Decrementor Buttons -->
                                                        <div class="d-flex justify-content-center gap-2 mb-2">
                                                            <button type="button" class="btn btn-dark stadium-pad-btn border border-secondary text-white" 
                                                                    onclick="changeScore('team2', -1)" title="Kurang 1">-</button>
                                                            <button type="button" class="btn btn-info stadium-pad-btn shadow-sm text-white" 
                                                                    onclick="changeScore('team2', 1)" title="Tambah 1">+</button>
                                                        </div>

                                                        <input type="text" name="team2_set_scores" class="form-control form-control-sm text-center fs-11 bg-dark text-white border-secondary" 
                                                               placeholder="Poin: 20, 22" value="{{ $selectedMatch->team2_set_scores }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Interactive Fast Winner Selection (Cards, No Plain Radio) -->
                                        <div class="mb-3">
                                            <label class="form-label fs-12 fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                                <span><i class="ri-trophy-fill text-warning me-1"></i> Tim Pemenang (Lolos ke Babak Selanjutnya):</span>
                                                <small class="text-muted fw-normal">Sistem Gugur</small>
                                            </label>

                                            <div class="d-flex flex-column gap-2" id="winner-cards-container">
                                                <!-- Hidden native radios for form submit -->
                                                <input type="radio" name="winner_registration_id" id="radio-winner-t1" value="{{ $selectedMatch->team1_registration_id ?? '' }}" class="d-none" {{ $selectedMatch->winner_registration_id == $selectedMatch->team1_registration_id && $selectedMatch->winner_registration_id ? 'checked' : '' }}>
                                                <input type="radio" name="winner_registration_id" id="radio-winner-t2" value="{{ $selectedMatch->team2_registration_id ?? '' }}" class="d-none" {{ $selectedMatch->winner_registration_id == $selectedMatch->team2_registration_id && $selectedMatch->winner_registration_id ? 'checked' : '' }}>
                                                <input type="radio" name="winner_registration_id" id="radio-winner-none" value="" class="d-none" {{ empty($selectedMatch->winner_registration_id) ? 'checked' : '' }}>

                                                <!-- Clickable Card for Team 1 -->
                                                @if($selectedMatch->team1)
                                                    <div class="winner-select-card {{ $selectedMatch->winner_registration_id == $selectedMatch->team1_registration_id && $selectedMatch->winner_registration_id ? 'is-chosen' : '' }}" 
                                                         id="card-winner-t1" onclick="selectWinner('t1')">
                                                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                            <span class="avatar-title bg-danger text-white rounded-circle fs-12" style="width: 26px; height: 26px;">
                                                                <i class="ri-trophy-line"></i>
                                                            </span>
                                                            <div class="text-truncate">
                                                                <div class="winner-team-title text-truncate">{{ $selectedMatch->team1->school_name }}</div>
                                                                <small class="text-muted fs-10">Tim 1 (Sudut Merah) Lolos</small>
                                                            </div>
                                                        </div>
                                                        <span class="badge {{ $selectedMatch->winner_registration_id == $selectedMatch->team1_registration_id && $selectedMatch->winner_registration_id ? 'bg-success' : 'bg-light text-muted border' }} fs-11 check-badge">
                                                            {{ $selectedMatch->winner_registration_id == $selectedMatch->team1_registration_id && $selectedMatch->winner_registration_id ? '✓ Pemenang' : 'Pilih' }}
                                                        </span>
                                                    </div>
                                                @endif

                                                <!-- Clickable Card for Team 2 -->
                                                @if($selectedMatch->team2)
                                                    <div class="winner-select-card {{ $selectedMatch->winner_registration_id == $selectedMatch->team2_registration_id && $selectedMatch->winner_registration_id ? 'is-chosen' : '' }}" 
                                                         id="card-winner-t2" onclick="selectWinner('t2')">
                                                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                            <span class="avatar-title bg-info text-white rounded-circle fs-12" style="width: 26px; height: 26px;">
                                                                <i class="ri-trophy-line"></i>
                                                            </span>
                                                            <div class="text-truncate">
                                                                <div class="winner-team-title text-truncate">{{ $selectedMatch->team2->school_name }}</div>
                                                                <small class="text-muted fs-10">Tim 2 (Sudut Biru) Lolos</small>
                                                            </div>
                                                        </div>
                                                        <span class="badge {{ $selectedMatch->winner_registration_id == $selectedMatch->team2_registration_id && $selectedMatch->winner_registration_id ? 'bg-success' : 'bg-light text-muted border' }} fs-11 check-badge">
                                                            {{ $selectedMatch->winner_registration_id == $selectedMatch->team2_registration_id && $selectedMatch->winner_registration_id ? '✓ Pemenang' : 'Pilih' }}
                                                        </span>
                                                    </div>
                                                @endif

                                                <!-- Option for No Winner Yet -->
                                                <button type="button" class="btn btn-link text-muted btn-sm p-0 fs-11 text-start mt-1" onclick="selectWinner('none')">
                                                    <i class="ri-close-circle-line me-1"></i> Reset / Belum Ada Pemenang (Laga Sedang Berlangsung)
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Match Status Segmented Control -->
                                        <div class="mb-3">
                                            <label class="form-label fs-12 fw-semibold mb-1">Status Pertandingan</label>
                                            <div class="status-segmented-bar">
                                                <label class="status-segment-opt {{ $selectedMatch->status === 'scheduled' ? 'is-active-scheduled' : '' }}" id="seg-scheduled" onclick="setStatusSegment('scheduled')">
                                                    <input type="radio" name="status" value="scheduled" {{ $selectedMatch->status === 'scheduled' ? 'checked' : '' }}>
                                                    <i class="ri-time-line me-1"></i> Terjadwal
                                                </label>
                                                <label class="status-segment-opt {{ $selectedMatch->status === 'ongoing' ? 'is-active-live' : '' }}" id="seg-ongoing" onclick="setStatusSegment('ongoing')">
                                                    <input type="radio" name="status" value="ongoing" {{ $selectedMatch->status === 'ongoing' ? 'checked' : '' }}>
                                                    <span class="live-pulse-beacon"></span> LIVE Arena
                                                </label>
                                                <label class="status-segment-opt {{ $selectedMatch->status === 'completed' ? 'is-active-completed' : '' }}" id="seg-completed" onclick="setStatusSegment('completed')">
                                                    <input type="radio" name="status" value="completed" {{ $selectedMatch->status === 'completed' ? 'checked' : '' }}>
                                                    <i class="ri-checkbox-circle-line me-1"></i> Selesai
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Additional Match Metadata -->
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label fs-11 fw-semibold mb-1">Nama Wasit / Referee</label>
                                                <input type="text" name="referee_name" class="form-control form-control-sm fs-12" 
                                                       value="{{ $selectedMatch->referee_name ?? 'Wasit Pertandingan' }}" placeholder="Wasit PBVSI">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fs-11 fw-semibold mb-1">Lokasi Lapangan</label>
                                                <input type="text" name="court" class="form-control form-control-sm fs-12" 
                                                       value="{{ $selectedMatch->court ?? 'Lapangan Utama' }}" placeholder="Lapangan 1">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fs-11 fw-semibold mb-1">Catatan / Detail Jalannya Laga</label>
                                            <textarea name="notes" rows="2" class="form-control form-control-sm fs-12" 
                                                      placeholder="Catatan rubber set, kartu, perpanjangan poin, atau evaluasi wasit...">{{ $selectedMatch->notes }}</textarea>
                                        </div>

                                        <!-- Save Action Button -->
                                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm rounded-3">
                                            <i class="ri-save-3-line me-1"></i> Simpan Skor & Perbarui Bagan
                                        </button>

                                        @if($selectedMatch->next_match_id)
                                            <div class="text-center mt-2">
                                                <small class="text-success fs-11">
                                                    <i class="ri-arrow-right-circle-fill me-1"></i> Pemenang otomatis lolos ke pertandingan babak selanjutnya.
                                                </small>
                                            </div>
                                        @endif
                                    </form>
                                @else
                                    <div class="text-center py-5 text-muted">
                                        <i class="ri-trophy-line fs-40 text-muted mb-2 d-block"></i>
                                        <p class="fs-13">Belum ada pertandingan dibuat untuk cabang turnamen ini.</p>
                                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalGenerateBracket">
                                            <i class="ri-shuffle-line me-1"></i> Buat Bagan Turnamen Sekarang
                                        </button>
                                    </div>
                                @endif

                                <!-- Quick Match List / Schedule -->
                                <hr class="my-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fs-12 fw-bold text-dark mb-0 text-uppercase tracking-wider">
                                        <i class="ri-calendar-event-line text-primary me-1"></i> Seluruh Jadwal Laga ({{ count($matches) }})
                                    </h6>
                                    <span class="badge bg-light text-muted border fs-10">{{ $completedMatchesCount }} Selesai</span>
                                </div>

                                <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                                    <table class="table table-sm table-hover align-middle mb-0 fs-11">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Laga</th>
                                                <th>Pertandingan</th>
                                                <th class="text-center">Skor</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($matches as $m)
                                                <tr class="{{ $selectedMatch && $selectedMatch->id == $m->id ? 'table-primary fw-bold' : '' }}">
                                                    <td>
                                                        <span class="badge bg-light text-dark border font-monospace">{{ $m->match_number }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="text-truncate" style="max-width: 145px;" title="{{ $m->team1_display_name }} vs {{ $m->team2_display_name }}">
                                                            {{ $m->team1_display_name }} vs {{ $m->team2_display_name }}
                                                        </div>
                                                        <small class="text-muted fs-10">{{ $m->round_name }}</small>
                                                    </td>
                                                    <td class="text-center fw-bold font-digital">
                                                        {{ $m->team1_score }} - {{ $m->team2_score }}
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="{{ route('admin.tournament.arena', ['competitionId' => $competition->id, 'match' => $m->id]) }}" 
                                                           class="btn btn-xs {{ $selectedMatch && $selectedMatch->id == $m->id ? 'btn-primary' : 'btn-outline-primary' }} py-0 px-2 rounded">
                                                            Pilih
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-3">Belum ada pertandingan.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Visual Knockout Tournament Bracket Tree (col-xl-8) -->
                    <div class="col-xl-8 col-lg-7 mb-4" id="bracket-col">
                        <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                            <div class="card-header border-bottom bg-white p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <h6 class="fs-14 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                        <i class="ri-git-branch-line text-primary fs-16"></i>
                                        Bagan Alur Turnamen Sistem Gugur (Official Knockout Tree)
                                    </h6>
                                    <small class="text-muted fs-11">Pemenang otomatis melaju ke babak berikutnya hingga penobatan Sang Juara.</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fs-11" id="btn-toggle-fullscreen" onclick="toggleBracketExpand()">
                                        <i class="ri-fullscreen-line me-1" id="fullscreen-icon"></i> <span id="fullscreen-text">Layar Penuh</span>
                                    </button>
                                </div>
                            </div>

                            <div class="card-body p-2 p-md-3 position-relative">
                                @if(count($matches) > 0)
                                    <div class="bracket-tree-canvas" id="bracket-tree-container">
                                        
                                        @php
                                            $totalRounds = $rounds->count();
                                            $currentRoundIdx = 0;
                                        @endphp

                                        @foreach($rounds as $roundName => $roundMatches)
                                            @php
                                                $currentRoundIdx++;
                                                $isFinalRound = ($currentRoundIdx === $totalRounds);
                                                $hasPreviousRound = ($currentRoundIdx > 1);
                                            @endphp

                                            <div class="bracket-round-col">
                                                <!-- Round Header Badge -->
                                                <div class="bracket-round-pill">
                                                    @if($isFinalRound)
                                                        <i class="ri-trophy-line text-warning fs-13"></i>
                                                    @elseif($roundName === 'Semi Final')
                                                        <i class="ri-medal-line text-primary fs-13"></i>
                                                    @else
                                                        <i class="ri-sword-line text-danger fs-13"></i>
                                                    @endif
                                                    <span>{{ $roundName }}</span>
                                                </div>

                                                <!-- Match Pairs Stream -->
                                                <div class="bracket-pairs-stream">
                                                    @foreach($roundMatches->chunk(2) as $pairIndex => $matchPair)
                                                        @php
                                                            $isSingleInPair = $matchPair->count() === 1;
                                                        @endphp
                                                        <div class="bracket-matchup-pair {{ (!$isFinalRound && !$isSingleInPair) ? 'with-spine' : '' }}">
                                                            @foreach($matchPair as $match)
                                                                @php
                                                                    $isSelected = $selectedMatch && $selectedMatch->id == $match->id;
                                                                    $isLive = $match->status === 'ongoing';
                                                                    $isDone = $match->status === 'completed';
                                                                    $t1Won = $isDone && $match->winner_registration_id == $match->team1_registration_id && $match->winner_registration_id;
                                                                    $t2Won = $isDone && $match->winner_registration_id == $match->team2_registration_id && $match->winner_registration_id;
                                                                    
                                                                    // Team Initials
                                                                    $t1Init = $match->team1 ? strtoupper(substr($match->team1->school_name, 0, 2)) : 'T1';
                                                                    $t2Init = $match->team2 ? strtoupper(substr($match->team2->school_name, 0, 2)) : 'T2';
                                                                @endphp

                                                                <div class="bracket-card-wrapper {{ (!$isFinalRound) ? 'has-outgoing' : 'final-connector' }} {{ ($hasPreviousRound) ? 'has-incoming' : '' }}">
                                                                    <div class="bracket-match-card {{ $isSelected ? 'is-selected' : '' }} {{ $isLive ? 'is-live' : '' }}" 
                                                                         onclick="window.location.href='{{ route('admin.tournament.arena', ['competitionId' => $competition->id, 'match' => $match->id]) }}'"
                                                                         title="Klik untuk buka input skor di sebelah kiri">
                                                                        
                                                                        <!-- Card Header Meta -->
                                                                        <div class="bracket-match-meta">
                                                                            <span class="bracket-match-num">
                                                                                <i class="ri-hashtag text-primary"></i> {{ $match->match_number }}
                                                                            </span>
                                                                            <div>
                                                                                @if($isLive)
                                                                                    <span class="badge bg-danger fs-10 px-2 py-0">
                                                                                        <span class="live-pulse-beacon"></span> LIVE
                                                                                    </span>
                                                                                @elseif($isDone)
                                                                                    <span class="badge bg-success-subtle text-success fs-10 px-2 py-0">
                                                                                        <i class="ri-check-line"></i> SELESAI
                                                                                    </span>
                                                                                @else
                                                                                    <span class="badge bg-light text-muted border fs-10 px-2 py-0">
                                                                                        {{ $match->scheduled_time ?? 'Jadwal' }}
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                        </div>

                                                                        <!-- Team 1 Row -->
                                                                        <div class="bracket-team-slot {{ $t1Won ? 'is-winner' : ($t2Won ? 'is-loser' : '') }}">
                                                                            <div class="bracket-team-identity">
                                                                                <div class="bracket-school-avatar">
                                                                                    {{ $t1Init }}
                                                                                </div>
                                                                                <span class="bracket-team-name" title="{{ $match->team1_display_name }}">
                                                                                    @if($t1Won) <i class="ri-trophy-fill text-warning me-1"></i> @endif
                                                                                    {{ $match->team1_display_name }}
                                                                                </span>
                                                                            </div>
                                                                            <span class="bracket-score-pill font-digital">{{ $match->team1_score }}</span>
                                                                        </div>

                                                                        <!-- Team 2 Row -->
                                                                        <div class="bracket-team-slot {{ $t2Won ? 'is-winner' : ($t1Won ? 'is-loser' : '') }}">
                                                                            <div class="bracket-team-identity">
                                                                                <div class="bracket-school-avatar">
                                                                                    {{ $t2Init }}
                                                                                </div>
                                                                                <span class="bracket-team-name" title="{{ $match->team2_display_name }}">
                                                                                    @if($t2Won) <i class="ri-trophy-fill text-warning me-1"></i> @endif
                                                                                    {{ $match->team2_display_name }}
                                                                                </span>
                                                                            </div>
                                                                            <span class="bracket-score-pill font-digital">{{ $match->team2_score }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach

                                        <!-- Champion Trophy Podium (Sang Juara) -->
                                        @php
                                            $finalMatch = $matches->where('round_order', $matches->max('round_order'))->first();
                                            $grandChampion = $finalMatch && $finalMatch->status === 'completed' ? $finalMatch->winner : null;
                                        @endphp
                                        <div class="bracket-champion-podium">
                                            <div class="champion-trophy-pedestal">
                                                <div class="champion-aura-glow"></div>
                                                <div class="champion-cup-badge">
                                                    <i class="ri-trophy-fill"></i>
                                                </div>

                                                @if($grandChampion)
                                                    <span class="badge bg-warning text-dark fs-11 px-3 py-1 fw-bold rounded-pill mb-2 shadow-sm">
                                                        🏆 JUARA 1 (CHAMPION)
                                                    </span>
                                                    <h5 class="fs-15 fw-bold text-success mb-1 text-truncate" title="{{ $grandChampion->school_name }}">
                                                        {{ $grandChampion->school_name }}
                                                    </h5>
                                                    <span class="badge bg-light text-muted border fs-10 mb-2">{{ $grandChampion->reg_code }}</span>
                                                    <small class="text-muted d-block fs-11">Official: {{ $grandChampion->mentor_name ?? '-' }}</small>
                                                    <div class="mt-2 text-warning fs-13">
                                                        <i class="ri-star-fill"></i> <i class="ri-star-fill"></i> <i class="ri-star-fill"></i>
                                                    </div>
                                                @else
                                                    <span class="badge bg-light text-muted border fs-11 px-3 py-1 rounded-pill mb-2">
                                                        🏆 SANG JUARA
                                                    </span>
                                                    <h6 class="fs-13 fw-bold text-muted mb-1">Menunggu Grand Final</h6>
                                                    <small class="text-muted fs-11 d-block">
                                                        Pemenang laga Grand Final akan dinobatkan sebagai Juara 1 Turnamen {{ $competition->title }}
                                                    </small>
                                                @endif
                                            </div>
                                        </div>

                                    </div>
                                @else
                                    <div class="text-center py-5">
                                        <img src="/assets/img/logo.png" alt="Logo" style="width: 64px; opacity: 0.3;" class="mb-3">
                                        <h5 class="fs-15 fw-bold text-muted">Bagan Turnamen Belum Dibuat</h5>
                                        <p class="text-muted fs-13 mb-3">Tekan tombol di bawah untuk membuat bagan sistem gugur otomatis dari tim terdaftar.</p>
                                        <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalGenerateBracket">
                                            <i class="ri-shuffle-line me-1"></i> Undi & Buat Bagan Sistem Gugur
                                        </button>
                                    </div>
                                @endif
                            </div>
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

    <!-- Modal Generate Bracket Otomatis -->
    <div class="modal fade" id="modalGenerateBracket" tabindex="-1" aria-labelledby="modalGenerateBracketLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
                <div class="modal-header bg-light border-bottom p-3">
                    <h5 class="modal-title fs-15 fw-bold text-dark" id="modalGenerateBracketLabel">
                        <i class="ri-shuffle-line text-primary me-1"></i> Undi & Buat Bagan Sistem Gugur
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.tournament.bracket.generate', $competition->id) }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="alert alert-warning border-0 d-flex align-items-start gap-2 mb-3 py-2 rounded-3">
                            <i class="ri-alert-line fs-20 text-warning mt-1"></i>
                            <div class="fs-12">
                                <strong>Perhatian:</strong> Membuat bagan baru akan mengatur ulang susunan bagan dan jadwal pertandingan sistem gugur untuk cabang <strong>{{ $competition->title }}</strong>.
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded border mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fs-12 fw-semibold text-dark">Jumlah Tim Terdaftar:</span>
                                <span class="badge bg-primary fs-12 px-3 py-1">{{ count($teams) }} Tim</span>
                            </div>
                            <small class="text-muted fs-11">Sistem akan mengacak pasangan laga di babak penyisihan (8 besar / semi final) dan menyiapkan bagan sampai Grand Final.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-12 fw-semibold">Daftar Tim yang Akan Masuk Bagan:</label>
                            <ul class="list-group list-group-flush fs-12 border rounded" style="max-height: 180px; overflow-y: auto;">
                                @forelse($teams as $t)
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                        <span>{{ $t->school_name }}</span>
                                        <span class="badge bg-light text-muted border">{{ $t->reg_code }}</span>
                                    </li>
                                @empty
                                    <li class="list-group-item text-danger py-2">Belum ada tim yang mendaftar pada cabang ini.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top p-3">
                        <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-semibold rounded-pill px-4 shadow-sm" {{ count($teams) < 2 ? 'disabled' : '' }}>
                            <i class="ri-check-double-line me-1"></i> Mulai Generate Bagan Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function changeScore(team, delta) {
            const input = document.getElementById(`input-${team}-score`);
            const display = document.getElementById(`display-${team}-score`);
            if (input && display) {
                let current = parseInt(input.value) || 0;
                current = Math.max(0, current + delta);
                input.value = current;
                display.innerText = current;

                // Update visual leading box
                updateLeadingBox();
            }
        }

        function updateLeadingBox() {
            const s1 = parseInt(document.getElementById('input-team1-score')?.value) || 0;
            const s2 = parseInt(document.getElementById('input-team2-score')?.value) || 0;
            const b1 = document.getElementById('stadium-box-team1');
            const b2 = document.getElementById('stadium-box-team2');
            
            if (b1 && b2) {
                b1.classList.remove('is-leading');
                b2.classList.remove('is-leading');
                if (s1 > s2) b1.classList.add('is-leading');
                else if (s2 > s1) b2.classList.add('is-leading');
            }
        }

        function selectWinner(slot) {
            const r1 = document.getElementById('radio-winner-t1');
            const r2 = document.getElementById('radio-winner-t2');
            const rNone = document.getElementById('radio-winner-none');
            const c1 = document.getElementById('card-winner-t1');
            const c2 = document.getElementById('card-winner-t2');

            if (c1) c1.classList.remove('is-chosen');
            if (c2) c2.classList.remove('is-chosen');

            if (slot === 't1' && r1) {
                r1.checked = true;
                if (c1) {
                    c1.classList.add('is-chosen');
                    const badge = c1.querySelector('.check-badge');
                    if (badge) {
                        badge.className = 'badge bg-success fs-11 check-badge';
                        badge.innerText = '✓ Pemenang';
                    }
                }
                if (c2) {
                    const badge = c2.querySelector('.check-badge');
                    if (badge) {
                        badge.className = 'badge bg-light text-muted border fs-11 check-badge';
                        badge.innerText = 'Pilih';
                    }
                }
            } else if (slot === 't2' && r2) {
                r2.checked = true;
                if (c2) {
                    c2.classList.add('is-chosen');
                    const badge = c2.querySelector('.check-badge');
                    if (badge) {
                        badge.className = 'badge bg-success fs-11 check-badge';
                        badge.innerText = '✓ Pemenang';
                    }
                }
                if (c1) {
                    const badge = c1.querySelector('.check-badge');
                    if (badge) {
                        badge.className = 'badge bg-light text-muted border fs-11 check-badge';
                        badge.innerText = 'Pilih';
                    }
                }
            } else if (slot === 'none' && rNone) {
                rNone.checked = true;
                if (c1) {
                    const badge = c1.querySelector('.check-badge');
                    if (badge) {
                        badge.className = 'badge bg-light text-muted border fs-11 check-badge';
                        badge.innerText = 'Pilih';
                    }
                }
                if (c2) {
                    const badge = c2.querySelector('.check-badge');
                    if (badge) {
                        badge.className = 'badge bg-light text-muted border fs-11 check-badge';
                        badge.innerText = 'Pilih';
                    }
                }
            }
        }

        function autoDetectWinner() {
            const s1 = parseInt(document.getElementById('input-team1-score')?.value) || 0;
            const s2 = parseInt(document.getElementById('input-team2-score')?.value) || 0;
            if (s1 > s2) {
                selectWinner('t1');
                setStatusSegment('completed');
            } else if (s2 > s1) {
                selectWinner('t2');
                setStatusSegment('completed');
            } else {
                alert('Skor masih seimbang (seri). Tentukan pemenang setelah ada selisih poin.');
            }
        }

        function setStatusSegment(val) {
            const segSched = document.getElementById('seg-scheduled');
            const segOngoing = document.getElementById('seg-ongoing');
            const segComp = document.getElementById('seg-completed');

            segSched?.classList.remove('is-active-scheduled');
            segOngoing?.classList.remove('is-active-live');
            segComp?.classList.remove('is-active-completed');

            const radio = document.querySelector(`input[name="status"][value="${val}"]`);
            if (radio) radio.checked = true;

            if (val === 'scheduled') segSched?.classList.add('is-active-scheduled');
            else if (val === 'ongoing') segOngoing?.classList.add('is-active-live');
            else if (val === 'completed') segComp?.classList.add('is-active-completed');
        }

        let isBracketExpanded = false;
        function toggleBracketExpand() {
            const row = document.getElementById('arena-main-row');
            const leftCol = document.getElementById('scorekeeper-col');
            const rightCol = document.getElementById('bracket-col');
            const icon = document.getElementById('fullscreen-icon');
            const text = document.getElementById('fullscreen-text');

            if (!isBracketExpanded) {
                leftCol.classList.add('d-none');
                rightCol.classList.remove('col-xl-8', 'col-lg-7');
                rightCol.classList.add('col-12');
                if (icon) icon.className = 'ri-fullscreen-exit-line me-1';
                if (text) text.innerText = 'Kecilkan Bagan';
                isBracketExpanded = true;
            } else {
                leftCol.classList.remove('d-none');
                rightCol.classList.remove('col-12');
                rightCol.classList.add('col-xl-8', 'col-lg-7');
                if (icon) icon.className = 'ri-fullscreen-line me-1';
                if (text) text.innerText = 'Layar Penuh';
                isBracketExpanded = false;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateLeadingBox();

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
