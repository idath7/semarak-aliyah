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
            background-color: #334155;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #0f172a;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
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
            gap: 10px;
            flex-wrap: wrap;
        }

        .toolbar-btn {
            background-color: transparent;
            color: #f1f5f9;
            border: 1px solid #475569;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 13px;
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

        /* Formal A4 Document Sheet */
        .invoice-sheet {
            width: 210mm;
            min-height: 297mm;
            background-color: #ffffff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            padding: 16mm 18mm;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* 1. KOP SURAT RESMI */
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

        .d-none {
            display: none !important;
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

        /* Double divider line */
        .double-divider {
            border-top: 2.5px solid #0f172a;
            border-bottom: 1px solid #0f172a;
            height: 4px;
            margin: 6px 0 14px 0;
        }

        /* 2. DOKUMEN TITLE & NUMBER */
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

        /* 3. METADATA INFO GRID */
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

        #qrcode-element {
            width: 72px !important;
            height: 72px !important;
            display: block;
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

        /* 4. ITEMIZATION TABLE */
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

        .participants-sublist {
            margin-left: 14px;
            margin-top: 3px;
            font-size: 8pt;
            color: #334155;
        }

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

        /* 5. TERBILANG BOX */
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

        /* 6. PAYMENT INSTRUCTIONS (For Invoice Mode) */
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

        /* 7. SIGNATURES & OFFICIAL SEALS */
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
            margin-bottom: 60px; /* Space for signature & stamp */
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

        /* Digital & Image Stamps */
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

        /* Lunas Stamp Overlay */
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

        /* 8. FOOTNOTE */
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
                Mode Dokumen:
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
                <span>Stempel Panitia</span>
            </label>
            <label class="toggle-switch-wrap" title="Tampilkan cap tanda lunas">
                <input type="checkbox" id="chk-lunas-stamp" checked onchange="toggleLunasStamp(this.checked)">
                <span>Cap LUNAS</span>
            </label>
            <span style="color: #94a3b8; font-size: 13px;">|</span>
            @if(auth()->guard('web')->check())
                <a href="{{ route('admin.settings.kop-invoice') }}" target="_blank" class="toolbar-btn" title="Sesuaikan KOP Surat, Logo, & Stempel">
                    <i class="ri-settings-3-line"></i> Sesuaikan KOP &amp; Stempel
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
            
            <div>
                <!-- 1. KOP SURAT RESMI -->
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

                <!-- 2. JUDUL DOKUMEN & NOMOR -->
                <div class="doc-title-wrapper">
                    <h2 class="doc-title" id="rendered-doc-title">
                        {{ $defaultMode === 'receipt' ? 'KWITANSI TANDA TERIMA PEMBAYARAN RESMI' : 'NOTA TAGIHAN BIAYA PENDAFTARAN' }}
                    </h2>
                    <div class="doc-number" id="rendered-doc-number">
                        {{ $defaultMode === 'receipt' ? 'Nomor Kwitansi: KW/SA-XIV/2026/' . $reg->reg_code : 'Nomor Invoice: INV/SA-XIV/2026/' . $reg->reg_code }}
                    </div>
                </div>

                <!-- 3. METADATA INFO GRID -->
                <div class="meta-grid">
                    <!-- Sisi Kiri: Lembaga & Pendamping -->
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

                    <!-- Sisi Kanan: Nomor & QR Code Validasi -->
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

                        <!-- Official Vector QR Code Box -->
                        <div class="qrcode-wrapper">
                            <div class="qrcode-box">
                                <canvas id="qrcode-element"></canvas>
                            </div>
                            <div class="qrcode-label font-monospace">SCAN VALIDASI</div>
                        </div>
                    </div>
                </div>

                <!-- 4. TABEL RINCIAN BIAYA PENDAFTARAN -->
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

                        <!-- Subtotal & Grand Total Rows -->
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

                <!-- 5. KOTAK TERBILANG -->
                <div class="terbilang-box">
                    <span class="terbilang-label">Terbilang:</span>
                    <span class="terbilang-text"># {{ $terbilang }} Rupiah #</span>
                </div>

                <!-- 6. INFORMASI PEMBAYARAN & REKENING RESMI (Bisa di-toggle / hanya tampil bila Invoice) -->
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

            <div>
                <!-- 7. PENGESAHAN & TANDA TANGAN -->
                <div class="signatures-section">
                    <!-- Sisi Kiri: Pembina / Kontingen -->
                    <div class="sig-block">
                        <div class="sig-date">&nbsp;</div>
                        <div class="sig-title" id="sig-title-payer">
                            {{ $defaultMode === 'receipt' ? 'Penyetor / Pembina Kontingen,' : 'Penerima Tagihan,' }}
                        </div>
                        <div class="sig-name">{{ $reg->mentor_name ?: 'Pembina Kontingen' }}</div>
                        <div class="sig-role">{{ $reg->school_name ?: 'Sekolah / Madrasah Peserta' }}</div>
                    </div>

                    <!-- Sisi Kanan: Bendahara Panitia Semarak Aliyah XIV -->
                    <div class="sig-block">
                        <div class="sig-date">{{ $invoiceConfig['sig_city'] ?? 'Balung - Jember' }}, {{ date('d/m/Y') }}</div>
                        <div class="sig-title">
                            {{ $invoiceConfig['treasurer_role'] ?? 'Bendahara Pelaksana' }},<br>
                            @if(!empty($invoiceConfig['treasurer_subrole']))
                                <span style="font-weight: 500; font-size: 7.5pt; color: #475569;">{{ $invoiceConfig['treasurer_subrole'] }}</span>
                            @endif
                        </div>

                        <!-- Cap Stempel Panitia (Digital Vektor vs Gambar PNG) -->
                        @if(($invoiceConfig['stamp_mode'] ?? 'digital') === 'image' && !empty($invoiceConfig['stamp_image']))
                            <img 
                                src="{{ $invoiceConfig['stamp_image'] }}" 
                                alt="Stempel Panitia" 
                                class="stamp-image-official" 
                                id="official-stamp-container" 
                            />
                        @else
                            <div class="stamp-official" id="official-stamp-container">
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
                        <div class="stamp-lunas-overlay {{ $defaultMode === 'receipt' ? '' : 'd-none' }}" id="lunas-stamp-container">
                            LUNAS
                        </div>

                        <div class="sig-name" style="position: relative; z-index: 3;">{{ $invoiceConfig['treasurer_name'] ?? $treasurerName }}</div>
                        <div class="sig-role" style="position: relative; z-index: 3;">{{ $invoiceConfig['treasurer_role'] ?? $treasurerRole }}</div>
                    </div>
                </div>

                <!-- 8. CATATAN KAKI RESMI -->
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
    </div>

    <!-- Script to render crisp QR Code & handle mode switches -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Render Crisp Vector QR Code
            try {
                const qrCanvas = document.getElementById('qrcode-element');
                if (qrCanvas && window.QRCode) {
                    window.QRCode.toCanvas(qrCanvas, "{{ $reg->reg_code }}", {
                        width: 72,
                        margin: 0,
                        color: {
                            dark: '#0f172a',
                            light: '#ffffff'
                        },
                        errorCorrectionLevel: 'M'
                    }, function (error) {
                        if (error) console.error("QR Code error:", error);
                    });
                }
            } catch (err) {
                console.warn("QRCode error:", err);
            }
        });

        // Mode Switching Function (Invoice vs Receipt)
        function setDocumentMode(mode) {
            const btnInvoice = document.getElementById('btn-mode-invoice');
            const btnReceipt = document.getElementById('btn-mode-receipt');
            const titleEl = document.getElementById('rendered-doc-title');
            const numEl = document.getElementById('rendered-doc-number');
            const targetLabel = document.getElementById('rendered-target-label');
            const sigTitlePayer = document.getElementById('sig-title-payer');
            const paymentBox = document.getElementById('payment-instructions-box');
            const lunasStamp = document.getElementById('lunas-stamp-container');
            const statusBadge = document.getElementById('rendered-status-badge');

            if (mode === 'receipt') {
                btnReceipt.classList.add('btn-active');
                btnInvoice.classList.remove('btn-active');

                titleEl.textContent = 'KWITANSI TANDA TERIMA PEMBAYARAN RESMI';
                numEl.textContent = 'Nomor Kwitansi: KW/SA-XIV/2026/{{ $reg->reg_code }}';
                targetLabel.textContent = 'Telah Diterima Dari';
                sigTitlePayer.innerHTML = 'Penyetor / Pembina Kontingen,';
                paymentBox.classList.add('d-none');
                lunasStamp.classList.remove('d-none');
                statusBadge.innerHTML = '<span class="status-badge-stamp status-badge-paid"><i class="ri-checkbox-circle-fill me-0.5"></i> LUNAS / TERVERIFIKASI</span>';
                document.title = 'Kwitansi Pembayaran - {{ $reg->reg_code }} | Semarak Aliyah XIV';
            } else {
                btnInvoice.classList.add('btn-active');
                btnReceipt.classList.remove('btn-active');

                titleEl.textContent = 'NOTA TAGIHAN BIAYA PENDAFTARAN';
                numEl.textContent = 'Nomor Invoice: INV/SA-XIV/2026/{{ $reg->reg_code }}';
                targetLabel.textContent = 'Ditagihkan Kepada';
                sigTitlePayer.innerHTML = 'Penerima Tagihan,';
                paymentBox.classList.remove('d-none');
                lunasStamp.classList.add('d-none');
                statusBadge.innerHTML = '<span class="status-badge-stamp status-badge-pending"><i class="ri-time-fill me-0.5"></i> MENUNGGU PEMBAYARAN</span>';
                document.title = 'Nota Invoice - {{ $reg->reg_code }} | Semarak Aliyah XIV';
            }
        }

        // Toggle Stamp Visibility
        function toggleStamp(visible) {
            const stamp = document.getElementById('official-stamp-container');
            if (stamp) stamp.classList.toggle('d-none', !visible);
        }

        // Toggle Lunas Stamp Visibility
        function toggleLunasStamp(visible) {
            const lunasStamp = document.getElementById('lunas-stamp-container');
            if (lunasStamp) lunasStamp.classList.toggle('d-none', !visible);
        }
    </script>
</body>
</html>
