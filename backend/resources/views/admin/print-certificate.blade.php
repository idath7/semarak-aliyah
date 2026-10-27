<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Sertifikat Resmi - {{ $reg->school_name }} | Semarak Aliyah XII 2026</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/assets/img/logo.png">

    <!-- Local Offline Fonts & Icons -->
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/vendor/remixicon/remixicon.css">
    <link rel="stylesheet" href="/assets/vendor/boxicons/css/boxicons.min.css">
    <script src="/assets/js/JsBarcode.all.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #334155;
            font-family: 'Poppins', sans-serif;
            color: #0f172a;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .no-print {
            width: 100%;
            background-color: #0f172a;
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        }

        .btn-print {
            background-color: #10b981;
            color: #ffffff;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .btn-print:hover {
            background-color: #059669;
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
        }

        /* Certificate A4 Landscape (297mm x 210mm) */
        .cert-container {
            width: 1050px;
            height: 742px;
            margin: 24px auto;
            background: #ffffff;
            padding: 24px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.35);
        }

        .cert-outer-border {
            width: 100%;
            height: 100%;
            border: 4px solid #d97706;
            padding: 8px;
            position: relative;
        }

        .cert-inner-border {
            width: 100%;
            height: 100%;
            border: 1.5px solid #064e3b;
            padding: 30px 40px;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            background: radial-gradient(circle, #ffffff 60%, #fdfbf7 100%);
        }

        /* Corner Ornaments */
        .corner {
            position: absolute;
            width: 36px;
            height: 36px;
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
            margin-bottom: 12px;
            width: 100%;
            border-bottom: 2px solid #064e3b;
            padding-bottom: 12px;
        }

        .cert-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }

        .cert-institution h4 {
            font-size: 13px;
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
            margin: 10px 0 6px;
        }
        .cert-title {
            font-family: 'Cinzel', serif;
            font-size: 32px;
            font-weight: 900;
            color: #d97706;
            letter-spacing: 4px;
            text-transform: uppercase;
        }
        .cert-number {
            font-size: 11px;
            letter-spacing: 1px;
            color: #64748b;
            margin-top: 2px;
        }

        .cert-body-text {
            font-size: 13px;
            color: #475569;
            margin: 10px 0 4px;
        }

        .recipient-name {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 800;
            color: #064e3b;
            margin: 4px 0;
            text-transform: uppercase;
            border-bottom: 1.5px solid #d97706;
            display: inline-block;
            padding: 0 20px 4px;
        }

        .predicate-box {
            margin: 12px 0 8px;
        }
        .predicate-badge {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            color: #ffffff;
            font-family: 'Cinzel', serif;
            font-size: 16px;
            font-weight: 800;
            padding: 6px 24px;
            border-radius: 30px;
            letter-spacing: 2px;
            box-shadow: 0 4px 10px rgba(217, 119, 6, 0.25);
            display: inline-block;
        }

        .competition-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 8px;
        }
        .event-subtitle {
            font-size: 12px;
            color: #64748b;
            max-width: 650px;
            margin: 2px auto 0;
            line-height: 1.4;
        }

        /* Signatures Grid */
        .signatures-grid {
            width: 100%;
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 30px;
        }

        .sig-col {
            text-align: center;
            width: 250px;
        }
        .sig-col p {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 50px;
        }
        .sig-name {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            text-decoration: underline;
        }
        .sig-nip {
            font-size: 10px;
            color: #64748b;
        }

        .cert-stamp-box {
            text-align: center;
        }
        .cert-barcode-svg {
            height: 32px;
            max-width: 150px;
        }

        @media print {
            body {
                background: none;
                color: #000000;
            }
            .no-print {
                display: none !important;
            }
            .cert-container {
                width: 100%;
                height: 100vh;
                margin: 0;
                padding: 10mm;
                box-shadow: none;
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
        <div>
            <strong>Preview Sertifikat Resmi (A4 Landscape)</strong>
            <span style="color: #94a3b8; font-size: 12px; margin-left: 8px;">Penerima: {{ $reg->school_name }} • Predikat: {{ $rank }}</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('admin.print.index') }}" class="btn-back">
                <i class="ri-arrow-left-line"></i> Kembali
            </a>
            <button onclick="window.print()" class="btn-print">
                <i class="ri-printer-line"></i> Cetak Sertifikat Sekarang
            </button>
        </div>
    </div>

    <!-- Certificate Document Container -->
    <div class="cert-container">
        <div class="cert-outer-border">
            <div class="corner corner-tl"></div>
            <div class="corner corner-tr"></div>
            <div class="corner corner-bl"></div>
            <div class="corner corner-br"></div>

            <div class="cert-inner-border">
                <!-- Header -->
                <div class="cert-header">
                    <img src="/assets/img/logo.png" alt="Emblem" class="cert-logo">
                    <div class="cert-institution">
                        <h4>YAYASAN PONDOK PESANTREN WAHID HASYIM</h4>
                        <h2>MADRASAH ALIYAH UNGGULAN WAHID HASYIM</h2>
                        <p>Jl. KH. Wachid Hasyim No. 01 Balung, Jember, Jawa Timur 68161 • Email: admin@maswahas.sch.id</p>
                    </div>
                </div>

                <!-- Title -->
                <div class="cert-title-box">
                    <h1 class="cert-title">SERTIFIKAT PENGHARGAAN</h1>
                    <div class="cert-number">Nomor: 421.3/SEMARAK-XII/MASWHA/X/2026</div>
                </div>

                <div class="cert-body-text">
                    Diberikan dengan rasa bangga dan apresiasi setinggi-tingginya kepada:
                </div>

                <!-- Recipient -->
                <div class="recipient-name">
                    {{ $reg->school_name }}
                </div>

                <!-- Predicate -->
                <div class="predicate-box">
                    <span class="predicate-badge">
                        SEBAGAI {{ strtoupper($rank) }}
                    </span>
                </div>

                <!-- Competition details -->
                <div class="competition-title">
                    PADA CABANG LOMBA: {{ strtoupper($reg->competition->title ?? $reg->competition_id) }}
                </div>
                <div class="event-subtitle">
                    Dalam Rangkaian Ajang Perlombaan <strong>"Semarak Aliyah XII 2026"</strong> Tingkat SMP/MTs Sederajat se-Jawa Timur yang Diselenggarakan oleh MA Unggulan Wahid Hasyim Balung.
                </div>

                <!-- Signatures -->
                <div class="signatures-grid">
                    <div class="sig-col">
                        <p>Mengetahui,<br>Ketua Panitia Pelaksana,</p>
                        <div class="sig-name">M. FATHUR RAHMAN, S.Pd.</div>
                        <div class="sig-nip">NIP. -</div>
                    </div>

                    <div class="cert-stamp-box">
                        <svg class="cert-barcode-svg" id="cert-barcode"></svg>
                        <div style="font-size: 8px; color: #94a3b8; letter-spacing: 0.5px; margin-top: 2px;">
                            ID VERIFIKASI: {{ $reg->barcode ?? $reg->reg_code }}
                        </div>
                    </div>

                    <div class="sig-col">
                        <p>Balung, 20 Oktober 2026<br>Kepala Madrasah Aliyah,</p>
                        <div class="sig-name">H. NURUL HUDA, S.Ag., M.Pd.I.</div>
                        <div class="sig-nip">NIP. 197405122005011003</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const barcodeEl = document.getElementById('cert-barcode');
            if (barcodeEl) {
                JsBarcode(barcodeEl, "{{ $reg->barcode ?? $reg->reg_code }}", {
                    format: "CODE128",
                    lineColor: "#0f172a",
                    width: 1.2,
                    height: 28,
                    displayValue: false
                });
            }
        });
    </script>
</body>
</html>
