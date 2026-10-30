<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class School extends Model
{
    use HasFactory;

    public const JEMBER_KECAMATANS = [
        'Ajung', 'Ambulu', 'Arjasa', 'Balung', 'Bangsalsari', 'Gumukmas', 'Jelbuk', 
        'Jenggawah', 'Jombang', 'Kalisat', 'Kaliwates', 'Kencong', 'Ledokombo', 
        'Mayang', 'Mumbulsari', 'Pakusari', 'Panti', 'Patrang', 'Puger', 
        'Rambipuji', 'Semboro', 'Silo', 'Sukorambi', 'Sukowono', 'Sumberbaru', 
        'Sumberjambe', 'Sumbersari', 'Tanggul', 'Tempurejo', 'Umbulsari', 'Wuluhan',
        'Lainnya'
    ];

    public static function getKecamatanList(): array
    {
        $db = self::distinct()
            ->whereNotNull('kecamatan')
            ->where('kecamatan', '!=', '')
            ->where('kecamatan', 'not like', '%luar%')
            ->pluck('kecamatan')
            ->toArray();

        return array_values(array_unique(array_merge(self::JEMBER_KECAMATANS, $db)));
    }

    protected $fillable = [
        'npsn',
        'name',
        'jenjang',
        'status',
        'kecamatan',
        'kabupaten',
        'alamat',
        'phone',
        'email',
        'principal_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope query to active schools.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query for keyword search across name, npsn, kecamatan, or address.
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (empty($keyword)) {
            return $query;
        }

        $term = '%' . trim($keyword) . '%';
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', $term)
              ->orWhere('npsn', 'like', $term)
              ->orWhere('kecamatan', 'like', $term)
              ->orWhere('alamat', 'like', $term);
        });
    }

    /**
     * Scope query by Jenjang (SMP / MTs).
     */
    public function scopeJenjang(Builder $query, ?string $jenjang): Builder
    {
        if (!empty($jenjang) && in_array(strtoupper($jenjang), ['SMP', 'MTS'])) {
            return $query->where('jenjang', strtoupper($jenjang));
        }
        return $query;
    }

    /**
     * Scope query by Status (Negeri / Swasta).
     */
    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if (!empty($status) && in_array(ucfirst(strtolower($status)), ['Negeri', 'Swasta'])) {
            return $query->where('status', ucfirst(strtolower($status)));
        }
        return $query;
    }

    /**
     * Scope query by Kecamatan.
     */
    public function scopeKecamatan(Builder $query, ?string $kecamatan): Builder
    {
        if (!empty($kecamatan)) {
            return $query->where('kecamatan', $kecamatan);
        }
        return $query;
    }

    /**
     * Normalize a school name for fuzzy and duplicate-free matching.
     */
    public static function normalizeSchoolName(?string $name): string
    {
        if (empty($name)) return '';

        // Lowercase
        $n = mb_strtolower(trim($name));

        // Strip trailing brackets like "(Balung Esports)", "(Tuan Rumah)", "(WH Cyber)", etc.
        $n = preg_replace('/\s*\([^)]*\)\s*$/', '', $n);

        // Replace common punctuation with space
        $n = preg_replace('/[.,\-_]/', ' ', $n);

        // Standardize common school terms
        $n = preg_replace('/\bsekolah menengah pertama\b/', 'smp', $n);
        $n = preg_replace('/\bmadrasah tsanawiyah\b/', 'mts', $n);
        $n = preg_replace('/\bsmpn\b/', 'smp negeri', $n);
        $n = preg_replace('/\bmtsn\b/', 'mts negeri', $n);
        $n = preg_replace('/\bsmps\b/', 'smp swasta', $n);
        $n = preg_replace('/\bmtss\b/', 'mts swasta', $n);

        // Remove leading zeros in numbers: "smp negeri 01" -> "smp negeri 1"
        $n = preg_replace('/\b0+(\d+)\b/', '$1', $n);

        // Normalize multiple spaces
        $n = preg_replace('/\s+/', ' ', trim($n));

        return $n;
    }

    /**
     * Find existing school record matching the name, preventing any duplicates.
     */
    public static function findExistingSchool(?string $rawSchoolName): ?self
    {
        if (empty($rawSchoolName) || trim($rawSchoolName) === '-' || trim($rawSchoolName) === '') {
            return null;
        }

        $cleanName = trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $rawSchoolName));
        $targetNorm = self::normalizeSchoolName($cleanName);

        // 1. Direct case-insensitive match on name
        $direct = self::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($cleanName)])
            ->orWhereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($rawSchoolName))])
            ->first();

        if ($direct) {
            return $direct;
        }

        // 2. Normalized comparison against all schools in DB
        $allSchools = self::all();
        foreach ($allSchools as $school) {
            $schoolNorm = self::normalizeSchoolName($school->name);
            if ($schoolNorm === $targetNorm) {
                return $school;
            }
        }

        // 3. Normalized contains check (e.g. "MTs SA Wahid Hasyim" vs "MTs SA Wahid Hasyim Balung")
        foreach ($allSchools as $school) {
            $schoolNorm = self::normalizeSchoolName($school->name);
            if (empty($schoolNorm)) continue;

            if ($schoolNorm === $targetNorm || stripos($schoolNorm, $targetNorm) !== false || stripos($targetNorm, $schoolNorm) !== false) {
                $targetJenjang = (str_starts_with($targetNorm, 'mts') || str_contains($targetNorm, 'mts')) ? 'MTs' : 'SMP';
                if ($school->jenjang === $targetJenjang) {
                    return $school;
                }
            }
        }

        return null;
    }

    /**
     * Automatically register or find school from registration school name.
     */
    public static function firstOrCreateFromRegistration(?string $rawSchoolName, ?string $phone = null, ?string $district = null, ?string $npsn = null, ?string $address = null): ?self
    {
        if (empty($rawSchoolName) || trim($rawSchoolName) === '-' || trim($rawSchoolName) === '') {
            return null;
        }

        $rawSchoolName = trim($rawSchoolName);
        $cleanName = trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $rawSchoolName));
        if (empty($cleanName)) {
            $cleanName = $rawSchoolName;
        }

        // Clean district if provided
        $explicitDistrict = null;
        if (!empty($district) && trim($district) !== '' && trim($district) !== '-') {
            $explicitDistrict = trim(preg_replace('/^(Kecamatan|Kec\.)\s+/i', '', trim($district)));
        }

        // 1. Check if school already exists in the database
        $existing = self::findExistingSchool($rawSchoolName);
        if ($existing) {
            $changed = false;
            // Update phone if previously empty
            if (empty($existing->phone) && !empty($phone)) {
                $existing->phone = $phone;
                $changed = true;
            }
            if ($explicitDistrict && ($existing->kecamatan === 'Balung' || empty($existing->kecamatan))) {
                $existing->kecamatan = $explicitDistrict;
                $changed = true;
            }
            if (!empty($npsn) && (empty($existing->npsn) || $existing->npsn === '-')) {
                $existing->npsn = trim($npsn);
                $changed = true;
            }
            if (!empty($address) && empty($existing->alamat)) {
                $existing->alamat = trim($address);
                $changed = true;
            }
            if ($changed) {
                $existing->save();
            }
            return $existing;
        }

        // 2. Search in official Seeder dataset if available
        $seederList = [];
        if (class_exists(\Database\Seeders\SchoolSeeder::class)) {
            $seederList = \Database\Seeders\SchoolSeeder::getSchoolList();
        }

        $targetNorm = self::normalizeSchoolName($cleanName);
        $matched = null;

        foreach ($seederList as $s) {
            $sNorm = self::normalizeSchoolName($s['name']);
            if ($sNorm === $targetNorm || strcasecmp($s['name'], $cleanName) === 0 || strcasecmp($s['name'], $rawSchoolName) === 0) {
                $matched = $s;
                break;
            }
        }

        if (!$matched) {
            foreach ($seederList as $s) {
                $sNorm = self::normalizeSchoolName($s['name']);
                if (stripos($sNorm, $targetNorm) !== false || stripos($targetNorm, $sNorm) !== false) {
                    $matched = $s;
                    break;
                }
            }
        }

        if ($matched) {
            // CRITICAL: Double-check that this official name does NOT already exist in DB!
            $existInDb = self::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($matched['name']))])->first();
            if ($existInDb) {
                if (empty($existInDb->phone) && !empty($phone)) {
                    $existInDb->phone = $phone;
                    $existInDb->save();
                }
                return $existInDb;
            }

            return self::create([
                'name'      => $matched['name'],
                'jenjang'   => $matched['jenjang'],
                'status'    => $matched['status'],
                'kecamatan' => $explicitDistrict ?: $matched['kecamatan'],
                'kabupaten' => 'Kabupaten Jember',
                'npsn'      => $matched['npsn'] ?? null,
                'alamat'    => $matched['alamat'] ?? ('Kecamatan ' . ($explicitDistrict ?: $matched['kecamatan']) . ', Kabupaten Jember'),
                'phone'     => $phone ?: ($matched['phone'] ?? null),
                'is_active' => true,
            ]);
        }

        // 3. Smart Fallback Inference
        // Jenjang
        $jenjang = 'SMP';
        if (stripos($cleanName, 'MTs') !== false || stripos($cleanName, 'Madrasah') !== false) {
            $jenjang = 'MTs';
        }

        // Status
        $status = 'Swasta';
        if (stripos($cleanName, 'Negeri') !== false || preg_match('/\b(SMPN|MTSN)\b/i', $cleanName)) {
            $status = 'Negeri';
        }

        // Kecamatan
        $allKecamatans = [
            'Ajung', 'Ambulu', 'Arjasa', 'Balung', 'Bangsalsari', 'Gumukmas', 'Jelbuk', 
            'Jenggawah', 'Jombang', 'Kalisat', 'Kaliwates', 'Kencong', 'Ledokombo', 
            'Mayang', 'Mumbulsari', 'Panti', 'Pakusari', 'Patrang', 'Puger', 
            'Rambipuji', 'Semboro', 'Silo', 'Sukorambi', 'Sukowono', 'Sumberbaru', 
            'Sumberjambe', 'Sumbersari', 'Tanggul', 'Tempurejo', 'Umbulsari', 'Wuluhan'
        ];
        $detectedKecamatan = $explicitDistrict ?: 'Balung';
        if (!$explicitDistrict) {
            foreach ($allKecamatans as $kec) {
                if (stripos($cleanName, $kec) !== false || stripos($rawSchoolName, $kec) !== false) {
                    $detectedKecamatan = $kec;
                    break;
                }
            }
        }

        // Double check cleanName in DB before insert
        $doubleCheck = self::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($cleanName))])->first();
        if ($doubleCheck) {
            return $doubleCheck;
        }

        return self::create([
            'name'      => $cleanName,
            'jenjang'   => $jenjang,
            'status'    => $status,
            'kecamatan' => $detectedKecamatan,
            'kabupaten' => 'Kabupaten Jember',
            'alamat'    => 'Kecamatan ' . $detectedKecamatan . ', Kabupaten Jember',
            'phone'     => $phone,
            'is_active' => true,
        ]);
    }

    /**
     * Sync all school names from registrations into the schools table.
     */
    public static function syncFromRegistrations(): int
    {
        $registrations = \App\Models\Registration::whereNotNull('school_name')
            ->where('school_name', '!=', '')
            ->where('school_name', '!=', '-')
            ->get();

        $synced = 0;
        foreach ($registrations as $reg) {
            $district = $reg->custom_fields['school_district'] 
                ?? $reg->custom_fields['kecamatan'] 
                ?? null;
            $created = self::firstOrCreateFromRegistration($reg->school_name, $reg->mentor_wa, $district);
            if ($created && $created->wasRecentlyCreated) {
                $synced++;
            }
        }

        return $synced;
    }
}
