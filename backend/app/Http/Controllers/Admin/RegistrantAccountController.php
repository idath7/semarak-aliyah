<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registrant;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrantAccountController extends Controller
{
    /**
     * Tampilkan halaman utama Manajemen Akun Pendaftar.
     */
    public function index(Request $request)
    {
        $query = Registrant::with(['registrations' => function ($q) {
            $q->select('id', 'registrant_id', 'reg_code', 'competition_id', 'competition_title', 'payment_status', 'registration_status', 'total_fee', 'created_at')
              ->orderBy('id', 'desc');
        }])->withCount('registrations');

        // 1. Filter Pencarian Teks
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('school_name', 'like', "%{$s}%")
                  ->orWhere('school_district', 'like', "%{$s}%");
            });
        }

        // 2. Filter Peran (Role)
        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('registrant_role', $request->role);
        }

        // 3. Filter Status Aktif / Nonaktif
        if ($request->filled('status') && $request->status !== 'all') {
            $isActive = in_array($request->status, ['active', '1', 1, true], true);
            $query->where('is_active', $isActive);
        }

        // 4. Filter Metode Login (Google vs Email Manual)
        if ($request->filled('login_method') && $request->login_method !== 'all') {
            if ($request->login_method === 'google') {
                $query->whereNotNull('google_id');
            } elseif ($request->login_method === 'email') {
                $query->whereNull('google_id');
            }
        }

        // 5. Filter Kecamatan Asal Lembaga
        if ($request->filled('district') && $request->district !== 'all') {
            $query->where('school_district', $request->district);
        }

        // Urutan Data Terbaru
        $query->orderBy('id', 'desc');

        // Paginasi Data
        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 5 || $perPage > 100) {
            $perPage = 15;
        }

        $registrants = $query->paginate($perPage)->withQueryString();

        // 6. Ringkasan Statistik (Metrics)
        $metrics = [
            'total_accounts' => Registrant::count(),
            'active_accounts' => Registrant::where('is_active', true)->count(),
            'inactive_accounts' => Registrant::where('is_active', false)->count(),
            'role_pendamping_count' => Registrant::where('registrant_role', 'Pendamping')->count(),
            'role_koordinator_count' => Registrant::where('registrant_role', 'Koordinator')->count(),
            'role_siswa_count' => Registrant::where('registrant_role', 'Siswa Peserta')->count(),
            'google_accounts_count' => Registrant::whereNotNull('google_id')->count(),
            'with_registrations_count' => Registrant::has('registrations')->count(),
        ];

        // 7. Ambil daftar kecamatan unik dari database pendaftar
        $existingDistricts = Registrant::whereNotNull('school_district')
            ->where('school_district', '!=', '')
            ->distinct()
            ->orderBy('school_district')
            ->pluck('school_district')
            ->toArray();

        // Daftar 31 kecamatan baku Jember
        $canonicalDistricts = [
            'Ajung', 'Ambulu', 'Arjasa', 'Balung', 'Bangsalsari', 'Gumukmas',
            'Jelbuk', 'Jenggawah', 'Jombang', 'Kalisat', 'Kaliwates', 'Kencong',
            'Ledokombo', 'Mayang', 'Mumbulsari', 'Pakusari', 'Panti', 'Patrang',
            'Puger', 'Rambipuji', 'Semboro', 'Silo', 'Sukorambi', 'Sukowono',
            'Sumberbaru', 'Sumberjambe', 'Sumbersari', 'Tanggul', 'Tempurejo',
            'Umbulsari', 'Wuluhan'
        ];

        $mergedDistricts = array_values(array_unique(array_merge($canonicalDistricts, $existingDistricts)));
        sort($mergedDistricts);

        return Inertia::render('Admin/RegistrantAccounts/Index', [
            'registrants' => $registrants,
            'metrics' => $metrics,
            'districts' => $mergedDistricts,
            'filters' => [
                'search' => $request->input('search', ''),
                'role' => $request->input('role', 'all'),
                'status' => $request->input('status', 'all'),
                'login_method' => $request->input('login_method', 'all'),
                'district' => $request->input('district', 'all'),
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Beralih status aktif / nonaktif akun pendaftar.
     */
    public function toggleStatus(int $id)
    {
        $registrant = Registrant::findOrFail($id);
        $registrant->is_active = !$registrant->is_active;
        $registrant->save();

        $statusText = $registrant->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()->with('success', "Akun \"{$registrant->name}\" berhasil {$statusText}.");
    }

    /**
     * Reset password akun pendaftar oleh admin.
     */
    public function resetPassword(Request $request, int $id)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        $registrant = Registrant::findOrFail($id);
        $registrant->password = Hash::make($validated['password']);
        $registrant->save();

        return redirect()->back()->with('success', "Password akun \"{$registrant->name}\" berhasil direset.");
    }

    /**
     * Perbarui data profil akun pendaftar.
     */
    public function update(Request $request, int $id)
    {
        $registrant = Registrant::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('registrants', 'email')->ignore($registrant->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', 'string', 'in:Laki-laki,Perempuan'],
            'school_name' => ['nullable', 'string', 'max:255'],
            'school_district' => ['nullable', 'string', 'max:100'],
            'registrant_role' => ['required', 'string', 'in:Pendamping,Koordinator,Siswa Peserta'],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'Nama lengkap pendaftar wajib diisi.',
            'email.required' => 'Email pendaftar wajib diisi.',
            'email.unique' => 'Email sudah digunakan oleh pendaftar lain.',
            'registrant_role.required' => 'Peran akun pendaftar wajib dipilih.',
        ]);

        $registrant->update($validated);

        return redirect()->back()->with('success', "Data akun \"{$registrant->name}\" berhasil diperbarui.");
    }

    /**
     * Hapus akun pendaftar dari sistem.
     */
    public function destroy(int $id)
    {
        $registrant = Registrant::findOrFail($id);
        $name = $registrant->name;

        // Lepaskan relasi pada pendaftaran lomba agar rekapitulasi lomba tidak terhapus
        Registration::where('registrant_id', $registrant->id)->update(['registrant_id' => null]);

        $registrant->delete();

        return redirect()->back()->with('success', "Akun \"{$name}\" berhasil dihapus.");
    }

    /**
     * Ekspor daftar akun pendaftar ke file CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $query = Registrant::withCount('registrations')->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('school_name', 'like', "%{$s}%")
                  ->orWhere('school_district', 'like', "%{$s}%");
            });
        }

        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('registrant_role', $request->role);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $isActive = in_array($request->status, ['active', '1', 1, true], true);
            $query->where('is_active', $isActive);
        }

        if ($request->filled('login_method') && $request->login_method !== 'all') {
            if ($request->login_method === 'google') {
                $query->whereNotNull('google_id');
            } elseif ($request->login_method === 'email') {
                $query->whereNull('google_id');
            }
        }

        if ($request->filled('district') && $request->district !== 'all') {
            $query->where('school_district', $request->district);
        }

        $records = $query->get();
        $filename = 'rekap-akun-pendaftar-' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($records) {
            $output = fopen('php://output', 'w');
            // Menulis UTF-8 BOM agar rapi di Microsoft Excel
            fputs($output, "\xEF\xBB\xBF");

            // Header kolom
            fputcsv($output, [
                'ID',
                'Nama Lengkap',
                'Email',
                'Nomor WhatsApp',
                'Jenis Kelamin',
                'Peran Akun',
                'Asal Lembaga / Sekolah',
                'Kecamatan',
                'Metode Login',
                'Status Akun',
                'Jumlah Pendaftaran Lomba',
                'Waktu Registrasi Akun'
            ]);

            foreach ($records as $item) {
                fputcsv($output, [
                    $item->id,
                    $item->name,
                    $item->email,
                    $item->phone ? "'" . $item->phone : '-',
                    $item->gender ?: '-',
                    $item->registrant_role ?: 'Pendamping',
                    $item->school_name ?: '-',
                    $item->school_district ?: '-',
                    $item->google_id ? 'Google OAuth' : 'Email & Password',
                    $item->is_active ? 'Aktif' : 'Nonaktif',
                    $item->registrations_count ?? 0,
                    $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-'
                ]);
            }

            fclose($output);
        }, 200, $headers);
    }
}

