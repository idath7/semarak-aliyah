<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Formulir-{{ $registration->reg_code }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 10mm 12mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #0f172a;
            line-height: 1.3;
        }

        /* KOP SURAT TABLE */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .kop-table td {
            vertical-align: middle;
            padding: 0;
        }

        .kop-logo {
            width: 65px;
            text-align: center;
        }

        .kop-logo img {
            max-width: 65px;
            max-height: 65px;
        }

        .kop-text {
            text-align: center;
            padding: 0 10px;
        }

        .kop-inst {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .kop-school {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            color: #1e3a8a;
            margin-bottom: 2px;
        }

        .kop-addr {
            font-size: 8.5px;
            color: #334155;
            margin-bottom: 2px;
        }

        .kop-contact {
            font-size: 8px;
            color: #64748b;
        }

        .double-divider {
            border-top: 2px solid #0f172a;
            border-bottom: 0.75px solid #0f172a;
            height: 2px;
            margin: 6px 0 10px 0;
        }

        /* DOC TITLE */
        .doc-title-box {
            text-align: center;
            margin-bottom: 10px;
        }

        .doc-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .doc-subtitle {
            font-size: 9.5px;
            color: #475569;
            margin-top: 2px;
        }

        /* STRIP TABLE */
        .strip-table {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .strip-table td {
            padding: 5px 8px;
            font-size: 9px;
            vertical-align: middle;
        }

        /* SECTION HEADER */
        .section-header {
            background-color: #f1f5f9;
            border-left: 3px solid #1e3a8a;
            padding: 3px 6px;
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin-top: 8px;
            margin-bottom: 4px;
        }

        /* DATA TABLE */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            margin-bottom: 6px;
        }

        .data-table td {
            padding: 3px 4px;
            vertical-align: top;
            border-bottom: 0.5px solid #e2e8f0;
        }

        .label-col {
            width: 28%;
            font-weight: bold;
            color: #475569;
        }

        .sep-col {
            width: 2%;
            text-align: center;
            color: #64748b;
        }

        .val-col {
            width: 70%;
            color: #0f172a;
        }

        /* PARTICIPANTS TABLE */
        .table-roster {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 8px;
        }

        .table-roster th {
            background-color: #e2e8f0;
            color: #0f172a;
            font-weight: bold;
            padding: 4px;
            border: 0.5px solid #94a3b8;
            text-align: left;
        }

        .table-roster td {
            padding: 4px;
            border: 0.5px solid #cbd5e1;
            color: #0f172a;
        }

        .table-roster tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* RULES */
        .rules-box {
            background-color: #fffbeb;
            border: 0.5px solid #fde68a;
            padding: 6px 8px;
            font-size: 8.5px;
            color: #78350f;
            margin-top: 6px;
            margin-bottom: 10px;
        }

        .rules-title {
            font-weight: bold;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        /* SIGNATURES */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-top: 10px;
        }

        .sig-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }

        .sig-role {
            font-weight: bold;
            color: #334155;
            margin-bottom: 45px;
        }

        .sig-name {
            font-weight: bold;
            color: #0f172a;
            border-bottom: 0.75px solid #0f172a;
            display: inline-block;
            min-width: 150px;
            padding-bottom: 1px;
        }

        .sig-note {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT -->
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Logo">
                @endif
            </td>
            <td class="kop-text">
                <div class="kop-inst">{{ $letterhead['committee_name'] ?: 'PANITIA PELAKSANA SEMARAK ALIYAH XIV TAHUN 2026' }}</div>
                <div class="kop-school">{{ $letterhead['school_name'] ?: 'MADRASAH ALIYAH UNGGULAN WAHID HASYIM BALUNG' }}</div>
                @if(!empty($letterhead['address']))
                    <div class="kop-addr">{{ $letterhead['address'] }}</div>
                @endif
                <div class="kop-contact">
                    Website: {{ $letterhead['website'] ?: 'semarak.ma-wh.sch.id' }} &bull; Email: {{ $letterhead['email'] ?: 'semarak@ma-wh.sch.id' }} &bull; WA: {{ $letterhead['wa'] ?: ($letterhead['phone'] ?: '-') }}
                </div>
            </td>
            <td class="kop-logo">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Logo">
                @endif
            </td>
        </tr>
    </table>
    <div class="double-divider"></div>

    <!-- DOCUMENT TITLE -->
    <div class="doc-title-box">
        <div class="doc-title">FORMULIR PENDAFTARAN & BIODATA PESERTA</div>
        <div class="doc-subtitle">Nomor Dokumen: SA-XIV/REG/2026/{{ $registration->reg_code }}</div>
    </div>

    <!-- STRIP TABLE -->
    <table class="strip-table">
        <tr>
            <td>
                <strong>Kode Registrasi Resmi:</strong><br>
                <span style="font-family: monospace; font-size: 13px; font-weight: bold; color: #1e3a8a;">#{{ $registration->reg_code }}</span>
            </td>
            <td>
                <strong>Waktu Pendaftaran:</strong><br>
                <span>{{ $registration->created_at ? $registration->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB' : '-' }}</span>
            </td>
            <td style="text-align: right;">
                <span style="background: #ecfdf5; border: 0.5px solid #a7f3d0; color: #059669; padding: 2px 6px; font-weight: bold;">
                    TERCATAT RESMI
                </span>
            </td>
        </tr>
    </table>

    <!-- BAGIAN I -->
    <div class="section-header">I. Data Lembaga / Sekolah Pengirim & Guru Pendamping</div>
    <table class="data-table">
        <tr>
            <td class="label-col">Asal Lembaga / Sekolah</td>
            <td class="sep-col">:</td>
            <td class="val-col" style="font-weight: bold; color: #1e3a8a;">{{ $registration->school_name }}</td>
        </tr>
        <tr>
            <td class="label-col">NPSN & Wilayah</td>
            <td class="sep-col">:</td>
            <td class="val-col">NPSN: {{ $school->npsn ?? '-' }} | Kecamatan: {{ $school->kecamatan ?? ($registration->custom_fields['school_district'] ?? '-') }}</td>
        </tr>
        <tr>
            <td class="label-col">Guru Pendamping / Official</td>
            <td class="sep-col">:</td>
            <td class="val-col" style="font-weight: bold;">{{ $registration->mentor_name ?: ($registration->user->name ?? '-') }}</td>
        </tr>
        <tr>
            <td class="label-col">Nomor WhatsApp Official</td>
            <td class="sep-col">:</td>
            <td class="val-col" style="font-family: monospace;">{{ $registration->mentor_wa ?: ($registration->user->phone ?? '-') }}</td>
        </tr>
        <tr>
            <td class="label-col">Skema Pendaftaran</td>
            <td class="sep-col">:</td>
            <td class="val-col">
                {{ $registration->package_type ?: 'Paket Satuan' }}
                @if($registration->package_type === 'Paket All-In')
                    (Paket Komplit Terpadu Seluruh Cabang)
                @endif
            </td>
        </tr>
    </table>

    <!-- BAGIAN II -->
    <div class="section-header">II. Cabang Perlombaan & Pengundian Nomor Urut</div>
    <table class="data-table">
        <tr>
            <td class="label-col">Cabang Perlombaan</td>
            <td class="sep-col">:</td>
            <td class="val-col" style="font-weight: bold; text-transform: uppercase;">
                {{ $registration->competition_title ?: ($registration->competition->title ?? '-') }}
            </td>
        </tr>
        <tr>
            <td class="label-col">Nomor Undian TM</td>
            <td class="sep-col">:</td>
            <td class="val-col">
                @if(!empty($registration->draw_number))
                    <strong style="font-family: monospace; font-size: 11px; color: #1e3a8a;">#{{ $registration->draw_number }}</strong>
                @else
                    <span style="color: #64748b; font-style: italic;">Belum Terbit (Diundi pada sesi Technical Meeting)</span>
                @endif
            </td>
        </tr>
    </table>

    <!-- BAGIAN III -->
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
    <table class="table-roster">
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">No</th>
                <th>Nama Lengkap Siswa</th>
                @if($hasParticipantWa)
                    <th style="width: 80px; text-align: center;">NISN</th>
                    <th style="width: 30px; text-align: center;">L/P</th>
                    <th style="width: 50px; text-align: center;">Kelas</th>
                    <th style="width: 85px; text-align: center;">Peran Tim</th>
                    <th style="width: 95px; text-align: center;">Kontak WA</th>
                @else
                    <th style="width: 90px; text-align: center;">NISN</th>
                    <th style="width: 35px; text-align: center;">L/P</th>
                    <th style="width: 60px; text-align: center;">Kelas</th>
                    <th style="width: 100px; text-align: center;">Peran Tim</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($participants as $pIdx => $p)
                <tr>
                    <td style="text-align: center; font-family: monospace;">{{ $pIdx + 1 }}</td>
                    <td style="font-weight: bold;">{{ $p['name'] }}</td>
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
                    <td colspan="{{ $hasParticipantWa ? 7 : 6 }}" style="text-align: center; padding: 8px; color: #64748b;">
                        Data rincian delegasi belum terinput.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- BAGIAN IV -->
    <div class="section-header">IV. Administrasi Biaya & Status Verifikasi Berkas</div>
    <table class="data-table">
        <tr>
            <td class="label-col">Total Biaya Pendaftaran</td>
            <td class="sep-col">:</td>
            <td class="val-col" style="font-weight: bold;">Rp {{ number_format($registration->total_fee, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label-col">Status Pembayaran</td>
            <td class="sep-col">:</td>
            <td class="val-col">
                @if(in_array(strtolower($registration->payment_status ?? ''), ['terverifikasi', 'lunas', 'paid']))
                    <strong style="color: #059669;">LUNAS TERVERIFIKASI</strong>
                @else
                    <strong style="color: #d97706;">MENUNGGU VERIFIKASI PEMBAYARAN</strong>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label-col">Status Verifikasi Berkas</td>
            <td class="sep-col">:</td>
            <td class="val-col">
                @if($registration->registration_status === 'Terverifikasi')
                    <strong style="color: #059669;">BERKAS LENGKAP & TERVERIFIKASI</strong>
                @elseif($registration->registration_status === 'Ditolak')
                    <strong style="color: #dc2626;">PERLU PERBAIKAN BERKAS</strong>
                @else
                    <strong style="color: #d97706;">DALAM PROSES PEMERIKSAAN PANITIA</strong>
                @endif
            </td>
        </tr>
    </table>

    <!-- PETUNJUK -->
    <div class="rules-box">
        <div class="rules-title">Petunjuk & Tata Tertib Peserta:</div>
        <p>1. Lembar formulir pendaftaran ini wajib dicetak dan dibawa saat registrasi ulang Hari-H di sekretariat {{ ucwords(strtolower($letterhead['school_name'] ?? \App\Models\Setting::get('school_name', 'MA Unggulan Wahid Hasyim Balung'))) }}.</p>
        <p>2. Siswa delegasi wajib membawa Kartu Tanda Siswa (KTS) / Surat Keterangan Asli Kepala Sekolah untuk verifikasi fisik sebelum memasuki arena.</p>
        <p>3. Peserta wajib hadir paling lambat 30 menit sebelum acara pembukaan / perlombaan dimulai untuk pengambilan ID Card resmi peserta.</p>
    </div>

    <!-- PENGESAHAN -->
    <table class="sig-table">
        <tr>
            <td>
                <div class="sig-role">Official / Guru Pendamping,</div>
                <div class="sig-name">{{ $registration->mentor_name ?: '................................................' }}</div>
                <div class="sig-note">{{ $registration->school_name }}</div>
            </td>
            <td>
                <div style="font-size: 8.5px; color: #475569; margin-bottom: 2px;">Balung, {{ $currentDateFormatted }}</div>
                <div class="sig-role">Panitia Pelaksana Semarak Aliyah XIV,</div>
                <div class="sig-name">( .................................................... )</div>
                <div class="sig-note">Sekretariat Pendaftaran & Verifikasi</div>
            </td>
        </tr>
    </table>

</body>
</html>

