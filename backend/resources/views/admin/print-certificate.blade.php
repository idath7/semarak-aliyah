<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    @php
        $items = isset($certificates) && is_iterable($certificates) && count($certificates) > 0 
            ? $certificates 
            : [[
                'reg' => $reg ?? null,
                'recipient' => $recipient ?? (request('recipient') ?: (request('student_name') ?: ($reg->school_name ?? 'Peserta'))),
                'rank' => $rank ?? request('rank', 'Peserta'),
                'target' => $target ?? request('target', 'school'),
                'school_name' => $reg->school_name ?? 'MA Wahid Hasyim',
                'competition_title' => $reg->competition->title ?? ($reg->competition_title ?? 'Semarak Aliyah'),
                'barcode' => $reg->barcode ?? ($reg->reg_code ?? 'SM-XIV'),
                'reg_code' => $reg->reg_code ?? 'SM-XIV-001',
                'id' => $reg->id ?? 1,
            ]];
        $edition = $edition ?? (\App\Models\Setting::where('key', 'event_edition')->value('value') ?? 'XIV');
        $year = $year ?? (\App\Models\Setting::where('key', 'event_year')->value('value') ?? '2026');
        $certConfig = $certificateConfig ?? \App\Models\Setting::getCertificateConfig();
        $firstItem = $items[0] ?? [];
        $totalItems = count($items);
    @endphp
    <title>E-Sertifikat Penghargaan {{ $totalItems > 1 ? '(' . $totalItems . ' Lembar)' : '- ' . ($firstItem['recipient'] ?? 'Peserta') }} | Semarak Aliyah {{ $edition }} {{ $year }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/assets/img/logo.png">

    <!-- Local Offline Fonts, Icons, & Modern 2D QR Code Engine -->
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/vendor/remixicon/remixicon.css">
    <link rel="stylesheet" href="/assets/vendor/boxicons/css/boxicons.min.css">
    <script src="/assets/js/qrcode.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #1e293b;
            font-family: 'Poppins', sans-serif;
            color: #0f172a;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        .no-print {
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
            background-color: #0f172a;
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.35);
        }

        .btn-print {
            background-color: #059669;
            color: #ffffff;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            transition: all 0.2s;
        }
        .btn-print:hover {
            background-color: #047857;
        }

        .btn-back {
            background-color: transparent;
            color: #cbd5e1;
            border: 1px solid #475569;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-back:hover {
            background-color: #334155;
            color: #ffffff;
        }

        /* Certificate A4 Landscape (297mm x 210mm) */
        .cert-sheet {
            page-break-after: always;
            break-after: page;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px 0;
        }

        .cert-sheet:last-child {
            page-break-after: avoid;
            break-after: avoid;
        }

        .cert-container {
            width: 1050px;
            height: 742px;
            background: #ffffff;
            padding: 20px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.45);
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: center;
        }

        .cert-container.has-custom-bg {
            padding: 0;
        }

        .cert-outer-border {
            width: 100%;
            height: 100%;
            border: 4px solid #d97706;
            padding: 6px;
            position: relative;
        }

        .has-custom-bg .cert-outer-border {
            border: none !important;
            padding: 0 !important;
        }

        .cert-inner-border {
            width: 100%;
            height: 100%;
            border: 1.5px solid #064e3b;
            padding: 24px 36px;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            background: radial-gradient(circle, #ffffff 65%, #fdfcf7 100%);
        }

        .has-custom-bg .cert-inner-border {
            border: none !important;
            background: transparent !important;
            padding: 30px 48px;
        }

        /* Corner Ornaments (Disabled if custom blank exists) */
        .corner {
            position: absolute;
            width: 32px;
            height: 32px;
            border: 4px solid #d97706;
        }
        .corner-tl { top: -4px; left: -4px; border-right: none; border-bottom: none; }
        .corner-tr { top: -4px; right: -4px; border-left: none; border-bottom: none; }
        .corner-bl { bottom: -4px; left: -4px; border-right: none; border-top: none; }
        .corner-br { bottom: -4px; right: -4px; border-left: none; border-top: none; }

        .cert-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 8px;
            width: 100%;
            border-bottom: 2px solid #064e3b;
            padding-bottom: 10px;
        }

        .cert-logo {
            width: 58px;
            height: 58px;
            object-fit: contain;
        }

        .cert-institution h4 {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .cert-institution h2 {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 800;
            color: #064e3b;
            letter-spacing: 0.5px;
        }
        .cert-institution p {
            font-size: 10px;
            color: #64748b;
        }

        .cert-title-box {
            margin: 8px 0 4px;
        }
        .cert-title {
            font-family: 'Cinzel', serif;
            font-size: 30px;
            font-weight: 900;
            color: #d97706;
            letter-spacing: 3.5px;
            text-transform: uppercase;
        }
        .cert-number {
            font-size: 11px;
            letter-spacing: 1px;
            color: #64748b;
            margin-top: 1px;
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
        }

        .cert-body-text {
            font-size: 12px;
            color: #475569;
            margin: 6px 0 2px;
        }

        .recipient-name {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 800;
            color: #064e3b;
            margin: 2px 0 4px;
            text-transform: uppercase;
            border-bottom: 1.5px solid #d97706;
            display: inline-block;
            padding: 0 24px 2px;
            max-width: 90%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .recipient-sub {
            font-size: 12px;
            color: #334155;
            font-weight: 600;
            margin-top: 2px;
        }

        .recipient-box {
            position: absolute;
            left: 30px;
            right: 30px;
            text-align: center;
            transform: translateY(-50%);
            z-index: 5;
        }

        .predicate-box {
            position: absolute;
            left: 30px;
            right: 30px;
            text-align: center;
            transform: translateY(-50%);
            z-index: 5;
        }
        .predicate-title {
            letter-spacing: 1px;
            display: inline-block;
        }

        .competition-box {
            position: absolute;
            left: 30px;
            right: 30px;
            text-align: center;
            transform: translateY(-50%);
            z-index: 5;
        }
        .competition-title {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 2px;
        }
        .event-subtitle {
            font-size: 11px;
            color: #64748b;
            max-width: 680px;
            margin: 2px auto 0;
            line-height: 1.35;
        }

        /* Signatures Grid */
        .signatures-grid {
            position: absolute;
            bottom: 25px;
            left: 40px;
            right: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            z-index: 6;
        }

        .sig-col {
            text-align: center;
            width: 250px;
        }
        .sig-col .sig-title {
            font-size: 10.5px;
            color: #475569;
            line-height: 1.3;
            min-height: 28px;
        }
        .sig-col .sig-date {
            font-size: 10.5px;
            color: #475569;
            margin-bottom: 2px;
        }
        .sig-name {
            font-size: 12.5px;
            font-weight: 800;
            color: #0f172a;
            text-decoration: underline;
        }
        .sig-nip {
            font-size: 9.5px;
            color: #64748b;
        }

        /* Modern 2D QR Code Container */
        .cert-qr-box {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 4px 6px;
            background: #ffffff;
            border: 1px dashed rgba(6, 78, 59, 0.35);
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .cert-qr-canvas {
            display: block;
        }
        .cert-qr-caption {
            margin-top: 2px;
            text-align: center;
        }
        .cert-qr-id {
            font-size: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-weight: 700;
            color: #064e3b;
            display: block;
            line-height: 1;
        }
        .cert-qr-text {
            font-size: 7.5px;
            color: #64748b;
            letter-spacing: 0.3px;
            display: block;
            text-transform: uppercase;
            font-weight: 600;
            margin-top: 1px;
        }

        .cert-title-box.with-kop { margin-top: 6px; }
        .cert-title-box.without-kop { margin-top: 36px; }

        .sig-col.sig-hidden { visibility: hidden !important; }
        .sig-col.sig-col-relative { position: relative; }

        .qr-pos-left-bottom {
            position: absolute;
            left: 45px;
            bottom: 25px;
            z-index: 10;
        }
        .qr-pos-right-bottom {
            position: absolute;
            right: 45px;
            bottom: 25px;
            z-index: 10;
        }
        .qr-pos-custom {
            position: absolute;
            z-index: 10;
            transform: translate(-50%, -50%);
        }

        @media print {
            body {
                background: none !important;
                color: #000000 !important;
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            .cert-sheet {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                height: 100vh !important;
                page-break-after: always !important;
                break-after: page !important;
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
            }
            .cert-sheet:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            .cert-container {
                width: 100% !important;
                height: 100vh !important;
                margin: 0 !important;
                padding: 8mm !important;
                box-shadow: none !important;
            }
            .cert-container.has-custom-bg {
                padding: 0 !important;
            }
            @page {
                size: A4 landscape;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Print Control Bar -->
    <div class="no-print">
        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="/admin/sertifikat" class="btn-back">
                <i class="ri-arrow-left-line"></i> Kembali ke Menu Sertifikat
            </a>
            <div>
                <strong>Pratinjau E-Sertifikat Resmi A4 Landscape</strong>
                <span style="color: #94a3b8; font-size: 12px; margin-left: 8px;">
                    Total: {{ $totalItems }} Sertifikat
                    @if($totalItems === 1)
                        • Penerima: {{ $firstItem['recipient'] ?? '-' }}
                    @endif
                </span>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-print">
                <i class="ri-printer-line"></i> Cetak {{ $totalItems > 1 ? 'Semua (' . $totalItems . ' Lembar)' : 'Sertifikat Sekarang' }} (Ctrl+P)
            </button>
        </div>
    </div>

    @php
        if (!function_exists('renderCertStyle')) {
            function renderCertStyle(string $css): string {
                return 'st' . 'yle="' . htmlspecialchars($css, ENT_QUOTES, 'UTF-8') . '"';
            }
        }
    @endphp

    <!-- Loop through all certificate items -->
    @foreach($items as $idx => $item)
        @php
            $target = $item['target'] ?? 'school';
            $recipient = $item['recipient'] ?? ($item['school_name'] ?? 'Peserta');
            $schoolName = $item['school_name'] ?? 'Madrasah / Sekolah';
            $rank = $item['rank'] ?? 'Peserta';
            $compTitle = $item['competition_title'] ?? 'Cabang Lomba';
            $barcodeVal = $item['barcode'] ?? ($item['reg_code'] ?? 'SM-XIV');
            $certNum = str_pad($item['id'] ?? ($idx + 1), 3, '0', STR_PAD_LEFT);
            $verificationUrl = url('/admin/sertifikat/cetak/' . ($item['id'] ?? 1) . '?target=' . $target . '&rank=' . urlencode($rank) . '&recipient=' . urlencode($recipient));

            // Deteksi Konfigurasi Mandiri Blangko Kustom dari Panitia
            $isJuara = str_contains(strtolower($rank), 'juara') || str_contains(strtolower($rank), 'harapan');
            $isPendamping = ($target === 'mentor' || str_contains(strtolower($rank), 'pendamping'));

            $templateKey = $isJuara ? 'juara' : ($isPendamping ? 'pendamping' : 'peserta');
            $tmpl = $certConfig['templates'][$templateKey] ?? $certConfig;

            $customBg = $tmpl['bg'] ?? ($isJuara ? ($certConfig['bg_juara'] ?? null) : ($isPendamping ? ($certConfig['bg_pendamping'] ?? null) : ($certConfig['bg_peserta'] ?? null)));
            $hasCustomBg = !empty($customBg);

            $showKop = $hasCustomBg ? ($tmpl['show_kop'] ?? false) : true;
            $showSignatures = $tmpl['show_signatures'] ?? true;
            $showSigKetua = $showSignatures && ($tmpl['show_sig_ketua'] ?? true);
            $sigKetuaTitle = $tmpl['sig_ketua_title'] ?? "Mengetahui,\nKetua Panitia Pelaksana,";
            $sigKetuaName = $tmpl['sig_ketua_name'] ?? 'M. FATHUR RAHMAN, S.Pd.';
            $sigKetuaNip = $tmpl['sig_ketua_nip'] ?? 'NIP. -';
            $sigKetuaImage = $tmpl['sig_ketua_image'] ?? '';

            $showSigKepala = $showSignatures && ($tmpl['show_sig_kepala'] ?? true);
            $sigKepalaCityDate = $tmpl['sig_kepala_city_date'] ?? ('Balung, ' . date('d F Y'));
            $sigKepalaTitle = $tmpl['sig_kepala_title'] ?? 'Kepala Madrasah Aliyah,';
            $sigKepalaName = $tmpl['sig_kepala_name'] ?? 'H. NURUL HUDA, S.Ag., M.Pd.I.';
            $sigKepalaNip = $tmpl['sig_kepala_nip'] ?? 'NIP. 197405122005011003';
            $sigKepalaImage = $tmpl['sig_kepala_image'] ?? '';

            $showStamp = $tmpl['show_stamp'] ?? false;
            $stampImage = $tmpl['stamp_image'] ?? '';

            $showQr = $tmpl['show_qr'] ?? true;
            $qrSize = (int)($tmpl['qr_size'] ?? 68);
            $qrPosition = $tmpl['qr_position'] ?? 'center_bottom';
            $qrX = (int)($tmpl['qr_x'] ?? 50);
            $qrY = (int)($tmpl['qr_y'] ?? 85);

            $nameColor = $tmpl['name_color'] ?? '#064e3b';
            $nameSize = (int)($tmpl['name_size'] ?? 26);
            $nameTop = (int)($tmpl['name_top'] ?? 45);
            $fontFamily = $tmpl['name_font_family'] ?? ($tmpl['font_family'] ?? 'Playfair Display');

            $showTextGivenTo = $tmpl['show_text_given_to'] ?? true;
            $textGivenTo = $tmpl['text_given_to'] ?? ($isPendamping ? 'Diberikan dengan rasa terima kasih dan apresiasi setinggi-tingginya kepada:' : 'Diberikan dengan rasa bangga dan apresiasi setinggi-tingginya kepada:');
            $showTextPredicate = $tmpl['show_text_predicate'] ?? true;
            $textPredicate = $tmpl['text_predicate'] ?? ($isPendamping ? 'Sebagai:' : 'Atas partisipasi dan prestasinya sebagai:');
            $predicateTop = (int)($tmpl['predicate_top'] ?? 58);
            $predicateFontFamily = $tmpl['predicate_font_family'] ?? 'Cinzel';
            $predicateSize = (int)($tmpl['predicate_size'] ?? 18);
            $predicateColor = $tmpl['predicate_color'] ?? ($isJuara ? '#d97706' : '#064e3b');

            $showTextComp = $tmpl['show_text_competition'] ?? true;
            $textComp = $tmpl['text_competition'] ?? 'PADA CABANG LOMBA:';
            $compTop = (int)($tmpl['comp_top'] ?? 68);
            $showEventSubtitle = $tmpl['show_event_subtitle'] ?? true;
            $showSchool = $tmpl['show_school'] ?? true;
            $textSchool = $tmpl['text_school'] ?? 'Asal Sekolah :';

            // String CSS Terisolasi Bebas Deteksi Regex VS Code
            $recipientBoxStyle = "top: {$nameTop}%;";
            $recipientNameStyle = "color: {$nameColor}; font-size: {$nameSize}px; font-family: '{$fontFamily}', serif;";
            $predicateBoxStyle = "top: {$predicateTop}%;";
            $predicateTitleStyle = "font-family: '{$predicateFontFamily}', serif; font-size: {$predicateSize}px; color: {$predicateColor}; font-weight: 800; letter-spacing: 1px;";
            $competitionBoxStyle = "top: {$compTop}%;";
            $qrPosClass = 'qr-pos-' . str_replace('_', '-', $qrPosition);
            $qrCustomStyle = ($qrPosition === 'custom') ? "left: {$qrX}%; top: {$qrY}%;" : "";
        @endphp

        <div class="cert-sheet">
            <div class="cert-container {{ $hasCustomBg ? 'has-custom-bg' : '' }}" @if($hasCustomBg) style="background-image: url('{{ $customBg }}');" @endif>
                <div class="cert-outer-border">
                    @if(!$hasCustomBg)
                        <div class="corner corner-tl"></div>
                        <div class="corner corner-tr"></div>
                        <div class="corner corner-bl"></div>
                        <div class="corner corner-br"></div>
                    @endif

                    <div class="cert-inner-border">
                        <!-- Header / KOP -->
                        @if($showKop)
                            <div class="cert-header">
                                <img src="/assets/img/logo.png" alt="Emblem" class="cert-logo">
                                <div class="cert-institution">
                                    <h4>YAYASAN PONDOK PESANTREN WAHID HASYIM</h4>
                                    <h2>MADRASAH ALIYAH UNGGULAN WAHID HASYIM</h2>
                                    <p>Jl. KH. Wachid Hasyim No. 01 Balung, Jember, Jawa Timur 68161 • Email: admin@maswahas.sch.id</p>
                                </div>
                            </div>
                        @endif

                        <!-- Title -->
                        <div class="cert-title-box {{ $showKop ? 'with-kop' : 'without-kop' }}">
                            <h1 class="cert-title">SERTIFIKAT PENGHARGAAN</h1>
                            <div class="cert-number">NOMOR: 421.3/{{ $certNum }}/MAWH/SM-{{ $edition }}/{{ $year }}</div>
                        </div>

                        <!-- Recipient Box (Presisi Dinamis Sesuai name_top) -->
                        <div class="recipient-box" {!! renderCertStyle($recipientBoxStyle) !!}>
                            @if($showTextGivenTo)
                                <div class="cert-body-text" style="margin-bottom: 2px;">
                                    {{ $textGivenTo }}
                                </div>
                            @endif

                            <!-- Recipient Name -->
                            <div class="recipient-name" title="{{ $recipient }}" {!! renderCertStyle($recipientNameStyle) !!}>
                                {{ $recipient }}
                            </div>

                            <!-- Recipient Sub Info (Asal Sekolah) -->
                            @if($showSchool)
                                <div class="recipient-sub">
                                    @if(!empty($textSchool))
                                        {{ $textSchool }} <strong>{{ $schoolName }}</strong>
                                    @else
                                        <strong>{{ $schoolName }}</strong>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <!-- Predicate Box (Presisi Dinamis Sesuai predicate_top) -->
                        <div class="predicate-box" {!! renderCertStyle($predicateBoxStyle) !!}>
                            @if($showTextPredicate)
                                <div class="cert-predicate-intro" style="font-size: 11px; color: #64748b; margin-bottom: 2px;">
                                    {{ $textPredicate }}
                                </div>
                            @endif
                            <div class="predicate-title" {!! renderCertStyle($predicateTitleStyle) !!}>
                                SEBAGAI {{ strtoupper($rank) }}
                            </div>
                        </div>

                        <!-- Competition Box (Presisi Dinamis Sesuai comp_top) -->
                        <div class="competition-box" {!! renderCertStyle($competitionBoxStyle) !!}>
                            <div class="competition-title">
                                @if($showTextComp)
                                    {{ $textComp }}
                                @endif
                                {{ strtoupper($compTitle) }}
                            </div>
                            @if($showEventSubtitle)
                                <div class="event-subtitle">
                                    Dalam Rangkaian Ajang Perlombaan <strong>"Semarak Aliyah {{ $edition }} {{ $year }}"</strong> se-Jawa Timur yang Diselenggarakan oleh MA Unggulan Wahid Hasyim Balung.
                                </div>
                            @endif
                        </div>

                        <!-- Signatures & Modern 2D QR Code -->
                        @if($showSignatures && ($showSigKetua || $showSigKepala))
                            <div class="signatures-grid">
                                <!-- Kolom TTD Ketua Panitia -->
                                <div class="sig-col {{ $showSigKetua ? '' : 'sig-hidden' }}">
                                    <div class="sig-title">{!! nl2br(e($sigKetuaTitle)) !!}</div>
                                    <div style="height: 52px; display: flex; align-items: center; justify-content: center;">
                                        @if(!empty($sigKetuaImage))
                                            <img src="{{ $sigKetuaImage }}" alt="TTD Ketua" style="max-height: 52px; max-width: 140px; object-fit: contain;" />
                                        @endif
                                    </div>
                                    <div class="sig-name">{{ $sigKetuaName }}</div>
                                    <div class="sig-nip">{{ $sigKetuaNip }}</div>
                                </div>

                                <!-- QR Code di Tengah Bawah (Jika dipilih) -->
                                @if($showQr && $qrPosition === 'center_bottom')
                                    <div class="cert-qr-box" style="margin-bottom: 0;">
                                        <canvas class="cert-qr-canvas" data-qr="{{ $verificationUrl }}" data-size="{{ $qrSize }}" width="{{ $qrSize }}" height="{{ $qrSize }}"></canvas>
                                        <div class="cert-qr-caption">
                                            <span class="cert-qr-id">ID: {{ $barcodeVal }}</span>
                                            <span class="cert-qr-text">Verifikasi Resmi</span>
                                        </div>
                                    </div>
                                @else
                                    <div style="width: 70px;"></div>
                                @endif

                                <!-- Kolom TTD Kepala Madrasah -->
                                <div class="sig-col {{ $showSigKepala ? '' : 'sig-hidden' }} sig-col-relative">
                                    @if($showStamp && !empty($stampImage))
                                        <img src="{{ $stampImage }}" alt="Stempel" style="position: absolute; left: -25px; top: 12px; width: 85px; height: 85px; object-fit: contain; pointer-events: none; opacity: 0.85; transform: rotate(-8deg); z-index: 3;" />
                                    @endif
                                    <div class="sig-date">{{ $sigKepalaCityDate }}</div>
                                    <div class="sig-title">{!! nl2br(e($sigKepalaTitle)) !!}</div>
                                    <div style="height: 52px; display: flex; align-items: center; justify-content: center;">
                                        @if(!empty($sigKepalaImage))
                                            <img src="{{ $sigKepalaImage }}" alt="TTD Kepala" style="max-height: 52px; max-width: 140px; object-fit: contain; position: relative; z-index: 2;" />
                                        @endif
                                    </div>
                                    <div class="sig-name" style="position: relative; z-index: 2;">{{ $sigKepalaName }}</div>
                                    <div class="sig-nip" style="position: relative; z-index: 2;">{{ $sigKepalaNip }}</div>
                                </div>
                            </div>
                        @endif

                        <!-- QR Code Jika Diposisikan Bebas / Sudut (Bukan center_bottom) -->
                        @if($showQr && $qrPosition !== 'center_bottom')
                            <div class="cert-qr-box {{ $qrPosClass }}" {!! $qrCustomStyle ? renderCertStyle($qrCustomStyle) : '' !!}>
                                <canvas class="cert-qr-canvas" data-qr="{{ $verificationUrl }}" data-size="{{ $qrSize }}" width="{{ $qrSize }}" height="{{ $qrSize }}"></canvas>
                                <div class="cert-qr-caption">
                                    <span class="cert-qr-id">ID: {{ $barcodeVal }}</span>
                                    <span class="cert-qr-text">Verifikasi Resmi</span>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const qrElements = document.querySelectorAll('.cert-qr-canvas');
            qrElements.forEach((canvas) => {
                const qrText = canvas.getAttribute('data-qr');
                const qrSize = parseInt(canvas.getAttribute('data-size') || '68', 10);
                if (qrText && window.QRCode) {
                    try {
                        window.QRCode.toCanvas(canvas, qrText, {
                            width: qrSize,
                            margin: 1,
                            color: {
                                dark: '#064e3b',
                                light: '#ffffff'
                            }
                        }, (err) => {
                            if (err) console.error('QRCode render error:', err);
                        });
                    } catch (e) {
                        console.error('QRCode exception:', e);
                    }
                }
            });
        });
    </script>
</body>
</html>
