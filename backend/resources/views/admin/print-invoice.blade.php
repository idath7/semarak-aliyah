<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $defaultMode === 'receipt' ? 'Kwitansi Pembayaran' : 'Nota Invoice' }} - {{ $reg->reg_code }} | Semarak Aliyah XIV</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/assets/img/logo.png">

    <!-- Local Offline Fonts & Icons (Rule 5) -->
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/vendor/remixicon/remixicon.css">
    <link rel="stylesheet" href="/assets/vendor/boxicons/css/boxicons.min.css">
    
    <!-- Local Offline QR Code Generator (qrcode.min.js - Rule 5) -->
    <script src="/assets/js/qrcode.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #1e293b;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #0f172a;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Top Sticky Toolbar (Hidden on Print) */
        .no-print-toolbar {
            width: 100%;
            background-color: #0f172a;
            color: #ffffff;
            padding: 10px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 4px 14px rgba(0,0,0,0.3);
            gap: 12px;
            flex-wrap: wrap;
        }

        .toolbar-left, .toolbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .toolbar-btn {
            background-color: transparent;
            color: #f1f5f9;
            border: 1px solid #475569;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .toolbar-btn:hover {
            background-color: #1e293b;
            color: #ffffff;
            border-color: #64748b;
        }

        .toolbar-btn.btn-active {
            background-color: #2563eb;
            border-color: #3b82f6;
            color: #ffffff;
            font-weight: 600;
        }

        .toolbar-btn.btn-print {
            background-color: #10b981;
            border-color: #10b981;
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.35);
        }
        .toolbar-btn.btn-print:hover {
            background-color: #059669;
            border-color: #059669;
        }

        .toggle-switch-wrap {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #cbd5e1;
            cursor: pointer;
            user-select: none;
        }
        .toggle-switch-wrap input {
            cursor: pointer;
        }

        /* Outer Paper Viewport Wrapper */
        .paper-viewport {
            padding: 24px 12px 60px 12px;
            display: flex;
            justify-content: center;
            width: 100%;
        }

        /* A4 Document Sheet */
        .invoice-sheet {
            width: 210mm;
            min-height: 297mm;
            background-color: #ffffff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            padding: 15mm 18mm;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .d-none {
            display: none !important;
        }

        /* =========================================================================
           TEMA 1: FORMAL (KLASIK KOP SURAT DINAS & KEDINASAN)
           ========================================================================= */
        .kop-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding-bottom: 8px;
        }

        .kop-logo {
            height: var(--logo-size, 68px);
            width: auto;
            max-width: 140px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .kop-logo-placeholder {
            width: var(--logo-size, 68px);
            height: 1px;
            flex-shrink: 0;
        }

        .kop-text {
            text-align: center;
            flex-grow: 1;
        }

        .kop-foundation {
            font-size: 11pt;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .kop-committee {
            font-size: 10.5pt;
            font-weight: 700;
            color: #047857;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .kop-school {
            font-size: 13.5pt;
            font-weight: 800;
            color: #1e3a8a;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin: 1px 0;
        }

        .kop-accreditation {
            font-size: 8pt;
            font-weight: 600;
            color: #475569;
        }

        .kop-address {
            font-size: 7.5pt;
            color: #334155;
            margin-top: 1px;
        }

        .kop-contact {
            font-size: 7pt;
            color: #475569;
        }

        .double-divider {
            border-top: 2.5px solid #0f172a;
            border-bottom: 1px solid #0f172a;
            height: 4px;
            margin: 6px 0 14px 0;
        }

        .doc-title-wrapper {
            text-align: center;
            margin-bottom: 14px;
            position: relative;
        }

        .doc-title {
            font-size: 14pt;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #0f172a;
            text-transform: uppercase;
        }

        .doc-number {
            font-size: 9.5pt;
            font-weight: 600;
            color: #334155;
            font-family: 'Courier New', Courier, monospace;
            margin-top: 2px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 12px;
            margin-bottom: 14px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
        }

        .meta-block {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .meta-row {
            display: flex;
            font-size: 9pt;
            line-height: 1.35;
        }

        .meta-label {
            width: 140px;
            color: #64748b;
            font-weight: 500;
            flex-shrink: 0;
        }

        .meta-colon {
            width: 14px;
            color: #64748b;
            text-align: center;
            flex-shrink: 0;
        }

        .meta-value {
            color: #0f172a;
            font-weight: 600;
            flex-grow: 1;
        }

        .meta-block-right {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .meta-right-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex-grow: 1;
        }

        .qrcode-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            flex-shrink: 0;
        }

        .qrcode-box {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
        }

        .qrcode-label {
            font-size: 6pt;
            font-weight: 700;
            color: #475569;
            letter-spacing: 0.5px;
            margin-top: 2px;
            text-transform: uppercase;
        }

        .status-badge-stamp {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .status-badge-paid {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .status-badge-pending {
            background-color: #fef9c3;
            color: #854d0e;
            border: 1px solid #fde047;
        }

        .formal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 12px;
        }

        .formal-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: 700;
            text-align: center;
            padding: 7px 8px;
            border: 1px solid #334155;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .formal-table td {
            padding: 7px 8px;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            vertical-align: middle;
        }

        .col-center { text-align: center; }
        .col-right { text-align: right; }
        .col-left { text-align: left; }

        .total-summary-row td {
            font-weight: 800;
            background-color: #f1f5f9;
            font-size: 9.5pt;
            border-top: 2px solid #0f172a;
        }

        .grand-total-amount {
            color: #047857;
            font-size: 11pt;
            font-weight: 800;
        }

        .terbilang-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-left: 4px solid #2563eb;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 8.5pt;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .terbilang-label {
            font-weight: 700;
            color: #1e3a8a;
            text-transform: uppercase;
            flex-shrink: 0;
            font-size: 8pt;
        }

        .terbilang-text {
            font-style: italic;
            font-weight: 600;
            color: #0f172a;
        }

        .payment-info-box {
            background-color: #f0fdf4;
            border: 1px dashed #86efac;
            border-radius: 6px;
            padding: 9px 12px;
            font-size: 8pt;
            margin-bottom: 14px;
        }

        .payment-info-title {
            font-weight: 700;
            color: #166534;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 8.5pt;
            text-transform: uppercase;
        }

        .bank-accounts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 8px;
            margin-top: 5px;
        }

        .bank-card {
            background: #ffffff;
            border: 1px solid #bbf7d0;
            border-radius: 4px;
            padding: 5px 8px;
        }

        .bank-name { font-weight: 700; color: #166534; font-size: 8pt; }
        .bank-acc { font-family: monospace; font-size: 9.5pt; font-weight: 800; color: #0f172a; }
        .bank-holder { font-size: 7.5pt; color: #475569; }

        .signatures-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 8px;
            padding: 0 10px;
            position: relative;
        }

        .sig-block {
            width: 220px;
            text-align: center;
            position: relative;
        }

        .sig-date {
            font-size: 8pt;
            color: #334155;
            margin-bottom: 2px;
        }

        .sig-title {
            font-size: 8.5pt;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 60px;
        }

        .sig-name {
            font-size: 9pt;
            font-weight: 800;
            color: #0f172a;
            text-decoration: underline;
        }

        .sig-role {
            font-size: 7.5pt;
            color: #64748b;
        }

        /* Stamps */
        .stamp-official {
            position: absolute;
            top: 18px;
            left: -15px;
            width: 82px;
            height: 82px;
            border: 2px solid var(--stamp-color, #2563eb);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--stamp-color, #2563eb);
            opacity: 0.88;
            transform: rotate(var(--stamp-rotation, -8deg));
            pointer-events: none;
            user-select: none;
            z-index: 2;
        }

        .stamp-inner-circle {
            width: 70px;
            height: 70px;
            border: 1px dashed var(--stamp-color, #2563eb);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2px;
        }

        .stamp-image-official {
            position: absolute;
            top: 15px;
            left: -15px;
            width: 84px;
            height: 84px;
            object-fit: contain;
            opacity: 0.88;
            transform: rotate(var(--stamp-rotation, -8deg));
            pointer-events: none;
            user-select: none;
            z-index: 2;
        }

        .sig-image-layer {
            position: absolute;
            top: 25px;
            left: 35px;
            height: 48px;
            max-width: 140px;
            object-fit: contain;
            pointer-events: none;
            z-index: 1;
        }

        .stamp-text-top { font-size: 5.5pt; font-weight: 800; text-transform: uppercase; }
        .stamp-star { font-size: 8pt; color: #2563eb; }
        .stamp-text-bot { font-size: 5pt; font-weight: 800; text-transform: uppercase; }

        .stamp-lunas-overlay {
            position: absolute;
            top: 15px;
            right: 0px;
            border: 3px double #dc2626;
            color: #dc2626;
            font-weight: 900;
            font-size: 15pt;
            letter-spacing: 2px;
            padding: 4px 14px;
            border-radius: 6px;
            transform: rotate(-14deg);
            opacity: 0.88;
            pointer-events: none;
            user-select: none;
            text-transform: uppercase;
            box-shadow: 0 0 0 1px #dc2626;
        }

        .document-footnote {
            margin-top: 16px;
            padding-top: 6px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 6.8pt;
            color: #64748b;
        }

        .doc-watermark {
            font-family: monospace;
            font-weight: 600;
        }

        /* =========================================================================
           TEMA 2: PROFESIONAL (MODERN CORPORATE & FINTECH SLEEK)
           ========================================================================= */
        .pro-top-accent {
            height: 5px;
            background: linear-gradient(90deg, #1e3a8a 0%, #2563eb 50%, #059669 100%);
            border-radius: 4px 4px 0 0;
            margin: -15mm -18mm 16px -18mm;
        }

        .pro-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 14px;
            border-bottom: 1.5px solid #e2e8f0;
            gap: 16px;
        }

        .pro-brand-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .pro-brand-logo {
            height: var(--logo-size, 64px);
            width: auto;
            max-width: 120px;
            object-fit: contain;
        }

        .pro-brand-content {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .pro-brand-title {
            font-size: 13.5pt;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.3px;
            line-height: 1.15;
        }

        .pro-brand-school {
            font-size: 9.5pt;
            font-weight: 700;
            color: #2563eb;
            line-height: 1.2;
        }

        .pro-brand-details {
            font-size: 7.2pt;
            color: #64748b;
            line-height: 1.35;
            margin-top: 2px;
        }

        .pro-doc-header {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
        }

        .pro-doc-type-badge {
            font-size: 21pt;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 1.5px;
            line-height: 1;
            text-transform: uppercase;
        }

        .pro-doc-num-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-family: 'Courier New', Courier, monospace;
            font-weight: 700;
            font-size: 8.5pt;
            color: #1e293b;
        }

        .pro-doc-date {
            font-size: 8pt;
            color: #64748b;
            font-weight: 500;
        }

        .pro-meta-section {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 14px;
            margin: 14px 0 16px 0;
        }

        .pro-card-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 14px;
        }

        .pro-card-label {
            font-size: 7.2pt;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .pro-client-school {
            font-size: 11pt;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
            line-height: 1.25;
        }

        .pro-client-line {
            font-size: 8.2pt;
            color: #334155;
            line-height: 1.4;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .pro-card-right-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            height: 100%;
        }

        .pro-quick-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex-grow: 1;
        }

        .pro-quick-row {
            display: flex;
            flex-direction: column;
        }
        .pro-quick-label {
            font-size: 6.8pt;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
        }
        .pro-quick-val {
            font-size: 8.5pt;
            font-weight: 700;
            color: #0f172a;
        }

        .pro-qr-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            flex-shrink: 0;
        }

        .pro-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.8pt;
            margin-bottom: 12px;
        }

        .pro-table thead th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 800;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            border-top: 1.5px solid #cbd5e1;
            border-bottom: 1.5px solid #cbd5e1;
        }

        .pro-table tbody td {
            padding: 10px 10px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: middle;
        }

        .pro-summary-split {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 14px;
            margin: 6px 0 14px 0;
            align-items: start;
        }

        .pro-terbilang-card {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-left: 3.5px solid #2563eb;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 8pt;
        }

        .pro-terbilang-title {
            font-weight: 800;
            color: #1e40af;
            font-size: 7.2pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .pro-terbilang-quote {
            font-style: italic;
            font-weight: 600;
            color: #1e3a8a;
            line-height: 1.35;
        }

        .pro-calculation-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
        }

        .pro-calc-row {
            display: flex;
            justify-content: space-between;
            font-size: 8pt;
            color: #475569;
            padding: 3px 0;
        }

        .pro-calc-total-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background-color: #0f172a;
            color: #ffffff;
            border-radius: 6px;
            margin-top: 6px;
        }

        .pro-total-val {
            font-size: 11.5pt;
            font-weight: 900;
            color: #34d399;
            letter-spacing: 0.3px;
        }

        .pro-payment-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-left: 3.5px solid #10b981;
            border-radius: 6px;
            padding: 9px 12px;
            font-size: 8pt;
            margin-bottom: 14px;
        }

        .pro-signatures {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 8px;
            padding: 0 10px;
        }

        .pro-sig-item {
            width: 220px;
            text-align: center;
            position: relative;
        }

        .pro-sig-title {
            font-size: 8pt;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 55px;
        }

        .pro-sig-name {
            font-size: 9.2pt;
            font-weight: 800;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
            display: inline-block;
            padding-bottom: 2px;
            margin-bottom: 2px;
        }

        .pro-sig-role {
            font-size: 7.5pt;
            color: #64748b;
        }

        .pro-footer-row {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 6.8pt;
            color: #64748b;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: none !important;
                color: #000000 !important;
            }

            .no-print-toolbar {
                display: none !important;
            }

            .paper-viewport {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
            }

            .invoice-sheet {
                box-shadow: none !important;
                width: 100% !important;
                min-height: 100% !important;
                padding: 8mm 12mm !important;
                page-break-after: avoid;
            }

            .pro-top-accent {
                margin: -8mm -12mm 12px -12mm !important;
            }

            @page {
                size: A4 portrait;
                margin: 6mm 10mm;
            }
        }
    </style>
