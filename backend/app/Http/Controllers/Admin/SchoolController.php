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

    /**
     * Export schools database to CSV for Excel / Google Sheets.
     */
    public function exportCsv()
    {
        $schools = School::orderBy('kecamatan', 'asc')->orderBy('name', 'asc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="database_sekolah_jember_' . date('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($schools) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens it with proper character encoding
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'NPSN',
                'Nama Sekolah / Madrasah',
                'Jenjang (SMP/MTs)',
                'Status (Negeri/Swasta)',
                'Kecamatan',
                'Kabupaten',
                'Alamat',
                'No Telepon / WA',
                'Email',
                'Nama Kepala Sekolah',
                'Status Aktif (1/0)'
            ]);

            foreach ($schools as $s) {
                fputcsv($file, [
                    $s->npsn ?: '',
                    $s->name,
                    $s->jenjang,
                    $s->status,
                    $s->kecamatan,
                    $s->kabupaten ?: 'Kabupaten Jember',
                    $s->alamat ?: '',
                    $s->phone ?: '',
                    $s->email ?: '',
                    $s->principal_name ?: '',
                    $s->is_active ? '1' : '0',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Download standard CSV template for importing schools.
     */
    public function templateCsv()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_sekolah_jember.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, [
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
                'is_active'
            ]);

            // Sample rows
            fputcsv($file, [
                '20581001',
                'MTs Unggulan Wahid Hasyim',
                'MTs',
                'Swasta',
                'Balung',
                'Kabupaten Jember',
                'Jl. Yos Sudarso No. 1, Balung Lor',
                '081234567890',
                'mtswahidhasyim@gmail.com',
                'H. Ahmad Baihaki, S.Pd',
                '1'
            ]);
            fputcsv($file, [
                '20523821',
                'SMPN 1 Balung',
                'SMP',
                'Negeri',
                'Balung',
                'Kabupaten Jember',
                'Jl. Puger No. 24, Balung',
                '0336123456',
                'smpn1balung@yahoo.com',
                'Drs. Bambang Sudarsono, M.Pd',
                '1'
            ]);
            fputcsv($file, [
                '20581101',
                'MTsN 1 Jember',
                'MTs',
                'Negeri',
                'Kaliwates',
                'Kabupaten Jember',
                'Jl. Imam Bonjol No. 50, Kaliwates',
                '0331481234',
                'mtsn1jember@kemenag.go.id',
                'Dra. Hj. Nurul Hidayah, M.Pd',
                '1'
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import schools from CSV file.
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120',
        ]);

        $path = $request->file('file')->getRealPath();
        $file = fopen($path, 'r');
        if (!$file) {
            return redirect()->back()->with('error', 'Gagal membaca file CSV.');
        }

        // Detect delimiter (comma or semicolon)
        $firstLine = fgets($file);
        rewind($file);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        // Read header
        $header = fgetcsv($file, 0, $delimiter);
        if (!$header) {
            fclose($file);
            return redirect()->back()->with('error', 'Format file CSV tidak valid atau kosong.');
        }

        // Clean UTF-8 BOM from first header column
        $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
        $headerMap = [];
        foreach ($header as $idx => $col) {
            $cleaned = strtolower(trim($col));
            if (str_contains($cleaned, 'npsn')) $headerMap['npsn'] = $idx;
            elseif (str_contains($cleaned, 'nama') || $cleaned === 'name' || str_contains($cleaned, 'sekolah')) $headerMap['name'] = $idx;
            elseif (str_contains($cleaned, 'jenjang')) $headerMap['jenjang'] = $idx;
            elseif (str_contains($cleaned, 'status')) $headerMap['status'] = $idx;
            elseif (str_contains($cleaned, 'kecamatan')) $headerMap['kecamatan'] = $idx;
            elseif (str_contains($cleaned, 'kabupaten')) $headerMap['kabupaten'] = $idx;
            elseif (str_contains($cleaned, 'alamat')) $headerMap['alamat'] = $idx;
            elseif (str_contains($cleaned, 'phone') || str_contains($cleaned, 'telepon') || str_contains($cleaned, 'wa')) $headerMap['phone'] = $idx;
            elseif (str_contains($cleaned, 'email')) $headerMap['email'] = $idx;
            elseif (str_contains($cleaned, 'kepala') || str_contains($cleaned, 'principal')) $headerMap['principal_name'] = $idx;
            elseif (str_contains($cleaned, 'aktif') || str_contains($cleaned, 'active')) $headerMap['is_active'] = $idx;
        }

        if (!isset($headerMap['name'])) {
            fclose($file);
            return redirect()->back()->with('error', 'Kolom Nama Sekolah tidak ditemukan di file CSV.');
        }

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
            $name = trim($row[$headerMap['name']] ?? '');
            if (empty($name)) {
                $skipped++;
                continue;
            }

            $npsn = isset($headerMap['npsn']) ? trim($row[$headerMap['npsn']] ?? '') : null;
            if ($npsn === '-' || empty($npsn)) $npsn = null;

            $jenjangRaw = isset($headerMap['jenjang']) ? strtoupper(trim($row[$headerMap['jenjang']] ?? '')) : '';
            if (str_contains($jenjangRaw, 'MTS')) {
                $jenjang = 'MTs';
            } elseif (str_contains($jenjangRaw, 'SMP')) {
                $jenjang = 'SMP';
            } else {
                $jenjang = (stripos($name, 'MTs') !== false) ? 'MTs' : 'SMP';
            }

            $statusRaw = isset($headerMap['status']) ? ucfirst(strtolower(trim($row[$headerMap['status']] ?? ''))) : '';
            if (str_contains(strtolower($statusRaw), 'neg') || stripos($name, 'SMPN') !== false || stripos($name, 'MTsN') !== false) {
                $status = 'Negeri';
            } else {
                $status = 'Swasta';
            }

            $kecamatan = isset($headerMap['kecamatan']) ? trim($row[$headerMap['kecamatan']] ?? '') : 'Balung';
            if (empty($kecamatan)) $kecamatan = 'Balung';

            $kabupaten = isset($headerMap['kabupaten']) ? trim($row[$headerMap['kabupaten']] ?? '') : 'Kabupaten Jember';
            $alamat = isset($headerMap['alamat']) ? trim($row[$headerMap['alamat']] ?? '') : null;
            $phone = isset($headerMap['phone']) ? trim($row[$headerMap['phone']] ?? '') : null;
            $email = isset($headerMap['email']) ? trim($row[$headerMap['email']] ?? '') : null;
            $principal = isset($headerMap['principal_name']) ? trim($row[$headerMap['principal_name']] ?? '') : null;
            $isActive = true;
            if (isset($headerMap['is_active'])) {
                $val = trim($row[$headerMap['is_active']] ?? '1');
                $isActive = !($val === '0' || strtolower($val) === 'false' || strtolower($val) === 'tidak');
            }

            // Match by NPSN if provided, or by name + kecamatan
            $existing = null;
            if ($npsn) {
                $existing = School::where('npsn', $npsn)->first();
            }
            if (!$existing) {
                $existing = School::where('name', $name)->where('kecamatan', $kecamatan)->first();
            }

            if ($existing) {
                $existing->update([
                    'npsn' => $npsn ?: $existing->npsn,
                    'jenjang' => $jenjang,
                    'status' => $status,
                    'kecamatan' => $kecamatan,
                    'kabupaten' => $kabupaten ?: 'Kabupaten Jember',
                    'alamat' => $alamat ?: $existing->alamat,
                    'phone' => $phone ?: $existing->phone,
                    'email' => $email ?: $existing->email,
                    'principal_name' => $principal ?: $existing->principal_name,
                    'is_active' => $isActive,
                ]);
                $updated++;
            } else {
                School::create([
                    'name' => $name,
                    'npsn' => $npsn,
                    'jenjang' => $jenjang,
                    'status' => $status,
                    'kecamatan' => $kecamatan,
                    'kabupaten' => $kabupaten ?: 'Kabupaten Jember',
                    'alamat' => $alamat,
                    'phone' => $phone,
                    'email' => $email,
                    'principal_name' => $principal,
                    'is_active' => $isActive,
                ]);
                $inserted++;
            }
        }

        fclose($file);

        return redirect()->back()->with('success', "Import selesai: {$inserted} data sekolah baru ditambahkan, {$updated} sekolah diperbarui, {$skipped} baris dilewati.");
    }

    /**
     * Toggle active/inactive status of a school.
     */
    public function toggleActive($id)
    {
        $school = School::findOrFail($id);
        $school->is_active = !$school->is_active;
        $school->save();

        $statusText = $school->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Status {$school->name} berhasil {$statusText}.");
    }

    /**
     * Clean invalid or wipe all schools.
     */
    public function cleanInvalid(Request $request)
    {
        $scope = $request->input('scope', 'all');

        if ($scope === 'all') {
            $deleted = School::count();
            School::truncate();
            return redirect()->back()->with('success', "Pembersihan selesai: Seluruh {$deleted} data sekolah hasil API/seeder berhasil dibersihkan dari database.");
        }

        if ($scope === 'inactive') {
            $deleted = School::where('is_active', false)->delete();
            return redirect()->back()->with('success', "Pembersihan selesai: {$deleted} data sekolah non-aktif berhasil dihapus.");
        }

        $deleted = School::where(function ($q) {
            $q->whereNull('name')
              ->orWhere('name', '')
              ->orWhere('name', 'like', '%test%')
              ->orWhere('name', 'like', '%contoh%')
              ->orWhere('name', 'like', '%dummy%');
        })->delete();

        return redirect()->back()->with('success', "Pembersihan selesai: {$deleted} data tidak valid berhasil dihapus.");
    }

    /**
     * Clear / truncate all schools.
     */
    public function clearAll()
    {
        $count = School::count();
        School::truncate();

        return redirect()->back()->with('success', "Seluruh {$count} data sekolah & madrasah berhasil dikosongkan.");
    }

    /**
     * Bulk delete selected schools.
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $count = School::whereIn('id', $validated['ids'])->delete();

        return redirect()->back()->with('success', "{$count} data sekolah terpilih berhasil dihapus dari database.");
    }
}
