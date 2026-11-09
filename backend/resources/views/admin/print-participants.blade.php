<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Nominatif Rincian Peserta (Siswa) - Semarak Aliyah XIV</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/assets/img/logo.png">

    <!-- Native A4 Landscape Hardware Page Box Locking -->
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 10mm;
        }
    </style>

    <!-- Local Offline Fonts & Icons -->
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/vendor/remixicon/remixicon.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #1e293b;
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            color: #0f172a;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Top Control Bar (Screen Only) */
        .no-print {
            width: 100%;
            background-color: #0f172a;
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35);
        }

        .no-print-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .no-print-title {
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
        }

        .no-print-badge {
            background-color: rgba(59, 130, 246, 0.2);
            color: #93c5fd;
            border: 1px solid rgba(59, 130, 246, 0.4);
            font-size: 11px;
            padding: 2px 10px;
            border-radius: 9999px;
            font-weight: 500;
        }

        .no-print-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-close-view {
            background-color: transparent;
            color: #cbd5e1;
            border: 1px solid #475569;
        }

        .btn-close-view:hover {
            background-color: #334155;
            color: #ffffff;
        }

        .btn-print-now {
            background-color: #10b981;
            color: #ffffff;
            border: 1px solid #059669;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
        }

        .btn-print-now:hover {
            background-color: #059669;
        }

        /* Screen Desk Workspace Container */
        .screen-desk {
            padding: 30px 16px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: calc(100vh - 60px);
        }

        /* Authentic A4 Landscape Paper Sheet (297 × 210 mm) */
        .paper-sheet {
            background-color: #ffffff;
            color: #0f172a;
            width: 100%;
            max-width: 1120px;
            min-width: 980px;
            padding: 32px 40px;
            border-radius: 4px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.45);
            margin: 0 auto;
        }

        /* Kop Surat Resmi Madrasah & Kepanitiaan */
        .print-kop-wrapper {
            margin-bottom: 10px;
        }

        .print-kop-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            text-align: center;
        }

        .print-kop-logo {
            width: 65px;
            height: 65px;
            flex-shrink: 0;
        }

        .print-logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .print-kop-text {
            flex-grow: 1;
        }

        .print-kop-inst {
            font-size: 11pt;
            font-weight: 700;
            color: #0f172a;
            margin: 1px 0;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .print-kop-school {
            font-size: 13.5pt;
            font-weight: 800;
            color: #0f172a;
            margin: 2px 0;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .print-kop-addr {
            font-size: 7.5pt;
            color: #475569;
            margin: 0;
        }

        .print-kop-contact {
            font-size: 7.5pt;
            color: #475569;
            margin: 1px 0 0 0;
        }

        .print-double-divider {
            border-top: 2.5px solid #0f172a;
            border-bottom: 1px solid #0f172a;
            height: 4px;
            margin: 6px 0 10px 0;
        }

        /* Judul Dokumen Resmi */
        .print-doc-header {
            text-align: center;
            margin-bottom: 10px;
        }

        .print-doc-title {
            font-size: 12pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            margin: 0 0 6px 0;
            letter-spacing: 0.5px;
        }

        .print-meta-grid {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: transparent;
            border: 1px solid #0f172a;
            border-radius: 4px;
            padding: 5px 12px;
            font-size: 7.5pt;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .print-meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .print-meta-label {
            font-weight: 600;
            color: #0f172a;
        }

        .print-meta-val {
            font-weight: 700;
            color: #0f172a;
        }

        /* Formal Table Styling */
        .formal-print-table {
            width: 100%;
            border-collapse: collapse;
        }

        .formal-print-table th,
        .formal-print-table td {
            border: 1px solid #0f172a;
            padding: 3.5px 5px;
            font-size: 7.8pt;
            line-height: 1.25;
            color: #0f172a;
            background: transparent;
        }

        .formal-print-table th {
            font-weight: 700;
            text-align: left;
            text-transform: uppercase;
            background: transparent;
        }

        .formal-print-table thead {
            display: table-header-group;
        }

        .formal-print-table tr {
            page-break-inside: avoid;
        }

        .print-sub-text {
            font-size: 7pt;
            color: #475569;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: 700; }
        .fw-semibold { font-weight: 600; }
        .font-monospace { font-family: monospace, monospace; }

        /* Signatures Block */
        .print-signatures-block {
            display: flex;
            justify-content: space-between;
            margin-top: 22px;
            page-break-inside: avoid;
            font-size: 8.5pt;
            width: 100%;
        }

        .print-sig-col {
            width: 320px;
            text-align: center;
        }

        .print-sig-title {
            margin-bottom: 2px;
        }

        .print-sig-role {
            font-weight: 700;
            margin-bottom: 0;
        }

        .print-sig-space {
            height: 52px;
        }

        .print-sig-name {
            font-weight: 700;
            margin-bottom: 1px;
        }

        .print-sig-note {
            font-size: 7.5pt;
            color: #475569;
            margin: 0;
        }

        /* Pure W3C A4 Landscape Media Specification */
        @page {
            size: A4 landscape;
            margin: 8mm 10mm;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm 10mm;
            }

            html, body {
                width: 100% !important;
                max-width: 297mm !important;
                background: #ffffff !important;
                color: #0f172a !important;
                margin: 0 !important;
                padding: 0 !important;
                transform: none !important;
                -webkit-transform: none !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }

            .screen-desk {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
                width: 100% !important;
                min-height: auto !important;
                background: transparent !important;
            }

            .paper-sheet {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                min-width: 0 !important;
                width: 100% !important;
                border: none !important;
                background: transparent !important;
                transform: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- Bilah Kontrol Aksi (Screen Only) -->
    <div class="no-print">
        <div class="no-print-left">
            <span class="no-print-title">
                <i class="ri-printer-line" style="margin-right: 6px;"></i> Pratinjau Dokumen Cetak Peserta (Siswa)
            </span>
            <span class="no-print-badge">
                <i class="ri-lock-line" style="margin-right: 4px;"></i> A4 Lanskap (297 × 210 mm) • Terkunci Otomatis
            </span>
        </div>
        <div class="no-print-actions">
            <button type="button" onclick="window.close()" class="btn-action btn-close-view" title="Tutup halaman pratinjau">
                <i class="ri-close-line"></i> Tutup
            </button>
            <a href="/admin/peserta/download-pdf?{{ request()->getQueryString() }}" class="btn-action" style="background-color: #ef4444; color: #ffffff; border: 1px solid #dc2626; box-shadow: 0 2px 6px rgba(239, 68, 68, 0.35);" title="Download file PDF A4 Landscape murni secara langsung">
                <i class="ri-file-pdf-2-line"></i> Download PDF (Lanskap Murni)
            </a>
            <button type="button" onclick="window.print()" class="btn-action btn-print-now" title="Cetak atau Simpan sebagai PDF">
                <i class="ri-printer-fill"></i> Cetak Dokumen (Ctrl + P)
            </button>
        </div>
    </div>

    <!-- Bidang Meja Lembar Kertas -->
    <div class="screen-desk">
        <div class="paper-sheet">
            <!-- 1. Kop Surat Resmi Madrasah & Kepanitiaan (Dinamis dari Profil Resmi Lembaga) -->
            <div class="print-kop-wrapper">
                <div class="print-kop-inner">
                    <!-- Logo Kiri -->
                    <div class="print-kop-logo">
                        <img 
                            src="{{ $letterhead['logo_url'] ?: '/assets/img/logo.png' }}" 
                            alt="Logo Madrasah" 
                            class="print-logo-img" 
                            onerror="this.style.display='none'" 
                        />
                    </div>

                    <!-- Teks KOP Lengkap Sesuai Database Setting Lembaga -->
                    <div class="print-kop-text">
                        <h4 class="print-kop-inst">{{ $letterhead['committee_name'] ?: 'PANITIA PELAKSANA SEMARAK ALIYAH XIV TAHUN 2026' }}</h4>
                        <h2 class="print-kop-school">{{ $letterhead['school_name'] ?: 'MADRASAH ALIYAH UNGGULAN WAHID HASYIM BALUNG' }}</h2>
                        @if(!empty($letterhead['address']))
                            <p class="print-kop-addr">{{ $letterhead['address'] }}</p>
                        @endif
                        <p class="print-kop-contact">
                            @if(!empty($letterhead['website']))
                                <span>Website: {{ $letterhead['website'] }}</span>
                            @endif
                            @if(!empty($letterhead['website']) && !empty($letterhead['email']))
                                <span> &bull; </span>
                            @endif
                            @if(!empty($letterhead['email']))
                                <span>Email: {{ $letterhead['email'] }}</span>
                            @endif
                            @if((!empty($letterhead['website']) || !empty($letterhead['email'])) && (!empty($letterhead['wa']) || !empty($letterhead['phone'])))
                                <span> &bull; </span>
                            @endif
                            @if(!empty($letterhead['wa']) || !empty($letterhead['phone']))
                                <span>Telp/WA: {{ $letterhead['wa'] ?: $letterhead['phone'] }}</span>
                            @endif
                        </p>
                    </div>

                    <!-- Logo Kanan (Logo Lembaga/Event) -->
                    @if(!empty($letterhead['logo_url']))
                        <div class="print-kop-logo">
                            <img 
                                src="{{ $letterhead['logo_url'] }}" 
                                alt="Logo Event" 
                                class="print-logo-img" 
                                onerror="this.style.display='none'" 
                            />
                        </div>
                    @endif
                </div>
                <!-- Garis Ganda Resmi Pembatas Kop Surat -->
                <div class="print-double-divider"></div>
            </div>

            <!-- 2. Judul Dokumen Resmi & Rincian Parameter -->
            <div class="print-doc-header">
                <h3 class="print-doc-title">
                    @if(!empty($activeCompetition))
                        FORMULIR & DAFTAR PESERTA CABANG LOMBA: {{ strtoupper($activeCompetitionTitle) }}
                    @else
                        DAFTAR NOMINATIF RINCIAN PESERTA (SISWA)
                    @endif
                </h3>
                <div class="print-meta-grid">
                    <div class="print-meta-item">
                        <span class="print-meta-label">Cabang Lomba:</span>
                        <span class="print-meta-val">{{ $activeCompetitionTitle }}</span>
                    </div>
                    <div class="print-meta-item">
                        <span class="print-meta-label">Jenis Kepesertaan:</span>
                        <span class="print-meta-val">{{ $activeTypeLabel }}</span>
                    </div>
                    <div class="print-meta-item">
                        <span class="print-meta-label">Total Nominatif:</span>
                        <span class="print-meta-val">{{ count($participants) }} Siswa</span>
                    </div>
                    <div class="print-meta-item text-end">
                        <span class="print-meta-label">Waktu Cetak:</span>
                        <span class="print-meta-val">{{ $formattedCurrentTime }}</span>
                    </div>
                </div>
            </div>

            <!-- 3. Tabel Nominatif Resmi Proporsional A4 Landscape -->
            <table class="formal-print-table">
                <thead>
                    <tr>
                        <th style="width: 32px;" class="text-center">No</th>
                        @if(!empty($activeCompetition))
                            <th style="width: 55px;" class="text-center">No. TM</th>
                            <th style="width: 75px;" class="text-center">No. Reg</th>
                            <th style="width: 190px;">Nama Lengkap Siswa</th>
                            <th style="width: 90px;" class="text-center">NISN</th>
                            <th style="width: 36px;" class="text-center">L/P</th>
                            <th style="width: 45px;" class="text-center">Kelas</th>
                            <th style="min-width: 180px;">Asal Sekolah / Madrasah</th>
                            <th style="width: 85px;" class="text-center">Peran Tim</th>
                            <th style="width: 110px;" class="text-center">Pembina & WA</th>
                            <th style="width: 65px;" class="text-center">Status</th>
                            <th style="width: 110px;" class="text-center">Kehadiran / Nilai</th>
                        @else
                            <th style="width: 210px;">Nama Lengkap Siswa</th>
                            <th style="width: 95px;" class="text-center">NISN</th>
                            <th style="width: 38px;" class="text-center">L/P</th>
                            <th style="width: 50px;" class="text-center">Kelas</th>
                            <th style="min-width: 170px;">Asal Sekolah / Madrasah</th>
                            <th style="min-width: 160px;">Cabang Lomba</th>
                            <th style="width: 65px;" class="text-center">Jenis</th>
                            <th style="width: 90px;" class="text-center">Peran Tim</th>
                            <th style="width: 80px;" class="text-center">No. Reg</th>
                            <th style="width: 105px;" class="text-center">No. WhatsApp</th>
                            <th style="width: 75px;" class="text-center">Status</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($participants as $idx => $st)
                        <tr>
                            <td class="text-center font-monospace">{{ $idx + 1 }}</td>
                            @if(!empty($activeCompetition))
                                <td class="text-center font-monospace fw-bold" style="color: #1e3a8a;">
                                    {{ !empty($st['draw_number']) ? '#' . $st['draw_number'] : '-' }}
                                </td>
                                <td class="text-center font-monospace">#{{ $st['reg_code'] }}</td>
                                <td class="fw-bold">{{ $st['name'] }}</td>
                                <td class="text-center font-monospace">{{ $st['nisn'] ?: '-' }}</td>
                                <td class="text-center">
                                    @if($st['gender'] === 'Perempuan') P @elseif($st['gender'] === 'Laki-laki') L @else - @endif
                                </td>
                                <td class="text-center">{{ $st['grade'] ?: '-' }}</td>
                                <td>
                                    <div>{{ $st['school_name'] }}</div>
                                    @if(!empty($st['school_district']))
                                        <div class="print-sub-text">Kec. {{ $st['school_district'] }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(!empty($st['is_captain']))
                                        <span class="fw-bold">{{ $st['team_role'] ?: 'Ketua' }}</span>
                                    @else
                                        <span>{{ $st['team_role'] ?: 'Anggota' }}</span>
                                    @endif
                                </td>
                                <td class="text-center font-monospace" style="font-size: 8.5px;">
                                    <div>{{ $st['mentor_name'] ?: '-' }}</div>
                                    <div>{{ $st['mentor_wa'] ?: '-' }}</div>
                                </td>
                                <td class="text-center">{{ $st['payment_status'] ?: 'Menunggu' }}</td>
                                <td class="text-center" style="background-color: #fafafa;"></td>
                            @else
                                <td class="fw-bold">{{ $st['name'] }}</td>
                                <td class="text-center font-monospace">{{ $st['nisn'] ?: '-' }}</td>
                                <td class="text-center">
                                    @if($st['gender'] === 'Perempuan') P @elseif($st['gender'] === 'Laki-laki') L @else - @endif
                                </td>
                                <td class="text-center">{{ $st['grade'] ?: '-' }}</td>
                                <td>
                                    <div>{{ $st['school_name'] }}</div>
                                    @if(!empty($st['school_district']))
                                        <div class="print-sub-text">Kec. {{ $st['school_district'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $st['competition_title'] }}</div>
                                </td>
                                <td class="text-center">
                                    {{ $st['participant_type'] === 'tim' ? 'Tim' : 'Individu' }}
                                </td>
                                <td class="text-center">
                                    @if(!empty($st['is_captain']))
                                        <span class="fw-bold">{{ $st['team_role'] ?: 'Ketua' }}</span>
                                    @else
                                        <span>{{ $st['team_role'] ?: 'Anggota' }}</span>
                                    @endif
                                </td>
                                <td class="text-center font-monospace">#{{ $st['reg_code'] }}</td>
                                <td class="text-center font-monospace">{{ $st['wa_number'] ?: '-' }}</td>
                                <td class="text-center">{{ $st['payment_status'] ?: 'Menunggu' }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ !empty($activeCompetition) ? 12 : 12 }}" class="text-center" style="padding: 24px;">
                                Tidak ada data peserta siswa yang memenuhi kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- 4. Lembar Pengesahan Tanda Tangan Panitia (Melebar Lanskap) -->
            <div class="print-signatures-block">
                @if(!empty($activeCompetition))
                    <div class="print-sig-col">
                        <p class="print-sig-title">Mengetahui,</p>
                        <p class="print-sig-role">Koordinator Cabang: {{ $activeCompetitionTitle }}</p>
                        <div class="print-sig-space"></div>
                        <p class="print-sig-name">( ................................................................ )</p>
                        <p class="print-sig-note">Panitia Pelaksana Semarak Aliyah XIV</p>
                    </div>

                    <div class="print-sig-col" style="margin-left: auto;">
                        <p class="print-sig-title">{{ $letterhead['city'] ?? 'Jember' }}, {{ $currentDateFormatted }}</p>
                        <p class="print-sig-role">Dewan Juri / Penanggung Jawab Arena</p>
                        <div class="print-sig-space"></div>
                        <p class="print-sig-name">( ................................................................ )</p>
                        <p class="print-sig-note">Dewan Juri Perlombaan</p>
                    </div>
                @else
                    <div class="print-sig-col">
                        <p class="print-sig-title">Mengetahui,</p>
                        <p class="print-sig-role">Koordinator Pendaftaran & Administrasi</p>
                        <div class="print-sig-space"></div>
                        <p class="print-sig-name">( ................................................................ )</p>
                        <p class="print-sig-note">Panitia Pelaksana Semarak Aliyah XIV</p>
                    </div>

                    <div class="print-sig-col" style="margin-left: auto;">
                        <p class="print-sig-title">{{ $letterhead['city'] ?? 'Jember' }}, {{ $currentDateFormatted }}</p>
                        <p class="print-sig-role">Ketua Panitia Pelaksana Semarak Aliyah XIV</p>
                        <div class="print-sig-space"></div>
                        <p class="print-sig-name">( ................................................................ )</p>
                        <p class="print-sig-note">Ketua Panitia Pelaksana</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Skrip Otomatis Pemicu Cetak & Shortcut -->
    <script>
        window.addEventListener('load', function() {
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(function() {
                    setTimeout(function() {
                        window.print();
                    }, 300);
                });
            } else {
                setTimeout(function() {
                    window.print();
                }, 500);
            }
        });

        window.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>

