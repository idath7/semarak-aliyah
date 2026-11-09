<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Registration extends Model
{
    protected $table = 'registrations';

    protected $fillable = [
        'user_id',
        'registrant_id',
        'reg_code',
        'barcode',
        'school_name',
        'mentor_name',
        'mentor_wa',
        'package_type',
        'competition_id',
        'competition_title',
        'participants_names',
        'notes',
        'total_fee',
        'registration_status',
        'payment_status',
        'payment_method',
        'checkin_status',
        'checkin_at',
        'file_documents',
        'verification_notes',
        'custom_fields',
        'draw_number',
        'draw_notes',
        'assigned_name',
        'assigned_phone',
        'assigned_role',
        'assigned_notes',
    ];

    protected $casts = [
        'total_fee' => 'integer',
        'draw_number' => 'integer',
        'checkin_at' => 'datetime',
        'file_documents' => 'array',
        'custom_fields' => 'array'
    ];

    protected $appends = [
        'signed_invoice_url',
    ];

    public function getSignedInvoiceUrlAttribute(): string
    {
        return $this->getSignedInvoiceUrl();
    }

    /**
     * The "booted" method of the model.
     * Automatically synchronize school data into schools database upon registration creation/update.
     */
    protected static function booted()
    {
        static::created(function (self $registration) {
            self::syncSchoolRecord($registration);
        });

        static::updated(function (self $registration) {
            if ($registration->isDirty('school_name') || $registration->isDirty('mentor_wa')) {
                self::syncSchoolRecord($registration);
            }
        });
    }

    /**
     * Synchronize school record seamlessly without duplicates.
     *
     * @param \App\Models\Registration $registration
     * @return void
     */
    protected static function syncSchoolRecord(self $registration): void
    {
        try {
            $schoolName = trim($registration->school_name ?? '');
            if (!empty($schoolName) && $schoolName !== '-') {
                $customFields = $registration->custom_fields ?? [];
                $district = $customFields['school_district'] 
                    ?? $customFields['kecamatan'] 
                    ?? $customFields['kecamatan_sekolah'] 
                    ?? $customFields['kecamatan_asal_sekolah'] 
                    ?? null;

                \App\Models\School::firstOrCreateFromRegistration($schoolName, $registration->mentor_wa, $district);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Auto sync school from registration error: ' . $e->getMessage());
        }
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class, 'competition_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function registrant()
    {
        return $this->belongsTo(Registrant::class, 'registrant_id');
    }

    public function juryScores()
    {
        return $this->hasMany(JuryScore::class, 'registration_id');
    }

    /**
     * Get or create a barcode value matching reg_code.
     *
     * @param string|null $value
     * @return string|null
     */
    public function getBarcodeAttribute(?string $value): ?string
    {
        return $value ?: $this->reg_code;
    }

    /**
     * Dapatkan daftar delegasi bersih murni (Nama Siswa & Kelas saja) untuk nota/invoice.
     *
     * @return array<int, array{name: string, grade: string}>
     */
    public function getCleanDelegatesAttribute(): array
    {
        $delegates = [];

        // 1. Cek dari custom_fields (repeater biodata_peserta / participants)
        $cf = is_array($this->custom_fields) ? $this->custom_fields : [];
        $repeater = $cf['biodata_peserta'] ?? $cf['participants'] ?? $cf['delegasi'] ?? null;

        if (is_array($repeater) && !empty($repeater)) {
            foreach ($repeater as $item) {
                if (!is_array($item)) continue;
                $name = trim($item['name'] ?? $item['nama_lengkap'] ?? $item['nama_peserta'] ?? $item['nama'] ?? '');
                $grade = trim($item['grade'] ?? $item['kelas'] ?? $item['tingkat'] ?? '');

                if ($name !== '') {
                    if ($grade !== '' && !preg_match('/^kelas/i', $grade)) {
                        $grade = 'Kelas ' . $grade;
                    }
                    $delegates[] = [
                        'name' => $name,
                        'grade' => $grade !== '' ? $grade : '-',
                    ];
                }
            }
            if (!empty($delegates)) {
                return $delegates;
            }
        }

        // 2. Cek dari field tunggal custom_fields (misal nama_lengkap & kelas)
        $singleName = trim($cf['nama_lengkap'] ?? $cf['nama_peserta'] ?? $cf['nama_siswa'] ?? '');
        $singleGrade = trim($cf['kelas'] ?? $cf['grade'] ?? $cf['tingkat'] ?? '');
        if ($singleName !== '') {
            if ($singleGrade !== '' && !preg_match('/^kelas/i', $singleGrade)) {
                $singleGrade = 'Kelas ' . $singleGrade;
            }
            return [
                [
                    'name' => $singleName,
                    'grade' => $singleGrade !== '' ? $singleGrade : '-',
                ]
            ];
        }

        // 3. Fallback cerdas: Parsing dari raw string participants_names
        $raw = trim($this->participants_names ?? '');
        if ($raw === '') {
            return [];
        }

        // Pisahkan baris terlebih dahulu
        $lines = preg_split('/[\r\n]+/', $raw);

        // Jika hanya 1 baris, cek apakah ada penomoran inline seperti "1. Budi 2. Siti" atau pemisah titik koma ";"
        if (count($lines) === 1) {
            if (preg_match('/[1-9]\d{0,1}\.\s+/u', $raw)) {
                $split = preg_split('/(?<=\S)\s+(?=[1-9]\d{0,1}\.\s+)/u', $raw);
                if (count($split) > 1) {
                    $lines = $split;
                }
            } elseif (str_contains($raw, ';')) {
                $lines = explode(';', $raw);
            }
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // Bersihkan nomor urut di depan: "1.", "1)", "-", "*", "•"
            $clean = preg_replace('/^(\d+[\.\)]|[-*•])\s*/u', '', $line);
            $clean = trim($clean);

            $name = $clean;
            $grade = '-';

            // 1. Cek tanda kurung di akhir: (...) atau [...]
            if (preg_match('/^(.*?)\s*[\(\[](.*?)[\)\]]$/u', $clean, $matches)) {
                $potentialName = trim($matches[1]);
                $bracketContent = trim($matches[2]);

                // Cari Kelas di dalam tanda kurung
                if (preg_match('/\b(?:Kelas|Kls|Tingkat)\s*[:=]?\s*([0-9A-Za-z\s-]+)\b/i', $bracketContent, $gm)) {
                    $grade = trim($gm[1]);
                } elseif (preg_match('/\b([789]|VII|VIII|IX)[A-Za-z]?\b/i', $bracketContent, $gm2)) {
                    if (!preg_match('/(?:NISN|NIK|HP|No|Telp)\s*[:=]?\s*' . preg_quote($gm2[0], '/') . '/i', $bracketContent)) {
                        $grade = trim($gm2[0]);
                    }
                }

                if ($potentialName !== '') {
                    $name = $potentialName;
                }
            }

            // 2. Jika grade belum ketemu di kurung, cari pola pemisah kelas di dalam nama
            if ($grade === '-' || $grade === '') {
                if (preg_match('/^(.*?)\s*[,–—\/-]\s*(?:Kelas|Kls)\s*[:=]?\s*([0-9A-Za-z\s]+)$/iu', $name, $matches2)) {
                    $name = trim($matches2[1]);
                    $grade = trim($matches2[2]);
                } elseif (preg_match('/^(.*?)\s*[-–—\/]\s*([789]|VII|VIII|IX)[A-Za-z]?\b/iu', $name, $matches3)) {
                    $name = trim($matches3[1]);
                    $grade = trim($matches3[2]);
                }
            }

            // 3. Bersihkan sisa-sisa noise tanda kurung (NISN, WA, HP, dll) di $name
            $name = preg_replace('/[\(\[][^\)\]]*[\)\]]/u', '', $name);
            $name = preg_replace('/\b(NISN|NIK|WA|HP|No|Telp|JK|Jenis Kelamin)\s*[:=]?\s*[0-9A-Za-z+\s-]+\b/i', '', $name);
            $name = trim($name, " ,;-\t\n\r\0\x0B");

            // Format grade agar rapi
            if ($grade !== '-' && $grade !== '') {
                $grade = preg_replace('/\b(NISN|NIK|WA|HP|No|Telp)\b.*$/i', '', $grade);
                $grade = trim($grade, " ,;-\t\n\r\0\x0B");
                if ($grade !== '' && !preg_match('/^kelas/i', $grade)) {
                    $grade = 'Kelas ' . $grade;
                }
            } else {
                $grade = '-';
            }

            if ($name !== '') {
                $delegates[] = [
                    'name' => $name,
                    'grade' => $grade,
                ];
            }
        }

        return $delegates;
    }

    /**
     * Dapatkan daftar rincian peserta/siswa secara granular dan terstruktur.
     * Mengidentifikasi nama, NISN, jenis kelamin, kelas, tgl lahir, WA, peran tim (kapten/anggota),
     * foto, serta jenis perlombaan (individu vs tim/regu).
     *
     * @return array<int, array>
     */
    public function getDetailedParticipantsAttribute(): array
    {
        $participants = [];
        $cf = is_array($this->custom_fields) ? $this->custom_fields : [];
        $comp = $this->competition;
        
        $isCompTeam = false;
        if ($comp && !empty($comp->participant_type)) {
            $isCompTeam = in_array(strtolower((string)$comp->participant_type), ['tim', 'beregu', 'team', 'grup', 'group']);
        }

        // Helper resolusi sebutan pemimpin tim secara dinamis dari database Kelola Formulir
        $resolveLeaderTitle = function () use ($comp): string {
            if (!$comp) return 'Ketua';
            
            // 1. Cek dari skema database Kelola Formulir (competitions.custom_fields)
            $compCustom = is_array($comp->custom_fields) ? $comp->custom_fields : [];
            $captainLabel = '';
            
            if (isset($compCustom['biodata_peserta']['sub_fields']['captain_option']['label'])) {
                $captainLabel = $compCustom['biodata_peserta']['sub_fields']['captain_option']['label'];
            } elseif (isset($compCustom['participants']['sub_fields']['captain_option']['label'])) {
                $captainLabel = $compCustom['participants']['sub_fields']['captain_option']['label'];
            } else {
                foreach ($compCustom as $f) {
                    if (is_array($f) && isset($f['sub_fields']['captain_option']['label'])) {
                        $captainLabel = $f['sub_fields']['captain_option']['label'];
                        break;
                    }
                }
            }

            if (!empty($captainLabel)) {
                $lower = strtolower($captainLabel);
                if (str_contains($lower, 'ketua')) return 'Ketua';
                if (str_contains($lower, 'kapten')) return 'Kapten';
            }

            // 2. Fallback cerdas berdasarkan cabang lomba (olahraga/esport vs seni/keagamaan/akademik)
            $compTitle = strtolower($comp->title ?? '');
            $compId = strtolower($comp->id ?? '');
            if (
                str_contains($compTitle, 'voli') || str_contains($compTitle, 'volleyball') ||
                str_contains($compTitle, 'futsal') || str_contains($compTitle, 'basket') ||
                str_contains($compTitle, 'mobile legends') || str_contains($compTitle, 'mlbb') ||
                str_contains($compTitle, 'esport') || str_contains($compId, 'volleyball') ||
                str_contains($compId, 'mobile-legends')
            ) {
                return 'Kapten';
            }

            return 'Ketua';
        };

        $leaderTitle = $resolveLeaderTitle();

        $compTitleStr = $this->competition_title ?: ($comp->title ?? '');
        $isFemaleComp = (bool)preg_match('/\b(putri|wanita|female|akhwat)\b/i', $compTitleStr);
        $isMaleComp = (bool)preg_match('/\b(putra|pria|male|ikhwan)\b/i', $compTitleStr);

        // Helper normalisasi tanggal lahir ke standar dd/mm/yyyy (Rule 4)
        $formatBirthDate = function (?string $rawDate): ?string {
            if (empty($rawDate) || $rawDate === '-') return null;
            $rawDate = trim($rawDate);
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $rawDate, $m)) {
                return "{$m[3]}/{$m[2]}/{$m[1]}";
            }
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $rawDate, $m)) {
                $d = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                $mo = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                return "{$d}/{$mo}/{$m[3]}";
            }
            return $rawDate;
        };

        // Helper normalisasi jenis kelamin ke 'Laki-laki' / 'Perempuan'
        $formatGender = function (?string $rawGender) use ($isFemaleComp, $isMaleComp): string {
            if (empty($rawGender) || $rawGender === '-') {
                if ($isFemaleComp) return 'Perempuan';
                if ($isMaleComp) return 'Laki-laki';
                return '-';
            }
            $raw = strtolower(trim($rawGender));
            if (
                $raw === 'p' || 
                str_contains($raw, 'perempuan') || 
                str_contains($raw, 'wanita') || 
                str_contains($raw, 'putri') || 
                str_contains($raw, 'female') ||
                str_contains($raw, 'akhwat')
            ) {
                return 'Perempuan';
            }
            if (
                $raw === 'l' || 
                str_contains($raw, 'laki') || 
                str_contains($raw, 'pria') || 
                str_contains($raw, 'putra') || 
                $raw === 'male' ||
                str_contains($raw, 'ikhwan')
            ) {
                return 'Laki-laki';
            }
            if ($isFemaleComp) return 'Perempuan';
            if ($isMaleComp) return 'Laki-laki';
            return ucfirst($rawGender);
        };

        // Helper untuk memformat URL foto jika ada
        $formatPhotoUrl = function (?string $path): ?string {
            if (empty($path) || $path === '-') return null;
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/storage/')) {
                return $path;
            }
            return '/storage/' . ltrim($path, '/');
        };

        // Helper ekstraksi kolom tambahan kustom di luar field standar
        $extractCustomCols = function (array $item): array {
            $cols = [];
            if (!empty($item['custom_columns']) && is_array($item['custom_columns'])) {
                $cols = $item['custom_columns'];
            }
            $standardKeys = [
                'name', 'nama', 'nama_siswa', 'nama_lengkap', 'nama_peserta', 'student_name',
                'nisn', 'nisn_siswa', 'gender', 'jenis_kelamin', 'jk', 'sex',
                'grade', 'kelas', 'kelas_siswa', 'tingkat',
                'birth_date', 'tanggal_lahir', 'tgl_lahir', 'tanggal_lahir_siswa', 'tgl_lahir_siswa',
                'custom_id', 'id_peserta', 'id_game', 'no_dada', 'no_punggung', 'alat_musik', 'peran_musik',
                'wa_number', 'no_wa_siswa', 'wa_siswa', 'nomor_wa_siswa', 'wa_peserta', 'wa', 'phone', 'whatsapp',
                'is_captain', 'captain_option', 'captain_wa', 'team_role', 'peran', 'role',
                'photo', 'foto', 'photo_formal', 'foto_formal', 'pas_foto', 'foto_siswa',
                'photo_freestyle', 'foto_bebas', 'custom_columns', 'index'
            ];
            foreach ($item as $k => $v) {
                if (!in_array($k, $standardKeys) && !is_array($v) && !is_object($v) && $v !== null && $v !== '') {
                    $cols[$k] = (string)$v;
                }
            }
            return $cols;
        };

        // 1. Cek dari $cf['delegates'] (biasanya diisi panitia / manual)
        if (!empty($cf['delegates']) && is_array($cf['delegates'])) {
            foreach ($cf['delegates'] as $idx => $st) {
                if (!is_array($st)) continue;
                $name = trim($st['name'] ?? $st['nama'] ?? $st['nama_lengkap'] ?? '');
                if ($name === '') continue;

                $grade = trim($st['grade'] ?? $st['kelas'] ?? '-');
                if ($grade !== '-' && $grade !== '' && !preg_match('/^kelas/i', $grade)) {
                    $grade = 'Kelas ' . $grade;
                }

                $isCaptain = !empty($st['is_captain']) || ($isCompTeam && $idx === 0 && count($cf['delegates']) > 1);
                $customRole = trim($st['team_role'] ?? $st['peran'] ?? $st['role'] ?? '');
                if (!empty($customRole) && !in_array($customRole, ['Kapten Regu', 'Anggota Regu', 'Peserta'])) {
                    $teamRole = trim(str_ireplace(['regu', '  '], ['', ' '], $customRole));
                } else {
                    $teamRole = !$isCompTeam 
                        ? 'Peserta Tunggal' 
                        : ($isCaptain ? $leaderTitle : 'Anggota');
                }

                $rawWa = $st['wa_number'] ?? ($isCaptain ? ($st['captain_wa'] ?? null) : null) ?? $st['wa'] ?? $st['phone'] ?? $st['whatsapp'] ?? null;
                $cleanWa = trim((string)$rawWa);
                $customId = trim($st['custom_id'] ?? $st['id_peserta'] ?? $st['id_game'] ?? $st['no_dada'] ?? $st['no_punggung'] ?? $st['peran_musik'] ?? '');

                $participants[] = [
                    'index' => $idx + 1,
                    'name' => $name,
                    'nisn' => trim($st['nisn'] ?? $st['nisn_siswa'] ?? '-') ?: '-',
                    'gender' => $formatGender($st['gender'] ?? $st['jenis_kelamin'] ?? $st['jk'] ?? '-'),
                    'grade' => $grade ?: '-',
                    'birth_date' => $formatBirthDate($st['birth_date'] ?? $st['tanggal_lahir'] ?? $st['tgl_lahir'] ?? $st['tanggal_lahir_siswa'] ?? null),
                    'wa_number' => ($cleanWa !== '' && $cleanWa !== '-') ? $cleanWa : null,
                    'is_captain' => (bool)$isCaptain,
                    'team_role' => $teamRole,
                    'custom_id' => $customId !== '' ? $customId : null,
                    'custom_columns' => $extractCustomCols($st),
                    'photo' => $formatPhotoUrl($st['photo_formal'] ?? ($st['photo'] ?? ($st['foto_formal'] ?? ($st['foto'] ?? null)))),
                    'photo_freestyle' => $formatPhotoUrl($st['photo_freestyle'] ?? ($st['foto_bebas'] ?? null)),
                    'source' => 'Delegasi Terstruktur',
                ];
            }

            if (!empty($participants)) {
                return $participants;
            }
        }

        // 2. Cek dari form builder repeater ($cf['biodata_peserta'], $cf['roster_tim'], $cf['participants'], dll)
        $repeater = null;
        foreach ($cf as $k => $val) {
            if (is_array($val) && count($val) > 0 && isset($val[0]) && is_array($val[0])) {
                if (isset($val[0]['nama_lengkap']) || isset($val[0]['nama_siswa']) || isset($val[0]['name']) || isset($val[0]['nama_peserta']) || isset($val[0]['nama'])) {
                    $repeater = $val;
                    break;
                }
            }
        }

        if (is_array($repeater) && !empty($repeater)) {
            foreach ($repeater as $idx => $st) {
                if (!is_array($st)) continue;
                $name = trim($st['nama_lengkap'] ?? $st['nama_siswa'] ?? $st['nama_peserta'] ?? $st['name'] ?? $st['nama'] ?? '');
                if ($name === '') continue;

                $grade = trim($st['kelas'] ?? $st['grade'] ?? $st['tingkat'] ?? '-');
                if ($grade !== '-' && $grade !== '' && !preg_match('/^kelas/i', $grade)) {
                    $grade = 'Kelas ' . $grade;
                }

                $isCaptain = !empty($st['is_captain']) || !empty($st['captain_option']) || ($isCompTeam && $idx === 0 && count($repeater) > 1);
                $customRole = trim($st['team_role'] ?? $st['peran'] ?? $st['role'] ?? '');
                if (!empty($customRole) && !in_array($customRole, ['Kapten Regu', 'Anggota Regu', 'Peserta'])) {
                    $teamRole = trim(str_ireplace(['regu', '  '], ['', ' '], $customRole));
                } else {
                    $teamRole = !$isCompTeam 
                        ? 'Peserta Tunggal' 
                        : ($isCaptain ? $leaderTitle : 'Anggota');
                }

                $rawWa = $st['wa_siswa'] ?? $st['no_wa_siswa'] ?? $st['nomor_wa_siswa'] ?? $st['wa_number'] ?? ($isCaptain ? ($st['captain_wa'] ?? null) : null) ?? $st['wa'] ?? $st['whatsapp'] ?? $st['phone'] ?? null;
                $cleanWa = trim((string)$rawWa);
                $customId = trim($st['custom_id'] ?? $st['id_peserta'] ?? $st['id_game'] ?? $st['no_dada'] ?? $st['no_punggung'] ?? $st['alat_musik'] ?? $st['peran_musik'] ?? '');

                $participants[] = [
                    'index' => $idx + 1,
                    'name' => $name,
                    'nisn' => trim($st['nisn'] ?? $st['nisn_siswa'] ?? '-') ?: '-',
                    'gender' => $formatGender($st['jenis_kelamin'] ?? $st['gender'] ?? $st['jk'] ?? '-'),
                    'grade' => $grade ?: '-',
                    'birth_date' => $formatBirthDate($st['tanggal_lahir'] ?? $st['tgl_lahir'] ?? $st['birth_date'] ?? $st['tanggal_lahir_siswa'] ?? null),
                    'wa_number' => ($cleanWa !== '' && $cleanWa !== '-') ? $cleanWa : null,
                    'is_captain' => (bool)$isCaptain,
                    'team_role' => $teamRole,
                    'custom_id' => $customId !== '' ? $customId : null,
                    'custom_columns' => $extractCustomCols($st),
                    'photo' => $formatPhotoUrl($st['foto_formal'] ?? ($st['pas_foto'] ?? ($st['foto_siswa'] ?? ($st['photo_formal'] ?? ($st['photo'] ?? null))))),
                    'photo_freestyle' => $formatPhotoUrl($st['foto_bebas'] ?? ($st['photo_freestyle'] ?? null)),
                    'source' => 'Formulir Pendaftaran (Repeater)',
                ];
            }

            if (!empty($participants)) {
                return $participants;
            }
        }

        // 3. Cek dari field tunggal Form Builder ($cf['nama_lengkap'], $cf['nama_siswa'], dll)
        $singleName = trim($cf['nama_lengkap'] ?? $cf['nama_siswa'] ?? $cf['nama_peserta'] ?? $cf['student_name'] ?? '');
        if ($singleName !== '') {
            $grade = trim($cf['kelas'] ?? $cf['kelas_siswa'] ?? $cf['grade'] ?? $cf['tingkat'] ?? '-');
            if ($grade !== '-' && $grade !== '' && !preg_match('/^kelas/i', $grade)) {
                $grade = 'Kelas ' . $grade;
            }

            $rawWa = $cf['wa_siswa'] ?? $cf['no_wa_siswa'] ?? $cf['nomor_wa_siswa'] ?? $cf['wa_peserta'] ?? $cf['wa_number'] ?? $cf['whatsapp'] ?? $cf['phone'] ?? null;
            $cleanWa = trim((string)$rawWa);
            $customId = trim($cf['custom_id'] ?? $cf['id_peserta'] ?? $cf['id_game'] ?? $cf['no_dada'] ?? $cf['no_punggung'] ?? '');

            $participants[] = [
                'index' => 1,
                'name' => $singleName,
                'nisn' => trim($cf['nisn'] ?? $cf['nisn_siswa'] ?? '-') ?: '-',
                'gender' => $formatGender($cf['jenis_kelamin'] ?? $cf['gender'] ?? $cf['jk'] ?? '-'),
                'grade' => $grade ?: '-',
                'birth_date' => $formatBirthDate($cf['tanggal_lahir'] ?? $cf['tgl_lahir'] ?? $cf['birth_date'] ?? $cf['tanggal_lahir_siswa'] ?? null),
                'wa_number' => ($cleanWa !== '' && $cleanWa !== '-') ? $cleanWa : null,
                'is_captain' => false,
                'team_role' => 'Peserta Tunggal',
                'custom_id' => $customId !== '' ? $customId : null,
                'custom_columns' => $extractCustomCols($cf),
                'photo' => $formatPhotoUrl($cf['foto_formal'] ?? ($cf['pas_foto'] ?? ($cf['foto_siswa'] ?? ($cf['photo_formal'] ?? ($cf['photo'] ?? null))))),
                'photo_freestyle' => $formatPhotoUrl($cf['foto_bebas'] ?? ($cf['photo_freestyle'] ?? null)),
                'source' => 'Formulir Pendaftaran (Tunggal)',
            ];

            return $participants;
        }

        // 4. Fallback cerdas: Parsing dari raw string participants_names
        $raw = trim($this->participants_names ?? '');
        if ($raw !== '') {
            $lines = preg_split('/[\r\n]+/', $raw);
            if (count($lines) === 1) {
                if (preg_match('/[1-9]\d{0,1}\.\s+/u', $raw)) {
                    $split = preg_split('/(?<=\S)\s+(?=[1-9]\d{0,1}\.\s+)/u', $raw);
                    if (count($split) > 1) {
                        $lines = $split;
                    }
                } elseif (str_contains($raw, ';')) {
                    $lines = explode(';', $raw);
                }
            }

            $cleanLines = [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') {
                    $cleanLines[] = $trimmed;
                }
            }

            $totalLines = count($cleanLines);
            // Jika competition type tidak ada, tapi lines > 1, asumsikan tim
            if (!$comp || empty($comp->participant_type)) {
                $isCompTeam = $totalLines > 1;
            }

            foreach ($cleanLines as $idx => $line) {
                $isCaptainTag = (bool)preg_match('/\[(?:Kapten|Ketua)\]/i', $line);
                $isCaptain = $isCaptainTag || ($isCompTeam && $idx === 0 && $totalLines > 1);

                $clean = preg_replace('/^(\d+[\.\)]|[-*•])\s*/u', '', $line);
                $clean = preg_replace('/\[(?:Kapten|Ketua)\]/i', '', $clean);
                $clean = trim($clean);

                $name = $clean;
                $nisn = '-';
                $grade = '-';
                $gender = '-';

                // Ekstrak dalam kurung (...) atau [...]
                if (preg_match('/^(.*?)\s*[\(\[](.*?)[\)\]]$/u', $clean, $matches)) {
                    $potentialName = trim($matches[1]);
                    $inside = trim($matches[2]);

                    // Cari NISN
                    if (preg_match('/NISN\s*[:=]?\s*([0-9]+)/i', $inside, $nm)) {
                        $nisn = $nm[1];
                    }

                    // Cari Kelas
                    if (preg_match('/\b(?:Kelas|Kls|Tingkat)\s*[:=]?\s*([0-9A-Za-z\s-]+)\b/i', $inside, $gm)) {
                        $grade = trim($gm[1]);
                    } elseif (preg_match('/\b([789]|VII|VIII|IX)[A-Za-z]?\b/i', $inside, $gm2)) {
                        if (!preg_match('/(?:NISN|NIK|HP|No|Telp)\s*[:=]?\s*' . preg_quote($gm2[0], '/') . '/i', $inside)) {
                            $grade = trim($gm2[0]);
                        }
                    }

                    // Cari Gender L / P
                    if (preg_match('/\b(Laki-laki|Perempuan|Wanita|Pria|Putri|Putra|L\/P|L|P)\b/i', $inside, $genderM)) {
                        $gender = $formatGender($genderM[1]);
                    }

                    if ($potentialName !== '') {
                        $name = $potentialName;
                    }
                }

                // Jika belum terdeteksi dari dalam kurung, cari dari teks baris
                if ($gender === '-') {
                    if (preg_match('/\b(Laki-laki|Perempuan|Wanita|Pria|Putri|Putra)\b/i', $clean, $gmClean)) {
                        $gender = $formatGender($gmClean[1]);
                    } elseif (preg_match('/[,\s-](L|P)[,\s-]?$/i', $clean, $gmClean2)) {
                        $gender = $formatGender($gmClean2[1]);
                    } elseif ($isFemaleComp) {
                        $gender = 'Perempuan';
                    } elseif ($isMaleComp) {
                        $gender = 'Laki-laki';
                    }
                }

                // Bersihkan sisa noise pada nama
                $name = preg_replace('/[\(\[][^\)\]]*[\)\]]/u', '', $name);
                $name = preg_replace('/\b(NISN|NIK|WA|HP|No|Telp|JK|Jenis Kelamin)\s*[:=]?\s*[0-9A-Za-z+\s-]+\b/i', '', $name);
                $name = trim($name, " ,;-\t\n\r\0\x0B");

                if ($grade !== '-' && $grade !== '') {
                    $grade = preg_replace('/\b(NISN|NIK|WA|HP|No|Telp)\b.*$/i', '', $grade);
                    $grade = trim($grade, " ,;-\t\n\r\0\x0B");
                    if ($grade !== '' && !preg_match('/^kelas/i', $grade)) {
                        $grade = 'Kelas ' . $grade;
                    }
                } else {
                    $grade = '-';
                }

                if ($name !== '') {
                    $teamRole = !$isCompTeam 
                        ? 'Peserta Tunggal' 
                        : ($isCaptain ? $leaderTitle : 'Anggota');

                    $participants[] = [
                        'index' => $idx + 1,
                        'name' => $name,
                        'nisn' => $nisn,
                        'gender' => $formatGender($gender),
                        'grade' => $grade ?: '-',
                        'birth_date' => null,
                        'wa_number' => null,
                        'is_captain' => (bool)$isCaptain,
                        'team_role' => $teamRole,
                        'custom_id' => null,
                        'custom_columns' => [],
                        'photo' => null,
                        'photo_freestyle' => null,
                        'source' => 'Catatan Delegasi (Teks)',
                    ];
                }
            }

            if (!empty($participants)) {
                return $participants;
            }
        }

        // 5. Terakhir jika benar-benar kosong: 1 placeholder
        return [
            [
                'index' => 1,
                'name' => 'Delegasi ' . ($this->school_name ?: 'Peserta'),
                'nisn' => '-',
                'gender' => $isFemaleComp ? 'Perempuan' : ($isMaleComp ? 'Laki-laki' : '-'),
                'grade' => '-',
                'birth_date' => null,
                'wa_number' => null,
                'is_captain' => false,
                'team_role' => $isCompTeam ? 'Perwakilan Tim' : 'Peserta Tunggal',
                'custom_id' => null,
                'custom_columns' => [],
                'photo' => null,
                'photo_freestyle' => null,
                'source' => 'Data Induk Registrasi',
            ]
        ];
    }

    /**
     * Periksa apakah akun pendaftar telah mendaftarkan seluruh cabang lomba aktif.
     * Jika ya, otomatis alihkan status paket menjadi 'Paket All-In' dan konsolidasikan tagihan.
     *
     * @param \App\Models\Registrant|\App\Models\User $user
     * @param bool $force Jika true, abaikan manual_package_choice (misal baru mengisi formulir lomba yang melengkapi seluruh cabang)
     * @return bool True jika upgrade dieksekusi, false jika tidak
     */
    public static function checkAndAutoUpgradeToAllIn(object $user, bool $force = false): bool
    {
        if (!$user) return false;

        $allinIsActiveSetting = Setting::get('package_allin_is_active', '1') === '1';
        if (!$allinIsActiveSetting) {
            return false;
        }

        $cleanPhone = Setting::formatWaNumber($user->phone ?? '');

        // 1. Ambil seluruh cabang lomba aktif (bukan paket all-in induk dan status bukan closed)
        $activeCompetitions = Competition::where('status', '!=', 'closed')
            ->where('id', '!=', 'all-in')
            ->get();

        if ($activeCompetitions->isEmpty()) {
            return false;
        }

        $activeCompIds = $activeCompetitions->pluck('id')->toArray();

        // 2. Ambil seluruh pendaftaran pendaftar ini
        $regs = static::where(function ($q) use ($user, $cleanPhone) {
            $q->where('registrant_id', $user->id)
              ->orWhere('user_id', $user->id);
            if (!empty($user->phone)) {
                $q->orWhere('mentor_wa', $user->phone)
                  ->orWhere('mentor_wa', $cleanPhone);
            }
        })
        ->orderBy('id', 'asc')
        ->get();

        if ($regs->isEmpty()) {
            return false;
        }

        // Jika pendaftar pernah sengaja beralih ke satuan, jangan override otomatis kecuali dipaksa ($force = true saat input lomba baru)
        if (!$force) {
            $hasManualSatuan = $regs->contains(function ($r) {
                return ($r->custom_fields['manual_package_choice'] ?? null) === 'satuan';
            });
            if ($hasManualSatuan) {
                return false;
            }
        }

        // 3. Ambil daftar competition_id unik yang telah didaftarkan
        $registeredCompIds = $regs->pluck('competition_id')
            ->filter(fn($id) => !empty($id) && $id !== 'all-in')
            ->unique()
            ->toArray();

        // 4. Periksa apakah seluruh cabang lomba aktif telah terpenuhi
        $missing = array_diff($activeCompIds, $registeredCompIds);
        if (!empty($missing)) {
            return false; // Belum semua cabang terisi
        }

        // 5. Cek apakah saat ini masih ada yang belum Paket All-In
        $needsUpgrade = $regs->contains(fn($r) => $r->package_type !== 'Paket All-In');
        $allinPrice = (int)Setting::get('package_allin_price', 200000);

        if (!$needsUpgrade) {
            // Sudah Paket All-In, pastikan konsolidasi fee tetap rapi (hanya 1 master yang bayar $allinPrice)
            $masterReg = $regs->firstWhere('total_fee', '>', 0) ?: $regs->first();
            $masterId = $masterReg ? $masterReg->id : null;
            foreach ($regs as $r) {
                $targetFee = ($r->id === $masterId) ? $allinPrice : 0;
                if ((int)$r->total_fee !== $targetFee) {
                    $r->total_fee = $targetFee;
                    $r->save();
                }
            }
            return false;
        }

        // 6. Jalankan Auto-Upgrade!
        $isAlreadyPaid = $regs->contains(fn($r) => in_array(strtolower((string)$r->payment_status), ['terverifikasi', 'lunas', 'verified']));
        $paymentStatus = $isAlreadyPaid ? 'Terverifikasi' : 'Menunggu Verifikasi';

        // Deteksi bukti pembayaran bersama jika ada
        $existingProof = null;
        foreach ($regs as $r) {
            $files = $r->file_documents ?: [];
            if (is_array($files)) {
                foreach ($files as $f) {
                    if (stripos($f['title'] ?? '', 'bukti') !== false || stripos($f['filename'] ?? '', 'bukti') !== false) {
                        $existingProof = $f;
                        break 2;
                    }
                }
            }
        }

        $masterReg = $regs->firstWhere('total_fee', '>', 0) ?: $regs->first();
        $masterId = $masterReg ? $masterReg->id : null;

        foreach ($regs as $r) {
            $r->package_type = 'Paket All-In';
            if ($isAlreadyPaid) {
                $r->payment_status = $paymentStatus;
            }
            if ($r->id === $masterId) {
                $r->total_fee = $allinPrice;
            } else {
                $r->total_fee = 0;
            }
            if ($existingProof && empty($r->file_documents)) {
                $r->file_documents = [$existingProof];
            }
            $cf = $r->custom_fields ?: [];
            unset($cf['manual_package_choice']);
            $r->custom_fields = $cf;
            $r->save();
        }

        return true;
    }

    /**
     * Dapatkan URL tanda tangan kriptografis (HMAC-SHA256 Signed URL) untuk nota invoice publik.
     *
     * @return string
     */
    public function getSignedInvoiceUrl(): string
    {
        $publicUrl = Setting::getPublicPortalUrl();
        $previousRoot = URL::to('/');

        URL::forceRootUrl($publicUrl);
        if (str_starts_with($publicUrl, 'https://')) {
            URL::forceScheme('https');
        }

        $identifier = $this->reg_code ?: $this->id;

        $signedUrl = URL::signedRoute('invoice.print', [
            'id' => $identifier
        ]);

        URL::forceRootUrl($previousRoot);

        return $signedUrl;
    }

    /**
     * Dapatkan nama berkas unduhan standar untuk nota invoice / kwitansi.
     *
     * @param string $mode 'invoice' atau 'receipt'
     * @return string
     */
    public function getInvoiceFilename(string $mode = 'invoice'): string
    {
        $prefix = ($mode === 'receipt') ? 'Kwitansi_Resmi' : 'Nota_Invoice';
        $code = Str::slug($this->reg_code ?: ('ID-' . $this->id), '_');
        $school = Str::slug($this->school_name ?: 'Kontingen', '_');

        return "{$prefix}_{$code}_{$school}.pdf";
    }

    /**
     * Temukan data pendaftaran berdasarkan ID numerik atau reg_code alfanumerik.
     * Aman dari error casting PostgreSQL 22P02.
     *
     * @param int|string $id
     * @param array $with
     * @return \App\Models\Registration
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function findByIdOrCodeOrFail(int|string $id, array $with = ['competition']): self
    {
        $query = static::with($with);

        if (is_numeric($id)) {
            /** @var self $model */
            $model = $query->where(function ($q) use ($id) {
                $q->where('id', (int) $id)
                  ->orWhere('reg_code', (string) $id);
            })->firstOrFail();
            return $model;
        }

        /** @var self $model */
        $model = $query->where('reg_code', (string) $id)->firstOrFail();
        return $model;
    }
}
