<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Formulir Kontingen {{ $schoolName }} - Semarak Aliyah XIV</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/assets/img/logo.png">

    <!-- Native A4 Portrait Hardware Locking -->
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm;
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

        /* Top Action Control Bar (Screen Only) */
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

        .btn-download-pdf {
            background-color: #ef4444;
            color: #ffffff;
            border: 1px solid #dc2626;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.35);
        }

        .btn-download-pdf:hover {
            background-color: #dc2626;
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

        /* Paper Sheet Layout (A4 Portrait) */
        .paper-sheet {
            background-color: #ffffff;
            width: 210mm;
            min-height: 297mm;
            padding: 14mm 16mm;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            border-radius: 4px;
            position: relative;
            box-sizing: border-box;
            color: #0f172a;
        }

        /* KOP SURAT */
        .print-kop-wrapper {
            margin-bottom: 12px;
        }

        .print-kop-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .print-kop-logo {
            flex-shrink: 0;
            width: 68px;
            text-align: center;
        }

        .print-logo-img {
            max-width: 68px;
            max-height: 68px;
            object-fit: contain;
        }

        .print-kop-text {
            flex-grow: 1;
            text-align: center;
        }

        .print-kop-inst {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .print-kop-school {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            color: #1e3a8a;
            margin-bottom: 3px;
        }

        .print-kop-addr {
            font-size: 9.5px;
            color: #334155;
            line-height: 1.35;
            margin-bottom: 2px;
        }

        .print-kop-contact {
            font-size: 9px;
            color: #64748b;
        }

        .print-double-divider {
            margin-top: 8px;
            border-top: 2.5px solid #0f172a;
            border-bottom: 1px solid #0f172a;
            height: 3px;
        }

        /* DOCUMENT TITLE */
        .print-doc-header {
            text-align: center;
            margin: 12px 0 14px 0;
        }

        .print-doc-title {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .print-doc-subtitle {
            font-size: 11px;
            font-weight: 600;
            color: #475569;
        }

        /* CONTINGENT PROFILE CARD */
        .contingent-profile {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 14px;
            font-size: 10.5px;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 20px;
        }

        .profile-item {
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        .profile-label {
            font-weight: 600;
            color: #475569;
            min-width: 110px;
        }

        .profile-val {
            font-weight: 700;
            color: #0f172a;
        }

        /* SECTION HEADER */
        .section-header {
            background-color: #f1f5f9;
            border-left: 4px solid #1e3a8a;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 8px;
            margin-top: 12px;
        }

        /* FORMAL TABLE */
        .formal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            margin-bottom: 14px;
        }

        .formal-table th {
            background-color: #e2e8f0;
            color: #0f172a;
            font-weight: 700;
            padding: 5px 6px;
            border: 1px solid #94a3b8;
            text-align: left;
        }

        .formal-table td {
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            vertical-align: top;
        }

        .formal-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* SIGNATURES BLOCK */
        .sig-block {
            display: flex;
            justify-content: space-between;
            margin-top: 18px;
            font-size: 10px;
        }

        .sig-col {
            width: 45%;
            text-align: center;
        }

        .sig-role {
            font-weight: 600;
            color: #334155;
            margin-bottom: 50px;
        }

        .sig-name {
            font-weight: 700;
            color: #0f172a;
            border-bottom: 1px solid #0f172a;
            display: inline-block;
            min-width: 160px;
            padding-bottom: 2px;
        }

        .sig-note {
            font-size: 9px;
            color: #64748b;
            margin-top: 3px;
        }

        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }

            .no-print {
                display: none !important;
            }

            .screen-desk {
                padding: 0 !important;
                margin: 0 !important;
                min-height: auto !important;
            }

            .paper-sheet {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                min-height: auto !important;
            }
        }
    </style>
</head>
<body>

    <!-- Top Action Control Bar (Screen Only) -->
    <div class="no-print">
        <div class="no-print-left">
            <span class="no-print-title">
                <i class="ri-community-line" style="margin-right: 6px;"></i> Pratinjau Formulir Kontingen Sekolah
            </span>
            <span class="no-print-badge">
                <i class="ri-lock-line" style="margin-right: 4px;"></i> A4 Portrait • Kontingen Resmi
            </span>
        </div>
        <div class="no-print-actions">
            <button type="button" onclick="window.close()" class="btn-action btn-close-view" title="Tutup pratinjau">
                <i class="ri-close-line"></i> Tutup
            </button>
            <a href="/admin/sekolah-terdaftar/download-formulir-pdf?school={{ urlencode($schoolName) }}" class="btn-action btn-download-pdf" title="Download PDF A4 Murni">
                <i class="ri-file-pdf-2-line"></i> Download PDF
            </a>
            <button type="button" onclick="window.print()" class="btn-action btn-print-now" title="Cetak Formulir (Ctrl + P)">
                <i class="ri-printer-fill"></i> Cetak Formulir
            </button>
        </div>
    </div>

    <!-- Paper Sheet Container -->
    <div class="screen-desk">
        <div class="paper-sheet">
            
            <!-- 1. KOP SURAT RESMI -->
            <div class="print-kop-wrapper">
                <div class="print-kop-inner">
                    <div class="print-kop-logo">
                        <img 
                            src="{{ $letterhead['logo_url'] ?: '/assets/img/logo.png' }}" 
                            alt="Logo Madrasah" 
                            class="print-logo-img" 
                            onerror="this.style.display='none'" 
                        />
                    </div>
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
                <div class="print-double-divider"></div>
            </div>

            <!-- 2. JUDUL DOKUMEN -->
            <div class="print-doc-header">
                <h3 class="print-doc-title">LEMBAR PENDAFTARAN KONTINGEN SEKOLAH / MADRASAH</h3>
                <div class="print-doc-subtitle">Rekapitulasi Delegasi Resmi Perlombaan Semarak Aliyah XIV Tahun 2026</div>
            </div>

            <!-- 3. IDENTITAS & REKAP KONTINGEN -->
            <div class="contingent-profile">
                <div class="profile-grid">
                    <div class="profile-item">
                        <span class="profile-label">Asal Lembaga</span>
                        <span>:</span>
                        <span class="profile-val" style="color: #1e3a8a; font-size: 11.5px;">{{ $schoolName }}</span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">Total Cabang Lomba</span>
                        <span>:</span>
                        <span class="profile-val">{{ $registrations->count() }} Cabang Perlombaan</span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">NPSN / Wilayah</span>
                        <span>:</span>
                        <span class="profile-val">NPSN: {{ $school->npsn ?? '-' }} | Kec. {{ $school->kecamatan ?? '-' }}</span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">Total Siswa Delegasi</span>
                        <span>:</span>
                        <span class="profile-val">{{ $totalStudents }} Orang Siswa</span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">Kepala / Pembina</span>
                        <span>:</span>
                        <span class="profile-val">{{ $school->principal_name ?? ($registrations->first()->mentor_name ?? '-') }}</span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">Skema Pendaftaran</span>
                        <span>:</span>
                        <span class="profile-val">
                            {{ $hasAllIn ? 'Paket Komplit (All-In)' : 'Paket Satuan' }}
                        </span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">Kontak WhatsApp</span>
                        <span>:</span>
                        <span class="profile-val" style="font-family: monospace;">{{ $registrations->first()->mentor_wa ?? ($school->phone ?? '-') }}</span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">Status Administrasi</span>
                        <span>:</span>
                        @if($isPaid)
                            <span class="profile-val" style="color: #059669;">Lunas Terverifikasi</span>
                        @else
                            <span class="profile-val" style="color: #d97706;">Menunggu Verifikasi Pembayaran</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 4. TABEL DELEGASI PER CABANG LOMBA -->
            <div class="section-header">Daftar Cabang Lomba & Rincian Anggota Delegasi</div>
            <table class="formal-table">
                <thead>
                    <tr>
                        <th style="width: 25px; text-align: center;">No</th>
                        <th style="width: 140px;">Cabang Lomba</th>
                        <th style="width: 75px; text-align: center;">No. Reg</th>
                        <th style="width: 60px; text-align: center;">No. TM</th>
                        <th>Daftar Anggota Delegasi (Siswa)</th>
                        <th style="width: 110px;">Guru Pendamping</th>
                        <th style="width: 75px; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($registrations as $rIdx => $reg)
                        <tr>
                            <td style="text-align: center; font-family: monospace;">{{ $rIdx + 1 }}</td>
                            <td>
                                <strong style="text-transform: uppercase;">{{ $reg->competition_title ?: ($reg->competition->title ?? '-') }}</strong>
                            </td>
                            <td style="text-align: center; font-family: monospace; font-size: 9px;">#{{ $reg->reg_code }}</td>
                            <td style="text-align: center; font-family: monospace; font-weight: bold; color: #1e3a8a;">
                                {{ $reg->draw_number ? '#' . $reg->draw_number : '-' }}
                            </td>
                            <td>
                                @php $delegates = $reg->detailed_participants; @endphp
                                @if(!empty($delegates))
                                    <ol style="margin-left: 14px; margin-bottom: 0;">
                                        @foreach($delegates as $d)
                                            <li>
                                                <strong>{{ $d['name'] }}</strong>
                                                <span style="font-size: 8.5px; color: #475569;">
                                                    ({{ $d['gender'] === 'Perempuan' ? 'P' : 'L' }}, {{ $d['grade'] ?: '-' }})
                                                    @if(!empty($d['is_captain'])) - <em style="color: #1e3a8a;">{{ $d['team_role'] ?: 'Ketua' }}</em>@endif
                                                </span>
                                            </li>
                                        @endforeach
                                    </ol>
                                @else
                                    <span style="color: #64748b; font-style: italic;">{{ $reg->participants_names ?: '-' }}</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $reg->mentor_name ?: '-' }}</div>
                                <div style="font-size: 8.5px; font-family: monospace; color: #64748b;">{{ $reg->mentor_wa ?: '-' }}</div>
                            </td>
                            <td style="text-align: center;">
                                @if(in_array(strtolower($reg->payment_status ?? ''), ['lunas', 'terverifikasi', 'paid']))
                                    <span style="color: #059669; font-weight: 700;">Lunas</span>
                                @else
                                    <span style="color: #d97706; font-weight: 600;">Menunggu</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- 5. LEMBAR PENGESAHAN DUA ARAH -->
            <div class="sig-block">
                <div class="sig-col">
                    <p class="sig-role">Kepala Sekolah / Guru Pendamping,</p>
                    <p class="sig-name">{{ $school->principal_name ?? ($registrations->first()->mentor_name ?? '................................................') }}</p>
                    <p class="sig-note">{{ $schoolName }}</p>
                </div>
                <div class="sig-col">
                    <p style="font-size: 9.5px; color: #475569; margin-bottom: 2px;">Balung, {{ $currentDateFormatted }}</p>
                    <p class="sig-role">Panitia Pelaksana Semarak Aliyah XIV,</p>
                    <p class="sig-name">( .................................................... )</p>
                    <p class="sig-note">Sekretariat Pendaftaran & Kontingen</p>
                </div>
            </div>

        </div>
    </div>

</body>
</html>