</head>
<body style="--stamp-color: {{ $invoiceConfig['stamp_color'] ?? '#2563eb' }}; --stamp-rotation: {{ $invoiceConfig['stamp_rotation'] ?? -8 }}deg; --logo-size: {{ $invoiceConfig['logo_size'] ?? 68 }}px;">

    <!-- Sticky Toolbar (Controls & Print Actions) -->
    <div class="no-print-toolbar">
        <div class="toolbar-left">
            <button type="button" class="toolbar-btn" onclick="window.history.back()">
                <i class="ri-arrow-left-line"></i> Kembali
            </button>
            <span style="color: #94a3b8; font-size: 13px;">|</span>
            <span style="font-weight: 600; font-size: 13px;">
                Tema UI:
            </span>
            <button 
                type="button" 
                class="toolbar-btn {{ $defaultTheme === 'formal' ? 'btn-active' : '' }}" 
                id="btn-theme-formal"
                onclick="setTheme('formal')"
                title="Beralih ke Desain Formal (KOP Surat Klasik Kedinasan)"
            >
                <i class="ri-government-line"></i> Tema Formal
            </button>
            <button 
                type="button" 
                class="toolbar-btn {{ $defaultTheme === 'professional' ? 'btn-active' : '' }}" 
                id="btn-theme-professional"
                onclick="setTheme('professional')"
                title="Beralih ke Desain Profesional (Modern Corporate Sleek)"
            >
                <i class="ri-briefcase-4-line text-warning"></i> Tema Profesional
            </button>
            <span style="color: #94a3b8; font-size: 13px;">|</span>
            <span style="font-weight: 600; font-size: 13px;">
                Mode:
            </span>
            <button 
                type="button" 
                class="toolbar-btn {{ $defaultMode === 'invoice' ? 'btn-active' : '' }}" 
                id="btn-mode-invoice"
                onclick="setDocumentMode('invoice')"
            >
                <i class="ri-file-list-line"></i> Nota Tagihan (Invoice)
            </button>
            <button 
                type="button" 
                class="toolbar-btn {{ $defaultMode === 'receipt' ? 'btn-active' : '' }}" 
                id="btn-mode-receipt"
                onclick="setDocumentMode('receipt')"
            >
                <i class="ri-receipt-line"></i> Kwitansi Lunas (Receipt)
            </button>
        </div>

        <div class="toolbar-right">
            <label class="toggle-switch-wrap" title="Tampilkan stempel resmi panitia">
                <input type="checkbox" id="chk-stamp" checked onchange="toggleStamp(this.checked)">
                <span>Stempel</span>
            </label>
            <label class="toggle-switch-wrap" title="Tampilkan cap tanda lunas">
                <input type="checkbox" id="chk-lunas-stamp" checked onchange="toggleLunasStamp(this.checked)">
                <span>Cap LUNAS</span>
            </label>
            <span style="color: #94a3b8; font-size: 13px;">|</span>
            @if(auth()->guard('web')->check())
                <a href="{{ route('admin.settings.kop-invoice') }}" target="_blank" class="toolbar-btn" title="Sesuaikan KOP Surat, Logo, & Stempel">
                    <i class="ri-settings-3-line"></i> Kustomisasi
                </a>
            @endif
            <button type="button" class="toolbar-btn btn-print" onclick="window.print()">
                <i class="ri-printer-line"></i> Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <!-- Paper Viewport Container -->
    <div class="paper-viewport">
        <div class="invoice-sheet" id="invoice-sheet">
            
            <!-- =========================================================================
                 1. TEMA FORMAL WRAPPER (KLASIK SURAT RESMI KEDINASAN)
                 ========================================================================= -->
            <div id="theme-formal-wrapper" class="{{ $defaultTheme === 'formal' ? '' : 'd-none' }}">
                <div>
                    <!-- KOP SURAT RESMI -->
                    <div class="kop-wrapper">
                        <img src="{{ $invoiceConfig['logo_left'] ?? '/assets/img/logo.png' }}" alt="Logo Madrasah" class="kop-logo" />

                        <div class="kop-text">
                            @if(!empty(trim($invoiceConfig['kop_foundation'] ?? '')))
                                <div class="kop-foundation">{{ $invoiceConfig['kop_foundation'] }}</div>
                            @endif
                            @if(!empty(trim($invoiceConfig['kop_committee'] ?? '')))
                                <div class="kop-committee">{{ $invoiceConfig['kop_committee'] }}</div>
                            @endif
                            @if(!empty(trim($invoiceConfig['kop_school'] ?? '')))
                                <div class="kop-school">{{ $invoiceConfig['kop_school'] }}</div>
                            @endif
                            @if(!empty(trim($invoiceConfig['kop_accreditation'] ?? '')))
                                <div class="kop-accreditation">{{ $invoiceConfig['kop_accreditation'] }}</div>
                            @endif
                            @if(!empty(trim($invoiceConfig['kop_address'] ?? '')))
                                <div class="kop-address">{{ $invoiceConfig['kop_address'] }}</div>
                            @endif
                            @if(!empty(trim($invoiceConfig['kop_contact'] ?? '')))
                                <div class="kop-contact">{{ $invoiceConfig['kop_contact'] }}</div>
                            @endif
                        </div>

                        @if(!empty($invoiceConfig['show_logo_right']))
                            <img src="{{ $invoiceConfig['logo_right'] ?? '/assets/img/logo.png' }}" alt="Logo Semarak" class="kop-logo" />
                        @else
                            <div class="kop-logo-placeholder"></div>
                        @endif
                    </div>

                    <!-- Double Divider Line -->
                    <div class="double-divider"></div>

                    <!-- JUDUL DOKUMEN & NOMOR -->
                    <div class="doc-title-wrapper">
                        <h2 class="doc-title" id="rendered-doc-title">
                            {{ $defaultMode === 'receipt' ? 'KWITANSI TANDA TERIMA PEMBAYARAN RESMI' : 'NOTA TAGIHAN BIAYA PENDAFTARAN' }}
                        </h2>
                        <div class="doc-number" id="rendered-doc-number">
                            {{ $defaultMode === 'receipt' ? 'Nomor Kwitansi: KW/SA-XIV/2026/' . $reg->reg_code : 'Nomor Invoice: INV/SA-XIV/2026/' . $reg->reg_code }}
                        </div>
                    </div>

                    <!-- METADATA INFO GRID -->
                    <div class="meta-grid">
                        <div class="meta-block">
                            <div class="meta-row">
                                <span class="meta-label" id="rendered-target-label">
                                    {{ $defaultMode === 'receipt' ? 'Telah Diterima Dari' : 'Ditagihkan Kepada' }}
                                </span>
                                <span class="meta-colon">:</span>
                                <span class="meta-value" style="font-size: 10pt; color: #1e3a8a;">{{ $reg->school_name ?: '-' }}</span>
                            </div>
                            <div class="meta-row">
                                <span class="meta-label">Guru Pendamping / PIC</span>
                                <span class="meta-colon">:</span>
                                <span class="meta-value">{{ $reg->mentor_name ?: ($reg->assigned_name ?: '-') }}</span>
                            </div>
                            <div class="meta-row">
                                <span class="meta-label">No. Telepon / WhatsApp</span>
                                <span class="meta-colon">:</span>
                                <span class="meta-value">{{ $reg->mentor_wa ?: ($reg->assigned_phone ?: '-') }}</span>
                            </div>
                            <div class="meta-row">
                                <span class="meta-label">Skema Pendaftaran</span>
                                <span class="meta-colon">:</span>
                                <span class="meta-value">{{ $reg->package_type ?: 'Paket Reguler (Satuan)' }}</span>
                            </div>
                        </div>

                        <div class="meta-block meta-block-right">
                            <div class="meta-right-info">
                                <div class="meta-row">
                                    <span class="meta-label">Tanggal Terbit</span>
                                    <span class="meta-colon">:</span>
                                    <span class="meta-value">{{ $reg->created_at ? $reg->created_at->format('d/m/Y') : date('d/m/Y') }}</span>
                                </div>
                                <div class="meta-row">
                                    <span class="meta-label">Kode Registrasi</span>
                                    <span class="meta-colon">:</span>
                                    <span class="meta-value font-monospace" style="color: #047857;">{{ $reg->reg_code }}</span>
                                </div>
                                <div class="meta-row">
                                    <span class="meta-label">Status Transaksi</span>
                                    <span class="meta-colon">:</span>
                                    <span class="meta-value" id="rendered-status-badge">
                                        @if($isPaid)
                                            <span class="status-badge-stamp status-badge-paid"><i class="ri-checkbox-circle-fill me-0.5"></i> LUNAS / TERVERIFIKASI</span>
                                        @else
                                            <span class="status-badge-stamp status-badge-pending"><i class="ri-time-fill me-0.5"></i> MENUNGGU PEMBAYARAN</span>
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <div class="qrcode-wrapper">
                                <div class="qrcode-box">
                                    <canvas id="qrcode-element-formal"></canvas>
                                </div>
                                <div class="qrcode-label font-monospace">SCAN VALIDASI</div>
                            </div>
                        </div>
                    </div>

                    <!-- TABEL RINCIAN BIAYA PENDAFTARAN -->
                    <table class="formal-table">
                        <thead>
                            <tr>
                                <th style="width: 38px;">No</th>
                                <th style="width: 250px;">Deskripsi Layanan / Cabang Lomba</th>
                                <th style="width: 140px;">Kategori / Skema</th>
                                <th>Delegasi Peserta / Keterangan</th>
                                <th style="width: 120px;" class="col-right">Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="col-center">1</td>
                                <td>
                                    <strong>{{ $reg->competition_title ?: 'Pendaftaran Cabang Lomba' }}</strong>
                                    <div style="font-size: 7.5pt; color: #64748b; margin-top: 1px;">
                                        Kode Unik: {{ $reg->reg_code }}
                                    </div>
                                </td>
                                <td class="col-center">
                                    <span style="font-weight: 600;">{{ $reg->package_type ?: 'Reguler (Satuan)' }}</span>
                                </td>
                                <td>
                                    @if($reg->participants_names)
                                        <div>{{ $reg->participants_names }}</div>
                                    @else
                                        <span style="color: #94a3b8; font-style: italic;">Data delegasi tercatat di sistem</span>
                                    @endif
                                </td>
                                <td class="col-right fw-bold">
                                    Rp {{ number_format((int) $reg->total_fee, 0, ',', '.') }}
                                </td>
                            </tr>

                            <tr class="total-summary-row">
                                <td colspan="4" class="col-right">
                                    TOTAL BIAYA PENDAFTARAN
                                </td>
                                <td class="col-right grand-total-amount">
                                    Rp {{ number_format((int) $reg->total_fee, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- KOTAK TERBILANG -->
                    <div class="terbilang-box">
                        <span class="terbilang-label">Terbilang:</span>
                        <span class="terbilang-text"># {{ $terbilang }} Rupiah #</span>
                    </div>

                    <!-- INFORMASI PEMBAYARAN & REKENING RESMI -->
                    <div class="payment-info-box {{ $defaultMode === 'receipt' ? 'd-none' : '' }}" id="payment-instructions-box">
                        <div class="payment-info-title">
                            <i class="ri-bank-card-line"></i> Instruksi & Rekening Pembayaran Resmi Panitia
                        </div>
                        <div>
                            Silakan melakukan pembayaran sesuai nominal di atas ke salah satu rekening resmi Panitia Semarak Aliyah XIV berikut:
                        </div>
                        <div class="bank-accounts-grid">
                            @if(!empty($bankAccounts))
                                @foreach($bankAccounts as $acc)
                                    <div class="bank-card">
                                        <div class="bank-name">{{ $acc['bank_name'] ?? 'Bank Syariah Indonesia' }}</div>
                                        <div class="bank-acc">{{ $acc['account_number'] ?? '7123456789' }}</div>
                                        <div class="bank-holder">a.n {{ $acc['account_holder'] ?? 'Semarak Aliyah MA Wahid Hasyim' }}</div>
                                    </div>
                                @endforeach
                            @else
                                <div class="bank-card">
                                    <div class="bank-name">Bank Syariah Indonesia (BSI)</div>
                                    <div class="bank-acc">7123456789</div>
                                    <div class="bank-holder">a.n Semarak Aliyah MA Wahid Hasyim</div>
                                </div>
                            @endif
                        </div>
                        <div style="margin-top: 6px; font-size: 7.2pt; color: #166534;">
                            <em>*Harap cantumkan Kode Registrasi <strong>{{ $reg->reg_code }}</strong> pada berita transfer, lalu unggah bukti transfer di dashboard pendaftar atau kirim ke WhatsApp Bendahara.</em>
                        </div>
                    </div>
                </div>

                <!-- TANDA TANGAN FORMAL -->
                <div>
                    <div class="signatures-section">
                        <div class="sig-block">
                            <div class="sig-date">&nbsp;</div>
                            <div class="sig-title" id="sig-title-payer">
                                {{ $defaultMode === 'receipt' ? 'Penyetor / Pembina Kontingen,' : 'Penerima Tagihan,' }}
                            </div>
                            <div class="sig-name">{{ $reg->mentor_name ?: 'Pembina Kontingen' }}</div>
                            <div class="sig-role">{{ $reg->school_name ?: 'Sekolah / Madrasah Peserta' }}</div>
                        </div>

                        <div class="sig-block">
                            <div class="sig-date">{{ $invoiceConfig['sig_city'] ?? 'Balung - Jember' }}, {{ date('d/m/Y') }}</div>
                            <div class="sig-title">
                                {{ $invoiceConfig['treasurer_role'] ?? 'Bendahara Pelaksana' }},<br>
                                @if(!empty($invoiceConfig['treasurer_subrole']))
                                    <span style="font-weight: 500; font-size: 7.5pt; color: #475569;">{{ $invoiceConfig['treasurer_subrole'] }}</span>
                                @endif
                            </div>

                            <!-- Cap Stempel Panitia -->
                            @if(($invoiceConfig['stamp_mode'] ?? 'digital') === 'image' && !empty($invoiceConfig['stamp_image']))
                                <img 
                                    src="{{ $invoiceConfig['stamp_image'] }}" 
                                    alt="Stempel Panitia" 
                                    class="stamp-image-official stamp-container-all" 
                                />
                            @else
                                <div class="stamp-official stamp-container-all">
                                    <div class="stamp-inner-circle">
                                        <div class="stamp-text-top">{{ $invoiceConfig['stamp_top_text'] ?? 'SEMARAK ALIYAH XIV' }}</div>
                                        <div class="stamp-star">{{ $invoiceConfig['stamp_center_text'] ?? '★' }}</div>
                                        <div class="stamp-text-bot">{{ $invoiceConfig['stamp_bottom_text'] ?? 'MA WAHID HASYIM' }}</div>
                                    </div>
                                </div>
                            @endif

                            <!-- Scan Tanda Tangan Gambar (Opsional) -->
                            @if(!empty($invoiceConfig['signature_image']))
                                <img src="{{ $invoiceConfig['signature_image'] }}" alt="Tanda Tangan Bendahara" class="sig-image-layer" />
                            @endif

                            <!-- Cap Lunas Overlay (Hanya untuk Mode Kwitansi) -->
                            <div class="stamp-lunas-overlay lunas-stamp-all {{ $defaultMode === 'receipt' ? '' : 'd-none' }}">
                                LUNAS
                            </div>

                            <div class="sig-name" style="position: relative; z-index: 3;">{{ $invoiceConfig['treasurer_name'] ?? $treasurerName }}</div>
                            <div class="sig-role" style="position: relative; z-index: 3;">{{ $invoiceConfig['treasurer_role'] ?? $treasurerRole }}</div>
                        </div>
                    </div>

                    <!-- CATATAN KAKI RESMI -->
                    <div class="document-footnote">
                        <span>
                            {{ $invoiceConfig['footer_note'] ?? 'Dokumen ini diterbitkan secara sah dan otomatis oleh Sistem Informasi Semarak Aliyah XIV MA Unggulan Wahid Hasyim Balung.' }}
                        </span>
                        <span class="doc-watermark">
                            ID: SA-XIV/{{ $reg->reg_code }}/{{ date('YmdHi') }}
                        </span>
                    </div>
                </div>
            </div>


            <!-- =========================================================================
                 2. TEMA PROFESIONAL WRAPPER (MODERN CORPORATE & FINTECH SLEEK)
                 ========================================================================= -->
            <div id="theme-professional-wrapper" class="{{ $defaultTheme === 'professional' ? '' : 'd-none' }}">
                <div>
                    <!-- Top Accent Bar -->
                    <div class="pro-top-accent"></div>

                    <!-- Modern Header Section -->
                    <div class="pro-header">
                        <div class="pro-brand-wrap">
                            <img src="{{ $invoiceConfig['logo_left'] ?? '/assets/img/logo.png' }}" alt="Logo Instansi" class="pro-brand-logo" />
                            <div class="pro-brand-content">
                                <div class="pro-brand-title">SEMARAK ALIYAH XIV</div>
                                <div class="pro-brand-school">{{ $invoiceConfig['kop_school'] ?? 'MA Unggulan Wahid Hasyim Balung' }}</div>
                                <div class="pro-brand-details">
                                    {{ $invoiceConfig['kop_address'] ?? 'Jl. Balung - Tutul No. 09 Balung Kidul, Jember' }}<br>
                                    {{ $invoiceConfig['kop_contact'] ?? 'semarak.ma-wh.sch.id • WA: 0838 5007 0767' }}
                                </div>
                            </div>
                        </div>

                        <div class="pro-doc-header">
                            <div class="pro-doc-type-badge" id="pro-rendered-doc-title">
                                {{ $defaultMode === 'receipt' ? 'KWITANSI' : 'INVOICE' }}
                            </div>
                            <div class="pro-doc-num-pill" id="pro-rendered-doc-number">
                                <i class="ri-hashtag text-primary"></i> {{ $defaultMode === 'receipt' ? 'KW/SA-XIV/2026/' . $reg->reg_code : 'INV/SA-XIV/2026/' . $reg->reg_code }}
                            </div>
                            <div class="pro-doc-date">
                                Tanggal: <strong>{{ $reg->created_at ? $reg->created_at->format('d/m/Y') : date('d/m/Y') }}</strong>
                            </div>
                            <div id="pro-rendered-status-badge">
                                @if($isPaid)
                                    <span class="status-badge-stamp status-badge-paid"><i class="ri-checkbox-circle-fill me-0.5"></i> LUNAS / TERVERIFIKASI</span>
                                @else
                                    <span class="status-badge-stamp status-badge-pending"><i class="ri-time-fill me-0.5"></i> MENUNGGU PEMBAYARAN</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Client Info & Document Meta Cards Grid -->
                    <div class="pro-meta-section">
                        <!-- Left Card: Customer / Billed To -->
                        <div class="pro-card-box">
                            <div class="pro-card-label">
                                <i class="ri-user-star-line text-primary"></i>
                                <span id="pro-rendered-target-label">
                                    {{ $defaultMode === 'receipt' ? 'TELAH DITERIMA DARI' : 'DITAGIHKAN KEPADA' }}
                                </span>
                            </div>
                            <div class="pro-client-school">{{ $reg->school_name ?: '-' }}</div>
                            <div class="pro-client-line">
                                <i class="ri-user-3-line text-muted"></i>
                                <span>Guru Pendamping: <strong>{{ $reg->mentor_name ?: ($reg->assigned_name ?: '-') }}</strong></span>
                            </div>
                            <div class="pro-client-line">
                                <i class="ri-whatsapp-line text-success"></i>
                                <span>Kontak WhatsApp: <strong>{{ $reg->mentor_wa ?: ($reg->assigned_phone ?: '-') }}</strong></span>
                            </div>
                            <div class="pro-client-line">
                                <i class="ri-award-line text-primary"></i>
                                <span>Skema: <span class="badge bg-primary-subtle text-primary fw-bold">{{ $reg->package_type ?: 'Reguler (Satuan)' }}</span></span>
                            </div>
                        </div>

                        <!-- Right Card: Validation & Quick Details -->
                        <div class="pro-card-box">
                            <div class="pro-card-right-content">
                                <div class="pro-quick-info">
                                    <div class="pro-quick-row">
                                        <span class="pro-quick-label">Kode Registrasi:</span>
                                        <span class="pro-quick-val font-monospace" style="color: #047857;">{{ $reg->reg_code }}</span>
                                    </div>
                                    <div class="pro-quick-row">
                                        <span class="pro-quick-label">Metode Pembayaran:</span>
                                        <span class="pro-quick-val">{{ $reg->payment_method ?: 'Transfer Bank / Online' }}</span>
                                    </div>
                                    <div class="pro-quick-row">
                                        <span class="pro-quick-label">Verifikasi Legalitas:</span>
                                        <span style="font-size: 7.2pt; color: #64748b;">Dokumen Otentik Sistem</span>
                                    </div>
                                </div>

                                <div class="pro-qr-container">
                                    <div style="width: 68px; height: 68px;">
                                        <canvas id="qrcode-element-pro"></canvas>
                                    </div>
                                    <div class="qrcode-label font-monospace" style="font-size: 5.5pt;">SCAN VALIDASI</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modern Professional Item Table -->
                    <table class="pro-table">
                        <thead>
                            <tr>
                                <th style="width: 35px; text-align: center;">#</th>
                                <th style="text-align: left;">Layanan / Cabang Lomba</th>
                                <th style="width: 140px; text-align: center;">Skema</th>
                                <th style="text-align: left;">Rincian Delegasi</th>
                                <th style="width: 125px; text-align: right;">Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="text-align: center; color: #64748b;">1</td>
                                <td>
                                    <strong style="color: #0f172a; font-size: 9.5pt;">{{ $reg->competition_title ?: 'Pendaftaran Cabang Lomba' }}</strong>
                                    <div style="font-size: 7.5pt; color: #64748b; margin-top: 1px;">
                                        Nomor Unik: {{ $reg->reg_code }}
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span style="display: inline-block; padding: 2px 8px; background: #f1f5f9; border-radius: 4px; font-weight: 600; font-size: 8pt; color: #334155;">
                                        {{ $reg->package_type ?: 'Reguler (Satuan)' }}
                                    </span>
                                </td>
                                <td>
                                    @if($reg->participants_names)
                                        <div style="font-size: 8.2pt; color: #1e293b;">{{ $reg->participants_names }}</div>
                                    @else
                                        <span style="color: #94a3b8; font-style: italic; font-size: 8pt;">Data delegasi tercatat di sistem</span>
                                    @endif
                                </td>
                                <td style="text-align: right; font-weight: 800; font-size: 9.5pt; color: #0f172a;">
                                    Rp {{ number_format((int) $reg->total_fee, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Split Summary & Terbilang -->
                    <div class="pro-summary-split">
                        <!-- Left: Terbilang Card -->
                        <div class="pro-terbilang-card">
                            <div class="pro-terbilang-title">
                                <i class="ri-chat-quote-line"></i> Terbilang Pembayaran:
                            </div>
                            <div class="pro-terbilang-quote">
                                "# {{ $terbilang }} Rupiah #"
                            </div>
                        </div>

                        <!-- Right: Calculation Box -->
                        <div class="pro-calculation-card">
                            <div class="pro-calc-row">
                                <span>Subtotal Biaya</span>
                                <span class="fw-semibold">Rp {{ number_format((int) $reg->total_fee, 0, ',', '.') }}</span>
                            </div>
                            <div class="pro-calc-row">
                                <span>Biaya Layanan &amp; Admin</span>
                                <span class="fw-semibold" style="color: #059669;">Rp 0 (Gratis)</span>
                            </div>
                            <div class="pro-calc-total-box">
                                <span style="font-size: 8.5pt; font-weight: 700; text-transform: uppercase;">Total Akhir</span>
                                <span class="pro-total-val">Rp {{ number_format((int) $reg->total_fee, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information (Invoice Mode Only) -->
                    <div class="pro-payment-box {{ $defaultMode === 'receipt' ? 'd-none' : '' }}" id="pro-payment-instructions-box">
                        <div style="font-weight: 800; color: #065f46; font-size: 8.5pt; margin-bottom: 4px; display: flex; align-items: center; gap: 5px;">
                            <i class="ri-bank-card-2-line"></i> Rekening Pembayaran Resmi Panitia
                        </div>
                        <div style="color: #064e3b; font-size: 7.8pt; margin-bottom: 6px;">
                            Mohon melakukan transfer tepat nominal ke salah satu rekening resmi di bawah ini:
                        </div>
                        <div class="bank-accounts-grid">
                            @if(!empty($bankAccounts))
                                @foreach($bankAccounts as $acc)
                                    <div class="bank-card" style="border: 1px solid #a7f3d0; background: #ffffff;">
                                        <div class="bank-name">{{ $acc['bank_name'] ?? 'Bank Syariah Indonesia' }}</div>
                                        <div class="bank-acc" style="color: #065f46;">{{ $acc['account_number'] ?? '7123456789' }}</div>
                                        <div class="bank-holder">a.n {{ $acc['account_holder'] ?? 'Semarak Aliyah MA Wahid Hasyim' }}</div>
                                    </div>
                                @endforeach
                            @else
                                <div class="bank-card" style="border: 1px solid #a7f3d0; background: #ffffff;">
                                    <div class="bank-name">Bank Syariah Indonesia (BSI)</div>
                                    <div class="bank-acc" style="color: #065f46;">7123456789</div>
                                    <div class="bank-holder">a.n Semarak Aliyah MA Wahid Hasyim</div>
                                </div>
                            @endif
                        </div>
                        <div style="margin-top: 6px; font-size: 7.2pt; color: #065f46;">
                            *Sertakan berita transfer: <strong>{{ $reg->reg_code }}</strong>. Simpan bukti transfer dan unggah melalui portal peserta.
                        </div>
                    </div>
                </div>

                <!-- Signatures Section Professional -->
                <div>
                    <div class="pro-signatures">
                        <!-- Left: Pembina / Kontingen -->
                        <div class="pro-sig-item">
                            <div class="pro-sig-title" id="pro-sig-title-payer">
                                {{ $defaultMode === 'receipt' ? 'Penyetor / Pembina Kontingen,' : 'Penerima Tagihan,' }}
                            </div>
                            <div class="pro-sig-name">{{ $reg->mentor_name ?: 'Pembina Kontingen' }}</div>
                            <div class="pro-sig-role">{{ $reg->school_name ?: 'Sekolah / Madrasah Peserta' }}</div>
                        </div>

                        <!-- Right: Bendahara Panitia -->
                        <div class="pro-sig-item">
                            <div style="font-size: 7.5pt; color: #64748b; margin-bottom: 2px;">
                                {{ $invoiceConfig['sig_city'] ?? 'Balung - Jember' }}, {{ date('d/m/Y') }}
                            </div>
                            <div class="pro-sig-title">
                                {{ $invoiceConfig['treasurer_role'] ?? 'Bendahara Pelaksana' }}
                            </div>

                            <!-- Cap Stempel Panitia -->
                            @if(($invoiceConfig['stamp_mode'] ?? 'digital') === 'image' && !empty($invoiceConfig['stamp_image']))
                                <img 
                                    src="{{ $invoiceConfig['stamp_image'] }}" 
                                    alt="Stempel Panitia" 
                                    class="stamp-image-official stamp-container-all" 
                                />
                            @else
                                <div class="stamp-official stamp-container-all">
                                    <div class="stamp-inner-circle">
                                        <div class="stamp-text-top">{{ $invoiceConfig['stamp_top_text'] ?? 'SEMARAK ALIYAH XIV' }}</div>
                                        <div class="stamp-star">{{ $invoiceConfig['stamp_center_text'] ?? '★' }}</div>
                                        <div class="stamp-text-bot">{{ $invoiceConfig['stamp_bottom_text'] ?? 'MA WAHID HASYIM' }}</div>
                                    </div>
                                </div>
                            @endif

                            <!-- Scan Tanda Tangan Gambar (Opsional) -->
                            @if(!empty($invoiceConfig['signature_image']))
                                <img src="{{ $invoiceConfig['signature_image'] }}" alt="Tanda Tangan Bendahara" class="sig-image-layer" />
                            @endif

                            <!-- Cap Lunas Overlay (Hanya untuk Mode Kwitansi) -->
                            <div class="stamp-lunas-overlay lunas-stamp-all {{ $defaultMode === 'receipt' ? '' : 'd-none' }}">
                                LUNAS
                            </div>

                            <div class="pro-sig-name" style="position: relative; z-index: 3;">{{ $invoiceConfig['treasurer_name'] ?? $treasurerName }}</div>
                            <div class="pro-sig-role" style="position: relative; z-index: 3;">{{ $invoiceConfig['treasurer_subrole'] ?? 'Panitia Pelaksana Semarak Aliyah XIV' }}</div>
                        </div>
                    </div>

                    <!-- Professional Footer -->
                    <div class="pro-footer-row">
                        <span>
                            <i class="ri-shield-check-line text-success me-1"></i>
                            {{ $invoiceConfig['footer_note'] ?? 'Dokumen diterbitkan secara sah dan terverifikasi digital oleh Sistem Informasi Semarak Aliyah XIV.' }}
                        </span>
                        <span class="doc-watermark">
                            SEC: SA14-{{ $reg->reg_code }}-{{ date('Ymd') }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Script to render crisp QR Codes & handle mode/theme switches -->
    <script>
        let currentTheme = "{{ $defaultTheme ?? 'formal' }}";
        let currentMode = "{{ $defaultMode ?? 'invoice' }}";

        document.addEventListener('DOMContentLoaded', function () {
            // Render Crisp Vector QR Code for both templates
            renderQrCodes();
        });

        function renderQrCodes() {
            try {
                if (window.QRCode) {
                    const qrFormal = document.getElementById('qrcode-element-formal');
                    if (qrFormal) {
                        window.QRCode.toCanvas(qrFormal, "{{ $reg->reg_code }}", {
                            width: 72,
                            margin: 0,
                            color: { dark: '#0f172a', light: '#ffffff' },
                            errorCorrectionLevel: 'M'
                        });
                    }

                    const qrPro = document.getElementById('qrcode-element-pro');
                    if (qrPro) {
                        window.QRCode.toCanvas(qrPro, "{{ $reg->reg_code }}", {
                            width: 68,
                            margin: 0,
                            color: { dark: '#0f172a', light: '#ffffff' },
                            errorCorrectionLevel: 'M'
                        });
                    }
                }
            } catch (err) {
                console.warn("QRCode error:", err);
            }
        }

        // Switch Theme Function (Formal vs Professional)
        function setTheme(theme) {
            currentTheme = theme;
            const btnFormal = document.getElementById('btn-theme-formal');
            const btnPro = document.getElementById('btn-theme-professional');
            const wrapFormal = document.getElementById('theme-formal-wrapper');
            const wrapPro = document.getElementById('theme-professional-wrapper');

            if (theme === 'professional') {
                btnPro.classList.add('btn-active');
                btnFormal.classList.remove('btn-active');
                wrapFormal.classList.add('d-none');
                wrapPro.classList.remove('d-none');
            } else {
                btnFormal.classList.add('btn-active');
                btnPro.classList.remove('btn-active');
                wrapPro.classList.add('d-none');
                wrapFormal.classList.remove('d-none');
            }

            // Re-render QR if needed
            renderQrCodes();
        }

        // Switch Document Mode Function (Invoice vs Receipt)
        function setDocumentMode(mode) {
            currentMode = mode;
            const btnInvoice = document.getElementById('btn-mode-invoice');
            const btnReceipt = document.getElementById('btn-mode-receipt');

            // Formal Elements
            const titleEl = document.getElementById('rendered-doc-title');
            const numEl = document.getElementById('rendered-doc-number');
            const targetLabel = document.getElementById('rendered-target-label');
            const sigTitlePayer = document.getElementById('sig-title-payer');
            const paymentBox = document.getElementById('payment-instructions-box');
            const statusBadge = document.getElementById('rendered-status-badge');

            // Professional Elements
            const proTitleEl = document.getElementById('pro-rendered-doc-title');
            const proNumEl = document.getElementById('pro-rendered-doc-number');
            const proTargetLabel = document.getElementById('pro-rendered-target-label');
            const proSigTitlePayer = document.getElementById('pro-sig-title-payer');
            const proPaymentBox = document.getElementById('pro-payment-instructions-box');
            const proStatusBadge = document.getElementById('pro-rendered-status-badge');

            // Common Elements
            const lunasStamps = document.querySelectorAll('.lunas-stamp-all');

            if (mode === 'receipt') {
                btnReceipt.classList.add('btn-active');
                btnInvoice.classList.remove('btn-active');

                // Formal updates
                if (titleEl) titleEl.textContent = 'KWITANSI TANDA TERIMA PEMBAYARAN RESMI';
                if (numEl) numEl.textContent = 'Nomor Kwitansi: KW/SA-XIV/2026/{{ $reg->reg_code }}';
                if (targetLabel) targetLabel.textContent = 'Telah Diterima Dari';
                if (sigTitlePayer) sigTitlePayer.innerHTML = 'Penyetor / Pembina Kontingen,';
                if (paymentBox) paymentBox.classList.add('d-none');
                if (statusBadge) statusBadge.innerHTML = '<span class="status-badge-stamp status-badge-paid"><i class="ri-checkbox-circle-fill me-0.5"></i> LUNAS / TERVERIFIKASI</span>';

                // Professional updates
                if (proTitleEl) proTitleEl.textContent = 'KWITANSI';
                if (proNumEl) proNumEl.innerHTML = '<i class="ri-hashtag text-primary"></i> KW/SA-XIV/2026/{{ $reg->reg_code }}';
                if (proTargetLabel) proTargetLabel.textContent = 'TELAH DITERIMA DARI';
                if (proSigTitlePayer) proSigTitlePayer.innerHTML = 'Penyetor / Pembina Kontingen,';
                if (proPaymentBox) proPaymentBox.classList.add('d-none');
                if (proStatusBadge) proStatusBadge.innerHTML = '<span class="status-badge-stamp status-badge-paid"><i class="ri-checkbox-circle-fill me-0.5"></i> LUNAS / TERVERIFIKASI</span>';

                // Lunas stamp display
                const chkLunas = document.getElementById('chk-lunas-stamp');
                lunasStamps.forEach(el => {
                    if (chkLunas && chkLunas.checked) {
                        el.classList.remove('d-none');
                    } else {
                        el.classList.add('d-none');
                    }
                });

                document.title = 'Kwitansi Pembayaran - {{ $reg->reg_code }} | Semarak Aliyah XIV';
            } else {
                btnInvoice.classList.add('btn-active');
                btnReceipt.classList.remove('btn-active');

                // Formal updates
                if (titleEl) titleEl.textContent = 'NOTA TAGIHAN BIAYA PENDAFTARAN';
                if (numEl) numEl.textContent = 'Nomor Invoice: INV/SA-XIV/2026/{{ $reg->reg_code }}';
                if (targetLabel) targetLabel.textContent = 'Ditagihkan Kepada';
                if (sigTitlePayer) sigTitlePayer.innerHTML = 'Penerima Tagihan,';
                if (paymentBox) paymentBox.classList.remove('d-none');
                if (statusBadge) statusBadge.innerHTML = '<span class="status-badge-stamp status-badge-pending"><i class="ri-time-fill me-0.5"></i> MENUNGGU PEMBAYARAN</span>';

                // Professional updates
                if (proTitleEl) proTitleEl.textContent = 'INVOICE';
                if (proNumEl) proNumEl.innerHTML = '<i class="ri-hashtag text-primary"></i> INV/SA-XIV/2026/{{ $reg->reg_code }}';
                if (proTargetLabel) proTargetLabel.textContent = 'DITAGIHKAN KEPADA';
                if (proSigTitlePayer) proSigTitlePayer.innerHTML = 'Penerima Tagihan,';
                if (proPaymentBox) proPaymentBox.classList.remove('d-none');
                if (proStatusBadge) proStatusBadge.innerHTML = '<span class="status-badge-stamp status-badge-pending"><i class="ri-time-fill me-0.5"></i> MENUNGGU PEMBAYARAN</span>';

                // Hide lunas stamps in invoice mode
                lunasStamps.forEach(el => el.classList.add('d-none'));

                document.title = 'Nota Invoice - {{ $reg->reg_code }} | Semarak Aliyah XIV';
            }
        }

        // Toggle Stamp Visibility
        function toggleStamp(visible) {
            const stamps = document.querySelectorAll('.stamp-container-all');
            stamps.forEach(stamp => stamp.classList.toggle('d-none', !visible));
        }

        // Toggle Lunas Stamp Visibility
        function toggleLunasStamp(visible) {
            const lunasStamps = document.querySelectorAll('.lunas-stamp-all');
            lunasStamps.forEach(el => {
                if (currentMode === 'receipt') {
                    el.classList.toggle('d-none', !visible);
                } else {
                    el.classList.add('d-none');
                }
            });
        }
    </script>
</body>
</html>
