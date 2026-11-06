<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
