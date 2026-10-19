<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Daftar peran (roles) baku dalam sistem SEMARAK
     */
    protected array $roles = [
        'super_admin' => [
            'label' => 'Super Admin',
            'badge' => 'danger',
            'description' => 'Akses penuh ke seluruh konfigurasi, user, dan operasional'
        ],
        'admin' => [
            'label' => 'Administrator',
            'badge' => 'primary',
            'description' => 'Akses pengelolaan pendaftaran, lomba, dan kustomisasi'
        ],
        'panitia' => [
            'label' => 'Panitia Pelaksana',
            'badge' => 'info',
            'description' => 'Panitia umum lapangan dan koordinator kegiatan'
        ],
        'kesekretariatan' => [
            'label' => 'Kesekretariatan',
            'badge' => 'secondary',
            'description' => 'Verifikasi berkas, pendaftaran, dan cetak dokumen'
        ],
        'bendahara' => [
            'label' => 'Bendahara & Tiket',
            'badge' => 'success',
            'description' => 'Validasi pembayaran, biaya pendaftaran, dan keuangan'
        ],
        'juri' => [
            'label' => 'Dewan Juri',
            'badge' => 'warning',
            'description' => 'Penilaian cabang lomba dan input rubrik penilaian'
        ],
        'wasit' => [
            'label' => 'Wasit / Match Official',
            'badge' => 'dark',
            'description' => 'Pengadil pertandingan turnamen dan live score'
        ],
    ];

    /**
     * Daftar divisi panitia baku
     */
    protected array $divisions = [
        'BPH / Pimpinan',
        'Kesekretariatan & Pendaftaran',
        'Bendahara & Keuangan',
        'Acara & Lomba',
        'Perlengkapan & Venue',
        'Publikasi & IT',
        'Konsumsi',
        'Keamanan & Medis',
        'Wasit & Juri',
    ];

    /**
     * Tampilkan halaman daftar pengguna dan panitia
     */
    public function index(Request $request)
    {
        $query = User::with('competition:id,title,category_label')
            ->select('id', 'name', 'email', 'role', 'division', 'phone', 'competition_id', 'is_active', 'created_at');

        // 1. Filter Pencarian Nama, Email, atau No. Telepon
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // 2. Filter Role
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        // 3. Filter Divisi
        if ($division = $request->input('division')) {
            $query->where('division', $division);
        }

        // 4. Filter Status
        if ($request->filled('status')) {
            $isActive = $request->input('status') === 'active';
            $query->where('is_active', $isActive);
        }

        $users = $query->orderBy('role')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        // Metrik Ringkasan Pengguna
        $metrics = [
            'total_users'     => User::count(),
            'total_active'    => User::where('is_active', true)->count(),
            'total_admin'     => User::whereIn('role', ['super_admin', 'admin'])->count(),
            'total_panitia'   => User::whereIn('role', ['panitia', 'kesekretariatan', 'bendahara'])->count(),
            'total_juri_wasit'=> User::whereIn('role', ['juri', 'wasit'])->count(),
        ];

        // Daftar lomba untuk penugasan PJ / Juri / Wasit
        $competitions = Competition::select('id', 'title', 'category_label')
            ->orderBy('title')
            ->get();

        return Inertia::render('Admin/Users/Index', [
            'users'        => $users,
            'filters'      => $request->only(['search', 'role', 'division', 'status']),
            'metrics'      => $metrics,
            'competitions' => $competitions,
            'divisions'    => $this->divisions,
            'roles'        => $this->roles,
            'currentUserId'=> Auth::id(),
        ]);
    }

    /**
     * Tambah pengguna / panitia baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'       => ['required', 'string', 'min:6'],
            'role'           => ['required', 'string', Rule::in(array_keys($this->roles))],
            'division'       => ['nullable', 'string', 'max:100'],
            'phone'          => ['nullable', 'string', 'max:25'],
            'competition_id' => ['nullable', 'string', 'exists:competitions,id'],
            'is_active'      => ['boolean'],
        ], [
            'name.required'     => 'Nama pengguna wajib diisi.',
            'email.required'    => 'Email pengguna wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email ini sudah digunakan oleh akun lain.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min'      => 'Kata sandi minimal 6 karakter.',
            'role.required'     => 'Peran/Role wajib dipilih.',
            'competition_id.exists' => 'Cabang lomba tidak ditemukan.',
        ]);

        $user = User::create([
            'name'           => $validated['name'],
            'email'          => strtolower(trim($validated['email'])),
            'password'       => Hash::make($validated['password']),
            'role'           => $validated['role'],
            'division'       => $validated['division'] ?? null,
            'phone'          => $validated['phone'] ?? null,
            'competition_id' => $validated['competition_id'] ?? null,
            'is_active'      => $validated['is_active'] ?? true,
        ]);

        return redirect()->back()->with('success', "Pengguna baru {$user->name} ({$user->role}) berhasil ditambahkan.");
    }

    /**
     * Perbarui data pengguna / panitia
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password'       => ['nullable', 'string', 'min:6'],
            'role'           => ['required', 'string', Rule::in(array_keys($this->roles))],
            'division'       => ['nullable', 'string', 'max:100'],
            'phone'          => ['nullable', 'string', 'max:25'],
            'competition_id' => ['nullable', 'string', 'exists:competitions,id'],
            'is_active'      => ['boolean'],
        ], [
            'name.required'     => 'Nama pengguna wajib diisi.',
            'email.required'    => 'Email pengguna wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email ini sudah digunakan oleh akun lain.',
            'password.min'      => 'Kata sandi minimal 6 karakter.',
            'role.required'     => 'Peran/Role wajib dipilih.',
            'competition_id.exists' => 'Cabang lomba tidak ditemukan.',
        ]);

        // Pencegahan: Jika super admin terakhir mencoba mendowngrade rolenya sendiri
        if ($user->role === 'super_admin' && $validated['role'] !== 'super_admin') {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                return redirect()->back()->withErrors([
                    'role' => 'Sistem harus memiliki setidaknya satu Super Admin aktif.'
                ]);
            }
        }

        $updateData = [
            'name'           => $validated['name'],
            'email'          => strtolower(trim($validated['email'])),
            'role'           => $validated['role'],
            'division'       => $validated['division'] ?? null,
            'phone'          => $validated['phone'] ?? null,
            'competition_id' => $validated['competition_id'] ?? null,
            'is_active'      => $validated['is_active'] ?? $user->is_active,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->back()->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    /**
     * Beralih status aktif / non-aktif pengguna secara cepat
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        // Jangan izinkan pengguna menonaktifkan akunnya sendiri
        if (Auth::id() === $user->id) {
            return redirect()->back()->withErrors([
                'error' => 'Anda tidak dapat menonaktifkan akun Anda sendiri yang sedang digunakan.'
            ]);
        }

        // Jangan izinkan menonaktifkan Super Admin terakhir
        if ($user->role === 'super_admin' && $user->is_active) {
            $activeSuperAdmins = User::where('role', 'super_admin')->where('is_active', true)->count();
            if ($activeSuperAdmins <= 1) {
                return redirect()->back()->withErrors([
                    'error' => 'Tidak dapat menonaktifkan Super Admin aktif terakhir.'
                ]);
            }
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Akun {$user->name} berhasil {$statusText}.");
    }

    /**
     * Hapus pengguna dari sistem
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Jangan izinkan menghapus diri sendiri
        if (Auth::id() === $user->id) {
            return redirect()->back()->withErrors([
                'error' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.'
            ]);
        }

        // Jangan izinkan menghapus Super Admin terakhir
        if ($user->role === 'super_admin') {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                return redirect()->back()->withErrors([
                    'error' => 'Tidak dapat menghapus akun Super Admin terakhir pada sistem.'
                ]);
            }
        }

        $name = $user->name;
        $user->delete();

        return redirect()->back()->with('success', "Pengguna {$name} berhasil dihapus dari sistem.");
    }
}
