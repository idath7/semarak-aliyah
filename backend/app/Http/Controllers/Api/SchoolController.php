<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
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
            'badge' => "{$s->jenjang} {$s->status} • {$s->kecamatan}",
            'created_at' => $s->created_at ? $s->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
