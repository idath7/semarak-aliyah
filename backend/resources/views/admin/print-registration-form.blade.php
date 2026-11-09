<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Formulir Pendaftaran #{{ $registration->reg_code }} - {{ $registration->school_name }} - Semarak Aliyah XIV</title>
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

        /* Paper Sheet Layout (A4 Portrait: 210mm x 297mm) */
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

        /* DOCUMENT TITLE & BADGE */
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

        /* SECURITY & REGISTRATION STRIP */
        .print-security-strip {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .strip-reg-code {
            font-family: monospace;
            font-size: 15px;
            font-weight: 800;
            color: #1e3a8a;
            letter-spacing: 1px;
        }

        .strip-tag {
            font-size: 10px;
            font-weight: 700;
            color: #059669;
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* SECTION TITLE */
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

        /* FORM DATA TABLE */
        .form-grid-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-bottom: 12px;
        }

        .form-grid-table td {
            padding: 4.5px 8px;
            vertical-align: top;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-label-col {
            width: 28%;
            font-weight: 600;
            color: #475569;
        }

        .form-sep-col {
            width: 2%;
            font-weight: 600;
            color: #64748b;
            text-align: center;
        }

        .form-val-col {
            width: 70%;
            color: #0f172a;
        }

        /* PARTICIPANTS TABLE */
        .participants-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 12px;
        }

        .participants-table th {
            background-color: #e2e8f0;
            color: #0f172a;
            font-weight: 700;
            padding: 5px 6px;
            border: 1px solid #94a3b8;
            text-align: left;
        }

        .participants-table td {
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            color: #0f172a;
        }

        .participants-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* RULES & NOTES BOX */
        .rules-box {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 9.5px;
            color: #78350f;
            margin-bottom: 14px;
            line-height: 1.4;
        }

        .rules-title {
            font-weight: 700;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .rules-list {
            margin-left: 14px;
        }

        /* SIGNATURES BLOCK */
        .sig-block {
            display: flex;
            justify-content: space-between;
            margin-top: 16px;
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

        /* PRINT STYLES */
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
                <i class="ri-survey-line" style="margin-right: 6px;"></i> Pratinjau Formulir Pendaftaran Peserta
            </span>
            <span class="no-print-badge">
                <i class="ri-lock-line" style="margin-right: 4px;"></i> A4 Portrait • Dokumen Resmi
            </span>
        </div>
        <div class="no-print-actions">
            <button type="button" onclick="window.close()" class="btn-action btn-close-view" title="Tutup pratinjau">
                <i class="ri-close-line"></i> Tutup
            </button>
            <a href="/admin/pendaftar/{{ $registration->id }}/download-formulir-pdf" class="btn-action btn-download-pdf" title="Download PDF A4 Murni">
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
                <h3 class="print-doc-title">FORMULIR PENDAFTARAN & BIODATA PESERTA</h3>
                <div class="print-doc-subtitle">Nomor Dokumen: SA-XIV/REG/2026/{{ $registration->reg_code }}</div>
            </div>

            <!-- 3. STRIP SECURITY & STATUS -->
            <div class="print-security-strip">
                <div>
                    <span style="font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: 700; display: block;">Kode Registrasi Resmi:</span>
                    <span class="strip-reg-code">#{{ $registration->reg_code }}</span>
                </div>
                <div>
                    <span style="font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: 700; display: block;">Waktu Daftar:</span>
                    <span style="font-size: 11px; font-weight: 600; color: #0f172a;">{{ $registration->created_at ? $registration->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB' : '-' }}</span>
                </div>
                <div style="text-align: right;">
                    <span class="strip-tag">
                        <i class="ri-shield-check-fill"></i> TERCATAT RESMI
                    </span>
                </div>
            </div>

            <!-- 4. BAGIAN I: DATA LEMBAGA PENGIRIM & OFFICIAL -->
            <div class="section-header">I. Data Lembaga / Sekolah Pengirim & Guru Pendamping</div>
            <table class="form-grid-table">
                <tr>
                    <td class="form-label-col">Asal Lembaga / Sekolah</td>
                    <td class="form-sep-col">:</td>
                    <td class="form-val-col" style="font-weight: 700; font-size: 11.5px; color: #1e3a8a;">{{ $registration->school_name }}</td>
                </tr>
                <tr>
                    <td class="form-label-col">NPSN & Wilayah</td>
                    <td class="form-sep-col">:</td>
                    <td class="form-val-col">
                        NPSN: {{ $school->npsn ?? '-' }} | Kecamatan: {{ $school->kecamatan ?? ($registration->custom_fields['school_district'] ?? '-') }}
                    </td>
                </tr>
                <tr>
                    <td class="form-label-col">Guru Pendamping / Official</td>
                    <td class="form-sep-col">:</td>
                    <td class="form-val-col" style="font-weight: 600;">{{ $registration->mentor_name ?: ($registration->user->name ?? '-') }}</td>
                </tr>
                <tr>
                    <td class="form-label-col">Nomor WhatsApp Official</td>
                    <td class="form-sep-col">:</td>
                    <td class="form-val-col" style="font-family: monospace; font-weight: 600;">{{ $registration->mentor_wa ?: ($registration->user->phone ?? '-') }}</td>
                </tr>
                <tr>
                    <td class="form-label-col">Skema Pendaftaran</td>
                    <td class="form-sep-col">:</td>
                    <td class="form-val-col" style="font-weight: 600;">
                        {{ $registration->package_type ?: 'Paket Satuan' }}
                        @if($registration->package_type === 'Paket All-In')
                            <span style="color: #b45309; font-size: 9.5px; margin-left: 4px;">(Paket Komplit Terpadu Seluruh Cabang)</span>
                        @endif
                    </td>
                </tr>
            </table>

            <!-- 5. BAGIAN II: CABANG LOMBA & INFORMASI TECHNICAL MEETING -->
            <div class="section-header">II. Cabang Perlombaan & Pengundian Nomor Urut</div>
            <table class="form-grid-table">
                <tr>
                    <td class="form-label-col">Cabang Perlombaan</td>
                    <td class="form-sep-col">:</td>
                    <td class="form-val-col" style="font-weight: 700; font-size: 12px; text-transform: uppercase;">
                        {{ $registration->competition_title ?: ($registration->competition->title ?? '-') }}
                    </td>
                </tr>
                <tr>
                    <td class="form-label-col">Nomor Undian TM</td>
                    <td class="form-sep-col">:</td>
                    <td class="form-val-col" style="font-weight: 700;">
                        @if(!empty($registration->draw_number))
                            <span style="font-family: monospace; font-size: 12px; color: #1e3a8a; background: #e0e7ff; padding: 1px 6px; border-radius: 4px;">#{{ $registration->draw_number }}</span>
                        @else
                            <span style="color: #64748b; font-style: italic; font-weight: normal;">Belum Terbit (Diundi pada sesi Technical Meeting)</span>
                        @endif
                    </td>
                </tr>
            </table>

            <!-- 6. BAGIAN III: RINCIAN SISWA / ANGGOTA DELEGASI -->
            @php
                $hasParticipantWa = false;
                foreach ($participants as $p) {
                    if (!empty($p['wa_number'])) {
                        $hasParticipantWa = true;
                        break;
                    }
                }
            @endphp
            <div class="section-header">III. Rincian Siswa Delegasi (Peserta Tanding)</div>
            <table class="participants-table">
                <thead>
                    <tr>
                        <th style="width: 28px; text-align: center;">No</th>
                        <th>Nama Lengkap Siswa</th>
                        @if($hasParticipantWa)
                            <th style="width: 85px; text-align: center;">NISN</th>
                            <th style="width: 38px; text-align: center;">L/P</th>
                            <th style="width: 55px; text-align: center;">Kelas</th>
                            <th style="width: 90px; text-align: center;">Peran Tim</th>
                            <th style="width: 100px; text-align: center;">No. Kontak WA</th>
                        @else
                            <th style="width: 95px; text-align: center;">NISN</th>
                            <th style="width: 42px; text-align: center;">L/P</th>
                            <th style="width: 65px; text-align: center;">Kelas</th>
                            <th style="width: 105px; text-align: center;">Peran Tim</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($participants as $pIdx => $p)
                        <tr>
                            <td style="text-align: center; font-family: monospace;">{{ $pIdx + 1 }}</td>
                            <td style="font-weight: 600;">{{ $p['name'] }}</td>
                            <td style="text-align: center; font-family: monospace;">{{ $p['nisn'] ?: '-' }}</td>
                            <td style="text-align: center;">
                                @if($p['gender'] === 'Perempuan') P @elseif($p['gender'] === 'Laki-laki') L @else - @endif
                            </td>
                            <td style="text-align: center;">{{ $p['grade'] ?: '-' }}</td>
                            <td style="text-align: center;">
                                @if(!empty($p['is_captain']))
                                    <strong>{{ $p['team_role'] ?: 'Ketua' }}</strong>
                                @else
                                    {{ $p['team_role'] ?: 'Anggota' }}
                                @endif
                            </td>
                            @if($hasParticipantWa)
                                <td style="text-align: center; font-family: monospace;">{{ $p['wa_number'] ?: '-' }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $hasParticipantWa ? 7 : 6 }}" style="text-align: center; padding: 10px; color: #64748b;">
                                Data rincian siswa delegasi belum terinput lengkap.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- 7. BAGIAN IV: ADMINISTRASI KEUANGAN & STATUS VERIFIKASI -->
            <div class="section-header">IV. Administrasi Biaya & Status Verifikasi Berkas</div>
            <table class="form-grid-table">
                <tr>
                    <td class="form-label-col">Total Biaya Pendaftaran</td>
                    <td class="form-sep-col">:</td>
                    <td class="form-val-col" style="font-weight: 800; font-size: 11.5px; color: #0f172a;">
                        Rp {{ number_format($registration->total_fee, 0, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td class="form-label-col">Status Pembayaran</td>
                    <td class="form-sep-col">:</td>
                    <td class="form-val-col">
                        @if(in_array(strtolower($registration->payment_status ?? ''), ['terverifikasi', 'lunas', 'paid']))
                            <span style="font-weight: 700; color: #059669;">LUNAS TERVERIFIKASI</span>
                        @else
                            <span style="font-weight: 700; color: #d97706;">MENUNGGU VERIFIKASI PEMBAYARAN</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="form-label-col">Status Verifikasi Berkas</td>
                    <td class="form-sep-col">:</td>
                    <td class="form-val-col">
                        @if($registration->registration_status === 'Terverifikasi')
                            <span style="font-weight: 700; color: #059669;">BERKAS LENGKAP & TERVERIFIKASI</span>
                        @elseif($registration->registration_status === 'Ditolak')
                            <span style="font-weight: 700; color: #dc2626;">PERLU PERBAIKAN BERKAS</span>
                        @else
                            <span style="font-weight: 700; color: #d97706;">DALAM PROSES PEMERIKSAAN PANITIA</span>
                        @endif
                    </td>
                </tr>
            </table>

            <!-- 8. PETUNJUK TATA TERTIB HARI-H -->
            <div class="rules-box">
                <div class="rules-title">Petunjuk & Tata Tertib Peserta:</div>
                <ol class="rules-list">
                    <li>Lembar formulir pendaftaran ini wajib dicetak dan dibawa oleh official/peserta saat registrasi ulang Hari-H di sekretariat lomba {{ ucwords(strtolower($letterhead['school_name'] ?? \App\Models\Setting::get('school_name', 'MA Unggulan Wahid Hasyim Balung'))) }}.</li>
                    <li>Siswa delegasi wajib membawa Kartu Tanda Siswa (KTS) / Surat Keterangan Asli dari Kepala Sekolah untuk verifikasi fisik sebelum memasuki arena tanding.</li>
                    <li>Peserta wajib hadir paling lambat 30 menit sebelum acara pembukaan / perlombaan dimulai untuk pengambilan ID Card resmi peserta.</li>
                </ol>
            </div>

            <!-- 9. LEMBAR PENGESAHAN DUA ARAH -->
            <div class="sig-block">
                <div class="sig-col">
                    <p class="sig-role">Official / Guru Pendamping,</p>
                    <p class="sig-name">{{ $registration->mentor_name ?: '................................................' }}</p>
                    <p class="sig-note">{{ $registration->school_name }}</p>
                </div>
                <div class="sig-col">
                    <p style="font-size: 9.5px; color: #475569; margin-bottom: 2px;">Balung, {{ $currentDateFormatted }}</p>
                    <p class="sig-role">Panitia Pelaksana Semarak Aliyah XIV,</p>
                    <p class="sig-name">( .................................................... )</p>
                    <p class="sig-note">Sekretariat Pendaftaran & Verifikasi</p>
                </div>
            </div>

        </div>
    </div>

</body>
</html>

