<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak ID Card Peserta | Semarak Aliyah XII 2026</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/assets/img/logo.png">

    <!-- Local Offline Fonts & Icons -->
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/vendor/remixicon/remixicon.css">

    <!-- JsBarcode library for crisp vector barcode rendering (Local Offline) -->
    <script src="/assets/js/JsBarcode.all.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #f1f5f9;
            font-family: 'Poppins', sans-serif;
            color: #1e293b;
        }

        .no-print {
            background-color: #0f172a;
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
            transition: all 0.2s;
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
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-back:hover {
            color: #ffffff;
            border-color: #94a3b8;
        }

        /* Print Container Grid */
        .cards-container {
            max-width: 960px;
            margin: 30px auto;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            padding: 0 16px 40px;
        }

        /* Lanyard Card (Approx 8.5cm x 13cm) */
        .id-card {
            width: 100%;
            height: 480px;
            background: #ffffff;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            page-break-inside: avoid;
        }

        /* Lanyard Hole Mockup */
        .lanyard-slot {
            width: 32px;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            margin: 8px auto 0;
            z-index: 10;
        }

        .id-card-header {
            background: linear-gradient(135deg, #064e3b 0%, #047857 100%);
            color: #ffffff;
            padding: 12px 16px;
            text-align: center;
            border-bottom: 3px solid #fbbf24;
            position: relative;
        }

        .id-card-header img {
            width: 38px;
            height: 38px;
            object-fit: contain;
            margin-bottom: 4px;
        }

        .id-card-header h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .id-card-header p {
            font-size: 10px;
            color: #a7f3d0;
            letter-spacing: 0.5px;
        }

        .id-card-body {
            padding: 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            justify-content: space-between;
        }

        .category-badge {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .school-name {
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin: 6px 0 2px;
            line-height: 1.25;
            max-height: 42px;
            overflow: hidden;
        }

        .mentor-info {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 6px;
        }

        .participants-summary {
            font-size: 11px;
            color: #334155;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 6px 12px;
            border-radius: 6px;
            width: 100%;
            margin-bottom: 8px;
            max-height: 48px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .barcode-wrapper {
            margin-top: auto;
            text-align: center;
            width: 100%;
        }

        .barcode-svg {
            max-width: 90%;
            height: 45px;
        }

        .id-card-footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 6px 12px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media print {
            body {
                background: none;
                color: #000000;
            }
            .no-print {
                display: none !important;
            }
            .cards-container {
                max-width: 100%;
                margin: 0;
                padding: 0;
                gap: 12px;
            }
            .id-card {
                border: 1px solid #94a3b8;
                box-shadow: none;
                page-break-inside: avoid;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>

    <!-- Print Control Bar -->
    <div class="no-print">
        <div>
            <strong>Antrian Cetak ID Card ({{ count($participants) }} Peserta)</strong>
            <span style="color: #94a3b8; font-size: 12px; margin-left: 8px;">Ukuran Standar Lanyard A4 (2 kartu per baris)</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('admin.print.index') }}" class="btn-back">
                <i class="ri-arrow-left-line"></i> Kembali ke Print Studio
            </a>
            <button onclick="window.print()" class="btn-print">
                <i class="ri-printer-line"></i> Cetak Dokumen Sekarang
            </button>
        </div>
    </div>

    <!-- Cards Layout Grid -->
    <div class="cards-container">
        @forelse($participants as $p)
            @php
                $barcodeVal = $p->barcode ?? $p->reg_code;
                $names = is_array($p->participants) ? $p->participants : json_decode($p->participants, true) ?? [];
                $namesStr = collect($names)->map(function($item) {
                    return is_array($item) ? ($item['name'] ?? '') : $item;
                })->filter()->implode(', ');
            @endphp
            <div class="id-card">
                <div class="lanyard-slot"></div>
                <div class="id-card-header">
                    <img src="/assets/img/logo.png" alt="Logo">
                    <h2>SEMARAK ALIYAH XII 2026</h2>
                    <p>MA UNGGULAN WAHID HASYIM BALUNG</p>
                </div>

                <div class="id-card-body">
                    <div class="category-badge">
                        {{ strtoupper($p->competition_id) }}
                    </div>

                    <div style="width: 100%;">
                        <div class="school-name">{{ $p->school_name }}</div>
                        <div class="mentor-info">
                            Official: <strong>{{ $p->mentor_name }}</strong>
                        </div>
                    </div>

                    <div class="participants-summary">
                        <strong>Peserta:</strong> {{ $namesStr ?: 'Kontingen Sekolah' }}
                    </div>

                    <div class="barcode-wrapper">
                        <svg class="barcode-svg" data-code="{{ $barcodeVal }}"></svg>
                    </div>
                </div>

                <div class="id-card-footer">
                    WAJIB DIBAWA SAAT REGISTRASI & VERIFIKASI MEJA CHECK-IN
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px;">
                <p>Tidak ada data peserta yang dipilih untuk dicetak.</p>
            </div>
        @endforelse
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Render vector barcodes for each card
            const barcodeElements = document.querySelectorAll('.barcode-svg');
            barcodeElements.forEach(el => {
                const code = el.getAttribute('data-code');
                if (code) {
                    try {
                        JsBarcode(el, code, {
                            format: "CODE128",
                            lineColor: "#0f172a",
                            width: 1.6,
                            height: 38,
                            displayValue: true,
                            fontSize: 11,
                            font: "Inter",
                            textMargin: 2
                        });
                    } catch (e) {
                        console.error('Barcode error:', e);
                    }
                }
            });
        });
    </script>
</body>
</html>
