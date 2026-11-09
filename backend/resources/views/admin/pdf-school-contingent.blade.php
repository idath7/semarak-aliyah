<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kontingen-{{ $schoolName }}</title>
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
            font-size: 9.5px;
            color: #0f172a;
            line-height: 1.3;
        }

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
            font-size: 9px;
            color: #475569;
            margin-top: 2px;
        }

        .profile-table {
            width: 100%;
            background-color: #f8fafc;
            border: 0.5px solid #cbd5e1;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .profile-table td {
            padding: 4px 6px;
            font-size: 9px;
            vertical-align: middle;
        }

        .section-header {
            background-color: #f1f5f9;
            border-left: 3px solid #1e3a8a;
            padding: 3px 6px;
            font-size: 9.5px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .formal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            margin-bottom: 12px;
        }

        .formal-table th {
            background-color: #e2e8f0;
            color: #0f172a;
            font-weight: bold;
            padding: 4px 5px;
            border: 0.5px solid #94a3b8;
            text-align: left;
        }

        .formal-table td {
            padding: 4px 5px;
            border: 0.5px solid #cbd5e1;
            color: #0f172a;
            vertical-align: top;
        }

        .formal-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

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
        <div class="doc-title">LEMBAR PENDAFTARAN KONTINGEN SEKOLAH / MADRASAH</div>
        <div class="doc-subtitle">Rekapitulasi Delegasi Resmi Perlombaan Semarak Aliyah XIV Tahun 2026</div>
    </div>

    <!-- PROFIL KONTINGEN -->
    <table class="profile-table">
        <tr>
            <td style="width: 20%; font-weight: bold; color: #475569;">Asal Lembaga</td>
            <td style="width: 30%; font-weight: bold; color: #1e3a8a;">{{ $schoolName }}</td>
            <td style="width: 20%; font-weight: bold; color: #475569;">Total Cabang</td>
            <td style="width: 30%; font-weight: bold;">{{ $registrations->count() }} Cabang Lomba</td>
        </tr>
        <tr>
            <td style="font-weight: bold; color: #475569;">NPSN / Wilayah</td>
            <td>NPSN: {{ $school->npsn ?? '-' }} | Kec. {{ $school->kecamatan ?? '-' }}</td>
            <td style="font-weight: bold; color: #475569;">Total Delegasi</td>
            <td style="font-weight: bold;">{{ $totalStudents }} Siswa</td>
        </tr>
        <tr>
            <td style="font-weight: bold; color: #475569;">Kepala / Pembina</td>
            <td>{{ $school->principal_name ?? ($registrations->first()->mentor_name ?? '-') }}</td>
            <td style="font-weight: bold; color: #475569;">Skema Pendaftaran</td>
            <td>{{ $hasAllIn ? 'Paket Komplit (All-In)' : 'Paket Satuan' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; color: #475569;">Kontak WhatsApp</td>
            <td style="font-family: monospace;">{{ $registrations->first()->mentor_wa ?? ($school->phone ?? '-') }}</td>
            <td style="font-weight: bold; color: #475569;">Status Pembayaran</td>
            @if($isPaid)
                <td style="font-weight: bold; color: #059669;">LUNAS TERVERIFIKASI</td>
            @else
                <td style="font-weight: bold; color: #d97706;">MENUNGGU VERIFIKASI</td>
            @endif
        </tr>
    </table>

    <!-- SECTION & TABLE -->
    <div class="section-header">Daftar Cabang Lomba & Rincian Anggota Delegasi</div>
    <table class="formal-table">
        <thead>
            <tr>
                <th style="width: 22px; text-align: center;">No</th>
                <th style="width: 120px;">Cabang Lomba</th>
                <th style="width: 60px; text-align: center;">No. Reg</th>
                <th style="width: 45px; text-align: center;">No. TM</th>
                <th>Daftar Anggota Delegasi Siswa</th>
                <th style="width: 95px;">Pembina</th>
                <th style="width: 55px; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($registrations as $rIdx => $reg)
                <tr>
                    <td style="text-align: center; font-family: monospace;">{{ $rIdx + 1 }}</td>
                    <td>
                        <strong>{{ $reg->competition_title ?: ($reg->competition->title ?? '-') }}</strong>
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 8px;">#{{ $reg->reg_code }}</td>
                    <td style="text-align: center; font-family: monospace; font-weight: bold; color: #1e3a8a;">
                        {{ $reg->draw_number ? '#' . $reg->draw_number : '-' }}
                    </td>
                    <td>
                        @php $delegates = $reg->detailed_participants; @endphp
                        @if(!empty($delegates))
                            @foreach($delegates as $dIdx => $d)
                                <div>
                                    &bull; <strong>{{ $d['name'] }}</strong>
                                    <span style="font-size: 7.5px; color: #475569;">({{ $d['gender'] === 'Perempuan' ? 'P' : 'L' }}, {{ $d['grade'] ?: '-' }})</span>
                                    @if(!empty($d['is_captain'])) <em style="color: #1e3a8a;">({{ $d['team_role'] ?: 'Ketua' }})</em>@endif
                                </div>
                            @endforeach
                        @else
                            <span style="color: #64748b; font-style: italic;">{{ $reg->participants_names ?: '-' }}</span>
                        @endif
                    </td>
                    <td>
                        <div>{{ $reg->mentor_name ?: '-' }}</div>
                        <div style="font-size: 7.5px; font-family: monospace; color: #64748b;">{{ $reg->mentor_wa ?: '-' }}</div>
                    </td>
                    <td style="text-align: center;">
                        @if(in_array(strtolower($reg->payment_status ?? ''), ['lunas', 'terverifikasi', 'paid']))
                            <span style="color: #059669; font-weight: bold;">Lunas</span>
                        @else
                            <span style="color: #d97706;">Menunggu</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- PENGESAHAN -->
    <table class="sig-table">
        <tr>
            <td>
                <div class="sig-role">Kepala Sekolah / Guru Pendamping,</div>
                <div class="sig-name">{{ $school->principal_name ?? ($registrations->first()->mentor_name ?? '................................................') }}</div>
                <div class="sig-note">{{ $schoolName }}</div>
            </td>
            <td>
                <div style="font-size: 8px; color: #475569; margin-bottom: 2px;">Balung, {{ $currentDateFormatted }}</div>
                <div class="sig-role">Panitia Pelaksana Semarak Aliyah XIV,</div>
                <div class="sig-name">( .................................................... )</div>
                <div class="sig-note">Sekretariat Pendaftaran & Kontingen</div>
            </td>
        </tr>
    </table>

</body>
</html>

