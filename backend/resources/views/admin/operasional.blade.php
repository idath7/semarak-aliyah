<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Operasional Hari-H | Semarak Aliyah XII 2026</title>
  <link rel="icon" type="image/png" href="/assets/img/logo.png">

  <!-- Local Offline Fonts, Bootstrap 5.3 CSS, RemixIcon & Boxicons -->
  <link rel="stylesheet" href="/assets/css/fonts.css">
  <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/vendor/remixicon/remixicon.css">
  <link rel="stylesheet" href="/assets/vendor/boxicons/css/boxicons.min.css">

  <style>
    :root {
      --bg-base: #070a13;
      --bg-surface: #0f172a;
      --bg-surface-elevated: #162032;
      --bg-surface-card: #131c2e;
      --bg-surface-glass: rgba(15, 23, 42, 0.85);

      --border-color: rgba(255, 255, 255, 0.12);
      --border-highlight: rgba(255, 255, 255, 0.22);
      --border-accent: #f59e0b;

      /* High-Contrast Typography */
      --text-main: #f8fafc;
      --text-heading: #ffffff;
      --text-body: #e2e8f0;
      --text-muted: #cbd5e1;       /* Cool Silver - 100% visible on dark */
      --text-subtle: #94a3b8;      /* Secondary annotations */
      --text-dim: #64748b;

      --accent-emerald: #10b981;
      --accent-emerald-bright: #34d399;
      --accent-emerald-subtle: rgba(16, 185, 129, 0.18);
      
      --accent-amber: #f59e0b;
      --accent-amber-bright: #fbbf24;
      --accent-amber-subtle: rgba(245, 158, 11, 0.18);
      
      --accent-blue: #3b82f6;
      --accent-blue-bright: #60a5fa;
      --accent-blue-subtle: rgba(59, 130, 246, 0.18);
      
      --accent-rose: #f43f5e;
      --accent-rose-bright: #fb7185;
      --accent-rose-subtle: rgba(244, 63, 94, 0.18);

      --accent-purple: #8b5cf6;
      --accent-cyan: #06b6d4;

      --sidebar-width: 270px;
      --topbar-height: 72px;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background-color: var(--bg-base);
      color: var(--text-main);
      margin: 0;
      padding: 0;
      overflow-x: hidden;
      line-height: 1.5;
    }

    h1, h2, h3, h4, h5, h6, .brand-font {
      font-family: 'Poppins', sans-serif;
      color: var(--text-heading);
    }

    /* ====================================================================== */
    /* GLOBAL HIGH-CONTRAST TYPOGRAPHY OVERRIDES (NO MORE UNREADABLE MUTED)   */
    /* ====================================================================== */
    .text-muted {
      color: var(--text-muted) !important;
    }
    .text-secondary {
      color: var(--text-subtle) !important;
    }
    .text-dim {
      color: var(--text-dim) !important;
    }
    .text-white-subtle {
      color: #e2e8f0 !important;
    }
    p {
      color: var(--text-body);
    }
    small {
      color: var(--text-muted);
    }

    /* Form Labels: High Contrast & Legible */
    .form-label, label {
      color: #f1f5f9 !important;
      font-weight: 600 !important;
      font-size: 13px !important;
      letter-spacing: 0.2px;
      margin-bottom: 7px;
    }

    /* Form Controls & Selects */
    .form-control, .form-select {
      background-color: #0b1120 !important;
      border: 1.5px solid rgba(255, 255, 255, 0.18) !important;
      color: #ffffff !important;
      font-weight: 500 !important;
      border-radius: 9px !important;
      padding: 9px 14px !important;
      font-size: 13.5px !important;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .form-control:focus, .form-select:focus {
      background-color: #080d19 !important;
      border-color: #f59e0b !important;
      box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25) !important;
      color: #ffffff !important;
      outline: none !important;
    }
    .form-control::placeholder {
      color: #64748b !important;
    }
    .form-select option {
      background-color: #162032 !important;
      color: #ffffff !important;
      padding: 8px 12px;
    }

    /* Range Sliders */
    input[type=range].form-range {
      height: 8px;
      border-radius: 4px;
      background: #1e293b;
      accent-color: #f59e0b;
    }
    input[type=range].form-range::-webkit-slider-thumb {
      width: 22px;
      height: 22px;
      border-radius: 50%;
      background: #f59e0b;
      cursor: pointer;
      box-shadow: 0 0 10px rgba(245, 158, 11, 0.7);
      border: 2px solid #ffffff;
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    input[type=range].form-range::-webkit-slider-thumb:hover {
      transform: scale(1.18);
      box-shadow: 0 0 16px rgba(245, 158, 11, 0.95);
    }

    /* Layout Wrapper */
    #layout-container {
      display: flex;
      min-height: 100vh;
      width: 100%;
    }

    /* Fixed Sidebar */
    .op-sidebar {
      width: var(--sidebar-width);
      background-color: var(--bg-surface);
      border-right: 1px solid var(--border-color);
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      z-index: 1040;
      display: flex;
      flex-direction: column;
      transition: transform 0.3s ease;
    }

    .op-brand-box {
      height: var(--topbar-height);
      padding: 0 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      background: #090d16;
      border-bottom: 1px solid var(--border-color);
    }
    .op-brand-box img {
      width: 38px;
      height: 38px;
      object-fit: contain;
    }
    .brand-title-wrap h6 {
      font-size: 13.5px;
      font-weight: 700;
      letter-spacing: 1px;
      color: #fff;
      margin: 0;
      line-height: 1.2;
    }
    .brand-title-wrap small {
      font-size: 10.5px;
      font-weight: 600;
      letter-spacing: 1.5px;
      color: var(--accent-amber);
      display: block;
    }

    .sidebar-scroll {
      flex: 1;
      overflow-y: auto;
      padding: 18px 0;
    }
    .nav-category {
      font-size: 10.5px;
      text-transform: uppercase;
      font-weight: 700;
      letter-spacing: 1.2px;
      color: var(--text-subtle);
      padding: 14px 22px 6px;
    }
    .op-nav-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .op-nav-item {
      margin: 4px 12px;
    }
    .op-nav-link {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 11px 15px;
      border-radius: 9px;
      color: var(--text-muted);
      font-size: 13.5px;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.2s ease;
      cursor: pointer;
    }
    .op-nav-link i {
      font-size: 18px;
      width: 22px;
      text-align: center;
      color: #94a3b8;
      transition: color 0.2s ease;
    }
    .op-nav-link:hover {
      color: #ffffff;
      background: rgba(255, 255, 255, 0.07);
    }
    .op-nav-link:hover i {
      color: #ffffff;
    }
    .op-nav-link.active {
      color: #ffffff;
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.3) 0%, rgba(59, 130, 246, 0.22) 100%);
      border: 1px solid rgba(16, 185, 129, 0.5);
      font-weight: 600;
      box-shadow: 0 4px 16px rgba(16, 185, 129, 0.2);
    }
    .op-nav-link.active i {
      color: var(--accent-emerald-bright);
    }

    /* Fixed Topbar */
    .op-topbar {
      position: fixed;
      top: 0;
      right: 0;
      left: var(--sidebar-width);
      height: var(--topbar-height);
      background-color: rgba(15, 23, 42, 0.92);
      backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--border-color);
      z-index: 1030;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 28px;
      transition: left 0.3s ease;
    }

    /* Main Content Area */
    .op-main {
      margin-left: var(--sidebar-width);
      width: calc(100% - var(--sidebar-width));
      padding-top: var(--topbar-height);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      transition: margin-left 0.3s ease, width 0.3s ease;
    }
    .op-body {
      padding: 28px 30px 60px;
      flex: 1;
    }

    /* Card Panels */
    .op-card {
      background: linear-gradient(180deg, #131c2e 0%, #0f172a 100%);
      border: 1px solid var(--border-color);
      border-radius: 14px;
      padding: 24px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
      margin-bottom: 24px;
      position: relative;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .op-card:hover {
      border-color: var(--border-highlight);
    }
    .op-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-bottom: 16px;
      margin-bottom: 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .op-card-title {
      font-size: 15px;
      font-weight: 700;
      color: #ffffff;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Live Pulsing Dot */
    .pulse-dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      background: var(--accent-emerald);
      box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
      animation: pulse 1.8s infinite;
      display: inline-block;
    }
    @keyframes pulse {
      0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
      70% { transform: scale(1); box-shadow: 0 0 0 7px rgba(16, 185, 129, 0); }
      100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    /* Metric Cards */
    .stat-card {
      background: linear-gradient(145deg, #162238 0%, #101827 100%);
      border: 1px solid var(--border-color);
      border-radius: 14px;
      padding: 22px;
      transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card:hover {
      transform: translateY(-3px);
      border-color: var(--border-highlight);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
    }
    .stat-val {
      font-size: 30px;
      font-weight: 800;
      line-height: 1.1;
      margin-bottom: 5px;
      color: #ffffff;
    }
    .stat-lbl {
      font-size: 12px;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      font-weight: 600;
    }

    /* Scanner Box */
    .scanner-box {
      background: rgba(16, 185, 129, 0.06);
      border: 2px dashed rgba(16, 185, 129, 0.45);
      border-radius: 16px;
      padding: 26px;
      text-align: center;
      transition: all 0.2s ease;
    }
    .scanner-box:focus-within {
      background: rgba(16, 185, 129, 0.12);
      border-color: var(--accent-emerald);
      box-shadow: 0 0 25px rgba(16, 185, 129, 0.35);
    }
    .scanner-input {
      background: rgba(0, 0, 0, 0.7);
      border: 1.5px solid rgba(16, 185, 129, 0.6);
      color: #ffffff;
      font-size: 22px;
      font-weight: 800;
      letter-spacing: 2px;
      text-align: center;
      border-radius: 12px;
      padding: 16px 22px;
      max-width: 540px;
      margin: 0 auto;
    }
    .scanner-input:focus {
      outline: none;
      border-color: #34d399;
      box-shadow: 0 0 20px rgba(52, 211, 153, 0.5);
    }

    /* Custom Tables: High Contrast & Legible */
    .op-table-wrap {
      overflow-x: auto;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.08);
    }
    table.op-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      color: var(--text-main);
      font-size: 13.5px;
      margin: 0;
    }
    table.op-table th {
      background: #090e1a;
      color: #cbd5e1;
      font-size: 11.5px;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 700;
      padding: 15px 18px;
      border-bottom: 1.5px solid rgba(255, 255, 255, 0.12);
      white-space: nowrap;
    }
    table.op-table td {
      padding: 14px 18px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      vertical-align: middle;
      color: #f1f5f9;
    }
    table.op-table tr:hover td {
      background: rgba(255, 255, 255, 0.04);
    }

    /* Badges */
    .badge-hadir {
      background: rgba(16, 185, 129, 0.2);
      color: #34d399;
      border: 1px solid rgba(16, 185, 129, 0.45);
      font-weight: 700;
      padding: 5px 12px;
      border-radius: 6px;
      font-size: 11.5px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    .badge-belum {
      background: rgba(244, 63, 94, 0.2);
      color: #fb7185;
      border: 1px solid rgba(244, 63, 94, 0.45);
      font-weight: 700;
      padding: 5px 12px;
      border-radius: 6px;
      font-size: 11.5px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    .badge-verif {
      background: rgba(59, 130, 246, 0.2);
      color: #60a5fa;
      border: 1px solid rgba(59, 130, 246, 0.45);
      font-weight: 700;
      padding: 5px 12px;
      border-radius: 6px;
      font-size: 11.5px;
    }

    /* Ultra-High Contrast Rubric Badges (AAA Contrast Ratio) */
    .badge-rubric-bobot {
      background: #f59e0b !important;
      color: #000000 !important;
      font-weight: 800 !important;
      font-size: 11.5px !important;
      letter-spacing: 0.3px !important;
      padding: 4px 11px !important;
      border-radius: 6px !important;
      border: 1px solid #fbbf24 !important;
      box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4) !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 5px !important;
      text-transform: uppercase !important;
      line-height: 1.2 !important;
    }
    .badge-rubric-bobot i {
      color: #000000 !important;
      font-weight: 800 !important;
      font-size: 13px !important;
    }

    .badge-rubric-maks {
      background: #06b6d4 !important;
      color: #000000 !important;
      font-weight: 800 !important;
      font-size: 11.5px !important;
      letter-spacing: 0.3px !important;
      padding: 4px 11px !important;
      border-radius: 6px !important;
      border: 1px solid #22d3ee !important;
      box-shadow: 0 2px 8px rgba(6, 182, 212, 0.4) !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 5px !important;
      text-transform: uppercase !important;
      line-height: 1.2 !important;
    }
    .badge-rubric-maks i {
      color: #000000 !important;
      font-weight: 800 !important;
      font-size: 13px !important;
    }

    .badge-mode-pill {
      font-weight: 800 !important;
      font-size: 12px !important;
      padding: 6px 14px !important;
      border-radius: 8px !important;
      letter-spacing: 0.3px !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 6px !important;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4) !important;
      line-height: 1.2 !important;
    }
    .badge-mode-percentage {
      background: #f59e0b !important;
      color: #000000 !important;
      border: 1.5px solid #fbbf24 !important;
    }
    .badge-mode-percentage i {
      color: #000000 !important;
      font-weight: 800 !important;
    }
    .badge-mode-point {
      background: #06b6d4 !important;
      color: #000000 !important;
      border: 1.5px solid #22d3ee !important;
    }
    .badge-mode-point i {
      color: #000000 !important;
      font-weight: 800 !important;
    }

    /* Professional Arena Sports Stopwatch HUD */
    .stopwatch-hud {
      background: linear-gradient(180deg, #090d16 0%, #060910 100%);
      border: 1.5px solid rgba(245, 158, 11, 0.4);
      border-radius: 12px;
      padding: 8px 18px;
      display: inline-flex;
      align-items: center;
      gap: 14px;
      box-shadow: 0 4px 18px rgba(0, 0, 0, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.1);
    }
    .stopwatch-hud-digits {
      font-family: 'Poppins', monospace;
      font-size: 26px;
      font-weight: 800;
      letter-spacing: 2px;
      color: #fbbf24;
      text-shadow: 0 0 12px rgba(251, 191, 36, 0.5);
      line-height: 1;
    }

    /* Delegate Spotlight Card */
    .delegate-spotlight {
      background: linear-gradient(135deg, rgba(245, 158, 11, 0.12) 0%, rgba(16, 185, 129, 0.08) 100%);
      border: 1.5px solid rgba(245, 158, 11, 0.35);
      border-radius: 12px;
      padding: 16px 20px;
      margin-bottom: 20px;
      position: relative;
      overflow: hidden;
    }

    /* Quick Score Preset Chips */
    .score-chip {
      background: rgba(255, 255, 255, 0.08);
      color: #cbd5e1;
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 6px;
      padding: 3px 9px;
      font-size: 11px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.15s ease;
      display: inline-flex;
      align-items: center;
      user-select: none;
    }
    .score-chip:hover {
      background: #f59e0b;
      color: #000;
      border-color: #f59e0b;
      transform: translateY(-1px);
    }
    .score-chip.active {
      background: #f59e0b;
      color: #000;
      border-color: #f59e0b;
    }

    /* Final Total Score Authority Card */
    .score-total-card {
      background: linear-gradient(135deg, #090d16 0%, #111a2e 100%);
      border: 2px solid rgba(245, 158, 11, 0.5);
      border-radius: 14px;
      padding: 20px 24px;
      margin-bottom: 20px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4), 0 0 20px rgba(245, 158, 11, 0.15);
    }

    /* Leaderboard Podiums */
    .podium-gold {
      border-left: 4px solid #f59e0b !important;
      background: rgba(245, 158, 11, 0.09) !important;
    }
    .podium-silver {
      border-left: 4px solid #94a3b8 !important;
      background: rgba(148, 163, 184, 0.09) !important;
    }
    .podium-bronze {
      border-left: 4px solid #b45309 !important;
      background: rgba(180, 83, 9, 0.09) !important;
    }

    /* Tournament Bracket Tree */
    .bracket-container {
      display: flex;
      gap: 32px;
      overflow-x: auto;
      padding: 20px 10px 40px;
    }
    .bracket-round {
      flex: 0 0 280px;
      display: flex;
      flex-direction: column;
      justify-content: space-around;
      gap: 24px;
    }
    .bracket-round-title {
      font-size: 13px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--accent-amber-bright);
      text-align: center;
      margin-bottom: 12px;
      padding-bottom: 8px;
      border-bottom: 2px solid rgba(245, 158, 11, 0.35);
    }
    .bracket-match {
      background: #151e2f;
      border: 1.5px solid var(--border-color);
      border-radius: 11px;
      padding: 13px;
      box-shadow: 0 4px 14px rgba(0,0,0,0.35);
      cursor: pointer;
      transition: all 0.2s ease;
      position: relative;
    }
    .bracket-match:hover {
      border-color: var(--accent-emerald);
      transform: scale(1.02);
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.25);
    }
    .match-team {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 10px;
      border-radius: 7px;
      background: rgba(0, 0, 0, 0.4);
      margin-bottom: 6px;
      font-size: 13px;
      color: #f1f5f9;
    }
    .match-team:last-child {
      margin-bottom: 0;
    }
    .match-team.winner {
      background: rgba(16, 185, 129, 0.25);
      border-left: 3px solid var(--accent-emerald);
      font-weight: 700;
      color: #ffffff;
    }
    .team-score {
      font-weight: 800;
      font-size: 14px;
      padding: 2px 8px;
      border-radius: 4px;
      background: rgba(255, 255, 255, 0.15);
      color: #ffffff;
    }

    /* Print ID Cards */
    .id-card-item {
      width: 250px;
      height: 380px;
      background: #ffffff;
      color: #111827;
      border-radius: 14px;
      padding: 16px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      text-align: center;
      box-shadow: 0 8px 24px rgba(0,0,0,0.4);
      position: relative;
      border: 2px solid #e2e8f0;
      page-break-inside: avoid;
    }
    .id-card-badge {
      background: #111827;
      color: #f59e0b;
      font-weight: 800;
      font-size: 11px;
      padding: 4px 10px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 1px;
      display: inline-block;
      margin-bottom: 8px;
    }

    @media print {
      body {
        background: #fff !important;
        color: #000 !important;
      }
      .op-sidebar, .op-topbar, .no-print {
        display: none !important;
      }
      .op-main {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
      }
      .print-grid {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 15px !important;
      }
      .id-card-item {
        box-shadow: none !important;
        border: 1px solid #94a3b8 !important;
      }
    }

    @media (max-width: 991px) {
      .op-sidebar { transform: translateX(-100%); }
      .op-sidebar.open { transform: translateX(0); }
      .op-topbar { left: 0; }
      .op-main { margin-left: 0; width: 100%; }
    }
  </style>
</head>
<body>

<div id="layout-container">

  <!-- ======================================================================== -->
  <!-- SIDEBAR OPERASIONAL HARI-H (STANDALONE EXPRESS.JS)                       -->
  <!-- ======================================================================== -->
  <aside class="op-sidebar" id="appSidebar">
    <!-- Brand Box -->
    <div class="op-brand-box">
      <img src="/assets/img/logo.png" alt="Logo Semarak">
      <div class="brand-title-wrap">
        <h6>PUSAT OPERASIONAL</h6>
        <small>KOMANDO HARI-H • PORT 8000</small>
      </div>
    </div>

    <!-- Scrollable Navigation -->
    <div class="sidebar-scroll">
      <div class="nav-category">Modul Operasional</div>
      <ul class="op-nav-list">
        <li class="op-nav-item">
          <a class="op-nav-link active" onclick="switchTab('dashboard')">
            <i class="ri-dashboard-2-line"></i>
            <span>Overview Hari-H</span>
          </a>
        </li>
        <li class="op-nav-item">
          <a class="op-nav-link" onclick="switchTab('gate')">
            <i class="ri-qr-scan-2-line"></i>
            <span>Gate Check-in Barcode</span>
            <span class="badge bg-success ms-auto fs-10">Live</span>
          </a>
        </li>
        <li class="op-nav-item">
          <a class="op-nav-link" onclick="switchTab('berkas')">
            <i class="ri-file-search-line"></i>
            <span>Review & Verifikasi Berkas</span>
          </a>
        </li>
        <li class="op-nav-item">
          <a class="op-nav-link" onclick="switchTab('juri')">
            <i class="ri-scales-3-line"></i>
            <span>Penjurian Digital Juri</span>
          </a>
        </li>
        <li class="op-nav-item">
          <a class="op-nav-link" onclick="switchTab('turnamen')">
            <i class="ri-trophy-line"></i>
            <span>Bagan Turnamen Gugur</span>
            <span class="badge bg-warning text-dark ms-auto fs-10">Arena</span>
          </a>
        </li>
        <li class="op-nav-item">
          <a class="op-nav-link" onclick="switchTab('print')">
            <i class="ri-printer-line"></i>
            <span>Print Studio ID Card</span>
          </a>
        </li>
      </ul>

      <div class="nav-category mt-3">Link Sistem Terpadu</div>
      <ul class="op-nav-list">
        <li class="op-nav-item">
          <a href="{{ route('public.scoreboard') }}" target="_blank" class="op-nav-link">
            <i class="ri-broadcast-line text-info"></i>
            <span>Layar Scoreboard Panggung</span>
            <i class="ri-external-link-line fs-12 ms-auto text-muted"></i>
          </a>
        </li>
        <li class="op-nav-item">
          <a href="{{ route('admin.dashboard') }}" class="op-nav-link text-warning">
            <i class="ri-shield-user-line text-warning"></i>
            <span>Panel Pendaftaran (Admin)</span>
            <i class="ri-arrow-right-line fs-12 ms-auto text-muted"></i>
          </a>
        </li>
        <li class="op-nav-item">
          <a href="{{ route('public.home') }}" target="_blank" class="op-nav-link">
            <i class="ri-global-line"></i>
            <span>Laman Publik Semarak</span>
            <i class="ri-external-link-line fs-12 ms-auto text-muted"></i>
          </a>
        </li>
      </ul>
    </div>
  </aside>

  <!-- ======================================================================== -->
  <!-- TOPBAR HEADER                                                            -->
  <!-- ======================================================================== -->
  <header class="op-topbar no-print">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary d-lg-none text-white border-0" onclick="toggleSidebar()">
        <i class="ri-menu-2-line fs-20"></i>
      </button>
      <div class="d-flex align-items-center gap-2">
        <span class="pulse-dot"></span>
        <span class="fs-13 fw-semibold text-white">SISTEM OPERASIONAL LAPANGAN HARI-H</span>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-none d-md-inline-block fs-11">
          Edisi XII • 2026
        </span>
      </div>
    </div>

    <div class="d-flex align-items-center gap-3">
      <!-- Live Clock WIB -->
      <div class="d-none d-sm-flex align-items-center gap-2 px-3 py-1 rounded bg-black border border-secondary" style="font-family: monospace; font-size: 13px;">
        <i class="ri-time-line text-warning"></i>
        <span id="liveClock" class="text-white fw-bold">--:--:-- WIB</span>
      </div>

      <!-- Database Status Badge -->
      <div id="dbBadge" class="badge bg-success-subtle text-success border border-success-subtle py-2 px-3 fs-12 d-flex align-items-center gap-1">
        <i class="ri-database-2-line"></i> <span id="dbDriverText">MariaDB Connected (3307)</span>
      </div>

      <!-- Quick Link to Laravel Panel -->
      <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-warning d-inline-flex align-items-center gap-1 fs-12 fw-semibold">
        <i class="ri-shield-user-line"></i> <span>Panel Pendaftaran</span>
      </a>
    </div>
  </header>

  <!-- ======================================================================== -->
  <!-- MAIN OPERATIONAL VIEW CONTAINER                                          -->
  <!-- ======================================================================== -->
  <main class="op-main">
    <div class="op-body">

      <!-- ==================================================================== -->
      <!-- TAB 1: DASHBOARD OVERVIEW                                            -->
      <!-- ==================================================================== -->
      <div id="tab-dashboard" class="tab-pane-custom">
        <!-- Greeting Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
          <div>
            <h4 class="fw-bold mb-1">Pusat Komando Operasional Lapangan</h4>
            <p class="text-muted fs-13 mb-0">Semarak Aliyah XII • Pantau alur masuk delegasi, verifikasi berkas, dan arena turnamen real-time.</p>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-success d-flex align-items-center gap-1" onclick="loadOverview()">
              <i class="ri-refresh-line"></i> Segarkan Data
            </button>
            <button class="btn btn-sm btn-primary d-flex align-items-center gap-1" onclick="switchTab('gate')">
              <i class="ri-qr-scan-line"></i> Buka Gate Scanner
            </button>
          </div>
        </div>

        <!-- 4 Metric Cards -->
        <div class="row g-3 mb-4">
          <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="stat-val" id="statTotalDelegasi">0</div>
                  <div class="stat-lbl">Total Delegasi Masuk</div>
                </div>
                <div class="p-2 rounded bg-primary-subtle text-primary fs-20"><i class="ri-group-line"></i></div>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-sm-6">
            <div class="stat-card" style="border-left: 4px solid var(--accent-emerald);">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="stat-val text-success" id="statHadirGate">0</div>
                  <div class="stat-lbl">Hadir di Gate (<span id="statPersenHadir">0%</span>)</div>
                </div>
                <div class="p-2 rounded bg-success-subtle text-success fs-20"><i class="ri-checkbox-circle-line"></i></div>
              </div>
              <div class="progress mt-3" style="height: 6px; background: rgba(255,255,255,0.06);">
                <div id="progressHadir" class="progress-bar bg-success" style="width: 0%"></div>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-sm-6">
            <div class="stat-card" style="border-left: 4px solid var(--accent-rose);">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="stat-val text-danger" id="statBelumHadir">0</div>
                  <div class="stat-lbl">Belum Hadir (Menunggu)</div>
                </div>
                <div class="p-2 rounded bg-danger-subtle text-danger fs-20"><i class="ri-time-line"></i></div>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-sm-6">
            <div class="stat-card" style="border-left: 4px solid var(--accent-amber);">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="stat-val text-warning" id="statNilaiJuri">0</div>
                  <div class="stat-lbl">Penjurian Selesai (Arsip)</div>
                </div>
                <div class="p-2 rounded bg-warning-subtle text-warning fs-20"><i class="ri-scales-3-line"></i></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Action & Recent Gate Activity -->
        <div class="row g-4">
          <!-- Recent Check-ins -->
          <div class="col-lg-8">
            <div class="op-card h-100">
              <div class="op-card-header">
                <h5 class="op-card-title"><i class="ri-radar-line text-success"></i> Aktivitas Gate Check-in Terbaru</h5>
                <span class="badge bg-success-subtle text-success fs-11">Real-time Stream</span>
              </div>
              <div class="op-table-wrap">
                <table class="op-table">
                  <thead>
                    <tr>
                      <th>Waktu</th>
                      <th>Kode Tiket</th>
                      <th>Asal Sekolah / Kontingen</th>
                      <th>Cabang Lomba</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody id="recentCheckinsTbody">
                    <tr><td colspan="5" class="text-center py-4 text-muted">Memuat data aktivitas gate...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Operational Shortcuts -->
          <div class="col-lg-4">
            <div class="op-card h-100">
              <div class="op-card-header">
                <h5 class="op-card-title"><i class="ri-flashlight-line text-warning"></i> Jalur Cepat Lapangan</h5>
              </div>
              <div class="d-flex flex-column gap-2">
                <button class="btn btn-outline-light text-start p-3 d-flex align-items-center justify-content-between rounded-3 border-secondary" onclick="switchTab('gate')">
                  <div>
                    <div class="fw-bold fs-13"><i class="ri-qr-scan-2-line text-success me-2"></i> Pintu Gate Barcode</div>
                    <small class="text-muted">Scan barcode ID Card peserta masuk</small>
                  </div>
                  <i class="ri-arrow-right-s-line fs-18"></i>
                </button>
                <button class="btn btn-outline-light text-start p-3 d-flex align-items-center justify-content-between rounded-3 border-secondary" onclick="switchTab('juri')">
                  <div>
                    <div class="fw-bold fs-13"><i class="ri-scales-3-line text-warning me-2"></i> Arena Penjurian Juri</div>
                    <small class="text-muted">Rubrik penilaian & stopwatch panggung</small>
                  </div>
                  <i class="ri-arrow-right-s-line fs-18"></i>
                </button>
                <button class="btn btn-outline-light text-start p-3 d-flex align-items-center justify-content-between rounded-3 border-secondary" onclick="switchTab('turnamen')">
                  <div>
                    <div class="fw-bold fs-13"><i class="ri-trophy-line text-info me-2"></i> Bagan Pertandingan</div>
                    <small class="text-muted">Voli Putra, Voli Putri, Mobile Legends</small>
                  </div>
                  <i class="ri-arrow-right-s-line fs-18"></i>
                </button>
                <button class="btn btn-outline-light text-start p-3 d-flex align-items-center justify-content-between rounded-3 border-secondary" onclick="switchTab('print')">
                  <div>
                    <div class="fw-bold fs-13"><i class="ri-printer-line text-purple me-2"></i> Cetak ID Card</div>
                    <small class="text-muted">Cetak kartu delegasi siap pakai</small>
                  </div>
                  <i class="ri-arrow-right-s-line fs-18"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================================================================== -->
      <!-- TAB 2: GATE CHECK-IN SCANNER                                         -->
      <!-- ==================================================================== -->
      <div id="tab-gate" class="tab-pane-custom d-none">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
          <div>
            <h4 class="fw-bold mb-1"><i class="ri-qr-scan-2-line text-success"></i> Gate Check-In & Scanner Tiket</h4>
            <p class="text-muted fs-13 mb-0">Gunakan Barcode Scanner USB HID atau ketik kode pendaftaran langsung (misal: SA26-102941).</p>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-dark border border-secondary px-3 py-2 text-white font-monospace fs-12">
              <i class="ri-sound-module-line text-warning me-1"></i> Audio Beep: Aktif
            </span>
          </div>
        </div>

        <!-- Scanner Big Box -->
        <div class="op-card mb-4">
          <div class="scanner-box">
            <div class="fs-14 fw-bold text-success mb-2 text-uppercase letter-spacing-1">
              <i class="ri-barcode-box-line me-1 fs-18 align-middle"></i> Arahkan Scanner Barcode ke Area Ini
            </div>
            <form id="scanForm" onsubmit="handleScanSubmit(event)">
              <input type="text" id="scanInput" class="form-control scanner-input" placeholder="SA26-XXXXXX" autofocus autocomplete="off">
            </form>
            <p class="text-muted fs-12 mt-2 mb-0">Tekan Enter atau tembakkan scanner USB untuk konfirmasi kehadiran otomatis</p>
          </div>

          <!-- Scan Result Notification Alert -->
          <div id="scanResultBox" class="mt-3 d-none"></div>
        </div>

        <!-- Live Delegates Gate Table -->
        <div class="op-card">
          <div class="op-card-header">
            <h5 class="op-card-title"><i class="ri-list-check-2"></i> Daftar Kehadiran Delegasi Pintu Masuk</h5>
            <div class="d-flex gap-2">
              <select id="filterGateComp" class="form-select form-select-sm bg-dark text-white border-secondary" style="width: 200px;" onchange="loadDelegates()">
                <option value="">Semua Cabang Lomba</option>
              </select>
              <select id="filterGateStatus" class="form-select form-select-sm bg-dark text-white border-secondary" style="width: 160px;" onchange="loadDelegates()">
                <option value="">Semua Status</option>
                <option value="hadir">Sudah Hadir</option>
                <option value="belum_hadir">Belum Hadir</option>
              </select>
              <input type="text" id="searchGate" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Cari nama / sekolah..." style="width: 200px;" oninput="loadDelegates()">
            </div>
          </div>
          <div class="op-table-wrap">
            <table class="op-table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Kode Tiket / Barcode</th>
                  <th>Sekolah / Kontingen</th>
                  <th>Cabang Lomba</th>
                  <th>Pembina (WhatsApp)</th>
                  <th>Status Hadir</th>
                  <th>Waktu Hadir</th>
                  <th>Aksi Manual</th>
                </tr>
              </thead>
              <tbody id="gateTableTbody">
                <tr><td colspan="8" class="text-center py-4 text-muted">Memuat data delegasi...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ==================================================================== -->
      <!-- TAB 3: REVIEW & VERIFIKASI BERKAS                                    -->
      <!-- ==================================================================== -->
      <div id="tab-berkas" class="tab-pane-custom d-none">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
          <div>
            <h4 class="fw-bold mb-1"><i class="ri-file-search-line text-primary"></i> Review & Verifikasi Berkas Fisik</h4>
            <p class="text-muted fs-13 mb-0">Verifikasi dokumen identitas peserta, surat tugas sekolah, dan konfirmasi pembayaran di meja registrasi.</p>
          </div>
        </div>

        <div class="op-card">
          <div class="op-table-wrap">
            <table class="op-table">
              <thead>
                <tr>
                  <th>Kode Reg</th>
                  <th>Sekolah / Kontingen</th>
                  <th>Cabang Lomba</th>
                  <th>Peserta Delegasi</th>
                  <th>Total Biaya</th>
                  <th>Status Pembayaran</th>
                  <th>Catatan Verifikasi</th>
                  <th>Update Status</th>
                </tr>
              </thead>
              <tbody id="verificationTableTbody">
                <tr><td colspan="8" class="text-center py-4 text-muted">Memuat berkas pendaftar...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ==================================================================== -->
      <!-- TAB 4: PENJURIAN DIGITAL JURI                                        -->
      <!-- ==================================================================== -->
      <div id="tab-juri" class="tab-pane-custom d-none">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
          <div>
            <h4 class="fw-bold mb-1"><i class="ri-scales-3-line text-warning"></i> Arena Penjurian Digital Juri</h4>
            <p class="text-muted fs-13 mb-0">Penjurian digital dengan kustomisasi rubrik kriteria, pilihan mode persentase (%) atau skor poin mutlak.</p>
          </div>
          <div class="d-flex flex-wrap align-items-center gap-2">
            <span id="tabActiveModeBadge" class="badge-mode-pill badge-mode-percentage">
              <i class="ri-percent-line"></i> <span id="tabActiveModeBadgeText">Mode: Persentase (%)</span>
            </span>

            <select id="juryCompetitionSelect" class="form-select bg-dark text-white border-warning fw-bold" style="min-width: 220px;" onchange="loadJuryArena()">
              <option value="albanjari">Festival Al-Banjari</option>
              <option value="singer">Singer Lagu Islami</option>
              <option value="storytelling">Storytelling Inggris</option>
              <option value="mipa">Olimpiade MIPA</option>
              <option value="ips">Olimpiade IPS</option>
              <option value="volleyball-putra">Turnamen Voli Putra</option>
              <option value="volleyball-putri">Turnamen Voli Putri</option>
              <option value="mobile-legends">Turnamen Mobile Legends</option>
            </select>

            <button type="button" class="btn btn-warning text-dark fw-bold d-flex align-items-center gap-2 px-3 shadow-sm" onclick="openRubricCustomizerModal()">
              <i class="ri-equalizer-line fs-16"></i>
              <span>Kustomisasi Rubrik & Mode</span>
            </button>
          </div>
        </div>

        <div class="row g-4">
          <!-- Left: Score Input Form & Stopwatch -->
          <div class="col-lg-7">
            <div class="op-card">
              <div class="op-card-header">
                <h5 class="op-card-title"><i class="ri-edit-line text-warning"></i> Formulir Penilaian Peserta</h5>
                <!-- Professional Arena Digital Stopwatch HUD -->
                <div class="stopwatch-hud">
                  <div class="d-flex flex-column align-items-end me-1">
                    <span class="fs-10 fw-bold text-uppercase letter-spacing-1" style="color: #cbd5e1;">Timer Panggung</span>
                    <span id="stopwatchStatusBadge" class="badge bg-secondary text-white fs-9 py-0 px-1">STANDBY</span>
                  </div>
                  <span id="stopwatchDisplay" class="stopwatch-hud-digits">00:00</span>
                  <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-success fw-bold px-2 py-1" onclick="toggleStopwatch()" id="btnStopwatch">
                      <i class="ri-play-fill me-1"></i> Start
                    </button>
                    <button type="button" class="btn btn-outline-secondary px-2 py-1" onclick="resetStopwatch()" title="Reset Stopwatch">
                      <i class="ri-restart-line"></i>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Scoring Mode Switcher Card -->
              <div class="p-3 mb-3 rounded-3 border" style="background: rgba(15, 23, 42, 0.85); border-color: rgba(255, 255, 255, 0.12);">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                  <div>
                    <span class="fs-11 fw-bold text-uppercase d-block" style="color: #cbd5e1;">Mode Format Nilai Dewan Juri:</span>
                    <span class="fs-13 fw-bold text-white" id="scoringModeTitle">
                      <i class="ri-percent-line text-warning me-1"></i> Mode Nilai Persentase (%)
                    </span>
                  </div>
                  <div class="btn-group btn-group-sm" role="group" aria-label="Mode Penilaian">
                    <button type="button" class="btn btn-outline-warning active" id="btnModePercentage" onclick="changeActiveScoringMode('percentage')">
                      <i class="ri-percent-line me-1"></i> Mode Persentase (%)
                    </button>
                    <button type="button" class="btn btn-outline-warning" id="btnModePoint" onclick="changeActiveScoringMode('point')">
                      <i class="ri-numbers-line me-1"></i> Mode Skor (Poin)
                    </button>
                  </div>
                </div>
                <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 fs-12" style="color: #cbd5e1;" id="scoringModeDesc">
                  <i class="ri-information-line text-warning me-1"></i> <strong>Mode Persentase (%):</strong> Tiap aspek dinilai skala 0 - 100, nilai akhir dihitung secara proporsional sesuai bobot persentase.
                </div>
              </div>

              <form id="juryScoreForm" onsubmit="handleJuryScoreSubmit(event)">
                <div class="mb-3">
                  <label class="form-label">Pilih Delegasi / Peserta Tampil:</label>
                  <select id="juryParticipantSelect" class="form-select bg-dark text-white border-secondary" required onchange="onSelectJuryParticipant()" oninput="onSelectJuryParticipant()">
                    <option value="">-- Pilih Peserta --</option>
                  </select>
                </div>

                <!-- Ergonomic Participant Spotlight Card (Appears on Select) -->
                <div id="participantSpotlight" class="delegate-spotlight d-none">
                  <!-- Dynamically populated in JavaScript -->
                </div>

                <div class="mb-3">
                  <label class="form-label">Nama Dewan Juri / Meja Penilai:</label>
                  <input type="text" id="juryNameInput" class="form-control bg-dark text-white border-secondary" value="Dewan Juri 1" required>
                </div>

                <div class="mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label text-warning mb-0">
                      <i class="ri-list-check-2 text-warning me-1"></i> Rubrik Kriteria Penilaian:
                    </label>
                    <span class="fs-12 fw-medium" style="color: #cbd5e1;" id="rubricScaleHint">Skala nilai: 0 - 100 per kriteria</span>
                  </div>
                  <div id="rubricInputsContainer" class="d-flex flex-column gap-3 p-3 rounded" style="background: rgba(11, 17, 32, 0.85); border: 1.5px solid rgba(255, 255, 255, 0.12);">
                    <!-- Dynamic Rubric Criteria with Quick Score Preset Chips inserted here -->
                  </div>
                </div>

                <!-- Authoritative Total Score Display HUD -->
                <div class="score-total-card">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                      <span class="d-block text-white fw-bold fs-13 text-uppercase letter-spacing-1" id="finalScoreCardTitle">Total Nilai Terkalkulasi</span>
                      <span class="fs-12" style="color: #cbd5e1;" id="finalScoreCardSubtitle">Kalkulasi bobot proporsional otomatis dewan juri</span>
                    </div>
                    <div class="text-end">
                      <div class="d-flex align-items-baseline justify-content-end gap-1">
                        <span class="fs-32 fw-black text-warning font-monospace lh-1" id="finalTotalScore">0.00</span>
                        <span class="fs-14 fw-bold" style="color: #cbd5e1;" id="finalTotalDenominator">/ 100</span>
                      </div>
                      <div id="performanceGradeBadge" class="badge bg-warning text-dark fs-11 fw-bold mt-1 px-2 py-1 shadow-sm">
                        ✨ Menunggu Input Nilai
                      </div>
                    </div>
                  </div>
                  <div class="progress" style="height: 7px; background: rgba(255,255,255,0.08); border-radius: 4px;">
                    <div id="finalScoreProgress" class="progress-bar bg-warning" role="progressbar" style="width: 0%; transition: width 0.3s ease;"></div>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Catatan Dewan Juri (Opsional):</label>
                  <textarea id="juryNotes" class="form-control bg-dark text-white border-secondary" rows="2" placeholder="Catatan kelebihan / kekurangan penampilan peserta..."></textarea>
                </div>

                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-warning text-dark fw-bold flex-grow-1 py-2 fs-14 shadow-sm" id="btnSubmitScore">
                    <i class="ri-save-3-line me-1"></i> Simpan & Kunci Nilai Juri
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Right: Leaderboard / Scores Archive -->
          <div class="col-lg-5">
            <div class="op-card">
              <div class="op-card-header">
                <div>
                  <h5 class="op-card-title"><i class="ri-trophy-line text-warning"></i> Klasemen & Arsip Nilai</h5>
                  <small class="fs-11 d-block mt-1" style="color: #cbd5e1;">Peringkat live berdasarkan akumulasi nilai juri</small>
                </div>
                <span class="badge bg-warning text-dark fw-bold fs-11 px-2 py-1" id="scoreCountBadge">0 Nilai</span>
              </div>

              <!-- Leaderboard Quick Search -->
              <div class="mb-3">
                <input type="text" id="searchLeaderboard" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="🔍 Cari sekolah di klasemen..." oninput="filterLeaderboardTable()">
              </div>

              <div class="op-table-wrap">
                <table class="op-table">
                  <thead>
                    <tr>
                      <th style="width: 75px;">Peringkat</th>
                      <th>Sekolah / Kontingen</th>
                      <th style="width: 75px;">Durasi</th>
                      <th style="width: 85px;" class="text-end">Nilai</th>
                    </tr>
                  </thead>
                  <tbody id="juryLeaderboardTbody">
                    <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada skor tersimpan.</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================================================================== -->
      <!-- TAB 5: ARENA TURNAMEN BAGAN GUGUR (NATIVE DARK TOURNAMENT ARENA)     -->
      <!-- ==================================================================== -->
      <div id="tab-turnamen" class="tab-pane-custom d-none">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
          <div>
            <h4 class="fw-bold mb-1"><i class="ri-trophy-line text-warning"></i> Arena Turnamen Sistem Gugur</h4>
            <p class="text-muted fs-13 mb-0">Bagan pertandingan 8 besar, papan skor pertandingan, dan lembar wasit digital interaktif.</p>
          </div>
          <div class="d-flex align-items-center gap-2">
            <select id="tournamentCompSelect" class="form-select bg-dark text-white border-warning fw-bold" style="min-width: 240px;" onchange="loadTournamentBracket()">
              <option value="volleyball-putra">🏐 Turnamen Voli Putra</option>
              <option value="volleyball-putri">🏐 Turnamen Voli Putri</option>
              <option value="mobile-legends">🎮 Turnamen Mobile Legends (MLBB)</option>
            </select>
            <button class="btn btn-outline-warning btn-sm d-flex align-items-center gap-1 fw-semibold" onclick="regenerateBracketPrompt()">
              <i class="ri-shuffle-line"></i> Acak Ulang Bagan
            </button>
            <a href="{{ route('public.scoreboard') }}" target="_blank" class="btn btn-primary btn-sm d-flex align-items-center gap-1 fw-semibold">
              <i class="ri-broadcast-line"></i> Scoreboard Panggung
            </a>
          </div>
        </div>

        <!-- Native Dark Bracket Tree Container -->
        <div class="op-card p-4">
          <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary border-opacity-25">
            <div class="d-flex align-items-center gap-2">
              <span class="pulse-dot"></span>
              <h5 class="op-card-title mb-0"><i class="ri-git-merge-line text-warning me-1"></i> Bagan Pertandingan 8 Besar Hingga Grand Final</h5>
            </div>
            <span class="badge bg-dark border border-secondary text-muted fs-11">
              <i class="ri-cursor-line me-1"></i> Klik kotak pertandingan untuk input skor & lembar wasit
            </span>
          </div>

          <div class="bracket-container" id="bracketTreeContainer">
            <div class="text-center py-5 w-100 text-muted">
              <div class="spinner-border text-warning spinner-border-sm mb-2" role="status"></div>
              <div>Memuat bagan pertandingan...</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================================================================== -->
      <!-- TAB 6: PRINT STUDIO ID CARD                                          -->
      <!-- ==================================================================== -->
      <div id="tab-print" class="tab-pane-custom d-none">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
          <div>
            <h4 class="fw-bold mb-1"><i class="ri-printer-line text-info"></i> Print Studio ID Card Delegasi</h4>
            <p class="text-muted fs-13 mb-0">Cetak kartu identitas resmi peserta bertanda barcode untuk dibawa di pintu gate masuk.</p>
          </div>
          <div class="d-flex gap-2">
            <select id="filterPrintComp" class="form-select form-select-sm bg-dark text-white border-secondary" style="width: 200px;" onchange="loadIdCards()">
              <option value="">Semua Lomba</option>
            </select>
            <button class="btn btn-primary btn-sm d-flex align-items-center gap-1" onclick="window.print()">
              <i class="ri-printer-fill"></i> Cetak Sekarang (Ctrl + P)
            </button>
          </div>
        </div>

        <!-- ID Card Printable Grid -->
        <div class="d-flex flex-wrap gap-4 justify-content-start print-grid" id="idCardsContainer">
          <div class="text-center py-5 w-100 text-muted">Memuat data kartu peserta...</div>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- ======================================================================== -->
<!-- MODAL: SCORE MATCH TURNAMEN                                              -->
<!-- ======================================================================== -->
<div class="modal fade" id="matchScoreModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-white border shadow-lg" style="background: #0f172a; border-color: rgba(255, 255, 255, 0.15); border-radius: 14px;">
      <div class="modal-header border-secondary p-3" style="background: #090d16;">
        <h5 class="modal-title fs-15 fw-bold text-white" id="matchModalTitle">Input Skor Pertandingan</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="matchScoreForm" onsubmit="handleMatchScoreSubmit(event)">
        <div class="modal-body p-4">
          <input type="hidden" id="modalMatchId">
          
          <div class="row g-3 align-items-center mb-3">
            <div class="col-5 text-center">
              <span class="d-block fw-bold fs-13 text-truncate text-white" id="modalTeam1Name">Tim 1</span>
              <input type="number" id="modalTeam1Score" class="form-control text-white text-center fs-22 fw-bold border-secondary mt-1" min="0" value="0" style="background: #0b1120;">
              <input type="text" id="modalTeam1Sets" class="form-control form-control-sm text-center border-secondary mt-1" style="background: #0b1120; color: #cbd5e1;" placeholder="Poin set: 25, 25">
            </div>
            <div class="col-2 text-center fs-20 fw-bold text-warning">VS</div>
            <div class="col-5 text-center">
              <span class="d-block fw-bold fs-13 text-truncate text-white" id="modalTeam2Name">Tim 2</span>
              <input type="number" id="modalTeam2Score" class="form-control text-white text-center fs-22 fw-bold border-secondary mt-1" min="0" value="0" style="background: #0b1120;">
              <input type="text" id="modalTeam2Sets" class="form-control form-control-sm text-center border-secondary mt-1" style="background: #0b1120; color: #cbd5e1;" placeholder="Poin set: 23, 21">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Status Pertandingan:</label>
            <select id="modalMatchStatus" class="form-select text-white border-secondary" style="background: #0b1120;">
              <option value="scheduled">Terjadwal (Scheduled)</option>
              <option value="ongoing">Sedang Bertanding (Ongoing)</option>
              <option value="completed">Selesai (Completed - Loloskan Pemenang)</option>
            </select>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">Lapangan / Court:</label>
              <input type="text" id="modalMatchCourt" class="form-control form-control-sm text-white border-secondary" style="background: #0b1120;" placeholder="Lapangan Utama">
            </div>
            <div class="col-6">
              <label class="form-label">Wasit / Referee:</label>
              <input type="text" id="modalMatchReferee" class="form-control form-control-sm text-white border-secondary" style="background: #0b1120;" placeholder="Nama Wasit">
            </div>
          </div>
        </div>
        <div class="modal-footer p-3" style="background: #090d16; border-top: 1px solid rgba(255, 255, 255, 0.1);">
          <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-success btn-sm fw-bold px-3">Simpan & Perbarui Bagan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ======================================================================== -->
<!-- MODAL: KUSTOMISASI RUBRIK PENJURIAN & MODE NILAI (CYBERNETIC DARK UI)    -->
<!-- ======================================================================== -->
<div class="modal fade" id="rubricCustomizerModal" tabindex="-1" aria-labelledby="rubricModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content text-white border shadow-lg" style="background: #0f172a; border-color: rgba(255, 255, 255, 0.15); border-radius: 14px; overflow: hidden;">
      <div class="modal-header p-3" style="background: #090d16; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
        <div class="d-flex align-items-center gap-2">
          <div class="p-2 rounded bg-warning-subtle text-warning fs-18">
            <i class="ri-equalizer-line"></i>
          </div>
          <div>
            <h5 class="modal-title fs-15 fw-bold text-white mb-0" id="rubricModalLabel">Kustomisasi Rubrik Penilaian</h5>
            <small class="fs-12" style="color: #cbd5e1;" id="rubricModalSubtitle">Cabang Lomba: Festival Albanjari</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="rubricCustomizerForm" onsubmit="handleRubricSave(event)">
        <div class="modal-body p-4" style="background: #0f172a;">
          <!-- Format Bobot Selector: Mode Skor / Poin vs Persentase -->
          <div class="card p-3 mb-3 border" style="background: #162032; border-color: rgba(255, 255, 255, 0.14); border-radius: 10px;">
            <div class="row align-items-center g-2">
              <div class="col-md-5">
                <label class="form-label fs-13 fw-bold text-white mb-0">
                  <i class="ri-sound-module-line text-warning me-1"></i> Pilihan Mode Format Nilai:
                </label>
                <p class="fs-11 mb-0" style="color: #cbd5e1;">Pilih metode kalkulasi dewan juri</p>
              </div>
              <div class="col-md-7">
                <div class="btn-group w-100" role="group" aria-label="Format Bobot">
                  <input type="radio" class="btn-check" name="modal_rubric_type" id="modalTypePercentage" value="percentage" checked onchange="onModalModeChange('percentage')">
                  <label class="btn btn-outline-warning btn-sm py-2 fs-12 fw-semibold" for="modalTypePercentage" id="modalLabelPercentage">
                    <i class="ri-percent-line me-1"></i> Nilai Persentase (%)
                  </label>

                  <input type="radio" class="btn-check" name="modal_rubric_type" id="modalTypePoint" value="point" onchange="onModalModeChange('point')">
                  <label class="btn btn-outline-warning btn-sm py-2 fs-12 fw-semibold" for="modalTypePoint" id="modalLabelPoint">
                    <i class="ri-numbers-line me-1"></i> Nilai Skor (Poin Mutlak)
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Live Total Weight / Score Banner -->
          <div class="card p-3 mb-3 border" style="background: #162032; border-color: rgba(255, 255, 255, 0.14); border-radius: 10px;">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="fs-12 fw-semibold text-white" id="modalCalcSummaryLabel">
                <i class="ri-scales-3-line text-warning me-1"></i> Total Akumulasi Bobot (%):
              </span>
              <span class="badge bg-success fs-12 px-3 py-1" id="modalTotalBadge">100% (Ideal)</span>
            </div>
            <div class="progress" style="height: 6px; background: rgba(255,255,255,0.08);">
              <div class="progress-bar bg-success" id="modalTotalProgress" role="progressbar" style="width: 100%;"></div>
            </div>
            <small class="fs-11 mt-2 d-block" style="color: #cbd5e1;" id="modalModeHelperText">
              <i class="ri-information-line text-warning me-1"></i> <strong>Mode Persentase:</strong> Tiap aspek memiliki bobot persentase (ideal 100%). Nilai akhir dihitung secara proporsional.
            </small>
          </div>

          <!-- Criteria Item Container -->
          <div class="table-responsive rounded border mb-3" style="border-color: rgba(255, 255, 255, 0.12) !important;">
            <table class="table table-dark table-hover align-middle mb-0">
              <thead>
                <tr class="fs-12" style="background: #090d16;">
                  <th style="width: 45px; color: #cbd5e1;" class="text-center">No</th>
                  <th style="color: #cbd5e1;">Aspek & Kriteria Penilaian</th>
                  <th style="width: 180px; color: #cbd5e1;" class="text-center" id="modalColWeightHeader">Bobot Nilai (%)</th>
                  <th style="width: 50px; color: #cbd5e1;" class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody id="modalCriteriaTableBody" class="fs-13">
                <!-- Dynamic Criteria Rows -->
              </tbody>
            </table>
          </div>

          <!-- Add & Reset Actions -->
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <button type="button" class="btn btn-outline-info btn-sm rounded-pill px-3" onclick="addModalCriteriaRow()">
              <i class="ri-add-circle-line me-1"></i> Tambah Kriteria Baru
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 text-white" onclick="resetModalToJuknis()">
              <i class="ri-restart-line me-1"></i> Reset ke Standar Juknis
            </button>
          </div>
        </div>

        <div class="modal-footer p-3 d-flex justify-content-between align-items-center" style="background: #090d16; border-top: 1px solid rgba(255, 255, 255, 0.1);">
          <span class="fs-12" style="color: #cbd5e1;" id="modalStatusText"></span>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-warning text-dark btn-sm px-4 fw-bold" id="modalBtnSave">
              <i class="ri-save-line me-1"></i> Simpan Kustomisasi Rubrik
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
@include('admin.partials.velzon-confirm-modal')


<script src="/assets/js/bootstrap.bundle.min.js"></script>

<script>
  window.velzonConfirm = function({ title, message, type = 'danger', confirmText = 'Ya, Lanjutkan', cancelText = 'Batal', icon }) {
    return new Promise((resolve) => {
      const modalEl = document.getElementById('velzonConfirmModal');
      if (!modalEl || typeof bootstrap === 'undefined') {
        resolve(window.confirm(message));
        return;
      }
      const titleEl = document.getElementById('velzonConfirmTitle');
      const msgEl = document.getElementById('velzonConfirmMessage');
      const okBtn = document.getElementById('velzonConfirmOkBtn');
      const cancelBtn = document.getElementById('velzonConfirmCancelBtn');
      const avatarEl = document.getElementById('velzonConfirmAvatar');
      const iconEl = document.getElementById('velzonConfirmIcon');

      titleEl.textContent = title || 'Konfirmasi Tindakan';
      msgEl.textContent = message || '';
      okBtn.textContent = confirmText;
      cancelBtn.textContent = cancelText;

      if (type === 'danger') {
        avatarEl.style.background = 'rgba(239, 68, 68, 0.15)';
        avatarEl.style.color = '#ef4444';
        iconEl.className = icon || 'ri-delete-bin-line';
        okBtn.className = 'btn btn-sm btn-danger px-3.5 py-1.5 rounded-3 text-white shadow-sm';
      } else if (type === 'warning') {
        avatarEl.style.background = 'rgba(245, 158, 11, 0.15)';
        avatarEl.style.color = '#f59e0b';
        iconEl.className = icon || 'ri-alert-line';
        okBtn.className = 'btn btn-sm btn-warning px-3.5 py-1.5 rounded-3 text-white shadow-sm';
      } else {
        avatarEl.style.background = 'rgba(16, 185, 129, 0.15)';
        avatarEl.style.color = '#10b981';
        iconEl.className = icon || 'ri-check-line';
        okBtn.className = 'btn btn-sm btn-success px-3.5 py-1.5 rounded-3 text-white shadow-sm';
      }

      const bsModal = new bootstrap.Modal(modalEl);
      let confirmed = false;

      const onOk = () => {
        confirmed = true;
        bsModal.hide();
      };

      const onHidden = () => {
        okBtn.removeEventListener('click', onOk);
        modalEl.removeEventListener('hidden.bs.modal', onHidden);
        resolve(confirmed);
      };

      okBtn.addEventListener('click', onOk);
      modalEl.addEventListener('hidden.bs.modal', onHidden);

      bsModal.show();
    });
  };
  // Global Operational State
  let currentTab = 'dashboard';
  let activeCompetitions = [];
  let delegatesCache = [];
  let currentJuryRubric = {};
  let currentJuryRubricType = 'percentage'; // 'percentage' or 'point'
  let currentTournamentMatches = [];
  let stopwatchInterval = null;
  let stopwatchSeconds = 0;
  let matchModalInstance = null;
  let rubricModalInstance = null;

  // Rubric Customizer Modal State
  let modalCompetitionId = 'albanjari';
  let modalRubricType = 'percentage';
  let modalCriteriaList = [];

  // Initialize
  document.addEventListener('DOMContentLoaded', () => {
    matchModalInstance = new bootstrap.Modal(document.getElementById('matchScoreModal'));
    const rModalEl = document.getElementById('rubricCustomizerModal');
    if (rModalEl) {
      rubricModalInstance = new bootstrap.Modal(rModalEl);
    }
    startLiveClock();

    // Check URL query parameters or hash to open specific tab & comp
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab') || window.location.hash.replace('#', '') || 'dashboard';
    const compParam = urlParams.get('comp') || '';

    if (compParam) {
      if (document.getElementById('tournamentCompSelect')) {
        document.getElementById('tournamentCompSelect').value = compParam;
      }
      if (document.getElementById('juryCompetitionSelect')) {
        document.getElementById('juryCompetitionSelect').value = compParam;
      }
      if (document.getElementById('filterGateComp')) {
        document.getElementById('filterGateComp').value = compParam;
      }
      if (document.getElementById('filterPrintComp')) {
        document.getElementById('filterPrintComp').value = compParam;
      }
    }

    switchTab(tabParam);
  });

  // Clock
  function startLiveClock() {
    function update() {
      const now = new Date();
      const hrs = String(now.getHours()).padStart(2, '0');
      const mins = String(now.getMinutes()).padStart(2, '0');
      const secs = String(now.getSeconds()).padStart(2, '0');
      document.getElementById('liveClock').textContent = `${hrs}:${mins}:${secs} WIB`;
    }
    update();
    setInterval(update, 1000);
  }

  // Synthesized Web Audio API Beep (Zero external files needed)
  function playAudioBeep(isSuccess = true) {
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      const ctx = new AudioCtx();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      
      osc.type = isSuccess ? 'sine' : 'sawtooth';
      osc.frequency.setValueAtTime(isSuccess ? 880 : 220, ctx.currentTime);
      gain.gain.setValueAtTime(0.25, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + (isSuccess ? 0.25 : 0.45));
      
      osc.start();
      osc.stop(ctx.currentTime + (isSuccess ? 0.25 : 0.45));
    } catch (e) {
      console.warn('AudioContext not permitted yet by browser gesture.');
    }
  }

  // Tab Switching (SPA navigation)
  function switchTab(tabId) {
    currentTab = tabId;
    document.querySelectorAll('.tab-pane-custom').forEach(el => el.classList.add('d-none'));
    const target = document.getElementById(`tab-${tabId}`);
    if (target) target.classList.remove('d-none');

    document.querySelectorAll('.op-nav-link').forEach(el => {
      const onclickAttr = el.getAttribute('onclick') || '';
      if (onclickAttr.includes(`'${tabId}'`)) {
        el.classList.add('active');
      } else {
        el.classList.remove('active');
      }
    });

    try {
      const url = new URL(window.location.href);
      url.searchParams.set('tab', tabId);
      window.history.replaceState(null, '', url.toString());
    } catch(e) {}

    // Trigger tab specific data load
    if (tabId === 'dashboard') loadOverview();
    if (tabId === 'gate') {
      loadDelegates();
      setTimeout(() => document.getElementById('scanInput')?.focus(), 200);
    }
    if (tabId === 'berkas') loadVerificationTable();
    if (tabId === 'juri') loadJuryArena();
    if (tabId === 'turnamen') loadTournamentBracket();
    if (tabId === 'print') loadIdCards();
  }

  function toggleSidebar() {
    document.getElementById('appSidebar').classList.toggle('open');
  }

  // ========================================================================
  // 1. DASHBOARD OVERVIEW DATA
  // ========================================================================
  async function loadOverview() {
    try {
      const res = await fetch('/api/operasional/overview');
      const json = await res.json();
      if (json.status !== 'success') throw new Error(json.message);

      const d = json.data;
      activeCompetitions = d.competitions || [];
      populateCompetitionDropdowns();

      // Stats
      document.getElementById('statTotalDelegasi').textContent = d.stats.total;
      document.getElementById('statHadirGate').textContent = d.stats.hadir;
      document.getElementById('statBelumHadir').textContent = d.stats.belumHadir;
      document.getElementById('statNilaiJuri').textContent = d.stats.scoresCount;
      document.getElementById('statPersenHadir').textContent = `${d.stats.persenKehadiran}%`;
      document.getElementById('progressHadir').style.width = `${d.stats.persenKehadiran}%`;

      document.getElementById('dbDriverText').textContent = `MySQL Connected (${d.port || '3307'})`;

      // Recent Activity Stream
      const tbody = document.getElementById('recentCheckinsTbody');
      if (d.recentCheckins && d.recentCheckins.length > 0) {
        tbody.innerHTML = d.recentCheckins.map(r => `
          <tr>
            <td class="font-monospace text-muted fs-12">${(r.checkin_at || '').slice(11, 19) || 'Baru saja'}</td>
            <td class="fw-bold text-success">${r.barcode || r.reg_code}</td>
            <td class="fw-semibold">${r.school_name}</td>
            <td><span class="badge bg-dark border border-secondary">${r.competition_title || r.competition_id}</span></td>
            <td><span class="badge-hadir"><i class="ri-check-line"></i> Hadir di Gate</span></td>
          </tr>
        `).join('');
      } else {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">Belum ada delegasi yang melakukan scan gate hari ini.</td></tr>`;
      }
    } catch (err) {
      console.error('Failed to load overview:', err);
    }
  }

  function populateCompetitionDropdowns() {
    const selects = ['filterGateComp', 'filterPrintComp'];
    selects.forEach(id => {
      const sel = document.getElementById(id);
      if (!sel) return;
      const cur = sel.value;
      sel.innerHTML = `<option value="">Semua Cabang Lomba</option>` + 
        activeCompetitions.map(c => `<option value="${c.id}">${c.title}</option>`).join('');
      sel.value = cur;
    });
  }

  // ========================================================================
  // 2. GATE SCANNER LOGIC
  // ========================================================================
  async function handleScanSubmit(e) {
    e.preventDefault();
    const input = document.getElementById('scanInput');
    const code = input.value.trim();
    if (!code) return;

    try {
      const res = await fetch('/api/operasional/gate/scan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code })
      });
      const data = await res.json();
      const resultBox = document.getElementById('scanResultBox');
      resultBox.classList.remove('d-none');

      if (data.success) {
        playAudioBeep(true);
        const del = data.delegate;
        resultBox.innerHTML = `
          <div class="alert ${data.alreadyCheckedIn ? 'alert-warning' : 'alert-success'} d-flex align-items-center justify-content-between p-3 rounded-3 shadow">
            <div class="d-flex align-items-center gap-3">
              <i class="${data.alreadyCheckedIn ? 'ri-alert-line text-warning' : 'ri-checkbox-circle-fill text-success'} fs-32"></i>
              <div>
                <h6 class="fw-bold mb-1">${del.school_name} <span class="badge bg-dark ms-2">${del.barcode || del.reg_code}</span></h6>
                <div class="fs-13 text-secondary">Cabang: <strong>${del.competition_title || del.competition_id}</strong> • Pembina: ${del.mentor_name} (${del.mentor_wa})</div>
                <div class="fs-12 ${data.alreadyCheckedIn ? 'text-warning' : 'text-success'} fw-semibold mt-1">${data.message}</div>
              </div>
            </div>
            <button class="btn btn-sm btn-outline-danger" onclick="undoCheckin(${del.id})">Batalkan</button>
          </div>
        `;
        input.value = '';
        loadDelegates();
        loadOverview();
      } else {
        playAudioBeep(false);
        resultBox.innerHTML = `
          <div class="alert alert-danger d-flex align-items-center gap-2 p-3 rounded-3">
            <i class="ri-close-circle-fill fs-24"></i>
            <div>
              <div class="fw-bold">Gagal Check-in</div>
              <div class="fs-13">${data.message}</div>
            </div>
          </div>
        `;
        input.select();
      }
    } catch (err) {
      alert('Error scanning: ' + err.message);
    }
  }

  async function loadDelegates() {
    const comp = document.getElementById('filterGateComp')?.value || '';
    const status = document.getElementById('filterGateStatus')?.value || '';
    const q = document.getElementById('searchGate')?.value || '';

    try {
      const res = await fetch(`/api/operasional/delegates?competition_id=${comp}&checkin_status=${status}&q=${encodeURIComponent(q)}`);
      const json = await res.json();
      delegatesCache = json.delegates || [];

      const tbody = document.getElementById('gateTableTbody');
      if (delegatesCache.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada peserta yang cocok dengan filter.</td></tr>`;
        return;
      }

      tbody.innerHTML = delegatesCache.map((d, i) => `
        <tr>
          <td>${i + 1}</td>
          <td><span class="font-monospace fw-bold text-success">${d.barcode || d.reg_code}</span></td>
          <td class="fw-bold text-white">${d.school_name}</td>
          <td><span class="badge bg-dark border border-secondary">${d.competition_title || d.competition_id}</span></td>
          <td class="fs-12">${d.mentor_name}<br><small class="text-muted">${d.mentor_wa}</small></td>
          <td>
            ${d.checkin_status === 'hadir' 
              ? '<span class="badge-hadir"><i class="ri-check-line"></i> Hadir</span>' 
              : '<span class="badge-belum"><i class="ri-time-line"></i> Belum Hadir</span>'}
          </td>
          <td class="fs-12 font-monospace text-muted">${d.checkin_at ? d.checkin_at.slice(11, 19) : '-'}</td>
          <td>
            ${d.checkin_status === 'hadir'
              ? `<button class="btn btn-sm btn-outline-danger py-1 px-2 fs-11" onclick="undoCheckin(${d.id})"><i class="ri-arrow-go-back-line"></i> Undo</button>`
              : `<button class="btn btn-sm btn-success py-1 px-2 fs-11" onclick="manualCheckin('${d.barcode || d.reg_code}')"><i class="ri-check-line"></i> Check-in</button>`}
          </td>
        </tr>
      `).join('');
    } catch (err) {
      console.error(err);
    }
  }

  async function manualCheckin(code) {
    document.getElementById('scanInput').value = code;
    handleScanSubmit(new Event('submit'));
  }

  async function undoCheckin(id) {
    const ok = await window.velzonConfirm({
      title: 'Batalkan Status Hadir?',
      message: 'Apakah Anda yakin ingin membatalkan status hadir delegasi ini?',
      type: 'warning',
      confirmText: 'Ya, Batalkan Hadir',
      icon: 'ri-user-unfollow-line'
    });
    if (!ok) return;
    try {
      const res = await fetch(`/api/operasional/gate/undo/${id}`, { method: 'POST' });
      const json = await res.json();
      if (json.success) {
        loadDelegates();
        loadOverview();
      }
    } catch (e) { alert(e.message); }
  }

  // ========================================================================
  // 3. FILE VERIFICATION LOGIC
  // ========================================================================
  async function loadVerificationTable() {
    try {
      const res = await fetch('/api/operasional/delegates');
      const json = await res.json();
      const delegates = json.delegates || [];

      const tbody = document.getElementById('verificationTableTbody');
      tbody.innerHTML = delegates.map(d => `
        <tr>
          <td class="font-monospace text-success fw-bold">${d.reg_code}</td>
          <td class="fw-bold">${d.school_name}</td>
          <td><span class="badge bg-dark border border-secondary">${d.competition_title || d.competition_id}</span></td>
          <td class="fs-12" style="max-width: 200px; white-space: pre-line;">${d.participants_names || '-'}</td>
          <td class="fw-bold">Rp ${(d.total_fee || 50000).toLocaleString('id-ID')}</td>
          <td>
            <span class="badge ${d.payment_status === 'Terverifikasi' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle'}">
              ${d.payment_status}
            </span>
          </td>
          <td>
            <input type="text" id="verNotes_${d.id}" class="form-control form-control-sm bg-dark text-white border-secondary" value="${d.verification_notes || ''}" placeholder="Catatan berkas...">
          </td>
          <td>
            <div class="btn-group btn-group-sm">
              <button class="btn btn-sm btn-success" onclick="saveVerification(${d.id}, 'Terverifikasi')"><i class="ri-check-line"></i> Sah</button>
              <button class="btn btn-sm btn-warning" onclick="saveVerification(${d.id}, 'Menunggu Verifikasi')">Pending</button>
            </div>
          </td>
        </tr>
      `).join('');
    } catch(e) { console.error(e); }
  }

  async function saveVerification(id, status) {
    const notes = document.getElementById(`verNotes_${id}`)?.value || '';
    try {
      const res = await fetch(`/api/operasional/verification/${id}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status, notes })
      });
      const json = await res.json();
      alert(json.message);
      loadVerificationTable();
    } catch(e) { alert(e.message); }
  }

  let currentJuryDelegates = [];
  let currentJuryScores = [];

  // ========================================================================
  // 4. DIGITAL JURY SCORING & RUBRICS (DUAL MODE: SKOR & PERSENTASE)
  // ========================================================================
  async function loadJuryArena() {
    const compId = document.getElementById('juryCompetitionSelect').value;
    try {
      const res = await fetch(`/api/operasional/jury/${compId}`);
      const json = await res.json();
      if (json.status !== 'success') return;

      const data = json.data;
      currentJuryRubric = data.criteria || {};
      currentJuryRubricType = data.rubric_type || 'percentage';
      currentJuryDelegates = data.delegates || [];
      currentJuryScores = data.scores || [];

      // Update Scoring Mode Toggle & Indicator UI
      updateScoringModeUI();

      // Populate Participant Select
      const sel = document.getElementById('juryParticipantSelect');
      sel.innerHTML = `<option value="">-- Pilih Peserta (${currentJuryDelegates.length} Terdaftar) --</option>` +
        currentJuryDelegates.map(d => `<option value="${d.id}">${d.school_name} (${d.checkin_status === 'hadir' ? '✅ Hadir di Gate' : '⏳ Belum Hadir'})</option>`).join('');

      // Hide spotlight initially until selected
      const spot = document.getElementById('participantSpotlight');
      if (spot) spot.classList.add('d-none');

      // Render Rubric Criteria Inputs based on active mode
      renderJuryRubricInputs();

      // Render Leaderboard
      renderLeaderboardTable(currentJuryScores, currentJuryDelegates);
    } catch(e) { console.error('Error loadJuryArena:', e); }
  }

  function renderLeaderboardTable(scores, delegates) {
    const tbody = document.getElementById('juryLeaderboardTbody');
    const badge = document.getElementById('scoreCountBadge');
    if (badge) badge.textContent = `${scores.length} Nilai`;

    if (!scores || scores.length === 0) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4" style="color: #cbd5e1;">Belum ada skor tersimpan untuk cabang lomba ini.</td></tr>`;
      return;
    }

    const sorted = [...scores].sort((a,b) => b.total_score - a.total_score);
    tbody.innerHTML = sorted.map((s, idx) => {
      const del = delegates.find(d => d.id === s.registration_id) || { school_name: 'Delegasi #' + s.registration_id, participants_names: '' };
      let podiumClass = '';
      let rankBadge = `<span class="fw-bold" style="color: #cbd5e1;">${idx + 1}</span>`;

      if (idx === 0) {
        podiumClass = 'podium-gold';
        rankBadge = `<span class="badge bg-warning text-dark fw-bold px-2 py-1">🥇 1</span>`;
      } else if (idx === 1) {
        podiumClass = 'podium-silver';
        rankBadge = `<span class="badge bg-secondary text-white fw-bold px-2 py-1">🥈 2</span>`;
      } else if (idx === 2) {
        podiumClass = 'podium-bronze';
        rankBadge = `<span class="badge text-white fw-bold px-2 py-1" style="background: #b45309;">🥉 3</span>`;
      }

      return `
        <tr class="${podiumClass}">
          <td class="text-center">${rankBadge}</td>
          <td>
            <div class="fw-bold text-white fs-13">${escapeHtml(del.school_name)}</div>
            ${del.participants_names ? `<small class="d-block text-truncate" style="color: #94a3b8; max-width: 170px;" title="${escapeHtml(del.participants_names)}">${escapeHtml(del.participants_names)}</small>` : ''}
          </td>
          <td class="text-center font-monospace fs-12 text-warning">${s.performance_duration || '-'}</td>
          <td class="text-end fw-black text-warning fs-14 font-monospace">${parseFloat(s.total_score).toFixed(2)}</td>
        </tr>
      `;
    }).join('');
  }

  function filterLeaderboardTable() {
    const q = (document.getElementById('searchLeaderboard')?.value || '').toLowerCase().trim();
    if (!q) {
      renderLeaderboardTable(currentJuryScores, currentJuryDelegates);
      return;
    }
    const filtered = currentJuryScores.filter(s => {
      const del = currentJuryDelegates.find(d => d.id === s.registration_id);
      if (!del) return false;
      return (del.school_name && del.school_name.toLowerCase().includes(q)) ||
             (del.participants_names && del.participants_names.toLowerCase().includes(q));
    });
    renderLeaderboardTable(filtered, currentJuryDelegates);
  }

  function updateScoringModeUI() {
    const isPoint = (currentJuryRubricType === 'point');
    const badgeEl = document.getElementById('tabActiveModeBadge');
    const badgeText = document.getElementById('tabActiveModeBadgeText');
    const titleEl = document.getElementById('scoringModeTitle');
    const descEl = document.getElementById('scoringModeDesc');
    const scaleHint = document.getElementById('rubricScaleHint');
    const cardTitle = document.getElementById('finalScoreCardTitle');
    const cardSub = document.getElementById('finalScoreCardSubtitle');
    const btnPct = document.getElementById('btnModePercentage');
    const btnPt = document.getElementById('btnModePoint');

    if (btnPct && btnPt) {
      if (isPoint) {
        btnPct.classList.remove('active');
        btnPt.classList.add('active');
      } else {
        btnPct.classList.add('active');
        btnPt.classList.remove('active');
      }
    }

    if (isPoint) {
      if (badgeText) badgeText.textContent = 'Mode: Skor (Poin Mutlak)';
      if (badgeEl) {
        badgeEl.className = 'badge-mode-pill badge-mode-point';
        badgeEl.querySelector('i').className = 'ri-numbers-line';
      }
      if (titleEl) titleEl.innerHTML = `<i class="ri-numbers-line text-info me-1"></i> Mode Nilai Skor / Poin Maksimal`;
      if (descEl) descEl.innerHTML = `<i class="ri-information-line text-info me-1"></i> <strong>Mode Skor (Poin):</strong> Tiap kriteria memiliki batas skor maksimal tertentu. Nilai akhir dihitung dari akumulasi total poin mutlak.`;
      if (scaleHint) scaleHint.textContent = 'Input nilai: 0 s/d Skor Maksimal tiap aspek';
      if (cardTitle) cardTitle.textContent = 'Total Akumulasi Skor / Poin';
      if (cardSub) cardSub.textContent = 'Penjumlahan langsung skor seluruh kriteria';
    } else {
      if (badgeText) badgeText.textContent = 'Mode: Persentase (%)';
      if (badgeEl) {
        badgeEl.className = 'badge-mode-pill badge-mode-percentage';
        badgeEl.querySelector('i').className = 'ri-percent-line';
      }
      if (titleEl) titleEl.innerHTML = `<i class="ri-percent-line text-warning me-1"></i> Mode Nilai Persentase (%)`;
      if (descEl) descEl.innerHTML = `<i class="ri-information-line text-warning me-1"></i> <strong>Mode Persentase (%):</strong> Tiap kriteria dinilai skala 0 - 100, nilai akhir dihitung secara proporsional sesuai bobot persentase.`;
      if (scaleHint) scaleHint.textContent = 'Skala nilai: 0 - 100 per kriteria (bobot %)';
      if (cardTitle) cardTitle.textContent = 'Total Nilai Terkalkulasi';
      if (cardSub) cardSub.textContent = 'Bobot persentase proporsional otomatis';
    }
  }

  function changeActiveScoringMode(mode) {
    if (currentJuryRubricType === mode) return;
    currentJuryRubricType = mode;
    updateScoringModeUI();
    renderJuryRubricInputs();
  }

  function renderJuryRubricInputs() {
    const cont = document.getElementById('rubricInputsContainer');
    const isPoint = (currentJuryRubricType === 'point');

    cont.innerHTML = Object.entries(currentJuryRubric).map(([k, rawWeight], idx) => {
      const weightVal = parseFloat(rawWeight) || 0;
      if (isPoint) {
        const maxPoint = weightVal > 0 ? weightVal : 100;
        const initialVal = Math.round(maxPoint * 0.8);
        return `
          <div class="rubric-item p-3 rounded" style="background: rgba(15, 23, 42, 0.8); border: 1.5px solid rgba(255,255,255,0.08); border-radius: 10px;">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fs-13 fw-bold text-white">${escapeHtml(k)}</span>
              <span class="badge-rubric-maks">
                <i class="ri-numbers-line me-1"></i>Maks: ${maxPoint} Poin
              </span>
            </div>
            <div class="d-flex align-items-center gap-3">
              <input type="range" class="form-range flex-grow-1" min="0" max="${maxPoint}" step="1" value="${initialVal}" id="range_${idx}" oninput="syncScoreInput(${idx}, this.value)">
              <div class="input-group input-group-sm" style="width: 110px;">
                <input type="number" class="form-control form-control-sm bg-black text-info text-center fw-bold border-secondary rubric-score-val" 
                  min="0" max="${maxPoint}" step="0.5" value="${initialVal}" id="num_${idx}" data-criteria="${escapeHtml(k)}" data-weight="${maxPoint}" oninput="syncScoreRange(${idx}, this.value)">
                <span class="input-group-text bg-dark border-secondary fs-11" style="color: #cbd5e1;">/${maxPoint}</span>
              </div>
            </div>
            
            <!-- Quick Preset Chips & Steppers -->
            <div class="d-flex flex-wrap align-items-center gap-1 mt-2 pt-2 border-top border-secondary border-opacity-25">
              <span class="fs-10 fw-bold text-uppercase me-1" style="color: #cbd5e1;">Pintasan:</span>
              <button type="button" class="score-chip" onclick="setCriterionPreset(${idx}, ${maxPoint})">⭐ Maks</button>
              <button type="button" class="score-chip" onclick="setCriterionPreset(${idx}, ${Math.round(maxPoint * 0.9)})">90%</button>
              <button type="button" class="score-chip" onclick="setCriterionPreset(${idx}, ${Math.round(maxPoint * 0.8)})">80%</button>
              <button type="button" class="score-chip" onclick="setCriterionPreset(${idx}, ${Math.round(maxPoint * 0.7)})">70%</button>
              <button type="button" class="score-chip" onclick="setCriterionPreset(${idx}, ${Math.round(maxPoint * 0.6)})">60%</button>
              <div class="ms-auto btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary py-0 px-2 fs-10 text-white" onclick="adjustCriterionScore(${idx}, -5)" title="-5 poin">-5</button>
                <button type="button" class="btn btn-outline-secondary py-0 px-2 fs-10 text-white" onclick="adjustCriterionScore(${idx}, -1)" title="-1 poin">-1</button>
                <button type="button" class="btn btn-outline-secondary py-0 px-2 fs-10 text-white" onclick="adjustCriterionScore(${idx}, 1)" title="+1 poin">+1</button>
                <button type="button" class="btn btn-outline-secondary py-0 px-2 fs-10 text-white" onclick="adjustCriterionScore(${idx}, 5)" title="+5 poin">+5</button>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2">
              <small class="fs-11 fw-medium" style="color: #cbd5e1;" id="subLabel_${idx}">Poin diperoleh: <strong class="text-info">${initialVal}</strong> / ${maxPoint}</small>
              <small class="fs-10" style="color: #94a3b8;">Pencapaian: ${((initialVal / maxPoint) * 100).toFixed(0)}%</small>
            </div>
          </div>
        `;
      } else {
        const weightPct = weightVal > 0 ? weightVal : 10;
        const initialVal = 80;
        const contrib = (initialVal * weightPct / 100).toFixed(2);
        return `
          <div class="rubric-item p-3 rounded" style="background: rgba(15, 23, 42, 0.8); border: 1.5px solid rgba(255,255,255,0.08); border-radius: 10px;">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fs-13 fw-bold text-white">${escapeHtml(k)}</span>
              <span class="badge-rubric-bobot">
                <i class="ri-percent-line me-1"></i>Bobot: ${weightPct}%
              </span>
            </div>
            <div class="d-flex align-items-center gap-3">
              <input type="range" class="form-range flex-grow-1" min="0" max="100" step="0.5" value="${initialVal}" id="range_${idx}" oninput="syncScoreInput(${idx}, this.value)">
              <div class="input-group input-group-sm" style="width: 105px;">
                <input type="number" class="form-control form-control-sm bg-black text-warning text-center fw-bold border-secondary rubric-score-val" 
                  min="0" max="100" step="0.5" value="${initialVal}" id="num_${idx}" data-criteria="${escapeHtml(k)}" data-weight="${weightPct}" oninput="syncScoreRange(${idx}, this.value)">
                <span class="input-group-text bg-dark border-secondary fs-11" style="color: #cbd5e1;">/100</span>
              </div>
            </div>

            <!-- Quick Preset Chips & Steppers -->
            <div class="d-flex flex-wrap align-items-center gap-1 mt-2 pt-2 border-top border-secondary border-opacity-25">
              <span class="fs-10 fw-bold text-uppercase me-1" style="color: #cbd5e1;">Pintasan:</span>
              <button type="button" class="score-chip" onclick="setCriterionPreset(${idx}, 100)">⭐ 100</button>
              <button type="button" class="score-chip" onclick="setCriterionPreset(${idx}, 95)">95</button>
              <button type="button" class="score-chip" onclick="setCriterionPreset(${idx}, 90)">90</button>
              <button type="button" class="score-chip" onclick="setCriterionPreset(${idx}, 85)">85</button>
              <button type="button" class="score-chip" onclick="setCriterionPreset(${idx}, 80)">80</button>
              <button type="button" class="score-chip" onclick="setCriterionPreset(${idx}, 75)">75</button>
              <button type="button" class="score-chip" onclick="setCriterionPreset(${idx}, 70)">70</button>
              <div class="ms-auto btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary py-0 px-2 fs-10 text-white" onclick="adjustCriterionScore(${idx}, -5)" title="-5 poin">-5</button>
                <button type="button" class="btn btn-outline-secondary py-0 px-2 fs-10 text-white" onclick="adjustCriterionScore(${idx}, -1)" title="-1 poin">-1</button>
                <button type="button" class="btn btn-outline-secondary py-0 px-2 fs-10 text-white" onclick="adjustCriterionScore(${idx}, 1)" title="+1 poin">+1</button>
                <button type="button" class="btn btn-outline-secondary py-0 px-2 fs-10 text-white" onclick="adjustCriterionScore(${idx}, 5)" title="+5 poin">+5</button>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2">
              <small class="fs-11 fw-medium" style="color: #cbd5e1;" id="subLabel_${idx}">Kontribusi Skor: <strong class="text-warning">${contrib}</strong> / ${weightPct}</small>
              <small class="fs-10" style="color: #94a3b8;">Nilai Mentah: ${initialVal}/100</small>
            </div>
          </div>
        `;
      }
    }).join('');

    calcTotalScore();
  }

  function setCriterionPreset(idx, val) {
    const rangeEl = document.getElementById(`range_${idx}`);
    const numEl = document.getElementById(`num_${idx}`);
    if (rangeEl) rangeEl.value = val;
    if (numEl) numEl.value = val;
    updateCriterionSubLabel(idx, val);
    calcTotalScore();
  }

  function adjustCriterionScore(idx, delta) {
    const numEl = document.getElementById(`num_${idx}`);
    const rangeEl = document.getElementById(`range_${idx}`);
    if (!numEl) return;
    const max = parseFloat(numEl.getAttribute('max')) || 100;
    const min = parseFloat(numEl.getAttribute('min')) || 0;
    let cur = parseFloat(numEl.value) || 0;
    cur = Math.min(max, Math.max(min, cur + delta));
    numEl.value = cur;
    if (rangeEl) rangeEl.value = cur;
    updateCriterionSubLabel(idx, cur);
    calcTotalScore();
  }

  function syncScoreInput(idx, val) {
    const numEl = document.getElementById(`num_${idx}`);
    if (numEl) {
      numEl.value = val;
      updateCriterionSubLabel(idx, val);
    }
    calcTotalScore();
  }

  function syncScoreRange(idx, val) {
    const rangeEl = document.getElementById(`range_${idx}`);
    if (rangeEl) {
      rangeEl.value = val;
      updateCriterionSubLabel(idx, val);
    }
    calcTotalScore();
  }

  function updateCriterionSubLabel(idx, val) {
    const numEl = document.getElementById(`num_${idx}`);
    const subEl = document.getElementById(`subLabel_${idx}`);
    if (!numEl || !subEl) return;
    const weight = parseFloat(numEl.getAttribute('data-weight')) || 0;
    const scoreVal = parseFloat(val) || 0;
    const isPoint = (currentJuryRubricType === 'point');

    if (isPoint) {
      subEl.innerHTML = `Poin diperoleh: <strong class="text-info">${scoreVal}</strong> / ${weight}`;
    } else {
      const contrib = (scoreVal * weight / 100).toFixed(2);
      subEl.innerHTML = `Kontribusi Skor: <strong class="text-warning">${contrib}</strong> / ${weight}`;
    }
  }

  function calcTotalScore() {
    let total = 0;
    let maxTotal = 0;
    const inputs = document.querySelectorAll('.rubric-score-val');
    const isPoint = (currentJuryRubricType === 'point');

    inputs.forEach(inp => {
      const score = parseFloat(inp.value) || 0;
      const weight = parseFloat(inp.getAttribute('data-weight')) || 0;
      if (isPoint) {
        total += score;
        maxTotal += weight;
      } else {
        total += (score * weight / 100);
      }
    });

    const scoreDisplayEl = document.getElementById('finalTotalScore');
    const denomEl = document.getElementById('finalTotalDenominator');
    const progEl = document.getElementById('finalScoreProgress');
    const gradeEl = document.getElementById('performanceGradeBadge');

    if (scoreDisplayEl) scoreDisplayEl.textContent = total.toFixed(2);

    let pct = 0;
    if (isPoint) {
      pct = maxTotal > 0 ? ((total / maxTotal) * 100) : 0;
      if (denomEl) denomEl.textContent = `/ ${maxTotal} Poin (${pct.toFixed(1)}%)`;
      if (progEl) {
        progEl.className = 'progress-bar bg-info';
        progEl.style.width = Math.min(100, Math.max(0, pct)) + '%';
      }
    } else {
      pct = Math.min(100, Math.max(0, total));
      if (denomEl) denomEl.textContent = '/ 100';
      if (progEl) {
        progEl.className = 'progress-bar bg-warning';
        progEl.style.width = pct + '%';
      }
    }

    // Dynamic Performance Grade
    if (gradeEl) {
      if (total === 0) {
        gradeEl.className = 'badge bg-secondary text-white fs-11 fw-bold mt-1 px-2 py-1 shadow-sm';
        gradeEl.innerHTML = '✨ Menunggu Input Nilai';
      } else if (pct >= 90) {
        gradeEl.className = 'badge bg-success text-white fs-11 fw-bold mt-1 px-2 py-1 shadow-sm';
        gradeEl.innerHTML = '🌟 Grade A+ • Istimewa / Sangat Memuaskan';
      } else if (pct >= 80) {
        gradeEl.className = 'badge bg-warning text-dark fs-11 fw-bold mt-1 px-2 py-1 shadow-sm';
        gradeEl.innerHTML = '✨ Grade A • Memuaskan';
      } else if (pct >= 70) {
        gradeEl.className = 'badge bg-info text-dark fs-11 fw-bold mt-1 px-2 py-1 shadow-sm';
        gradeEl.innerHTML = '👍 Grade B • Baik';
      } else if (pct >= 60) {
        gradeEl.className = 'badge bg-secondary text-white fs-11 fw-bold mt-1 px-2 py-1 shadow-sm';
        gradeEl.innerHTML = '⚠️ Grade C • Cukup';
      } else {
        gradeEl.className = 'badge bg-danger text-white fs-11 fw-bold mt-1 px-2 py-1 shadow-sm';
        gradeEl.innerHTML = '🛑 Grade D • Perlu Peningkatan';
      }
    }
  }

  function onSelectJuryParticipant() {
    resetStopwatch();
    const sel = document.getElementById('juryParticipantSelect');
    const regId = sel ? sel.value : '';
    const spot = document.getElementById('participantSpotlight');
    if (!spot) return;

    if (!regId) {
      spot.classList.add('d-none');
      return;
    }

    const del = currentJuryDelegates.find(d => String(d.id) === String(regId));
    if (!del) {
      spot.classList.add('d-none');
      return;
    }

    spot.innerHTML = `
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning text-dark fw-bold fs-22" style="width: 50px; height: 50px; box-shadow: 0 0 16px rgba(245, 158, 11, 0.45); flex-shrink: 0;">
            <i class="ri-user-star-line"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2">
              <h5 class="fw-bold text-white mb-0 fs-16">${escapeHtml(del.school_name)}</h5>
              <span class="badge bg-dark border border-warning text-warning fs-11 font-monospace">${escapeHtml(del.barcode || del.reg_code)}</span>
            </div>
            <div class="fs-12 text-white mt-1">
              <i class="ri-team-line text-warning me-1"></i> Peserta: <strong>${escapeHtml(del.participants_names || '-')}</strong>
            </div>
            <div class="fs-11 text-light mt-0">
              <i class="ri-user-voice-line text-info me-1"></i> Pembina: ${escapeHtml(del.mentor_name || '-')} • ${escapeHtml(del.mentor_wa || '-')}
            </div>
          </div>
        </div>
        <div class="d-flex flex-column align-items-end gap-2">
          <div>
            ${del.checkin_status === 'hadir' 
              ? `<span class="badge bg-success text-white border border-success px-2 py-1 fs-12 fw-bold"><i class="ri-checkbox-circle-fill me-1"></i> Hadir di Gate (${del.checkin_at ? del.checkin_at.slice(11, 16) : ''} WIB)</span>`
              : `<span class="badge bg-warning text-dark border border-warning px-2 py-1 fs-12 fw-bold"><i class="ri-time-line me-1"></i> Belum Check-in Gate</span>`}
          </div>
          <button type="button" class="btn btn-warning btn-sm text-dark fw-bold px-3 py-1 shadow-sm d-flex align-items-center gap-1" onclick="startPerformanceSession()">
            <i class="ri-play-circle-line fs-16"></i> Mulai Tampil & Start Timer
          </button>
        </div>
      </div>
    `;
    spot.classList.remove('d-none');
  }

  function startPerformanceSession() {
    resetStopwatch();
    toggleStopwatch();
    playAudioBeep(true);
  }

  // Stopwatch Logic
  function toggleStopwatch() {
    const btn = document.getElementById('btnStopwatch');
    const badge = document.getElementById('stopwatchStatusBadge');
    if (stopwatchInterval) {
      clearInterval(stopwatchInterval);
      stopwatchInterval = null;
      if (btn) {
        btn.innerHTML = '<i class="ri-play-fill me-1"></i> Resume';
        btn.className = 'btn btn-success fw-bold px-2 py-1';
      }
      if (badge) {
        badge.textContent = 'PAUSED';
        badge.className = 'badge bg-warning text-dark fs-9 py-0 px-1';
      }
    } else {
      if (btn) {
        btn.innerHTML = '<i class="ri-pause-fill me-1"></i> Pause';
        btn.className = 'btn btn-warning text-dark fw-bold px-2 py-1';
      }
      if (badge) {
        badge.textContent = 'PERFORMING';
        badge.className = 'badge bg-danger text-white fs-9 py-0 px-1';
      }
      stopwatchInterval = setInterval(() => {
        stopwatchSeconds++;
        const mins = String(Math.floor(stopwatchSeconds / 60)).padStart(2, '0');
        const secs = String(stopwatchSeconds % 60).padStart(2, '0');
        document.getElementById('stopwatchDisplay').textContent = `${mins}:${secs}`;
      }, 1000);
    }
  }

  function resetStopwatch() {
    clearInterval(stopwatchInterval);
    stopwatchInterval = null;
    stopwatchSeconds = 0;
    const disp = document.getElementById('stopwatchDisplay');
    if (disp) disp.textContent = '00:00';
    const btn = document.getElementById('btnStopwatch');
    if (btn) {
      btn.innerHTML = '<i class="ri-play-fill me-1"></i> Start';
      btn.className = 'btn btn-success fw-bold px-2 py-1';
    }
    const badge = document.getElementById('stopwatchStatusBadge');
    if (badge) {
      badge.textContent = 'STANDBY';
      badge.className = 'badge bg-secondary text-white fs-9 py-0 px-1';
    }
  }

  async function handleJuryScoreSubmit(e) {
    e.preventDefault();
    const regId = document.getElementById('juryParticipantSelect').value;
    if (!regId) { alert('Silakan pilih peserta terlebih dahulu!'); return; }

    const compId = document.getElementById('juryCompetitionSelect').value;
    const juryName = document.getElementById('juryNameInput').value;
    const notes = document.getElementById('juryNotes').value;
    const duration = document.getElementById('stopwatchDisplay').textContent;
    const totalScore = parseFloat(document.getElementById('finalTotalScore').textContent) || 0;

    const criteriaScores = {};
    document.querySelectorAll('.rubric-score-val').forEach(inp => {
      criteriaScores[inp.getAttribute('data-criteria')] = parseFloat(inp.value) || 0;
    });

    try {
      const res = await fetch('/api/operasional/jury/score', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          registration_id: regId,
          competition_id: compId,
          jury_name: juryName,
          criteria_scores: criteriaScores,
          total_score: totalScore,
          performance_duration: duration,
          is_locked: 1,
          notes: notes
        })
      });
      const json = await res.json();
      if (json.success) {
        alert('✅ ' + json.message);
        loadJuryArena();
        loadOverview();
      }
    } catch(err) { alert('Gagal menyimpan nilai: ' + err.message); }
  }

  // ========================================================================
  // RUBRIC CUSTOMIZER MODAL CONTROLLER (CYBERNETIC DARK UI)
  // ========================================================================
  async function openRubricCustomizerModal() {
    modalCompetitionId = document.getElementById('juryCompetitionSelect').value;
    const statusText = document.getElementById('modalStatusText');
    if (statusText) statusText.textContent = '';

    try {
      const res = await fetch(`/api/operasional/jury/rubric/${modalCompetitionId}`);
      const json = await res.json();
      if (!json.success) {
        alert('Gagal memuat rubrik: ' + (json.message || 'Error'));
        return;
      }

      document.getElementById('rubricModalSubtitle').textContent = `Cabang Lomba: ${json.competition_title || modalCompetitionId}`;
      modalRubricType = json.rubric_type || 'percentage';

      // Set Radio
      if (modalRubricType === 'point') {
        document.getElementById('modalTypePoint').checked = true;
      } else {
        document.getElementById('modalTypePercentage').checked = true;
      }

      // Populate Criteria List
      modalCriteriaList = Object.entries(json.active_rubric || {}).map(([name, weight]) => ({
        name: name,
        weight: parseFloat(weight) || (modalRubricType === 'point' ? 100 : 10)
      }));

      if (modalCriteriaList.length === 0) {
        modalCriteriaList = [
          { name: 'Kriteria Penilaian 1', weight: modalRubricType === 'point' ? 100 : 50 },
          { name: 'Kriteria Penilaian 2', weight: modalRubricType === 'point' ? 100 : 50 }
        ];
      }

      updateModalModeHeaders();
      renderModalCriteriaTable();

      if (rubricModalInstance) {
        rubricModalInstance.show();
      }
    } catch (err) {
      alert('Terjadi kesalahan saat membuka kustomisasi rubrik: ' + err.message);
    }
  }

  function onModalModeChange(mode) {
    modalRubricType = mode;
    updateModalModeHeaders();
    renderModalCriteriaTable();
  }

  function updateModalModeHeaders() {
    const isPoint = (modalRubricType === 'point');
    const colHeader = document.getElementById('modalColWeightHeader');
    const helperText = document.getElementById('modalModeHelperText');

    if (isPoint) {
      if (colHeader) colHeader.textContent = 'Skor / Poin Maksimal';
      if (helperText) {
        helperText.innerHTML = `<i class="ri-information-line text-info me-1"></i> <strong>Mode Nilai Skor (Poin Mutlak):</strong> Tiap kriteria memiliki skor maksimal mutlak (cth: 100). Total nilai akhir adalah penjumlahan langsung akumulasi poin per kriteria.`;
      }
    } else {
      if (colHeader) colHeader.textContent = 'Bobot Nilai (%)';
      if (helperText) {
        helperText.innerHTML = `<i class="ri-information-line text-warning me-1"></i> <strong>Mode Nilai Persentase (%):</strong> Tiap kriteria bernilai persentase dengan total ideal 100%. Nilai akhir peserta dihitung secara proporsional.`;
      }
    }
  }

  function renderModalCriteriaTable() {
    const tbody = document.getElementById('modalCriteriaTableBody');
    const isPoint = (modalRubricType === 'point');

    tbody.innerHTML = modalCriteriaList.map((item, idx) => `
      <tr>
        <td class="text-center font-monospace" style="color: #cbd5e1;">${idx + 1}</td>
        <td>
          <input type="text" class="form-control form-control-sm text-white border-secondary rubric-modal-name" 
            style="background: #0b1120;" value="${escapeHtml(item.name)}" placeholder="Nama Aspek / Kriteria" oninput="updateModalCriteriaItem(${idx}, 'name', this.value)">
        </td>
        <td>
          <div class="input-group input-group-sm">
            <input type="number" class="form-control form-control-sm ${isPoint ? 'text-info' : 'text-warning'} text-center fw-bold border-secondary rubric-modal-weight" 
              style="background: #0b1120;" value="${item.weight}" min="1" max="1000" step="${isPoint ? '5' : '1'}" 
              oninput="updateModalCriteriaItem(${idx}, 'weight', this.value)">
            <span class="input-group-text bg-dark border-secondary fs-11" style="color: #cbd5e1;">
              ${isPoint ? 'Poin' : '%'}
            </span>
          </div>
        </td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger p-1 py-0" onclick="removeModalCriteriaRow(${idx})" title="Hapus Kriteria" ${modalCriteriaList.length <= 1 ? 'disabled' : ''}>
            <i class="ri-delete-bin-line"></i>
          </button>
        </td>
      </tr>
    `).join('');

    calcModalTotal();
  }

  function updateModalCriteriaItem(idx, field, val) {
    if (!modalCriteriaList[idx]) return;
    if (field === 'weight') {
      modalCriteriaList[idx].weight = parseFloat(val) || 0;
      calcModalTotal();
    } else {
      modalCriteriaList[idx].name = val;
    }
  }

  function calcModalTotal() {
    const isPoint = (modalRubricType === 'point');
    const sum = modalCriteriaList.reduce((acc, cur) => acc + (parseFloat(cur.weight) || 0), 0);
    const sumRounded = Math.round(sum * 100) / 100;

    const labelEl = document.getElementById('modalCalcSummaryLabel');
    const badgeEl = document.getElementById('modalTotalBadge');
    const progEl = document.getElementById('modalTotalProgress');

    if (isPoint) {
      if (labelEl) labelEl.innerHTML = `<i class="ri-scales-3-line text-info me-1"></i> Total Skor / Poin Maksimal:`;
      if (badgeEl) {
        badgeEl.className = 'badge bg-info text-dark fs-12 px-3 py-1';
        badgeEl.textContent = `${sumRounded} Poin Maksimal`;
      }
      if (progEl) {
        progEl.className = 'progress-bar bg-info';
        progEl.style.width = '100%';
      }
    } else {
      if (labelEl) labelEl.innerHTML = `<i class="ri-scales-3-line text-warning me-1"></i> Total Akumulasi Bobot (%):`;
      if (sumRounded === 100) {
        if (badgeEl) {
          badgeEl.className = 'badge bg-success fs-12 px-3 py-1';
          badgeEl.textContent = '100% (Sempurna & Ideal)';
        }
        if (progEl) {
          progEl.className = 'progress-bar bg-success';
          progEl.style.width = '100%';
        }
      } else if (sumRounded < 100) {
        if (badgeEl) {
          badgeEl.className = 'badge bg-warning text-dark fs-12 px-3 py-1';
          badgeEl.textContent = `${sumRounded}% (Kurang ${100 - sumRounded}%)`;
        }
        if (progEl) {
          progEl.className = 'progress-bar bg-warning';
          progEl.style.width = Math.min(100, sumRounded) + '%';
        }
      } else {
        if (badgeEl) {
          badgeEl.className = 'badge bg-danger fs-12 px-3 py-1';
          badgeEl.textContent = `${sumRounded}% (Lebih ${sumRounded - 100}%)`;
        }
        if (progEl) {
          progEl.className = 'progress-bar bg-danger';
          progEl.style.width = '100%';
        }
      }
    }
  }

  function addModalCriteriaRow() {
    const isPoint = (modalRubricType === 'point');
    modalCriteriaList.push({
      name: `Kriteria Baru ${modalCriteriaList.length + 1}`,
      weight: isPoint ? 100 : 10
    });
    renderModalCriteriaTable();
  }

  function removeModalCriteriaRow(idx) {
    if (modalCriteriaList.length <= 1) {
      alert('Minimal harus ada 1 kriteria penilaian!');
      return;
    }
    modalCriteriaList.splice(idx, 1);
    renderModalCriteriaTable();
  }

  async function resetModalToJuknis() {
    const ok = await window.velzonConfirm({
      title: 'Reset Rubrik Penilaian?',
      message: 'Kembalikan rubrik cabang ini ke standar resmi Juknis Semarak Aliyah XII?',
      type: 'warning',
      confirmText: 'Ya, Reset Rubrik',
      icon: 'ri-refresh-line'
    });
    if (!ok) return;

    try {
      const res = await fetch(`/api/operasional/jury/rubric/${modalCompetitionId}/reset`, { method: 'POST' });
      const json = await res.json();
      if (json.success) {
        modalRubricType = 'percentage';
        document.getElementById('modalTypePercentage').checked = true;

        modalCriteriaList = Object.entries(json.criteria || {}).map(([name, weight]) => ({
          name: name,
          weight: parseFloat(weight) || 10
        }));

        updateModalModeHeaders();
        renderModalCriteriaTable();

        const st = document.getElementById('modalStatusText');
        if (st) st.innerHTML = `<span class="text-success"><i class="ri-check-line"></i> Berhasil direset ke standar resmi Juknis!</span>`;
      }
    } catch(err) {
      alert('Gagal mereset rubrik: ' + err.message);
    }
  }

  async function handleRubricSave(e) {
    e.preventDefault();

    // Validate empty names
    for (let i = 0; i < modalCriteriaList.length; i++) {
      if (!modalCriteriaList[i].name || modalCriteriaList[i].name.trim() === '') {
        alert(`Nama kriteria baris ke-${i + 1} tidak boleh kosong!`);
        return;
      }
      if (modalCriteriaList[i].weight <= 0) {
        alert(`Bobot/Poin kriteria baris ke-${i + 1} harus lebih dari 0!`);
        return;
      }
    }

    // Check sum in percentage mode
    if (modalRubricType === 'percentage') {
      const sum = modalCriteriaList.reduce((acc, cur) => acc + (parseFloat(cur.weight) || 0), 0);
      if (Math.round(sum) !== 100) {
        const proceed = await window.velzonConfirm({
          title: 'Peringatan Bobot Penilaian',
          message: `Total bobot persentase adalah ${sum}%, bukan 100%. Apakah Anda yakin ingin tetap menyimpan perubahan?`,
          type: 'warning',
          confirmText: 'Tetap Simpan',
          icon: 'ri-scales-3-line'
        });
        if (!proceed) return;
      }
    }

    const saveBtn = document.getElementById('modalBtnSave');
    saveBtn.disabled = true;
    saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...`;

    try {
      const res = await fetch(`/api/operasional/jury/rubric/${modalCompetitionId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          rubric_type: modalRubricType,
          criteria_names: modalCriteriaList.map(c => c.name.trim()),
          criteria_weights: modalCriteriaList.map(c => c.weight)
        })
      });
      const json = await res.json();
      if (json.success) {
        if (rubricModalInstance) rubricModalInstance.hide();
        await loadJuryArena();
        alert('✅ ' + json.message);
      } else {
        alert('Gagal menyimpan: ' + (json.message || 'Error'));
      }
    } catch (err) {
      alert('Terjadi kesalahan: ' + err.message);
    } finally {
      saveBtn.disabled = false;
      saveBtn.innerHTML = `<i class="ri-save-line me-1"></i> Simpan Kustomisasi Rubrik`;
    }
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // ========================================================================
  // 5. TOURNAMENT BRACKET SYSTEM
  // ========================================================================
  async function loadTournamentBracket() {
    const compId = document.getElementById('tournamentCompSelect').value;
    try {
      const res = await fetch(`/api/operasional/tournament/${compId}`);
      const json = await res.json();
      if (json.status !== 'success') return;

      currentTournamentMatches = json.data.matches || [];
      renderBracketTree(currentTournamentMatches);
    } catch(e) { console.error(e); }
  }

  function renderBracketTree(matches) {
    const cont = document.getElementById('bracketTreeContainer');
    if (matches.length === 0) {
      cont.innerHTML = `<div class="text-center py-5 w-100 text-muted">Belum ada bagan pertandingan. Klik tombol Acak Ulang Bagan.</div>`;
      return;
    }

    // Group by round_order
    const rounds = {
      1: { name: 'Perempat Final (QF)', matches: [] },
      2: { name: 'Semi Final (SF)', matches: [] },
      3: { name: 'Grand Final', matches: [] }
    };

    matches.forEach(m => {
      if (rounds[m.round_order]) rounds[m.round_order].matches.push(m);
    });

    cont.innerHTML = Object.entries(rounds).map(([order, r]) => `
      <div class="bracket-round">
        <div class="bracket-round-title">${r.name}</div>
        ${r.matches.map(m => {
          const t1Win = m.winner_registration_id && m.winner_registration_id === m.team1_registration_id;
          const t2Win = m.winner_registration_id && m.winner_registration_id === m.team2_registration_id;
          return `
            <div class="bracket-match" onclick="openMatchScoreModal(${m.id})">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="badge bg-dark text-warning fs-10 font-monospace">${m.match_number || 'MATCH'}</span>
                <span class="fs-10 text-muted">${m.court || 'Lapangan'} • ${m.status}</span>
              </div>
              <div class="match-team ${t1Win ? 'winner' : ''}">
                <span class="text-truncate" style="max-width: 180px;">${m.team1_name || m.team1_placeholder || 'TBD'}</span>
                <span class="team-score">${m.team1_score}</span>
              </div>
              <div class="match-team ${t2Win ? 'winner' : ''}">
                <span class="text-truncate" style="max-width: 180px;">${m.team2_name || m.team2_placeholder || 'TBD'}</span>
                <span class="team-score">${m.team2_score}</span>
              </div>
            </div>
          `;
        }).join('')}
      </div>
    `).join('');
  }

  function openMatchScoreModal(matchId) {
    const m = currentTournamentMatches.find(x => x.id === matchId);
    if (!m) return;

    document.getElementById('modalMatchId').value = m.id;
    document.getElementById('matchModalTitle').textContent = `Skor Pertandingan ${m.match_number} (${m.round_name})`;
    document.getElementById('modalTeam1Name').textContent = m.team1_name || m.team1_placeholder || 'Tim 1';
    document.getElementById('modalTeam2Name').textContent = m.team2_name || m.team2_placeholder || 'Tim 2';
    document.getElementById('modalTeam1Score').value = m.team1_score || 0;
    document.getElementById('modalTeam2Score').value = m.team2_score || 0;
    document.getElementById('modalTeam1Sets').value = m.team1_set_scores || '';
    document.getElementById('modalTeam2Sets').value = m.team2_set_scores || '';
    document.getElementById('modalMatchStatus').value = m.status || 'scheduled';
    document.getElementById('modalMatchCourt').value = m.court || '';
    document.getElementById('modalMatchReferee').value = m.referee_name || '';

    matchModalInstance.show();
  }

  async function handleMatchScoreSubmit(e) {
    e.preventDefault();
    const matchId = document.getElementById('modalMatchId').value;
    const body = {
      team1_score: document.getElementById('modalTeam1Score').value,
      team2_score: document.getElementById('modalTeam2Score').value,
      team1_set_scores: document.getElementById('modalTeam1Sets').value,
      team2_set_scores: document.getElementById('modalTeam2Sets').value,
      status: document.getElementById('modalMatchStatus').value,
      court: document.getElementById('modalMatchCourt').value,
      referee_name: document.getElementById('modalMatchReferee').value
    };

    try {
      const res = await fetch(`/api/operasional/tournament/match/${matchId}/score`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      const json = await res.json();
      if (json.success) {
        matchModalInstance.hide();
        loadTournamentBracket();
      }
    } catch(err) { alert(err.message); }
  }

  async function regenerateBracketPrompt() {
    const compId = document.getElementById('tournamentCompSelect').value;
    const ok = await window.velzonConfirm({
      title: 'Buat Ulang Bagan Pertandingan?',
      message: 'Peringatan: Membuat ulang bagan akan mereset skor semua pertandingan cabang ini. Lanjutkan?',
      type: 'danger',
      confirmText: 'Ya, Buat Ulang Bagan',
      icon: 'ri-shuffle-line'
    });
    if (!ok) return;
    try {
      const res = await fetch(`/api/operasional/tournament/generate/${compId}`, { method: 'POST' });
      const json = await res.json();
      alert(json.message);
      loadTournamentBracket();
    } catch(e) { alert(e.message); }
  }

  // ========================================================================
  // 6. PRINT STUDIO ID CARDS
  // ========================================================================
  async function loadIdCards() {
    const comp = document.getElementById('filterPrintComp')?.value || '';
    try {
      const res = await fetch(`/api/operasional/id-cards?competition_id=${comp}`);
      const json = await res.json();
      const cards = json.cards || [];

      const cont = document.getElementById('idCardsContainer');
      if (cards.length === 0) {
        cont.innerHTML = `<div class="text-center py-5 w-100 text-muted">Belum ada delegasi untuk dicetak.</div>`;
        return;
      }

      cont.innerHTML = cards.map(c => `
        <div class="id-card-item">
          <div>
            <div class="id-card-badge">SEMARAK ALIYAH XII 2026</div>
            <h6 class="fw-bold mb-1 fs-14">${c.school_name}</h6>
            <div class="badge bg-primary text-white fs-10 mb-2">${c.competition_title || c.competition_id}</div>
          </div>

          <div class="my-2 p-2 rounded bg-light border">
            <small class="text-muted d-block fs-11 text-uppercase fw-bold">Delegasi Peserta:</small>
            <div class="fw-bold fs-12 text-dark" style="white-space: pre-line; max-height: 80px; overflow-y: hidden;">
              ${c.participants_names}
            </div>
          </div>

          <div>
            <!-- Visual Barcode Lines -->
            <div class="p-2 bg-light rounded border mb-1">
              <div style="font-family: 'Libre Barcode 39', monospace; font-size: 32px; letter-spacing: 5px; line-height: 1;">
                ||||| | |||| ||| | |||
              </div>
              <div class="font-monospace fw-bold fs-12 text-dark">${c.barcode || c.reg_code}</div>
            </div>
            <small class="text-muted fs-10 d-block">MA Unggulan Wahid Hasyim Balung</small>
          </div>
        </div>
      `).join('');
    } catch(e) { console.error(e); }
  }
</script>
</body>
</html>
