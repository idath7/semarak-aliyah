<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Database\Seeders\SchoolSeeder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SchoolController extends Controller
{
    /**
     * Display the school database management page in admin panel.
     */
    public function index(Request $request)
    {
        $query = School::query();

        // 1. Search keyword
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // 2. Filter Jenjang (SMP / MTs)
        if ($jenjang = $request->input('jenjang')) {
            $query->jenjang($jenjang);
        }

        // 3. Filter Status (Negeri / Swasta)
        if ($status = $request->input('status')) {
            $query->status($status);
        }

        // 4. Filter Kecamatan
        if ($kecamatan = $request->input('kecamatan')) {
            $query->kecamatan($kecamatan);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'name');
        $order = strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['name', 'kecamatan', 'jenjang', 'status', 'created_at', 'npsn'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $order);
        } else {
            $query->orderBy('name', 'asc');
        }

        $schools = $query->paginate(20)->withQueryString();

        // Metrics for summary cards
        $metrics = [
            'total_schools' => School::count(),
            'total_smp'     => School::where('jenjang', 'SMP')->count(),
            'total_mts'     => School::where('jenjang', 'MTs')->count(),
            'total_negeri'  => School::where('status', 'Negeri')->count(),
            'total_swasta'  => School::where('status', 'Swasta')->count(),
            'total_active'  => School::where('is_active', true)->count(),
        ];

        // List of all distinct kecamatan in Jember with counts
        $kecamatans = School::select('kecamatan')
            ->distinct()
            ->orderBy('kecamatan', 'asc')
            ->pluck('kecamatan')
            ->values()
            ->toArray();

        return Inertia::render('Admin/Schools/Index', [
            'schools'    => $schools,
            'metrics'    => $metrics,
            'kecamatans' => $kecamatans,
            'filters'    => $request->only(['search', 'jenjang', 'status', 'kecamatan', 'sort_by', 'order']),
        ]);
    }

    /**
     * Store new school into the database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:150',
            'jenjang'        => 'required|in:SMP,MTs,smp,mts',
            'status'         => 'required|in:Negeri,Swasta,negeri,swasta',
            'kecamatan'      => 'required|string|max:100',
            'npsn'           => 'nullable|string|max:20',
            'alamat'         => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:100',
            'principal_name' => 'nullable|string|max:150',
        ]);

        School::create([
            'name'           => $validated['name'],
            'jenjang'        => strtoupper($validated['jenjang']),
            'status'         => ucfirst(strtolower($validated['status'])),
            'kecamatan'      => $validated['kecamatan'],
            'kabupaten'      => 'Kabupaten Jember',
            'npsn'           => $validated['npsn'] ?? null,
            'alamat'         => $validated['alamat'] ?? null,
            'phone'          => $validated['phone'] ?? null,
            'email'          => $validated['email'] ?? null,
            'principal_name' => $validated['principal_name'] ?? null,
            'is_active'      => true,
        ]);

        return redirect()->back()->with('success', 'Sekolah/Madrasah baru berhasil ditambahkan ke database.');
    }

    /**
     * Update school record.
     */
    public function update(Request $request, $id)
    {
        $school = School::findOrFail($id);

        $validated = $request->validate([
            'name'           => 'required|string|max:150',
            'jenjang'        => 'required|in:SMP,MTs,smp,mts',
            'status'         => 'required|in:Negeri,Swasta,negeri,swasta',
            'kecamatan'      => 'required|string|max:100',
            'npsn'           => 'nullable|string|max:20',
            'alamat'         => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:100',
            'principal_name' => 'nullable|string|max:150',
            'is_active'      => 'sometimes|boolean',
        ]);

        $validated['jenjang'] = strtoupper($validated['jenjang']);
        $validated['status'] = ucfirst(strtolower($validated['status']));

        $school->update($validated);

        return redirect()->back()->with('success', "Data {$school->name} berhasil diperbarui.");
    }

    /**
     * Delete school record.
     */
    public function destroy($id)
    {
        $school = School::findOrFail($id);
        $name = $school->name;
        $school->delete();

        return redirect()->back()->with('success', "Sekolah {$name} berhasil dihapus dari database.");
    }

    /**
     * Sync or reset to default dataset.
     */
    public function syncDefaults()
    {
        $seeder = new SchoolSeeder();
        $seeder->run();

        return redirect()->back()->with('success', 'Database sekolah berhasil disinkronkan dengan 146+ dataset resmi SMP & MTs Kabupaten Jember.');
    }
}
