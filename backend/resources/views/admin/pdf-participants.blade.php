<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Nominatif Rincian Peserta (Siswa) - Semarak Aliyah XIV</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 8mm 10mm 12mm 10mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8pt;
            color: #0f172a;
            background-color: #ffffff;
            line-height: 1.25;
        }

        /* Kop Surat Resmi Lembaga */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .kop-logo-td {
            width: 70px;
            vertical-align: middle;
            text-align: center;
        }

        .kop-logo-img {
            max-width: 65px;
            max-height: 65px;
        }

        .kop-text-td {
            text-align: center;
            vertical-align: middle;
            padding: 0 10px;
        }

        .kop-inst {
            font-size: 9.5pt;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .kop-school {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .kop-addr {
            font-size: 7.5pt;
            color: #334155;
            margin-bottom: 1px;
        }

        .kop-contact {
            font-size: 7.5pt;
            color: #334155;
        }

        /* Garis Ganda Resmi Kop Surat */
        .kop-divider-top {
            border-top: 2px solid #0f172a;
            margin-top: 5px;
            margin-bottom: 1.5px;
        }

        .kop-divider-bottom {
            border-top: 0.8px solid #0f172a;
            margin-bottom: 8px;
        }

        /* Judul Dokumen & Metadata */
        .doc-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #0f172a;
            margin-bottom: 8px;
        }

        .meta-td {
            padding: 4px 8px;
            font-size: 7.5pt;
            color: #0f172a;
            vertical-align: middle;
        }

        .meta-label {
            color: #475569;
        }

        .meta-value {
            font-weight: bold;
        }

        /* Tabel Data Nominatif Peserta */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #0f172a;
            padding: 3.5px 4.5px;
            font-size: 7.2pt;
            line-height: 1.2;
            vertical-align: middle;
        }

        .data-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
            font-size: 7pt;
        }

        .data-table thead {
            display: table-header-group;
        }

        .data-table tr {
            page-break-inside: avoid;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }

        .sub-text {
            font-size: 6.5pt;
            color: #475569;
        }

        /* Lembar Pengesahan Tanda Tangan */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .sig-td {
            width: 45%;
            text-align: center;
            vertical-align: top;
            font-size: 8pt;
        }

        .sig-td-space {
            width: 10%;
        }

        .sig-title {
            margin-bottom: 2px;
        }

        .sig-role {
            font-weight: bold;
            margin-bottom: 45px;
        }

        .sig-name {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .sig-note {
            font-size: 7pt;
            color: #475569;
        }

        /* Footer Nota */
        .footer-note {
            margin-top: 8px;
            font-size: 6.5pt;
            color: #64748b;
            font-style: italic;
        }
    </style>
</head>
<body>

    <!-- KOP Surat Resmi -->
    <table class="kop-table">
        <tr>
            <td class="kop-logo-td">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Logo" class="kop-logo-img">
                @endif
            </td>
            <td class="kop-text-td">
                <div class="kop-inst">{{ $letterhead['committee_name'] ?: 'PANITIA PELAKSANA SEMARAK ALIYAH XIV TAHUN 2026' }}</div>
                <div class="kop-school">{{ $letterhead['school_name'] ?: 'MADRASAH ALIYAH UNGGULAN WAHID HASYIM BALUNG' }}</div>
                @if(!empty($letterhead['address']))
                    <div class="kop-addr">{{ $letterhead['address'] }}</div>
                @endif
                <div class="kop-contact">
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
                    @if(!empty($letterhead['wa']))
                        <span>WhatsApp: {{ $letterhead['wa'] }}</span>
                    @elseif(!empty($letterhead['phone']))
                        <span>Telp: {{ $letterhead['phone'] }}</span>
                    @endif
                </div>
            </td>
            <td class="kop-logo-td">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Logo" class="kop-logo-img">
                @endif
            </td>
        </tr>
    </table>

    <div class="kop-divider-top"></div>
    <div class="kop-divider-bottom"></div>

    <!-- Judul Dokumen -->
    <div class="doc-title">
        @if(!empty($activeCompetition))
            Formulir & Daftar Peserta Cabang Lomba: {{ strtoupper($activeCompetitionTitle) }}
        @else
            Daftar Nominatif Rincian Peserta (Siswa) Lomba
        @endif
    </div>

    <!-- Metadata Banner -->
    <table class="meta-table">
        <tr>
            <td class="meta-td" style="width: 32%;">
                <span class="meta-label">Cabang Lomba:</span>
                <span class="meta-value">{{ $activeCompetitionTitle }}</span>
            </td>
            <td class="meta-td" style="width: 25%;">
                <span class="meta-label">Kategori / Jenis:</span>
                <span class="meta-value">{{ $activeTypeLabel }}</span>
            </td>
            <td class="meta-td" style="width: 20%;">
                <span class="meta-label">Total Delegasi:</span>
                <span class="meta-value">{{ count($participants) }} Siswa</span>
            </td>
            <td class="meta-td text-end" style="width: 23%;">
                <span class="meta-label">Dicetak Pada:</span>
                <span class="meta-value">{{ $formattedCurrentTime }}</span>
            </td>
        </tr>
    </table>

    <!-- Tabel Data Siswa -->
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 22px;">No</th>
                @if(!empty($activeCompetition))
                    <th class="text-center" style="width: 45px;">No. TM</th>
                    <th class="text-center" style="width: 55px;">No. Reg</th>
                    <th style="width: 140px;">Nama Peserta (Siswa)</th>
                    <th class="text-center" style="width: 65px;">NISN</th>
                    <th class="text-center" style="width: 26px;">L/P</th>
                    <th class="text-center" style="width: 28px;">Kls</th>
                    <th style="width: 135px;">Asal Sekolah (SMP/MTs)</th>
                    <th class="text-center" style="width: 60px;">Peran</th>
                    <th class="text-center" style="width: 75px;">Kontak WA</th>
                    <th class="text-center" style="width: 50px;">Status</th>
                    <th class="text-center" style="width: 75px;">Kehadiran / Nilai</th>
                @else
                    <th style="width: 140px;">Nama Peserta (Siswa)</th>
                    <th class="text-center" style="width: 65px;">NISN</th>
                    <th class="text-center" style="width: 28px;">L/P</th>
                    <th class="text-center" style="width: 30px;">Kls</th>
                    <th style="width: 130px;">Asal Sekolah (SMP/MTs)</th>
                    <th style="width: 70px;">Wilayah / Kec.</th>
                    <th style="width: 100px;">Cabang Lomba</th>
                    <th class="text-center" style="width: 48px;">Jenis</th>
                    <th class="text-center" style="width: 70px;">Peran</th>
                    <th class="text-center" style="width: 60px;">No. Reg</th>
                    <th class="text-center" style="width: 75px;">Kontak WA</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($participants as $index => $st)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    @if(!empty($activeCompetition))
                        <td class="text-center font-mono" style="font-weight: bold; color: #1e3a8a;">
                            {{ !empty($st['draw_number']) ? '#' . $st['draw_number'] : '-' }}
                        </td>
                        <td class="text-center font-mono">#{{ $st['reg_code'] }}</td>
                        <td>
                            <div class="fw-bold">{{ $st['name'] }}</div>
                            @if(!empty($st['birth_date']))
                                <div class="sub-text">Tgl Lahir: {{ $st['birth_date'] }}</div>
                            @endif
                        </td>
                        <td class="text-center font-mono">{{ $st['nisn'] ?: '-' }}</td>
                        <td class="text-center">
                            @if($st['gender'] === 'L' || strtolower($st['gender']) === 'laki-laki') L @elseif($st['gender'] === 'P' || strtolower($st['gender']) === 'perempuan') P @else {{ $st['gender'] ?: '-' }} @endif
                        </td>
                        <td class="text-center">{{ $st['grade'] ?: '-' }}</td>
                        <td>
                            <div class="fw-bold">{{ $st['school_name'] }}</div>
                            @if(!empty($st['mentor_name']) && $st['mentor_name'] !== '-')
                                <div class="sub-text">Pembina: {{ $st['mentor_name'] }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            @if(!empty($st['is_captain']))
                                <span class="fw-bold">{{ $st['team_role'] ?: 'Ketua' }}</span>
                            @else
                                <span>{{ $st['team_role'] ?: 'Anggota' }}</span>
                            @endif
                        </td>
                        <td class="text-center font-mono">{{ $st['wa_number'] ?: '-' }}</td>
                        <td class="text-center" style="font-size: 8px;">{{ $st['payment_status'] ?: 'Menunggu' }}</td>
                        <td class="text-center" style="background-color: #fafafa;"></td>
                    @else
                        <td>
                            <div class="fw-bold">{{ $st['name'] }}</div>
                            @if(!empty($st['birth_date']))
                                <div class="sub-text">Tgl Lahir: {{ $st['birth_date'] }}</div>
                            @endif
                        </td>
                        <td class="text-center font-mono">{{ $st['nisn'] ?: '-' }}</td>
                        <td class="text-center">
                            @if($st['gender'] === 'L' || strtolower($st['gender']) === 'laki-laki')
                                L
                            @elseif($st['gender'] === 'P' || strtolower($st['gender']) === 'perempuan')
                                P
                            @else
                                {{ $st['gender'] ?: '-' }}
                            @endif
                        </td>
                        <td class="text-center">{{ $st['grade'] ?: '-' }}</td>
                        <td>
                            <div class="fw-bold">{{ $st['school_name'] }}</div>
                            @if(!empty($st['mentor_name']) && $st['mentor_name'] !== '-')
                                <div class="sub-text">Pembina: {{ $st['mentor_name'] }}</div>
                            @endif
                        </td>
                        <td>{{ $st['school_district'] ?: '-' }}</td>
                        <td>
                            <div class="fw-bold">{{ $st['competition_title'] }}</div>
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
                        <td class="text-center font-mono">#{{ $st['reg_code'] }}</td>
                        <td class="text-center font-mono">{{ $st['wa_number'] ?: '-' }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center" style="padding: 16px;">
                        Tidak ada data peserta siswa yang memenuhi kriteria filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Lembar Pengesahan Tanda Tangan -->
    <table class="signatures-table">
        <tr>
            @if(!empty($activeCompetition))
                <td class="sig-td">
                    <div class="sig-title">Mengetahui,</div>
                    <div class="sig-role">Koordinator Cabang: {{ $activeCompetitionTitle }}</div>
                    <div class="sig-name">( ................................................................ )</div>
                    <div class="sig-note">Panitia Pelaksana Semarak Aliyah XIV</div>
                </td>
                <td class="sig-td-space"></td>
                <td class="sig-td">
                    <div class="sig-title">{{ $letterhead['city'] ?? 'Jember' }}, {{ $currentDateFormatted }}</div>
                    <div class="sig-role">Dewan Juri / Penanggung Jawab Arena</div>
                    <div class="sig-name">( ................................................................ )</div>
                    <div class="sig-note">Dewan Juri Perlombaan</div>
                </td>
            @else
                <td class="sig-td">
                    <div class="sig-title">Mengetahui,</div>
                    <div class="sig-role">Koordinator Pendaftaran & Administrasi</div>
                    <div class="sig-name">( ................................................................ )</div>
                    <div class="sig-note">Panitia Pelaksana Semarak Aliyah XIV</div>
                </td>
                <td class="sig-td-space"></td>
                <td class="sig-td">
                    <div class="sig-title">{{ $letterhead['city'] ?? 'Jember' }}, {{ $currentDateFormatted }}</div>
                    <div class="sig-role">Ketua Panitia Pelaksana Semarak Aliyah XIV</div>
                    <div class="sig-name">( ................................................................ )</div>
                    <div class="sig-note">Ketua Panitia Pelaksana</div>
                </td>
            @endif
        </tr>
    </table>

    <div class="footer-note">
        * Dokumen ini dibuat dan diterbitkan secara resmi melalui Sistem Informasi Manajemen Pendaftaran Semarak Aliyah XIV.
    </div>

    <!-- Script Penomoran Halaman Otomatis Dompdf -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Halaman {PAGE_NUM} dari {PAGE_COUNT}";
            $size = 7.5;
            $font = $fontMetrics->getFont("Helvetica");
            $width = $fontMetrics->get_text_width($text, $font, $size);
            // 841.89 pt lebar A4 Lanskap, 595.28 pt tinggi A4 Lanskap
            $pdf->page_text(841.89 - 10 - $width - 20, 595.28 - 25, $text, $font, $size, array(0.3, 0.3, 0.3));
        }
    </script>
</body>
</html>

