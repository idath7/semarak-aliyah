<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Registration;
use Database\Seeders\SchoolSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolController extends Controller
{
    /**
     * Get paginated or filtered list of schools in Kabupaten Jember.
     */
    public function index(Request $request)
    {
        $keyword = $request->input('search', $request->input('q'));
        $jenjang = $request->input('jenjang');
        $status = $request->input('status');
        $kecamatan = $request->input('kecamatan');
        $sortBy = $request->input('sort_by', 'name');
        $order = strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $limit = min(200, max(1, (int)$request->input('limit', 25)));
        $isPaginate = $request->boolean('paginate', true) && !$request->has('all');

        $query = School::active()
            ->search($keyword)
            ->jenjang($jenjang)
            ->status($status)
            ->kecamatan($kecamatan);

        // Sorting
        $allowedSorts = ['name', 'kecamatan', 'jenjang', 'status', 'created_at', 'npsn'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $order);
        } else {
            $query->orderBy('name', 'asc');
        }

        if ($isPaginate) {
            $paginator = $query->paginate($limit);
            
            $items = collect($paginator->items())->map(function ($s) {
                return $this->formatSchool($s);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Data sekolah/madrasah Kabupaten Jember berhasil dimuat.',
                'data' => $items,
                'meta' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ]
            ]);
        }

        $schools = $query->limit($limit)->get()->map(function ($s) {
            return $this->formatSchool($s);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Data sekolah/madrasah Kabupaten Jember berhasil dimuat.',
            'total' => $schools->count(),
            'data' => $schools
        ]);
    }

    /**
     * Get single school details.
     */
    public function show($id)
    {
        $school = School::find($id);
        if (!$school) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sekolah/Madrasah tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->formatSchool($school)
        ]);
    }

    /**
     * Get list of kecamatan in Kabupaten Jember with school counts.
     */
    public function kecamatan()
    {
        $kecamatanList = School::select(
            'kecamatan',
            DB::raw('COUNT(*) as total_schools'),
            DB::raw("SUM(CASE WHEN jenjang = 'SMP' THEN 1 ELSE 0 END) as total_smp"),
            DB::raw("SUM(CASE WHEN jenjang = 'MTs' THEN 1 ELSE 0 END) as total_mts")
        )
        ->groupBy('kecamatan')
        ->orderBy('kecamatan', 'asc')
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $kecamatanList
        ]);
    }

    /**
     * Get overall statistics of SMP & MTs database in Kabupaten Jember.
     */
    public function stats()
    {
        $total = School::count();
        $totalSmp = School::where('jenjang', 'SMP')->count();
        $totalMts = School::where('jenjang', 'MTs')->count();
        $totalNegeri = School::where('status', 'Negeri')->count();
        $totalSwasta = School::where('status', 'Swasta')->count();
        $totalKecamatan = School::distinct('kecamatan')->count('kecamatan');

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_sekolah' => $total,
                'total_smp' => $totalSmp,
                'total_mts' => $totalMts,
                'total_negeri' => $totalNegeri,
                'total_swasta' => $totalSwasta,
                'total_kecamatan' => $totalKecamatan,
                'kabupaten' => 'Kabupaten Jember'
            ]
        ]);
    }

    /**
     * Admin store new school.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'jenjang' => 'required|in:SMP,MTs,smp,mts',
            'status' => 'required|in:Negeri,Swasta,negeri,swasta',
            'kecamatan' => 'required|string|max:100',
            'npsn' => 'nullable|string|max:20',
            'alamat' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'principal_name' => 'nullable|string|max:150',
        ]);

        $school = School::create([
            'name' => $validated['name'],
            'jenjang' => strtoupper($validated['jenjang']),
            'status' => ucfirst(strtolower($validated['status'])),
            'kecamatan' => $validated['kecamatan'],
            'kabupaten' => 'Kabupaten Jember',
            'npsn' => $validated['npsn'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'principal_name' => $validated['principal_name'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data sekolah berhasil ditambahkan.',
            'data' => $this->formatSchool($school)
        ], 201);
    }

    /**
     * Admin update school.
     */
    public function update(Request $request, $id)
    {
        $school = School::find($id);
        if (!$school) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sekolah tidak ditemukan.'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'jenjang' => 'sometimes|required|in:SMP,MTs,smp,mts',
            'status' => 'sometimes|required|in:Negeri,Swasta,negeri,swasta',
            'kecamatan' => 'sometimes|required|string|max:100',
            'npsn' => 'nullable|string|max:20',
            'alamat' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'principal_name' => 'nullable|string|max:150',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['jenjang'])) {
            $validated['jenjang'] = strtoupper($validated['jenjang']);
        }
        if (isset($validated['status'])) {
            $validated['status'] = ucfirst(strtolower($validated['status']));
        }

        $school->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Data sekolah berhasil diperbarui.',
            'data' => $this->formatSchool($school)
        ]);
    }

    /**
     * Admin delete school.
     */
    public function destroy($id)
    {
        $school = School::find($id);
        if (!$school) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sekolah tidak ditemukan.'
            ], 404);
        }

        $school->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data sekolah berhasil dihapus.'
        ]);
    }

    /**
     * Sync / Re-seed default schools of Kabupaten Jember.
     */
    public function syncDefaults()
    {
        $seeder = new SchoolSeeder();
        $seeder->run();

        return response()->json([
            'status' => 'success',
            'message' => 'Database sekolah/madrasah Kabupaten Jember berhasil disinkronkan dengan dataset resmi.',
            'total' => School::count()
        ]);
    }

    /**
     * Helper to format school resource.
     */
    protected function formatSchool(School $s): array
    {
        return [
            'id' => $s->id,
            'npsn' => $s->npsn ?: '-',
            'name' => $s->name,
            'jenjang' => $s->jenjang,
            'status' => $s->status,
            'kecamatan' => $s->kecamatan,
            'kabupaten' => $s->kabupaten,
            'alamat' => $s->alamat,
            'phone' => $s->phone,
            'email' => $s->email,
            'principal_name' => $s->principal_name,
            'is_active' => (bool)$s->is_active,
            'created_at' => $s->created_at ? $s->created_at->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * Check if a school is already registered in Semarak Aliyah,
     * what package they took (Paket Satuan vs Paket All-In),
     * which competitions they registered for, and verification status.
     */
    public function checkRegistrationStatus(Request $request)
    {
        $schoolId = $request->input('school_id');
        $schoolName = trim($request->input('school_name', ''));
        $school = null;

        if (!empty($schoolId)) {
            $school = School::find($schoolId);
            if ($school) {
                $schoolName = $school->name;
            }
        }

        if (!$school && !empty($schoolName)) {
            $school = School::findExistingSchool($schoolName);
        }

        if (empty($schoolName) && $school) {
            $schoolName = $school->name;
        }

        if (empty($schoolName)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter nama sekolah atau ID sekolah diperlukan.',
                'data' => null
            ], 400);
        }

        $cleanTarget = School::normalizeSchoolName($schoolName);

        // Find all matching registrations
        $registrations = Registration::orderBy('created_at', 'desc')->get()->filter(function ($r) use ($schoolName, $cleanTarget) {
            if (empty($r->school_name)) return false;
            if (strcasecmp(trim($r->school_name), trim($schoolName)) === 0) return true;
            return School::normalizeSchoolName($r->school_name) === $cleanTarget;
        })->values();

        $hasRegistrations = $registrations->isNotEmpty();
        $allInRegistration = $registrations->first(fn($r) => $r->package_type === 'Paket All-In');
        $hasAllIn = !is_null($allInRegistration);

        // Check verification (2 verifikasi: verifikasi pendaftaran & verifikasi pembayaran)
        $isAllInPaymentVerified = false;
        $isAllInRegistrationVerified = false;

        if ($allInRegistration) {
            $pStatus = strtolower($allInRegistration->payment_status ?? '');
            $isAllInPaymentVerified = in_array($pStatus, ['terverifikasi', 'lunas', 'verified']);

            $rStatus = strtolower($allInRegistration->registration_status ?? 'terverifikasi');
            $isAllInRegistrationVerified = in_array($rStatus, ['terverifikasi', 'verified']);
        }

        $isAllInFullyVerified = $hasAllIn && $isAllInPaymentVerified && $isAllInRegistrationVerified;

        // Registered competitions mapping
        $registeredCompetitions = $registrations->map(function ($r) {
            $isPVerified = in_array(strtolower($r->payment_status ?? ''), ['terverifikasi', 'lunas', 'verified']);
            $isRVerified = in_array(strtolower($r->registration_status ?? 'terverifikasi'), ['terverifikasi', 'verified']);

            return [
                'id' => $r->id,
                'reg_code' => $r->reg_code,
                'competition_id' => $r->competition_id,
                'competition_title' => $r->competition_title,
                'package_type' => $r->package_type,
                'total_fee' => (int)$r->total_fee,
                'registration_status' => $r->registration_status ?? 'Menunggu Verifikasi',
                'payment_status' => $r->payment_status ?? 'Menunggu Verifikasi',
                'is_registration_verified' => $isRVerified,
                'is_payment_verified' => $isPVerified,
                'is_fully_verified' => $isPVerified && $isRVerified,
                'mentor_name' => $r->mentor_name,
                'mentor_wa' => $r->mentor_wa,
                'participants_names' => $r->participants_names,
                'created_at' => $r->created_at ? $r->created_at->format('d/m/Y H:i') : null,
            ];
        });

        $registeredCompIds = $registrations->pluck('competition_id')->filter()->unique()->values()->toArray();

        // Primary package determining
        $packageType = $hasAllIn ? 'Paket All-In' : ($hasRegistrations ? 'Paket Satuan' : null);

        // Extract mentor / koordinator info from the latest registration if any
        $latestReg = $registrations->first();
        $coordinator = [
            'name' => $latestReg?->mentor_name ?: '',
            'wa' => $latestReg?->mentor_wa ?: ($school?->phone ?: ''),
            'coordinators_list' => $allInRegistration && isset($allInRegistration->custom_fields['coordinators']) 
                ? $allInRegistration->custom_fields['coordinators'] 
                : [],
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'school' => $school ? $this->formatSchool($school) : [
                    'id' => null,
                    'name' => $schoolName,
                    'npsn' => '-',
                    'jenjang' => 'SMP/MTs',
                    'status' => 'Terdaftar Manual',
                    'kecamatan' => '-',
                    'kabupaten' => 'Kabupaten Jember',
                ],
                'has_registrations' => $hasRegistrations,
                'package_type' => $packageType,
                'is_all_in' => $hasAllIn,
                'all_in_reg_code' => $allInRegistration?->reg_code,
                'all_in_status' => [
                    'is_payment_verified' => $isAllInPaymentVerified,
                    'is_registration_verified' => $isAllInRegistrationVerified,
                    'is_fully_verified' => $isAllInFullyVerified,
                    'payment_status' => $allInRegistration?->payment_status ?? null,
                    'registration_status' => $allInRegistration?->registration_status ?? null,
                ],
                'can_register_all_competitions_free' => $isAllInFullyVerified,
                'registered_competitions' => $registeredCompetitions,
                'registered_competition_ids' => $registeredCompIds,
                'coordinator' => $coordinator,
                'total_competitions_registered' => count($registeredCompIds),
            ]
        ]);
    }

    /**
     * Verifikasi Kode Paket Komplit (All-In).
     * Digunakan saat guru pendamping memasukkan kode paket sekolah secara manual
     * untuk menautkan pendaftaran mandiri cabang lomba agar bebas biaya (Rp 0).
     */
    public function verifyPackageCode(Request $request)
    {
        $code = trim($request->input('code') ?? $request->input('allin_code') ?? '');
        if (empty($code)) {
            return response()->json([
                'status' => 'error',
                'valid' => false,
                'message' => 'Silakan masukkan Kode Paket Komplit.'
            ], 400);
        }

        $cleanCode = strtoupper($code);
        $reg = Registration::where('package_type', 'Paket All-In')
            ->where(function ($q) use ($cleanCode) {
                $q->whereRaw('UPPER(reg_code) = ?', [$cleanCode])
                  ->orWhereRaw('UPPER(barcode) = ?', [$cleanCode]);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$reg) {
            return response()->json([
                'status' => 'error',
                'valid' => false,
                'message' => "Kode \"{$code}\" tidak ditemukan dalam database pendaftaran Paket Komplit (All-In). Periksa kembali format kode registrasi Anda."
            ], 404);
        }

        $pStatus = strtolower($reg->payment_status ?? '');
        $rStatus = strtolower($reg->registration_status ?? '');
        $isPaymentVerified = in_array($pStatus, ['terverifikasi', 'lunas', 'verified']);
        $isRegVerified = in_array($rStatus, ['terverifikasi', 'verified']);
        $isFullyVerified = $isPaymentVerified && $isRegVerified;

        $school = School::findExistingSchool($reg->school_name);
        $schoolDistrict = $reg->custom_fields['school_district'] ?? ($school?->kecamatan ?? '');
        $schoolNpsn = $reg->custom_fields['school_npsn'] ?? $reg->custom_fields['npsn'] ?? ($school?->npsn ?? '');
        $schoolAddress = $reg->custom_fields['school_address'] ?? $reg->custom_fields['alamat'] ?? ($school?->alamat ?? '');

        return response()->json([
            'status' => 'success',
            'valid' => true,
            'data' => [
                'reg_code' => $reg->reg_code,
                'school_name' => $reg->school_name,
                'school_district' => $schoolDistrict,
                'school_npsn' => $schoolNpsn,
                'school_address' => $schoolAddress,
                'school_jenjang' => $school?->jenjang ?? null,
                'mentor_name' => $reg->mentor_name,
                'mentor_wa' => $reg->mentor_wa,
                'payment_status' => $reg->payment_status ?? 'Menunggu Verifikasi',
                'registration_status' => $reg->registration_status ?? 'Menunggu Verifikasi',
                'is_payment_verified' => $isPaymentVerified,
                'is_registration_verified' => $isRegVerified,
                'is_fully_verified' => $isFullyVerified,
                'message' => "Kode Paket Komplit valid untuk sekolah {$reg->school_name}."
            ]
        ]);
    }

    /**
     * Public Self-Registration for New Schools not yet in database.
     */
    public function publicRegisterSchool(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:200',
            'jenjang' => 'required|string|in:SMP,MTs,smp,mts,Smp,Mts',
            'status' => 'nullable|string',
            'kecamatan' => 'required|string',
            'npsn' => 'nullable|string|max:30',
            'alamat' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
        ]);

        $name = trim($validated['name']);
        $existing = School::findExistingSchool($name);

        if ($existing) {
            return response()->json([
                'status' => 'success',
                'message' => "Sekolah \"{$existing->name}\" sudah terdaftar dalam sistem.",
                'data' => $this->formatSchool($existing),
                'is_existing' => true
            ]);
        }

        $jenjang = strtoupper($validated['jenjang']);
        $status = !empty($validated['status']) ? ucfirst(strtolower($validated['status'])) : 'Terdaftar';
        $kecamatan = trim($validated['kecamatan']);

        $school = School::create([
            'npsn' => $validated['npsn'] ?? null,
            'name' => $name,
            'jenjang' => $jenjang,
            'status' => $status,
            'kecamatan' => $kecamatan,
            'kabupaten' => 'Kabupaten Jember',
            'alamat' => $validated['alamat'] ?? ("Kecamatan {$kecamatan}, Kabupaten Jember"),
            'phone' => $validated['phone'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Sekolah \"{$school->name}\" berhasil didaftarkan ke sistem!",
            'data' => $this->formatSchool($school),
            'is_existing' => false
        ], 201);
    }
}
