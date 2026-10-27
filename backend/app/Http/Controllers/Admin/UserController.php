<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\SipresensiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
        'ketua_panitia' => [
            'label' => 'Ketua Panitia',
            'badge' => 'dark',
            'description' => 'Pimpinan umum, pengawasan seluruh cabang lomba & TM'
        ],
        'wakil_ketua_panitia' => [
            'label' => 'Wakil Ketua Panitia',
            'badge' => 'primary',
            'description' => 'Pendamping teknis & operasional seluruh lomba & TM'
        ],
        'sekretaris' => [
            'label' => 'Sekretaris Pelaksana',
            'badge' => 'secondary',
            'description' => 'Administrasi lomba, formulir builder, juknis, dan TM'
        ],
        'koordinator_lomba' => [
            'label' => 'Koordinator Lomba',
            'badge' => 'warning',
            'description' => 'Penanggung jawab teknis cabang lomba spesifik & TM'
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
            ->select(
                'id', 'name', 'email', 'nip', 'role', 'division', 'phone', 'competition_id', 'is_active', 'must_change_password', 'avatar', 'created_at',
                DB::raw("(CASE WHEN password IS NOT NULL AND password != '' THEN 1 ELSE 0 END) as has_password")
            );

        // 1. Filter Pencarian Nama, Email, NIP, atau No. Telepon
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
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

        $activeRoles = Role::all()->mapWithKeys(function ($r) {
            return [
                $r->name => [
                    'label' => $r->label,
                    'badge' => $r->badge_color,
                    'description' => $r->description,
                ]
            ];
        })->toArray();

        return Inertia::render('Admin/Users/Index', [
            'users'        => $users,
            'filters'      => $request->only(['search', 'role', 'division', 'status']),
            'metrics'      => $metrics,
            'competitions' => $competitions,
            'divisions'    => $this->divisions,
            'roles'        => !empty($activeRoles) ? $activeRoles : $this->roles,
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
            'nip'            => ['nullable', 'string', 'max:50'],
            'password'       => ['required', 'string', 'min:6'],
            'role'           => ['required', 'string', 'exists:roles,name'],
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
            'role.exists'       => 'Peran/Role yang dipilih tidak valid dalam sistem.',
            'competition_id.exists' => 'Cabang lomba tidak ditemukan.',
        ]);

        $roleObj = Role::where('name', $validated['role'])->first();

        $avatarPath = null;
        if ($request->filled('avatar_data')) {
            $avatarPath = $this->saveAvatarData($request->input('avatar_data'), null, 'user');
        }

        $user = User::create([
            'name'           => $validated['name'],
            'email'          => strtolower(trim($validated['email'])),
            'nip'            => !empty($validated['nip']) ? trim($validated['nip']) : null,
            'password'       => Hash::make($validated['password']),
            'must_change_password' => false,
            'role'           => $validated['role'],
            'role_id'        => $roleObj?->id,
            'division'       => $validated['division'] ?? null,
            'phone'          => $validated['phone'] ?? null,
            'competition_id' => $validated['competition_id'] ?? null,
            'is_active'      => $validated['is_active'] ?? true,
            'avatar'         => $avatarPath,
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
            'nip'            => ['nullable', 'string', 'max:50'],
            'password'       => ['nullable', 'string', 'min:6'],
            'role'           => ['required', 'string', 'exists:roles,name'],
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
            'role.exists'       => 'Peran/Role yang dipilih tidak valid dalam sistem.',
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

        $roleObj = Role::where('name', $validated['role'])->first();

        $updateData = [
            'name'           => $validated['name'],
            'email'          => strtolower(trim($validated['email'])),
            'nip'            => !empty($validated['nip']) ? trim($validated['nip']) : null,
            'role'           => $validated['role'],
            'role_id'        => $roleObj?->id,
            'division'       => $validated['division'] ?? null,
            'phone'          => $validated['phone'] ?? null,
            'competition_id' => $validated['competition_id'] ?? null,
            'is_active'      => $validated['is_active'] ?? $user->is_active,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
            $updateData['must_change_password'] = false;
        }

        if ($request->boolean('remove_avatar')) {
            $this->deleteAvatarFile($user->avatar);
            $updateData['avatar'] = null;
        } elseif ($request->filled('avatar_data')) {
            $newAvatar = $this->saveAvatarData($request->input('avatar_data'), $user->avatar, 'user_' . $user->id);
            if ($newAvatar) {
                $updateData['avatar'] = $newAvatar;
            }
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

    /**
     * Preview data guru dari Sipresensi sebelum sinkronisasi
     * (Menggunakan SipresensiService REST API / Isolated CLI Runner tanpa direct DB coupling)
     */
    public function previewSipresensiTeachers(Request $request)
    {
        try {
            $fetch = SipresensiService::fetchTeachers();

            if (empty($fetch['success'])) {
                return response()->json([
                    'success'   => false,
                    'connected' => false,
                    'message'   => $fetch['message'] ?? 'Layanan Sipresensi tidak dapat dihubungi.',
                    'service'   => [
                        'mode'   => 'Layanan API / Service Lokal',
                        'status' => 'Terputus / Offline',
                    ],
                ], 500);
            }

            $teachers = $fetch['data'] ?? [];

            // Ambil semua pengguna Semarak yang sudah ada untuk pengecekan duplikasi
            $existingUsers = User::select('id', 'name', 'email', 'nip', 'phone', 'role', 'division')->get();
            $existingEmails = $existingUsers->pluck('email')->map(fn($e) => strtolower(trim($e)))->all();
            $existingNips = $existingUsers->whereNotNull('nip')->pluck('nip')->map(fn($n) => trim($n))->filter()->all();
            $existingPhones = $existingUsers->whereNotNull('phone')->pluck('phone')->map(fn($p) => preg_replace('/[^0-9]/', '', $p))->filter()->all();

            $mappedTeachers = [];
            foreach ($teachers as $t) {
                $positionStr = $t['position'] ?? (!empty($t['positions']) ? implode(', ', $t['positions']) : 'Guru');
                $suggestedDivision = $this->mapPositionToDivision($positionStr);

                // Normalisasi Email & NIP
                $nip = trim($t['nip'] ?? $t['username'] ?? '');
                $email = strtolower(trim($t['email'] ?? ''));
                if (empty($email)) {
                    $email = strtolower("{$nip}@sipresensi.local");
                }

                // Normalisasi Telepon / WhatsApp
                $phone = !empty($t['phone']) ? trim($t['phone']) : null;
                $cleanPhone = $phone ? preg_replace('/[^0-9]/', '', $phone) : null;

                // Cek apakah sudah ada di Semarak (cek NIP, email, nomor telepon, atau nama)
                $match = null;
                if ($nip && in_array($nip, $existingNips)) {
                    $match = $existingUsers->first(fn($u) => trim((string)$u->nip) === $nip);
                } elseif ($email && in_array($email, $existingEmails)) {
                    $match = $existingUsers->first(fn($u) => strtolower(trim($u->email)) === $email);
                } elseif ($cleanPhone && in_array($cleanPhone, $existingPhones)) {
                    $match = $existingUsers->first(fn($u) => preg_replace('/[^0-9]/', '', (string)$u->phone) === $cleanPhone);
                } else {
                    $cleanName = strtolower(trim($t['name'] ?? ''));
                    $match = $existingUsers->first(fn($u) => strtolower(trim($u->name)) === $cleanName);
                }

                $mappedTeachers[] = [
                    'sipresensi_id'       => $t['id'],
                    'name'                => $t['name'],
                    'email'               => $email,
                    'nip'                 => $nip ?: null,
                    'username'            => $t['username'] ?? null,
                    'phone'               => $phone,
                    'gender'              => $t['gender'] ?? null,
                    'status_guru'         => $t['status_guru'] ?? null,
                    'sipresensi_position' => $positionStr,
                    'suggested_division'  => $suggestedDivision,
                    'already_exists'      => (bool) $match,
                    'existing_user'       => $match ? [
                        'id'       => $match->id,
                        'name'     => $match->name,
                        'email'    => $match->email,
                        'nip'      => $match->nip,
                        'role'     => $match->role,
                        'division' => $match->division,
                    ] : null,
                ];
            }

            return response()->json([
                'success'    => true,
                'connected'  => true,
                'source'     => $fetch['source'] ?? 'service',
                'service'    => [
                    'mode'   => ($fetch['source'] === 'http_api') ? 'REST API Sipresensi' : 'Isolated Artisan Service Lokal',
                    'status' => 'Terhubung Aktif',
                ],
                'total'      => count($mappedTeachers),
                'teachers'   => $mappedTeachers,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success'    => false,
                'connected'  => false,
                'message'    => 'Gagal mengambil data dari Sipresensi: ' . $e->getMessage(),
                'service'    => [
                    'mode'   => 'Layanan API / Service Lokal',
                    'status' => 'Error: ' . $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Proses sinkronisasi guru dari Sipresensi ke tabel users Semarak
     */
    public function syncSipresensiTeachers(Request $request)
    {
        $validated = $request->validate([
            'teacher_ids'      => ['nullable', 'array'],
            'teacher_ids.*'    => ['integer'],
            'default_role'     => ['required', 'string', Rule::in(array_keys($this->roles))],
            'default_division' => ['nullable', 'string', 'max:100'],
            'update_existing'  => ['boolean'],
        ]);

        $defaultRole = $validated['default_role'] ?? 'panitia';
        $updateExisting = (bool) ($validated['update_existing'] ?? false);
        $targetIds = $validated['teacher_ids'] ?? [];

        try {
            $fetch = SipresensiService::fetchTeachers();
            if (empty($fetch['success'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil data guru dari Sipresensi: ' . ($fetch['message'] ?? 'Layanan tidak dapat dihubungi.'),
                ], 500);
            }

            $allTeachers = $fetch['data'] ?? [];
            if (!empty($targetIds)) {
                $teachers = array_filter($allTeachers, fn($t) => in_array($t['id'], $targetIds));
            } else {
                $teachers = $allTeachers;
            }

            $createdCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $syncedDetails = [];

            DB::beginTransaction();

            foreach ($teachers as $t) {
                // Email & NIP
                $nip = trim($t['nip'] ?? $t['username'] ?? '');
                $email = strtolower(trim($t['email'] ?? ''));
                if (empty($email)) {
                    $email = strtolower("{$nip}@sipresensi.local");
                }

                // Phone
                $phone = !empty($t['phone']) ? trim($t['phone']) : null;
                $cleanPhone = $phone ? preg_replace('/[^0-9]/', '', $phone) : null;

                // Suggested Division
                $positionStr = $t['position'] ?? (!empty($t['positions']) ? implode(', ', $t['positions']) : 'Guru');
                $division = $validated['default_division'] ?: $this->mapPositionToDivision($positionStr);

                // Cek user yang sudah ada by NIP, email, atau phone
                $existing = User::where(function ($q) use ($nip, $email, $cleanPhone) {
                    if ($nip) {
                        $q->where('nip', $nip);
                    }
                    if ($email) {
                        $q->orWhere('email', $email);
                    }
                    if ($cleanPhone) {
                        $q->orWhere('phone', $cleanPhone);
                    }
                })->first();

                if ($existing) {
                    if ($updateExisting) {
                        $existingUpdate = [
                            'name'   => $t['name'],
                            'gender' => $t['gender'] ?? $existing->gender,
                        ];
                        if ($nip && empty($existing->nip)) {
                            $existingUpdate['nip'] = $nip;
                        }
                        if ($phone && empty($existing->phone)) {
                            $existingUpdate['phone'] = $phone;
                        }
                        if (empty($existing->division) && $division) {
                            $existingUpdate['division'] = $division;
                        }
                        // Jika akun belum punya password, pastikan flag must_change_password tetap true
                        if (empty($existing->password)) {
                            $existingUpdate['must_change_password'] = true;
                        }
                        $existing->update($existingUpdate);

                        $updatedCount++;
                        $syncedDetails[] = [
                            'name'   => $t['name'],
                            'email'  => $email,
                            'nip'    => $nip,
                            'status' => 'updated',
                        ];
                    } else {
                        $skippedCount++;
                        $syncedDetails[] = [
                            'name'   => $t['name'],
                            'email'  => $email,
                            'nip'    => $nip,
                            'status' => 'skipped',
                        ];
                    }
                } else {
                    // Password hasil sinkron Sipresensi sengaja KOSONG (belum diatur)
                    // Panitia masuk via SSO Sipresensi atau OTP WA, dan wajib buat password saat pertama kali login
                    User::create([
                        'name'                 => $t['name'],
                        'email'                => $email,
                        'nip'                  => $nip ?: null,
                        'password'             => null,
                        'must_change_password' => true,
                        'role'                 => $defaultRole,
                        'division'             => $division,
                        'phone'                => $phone,
                        'gender'               => $t['gender'] ?? null,
                        'is_active'            => true,
                        'competition_id'       => null,
                    ]);

                    $createdCount++;
                    $syncedDetails[] = [
                        'name'   => $t['name'],
                        'email'  => $email,
                        'nip'    => $nip,
                        'status' => 'created',
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success'       => true,
                'message'       => "Sinkronisasi berhasil: {$createdCount} akun baru ditambahkan, {$updatedCount} akun diperbarui, {$skippedCount} akun dilewati.",
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'details'       => $syncedDetails,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat melakukan sinkronisasi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Petakan nama jabatan di Sipresensi ke divisi panitia Semarak
     */
    protected function mapPositionToDivision(?string $position): string
    {
        if (!$position) return 'Acara & Lomba';
        $pos = strtolower($position);

        if (str_contains($pos, 'kepala madrasah') || str_contains($pos, 'pimpinan') || str_contains($pos, 'waka') || str_contains($pos, 'direktur')) {
            return 'BPH / Pimpinan';
        }
        if (str_contains($pos, 'tata usaha') || str_contains($pos, 'sekretariat') || str_contains($pos, 'administrasi')) {
            return 'Kesekretariatan & Pendaftaran';
        }
        if (str_contains($pos, 'bendahara') || str_contains($pos, 'keuangan')) {
            return 'Bendahara & Keuangan';
        }
        if (str_contains($pos, 'humas') || str_contains($pos, 'it') || str_contains($pos, 'komputer') || str_contains($pos, 'lab') || str_contains($pos, 'media') || str_contains($pos, 'publikasi')) {
            return 'Publikasi & IT';
        }
        if (str_contains($pos, 'sarpras') || str_contains($pos, 'perlengkapan') || str_contains($pos, 'venue') || str_contains($pos, 'sarana')) {
            return 'Perlengkapan & Venue';
        }
        if (str_contains($pos, 'wasit') || str_contains($pos, 'juri')) {
            return 'Wasit & Juri';
        }
        if (str_contains($pos, 'keamanan') || str_contains($pos, 'medis') || str_contains($pos, 'kesehatan') || str_contains($pos, 'satpam')) {
            return 'Keamanan & Medis';
        }
        if (str_contains($pos, 'konsumsi') || str_contains($pos, 'dapur') || str_contains($pos, 'makan')) {
            return 'Konsumsi';
        }
        return 'Acara & Lomba';
    }

    /**
     * Simpan gambar avatar dari data URL base64 ke storage public.
     */
    protected function saveAvatarData(string $base64OrFile, ?string $oldAvatar = null, string $prefix = 'user'): ?string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64OrFile, $matches)) {
            $imageType = strtolower($matches[1]);
            if (!in_array($imageType, ['jpeg', 'jpg', 'png', 'webp'])) {
                $imageType = 'jpg';
            }
            $base64Data = substr($base64OrFile, strpos($base64OrFile, ',') + 1);
            $decodedData = base64_decode($base64Data);

            if ($decodedData === false) {
                return null;
            }

            // Hapus avatar lama jika ada di storage public
            $this->deleteAvatarFile($oldAvatar);

            $filename = 'avatars/' . $prefix . '_' . uniqid() . '_' . time() . '.' . ($imageType === 'jpeg' ? 'jpg' : $imageType);
            Storage::disk('public')->put($filename, $decodedData);
            return $filename;
        }

        return null;
    }

    /**
     * Hapus berkas avatar fisik dari disk storage.
     */
    protected function deleteAvatarFile(?string $avatarPath): void
    {
        if (empty($avatarPath)) {
            return;
        }

        $cleanPath = ltrim(str_replace(['/storage/', 'storage/'], '', $avatarPath), '/');
        if (Storage::disk('public')->exists($cleanPath)) {
            Storage::disk('public')->delete($cleanPath);
        }
    }

    /**
     * Ubah foto profil akun pengguna yang sedang login.
     */
    public function updateMyAvatar(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user) {
            return redirect()->back()->with('error', 'Sesi login telah kedaluwarsa.');
        }

        $request->validate([
            'avatar_data' => ['required', 'string'],
        ], [
            'avatar_data.required' => 'Data gambar foto profil wajib disertakan.',
        ]);

        $newPath = $this->saveAvatarData($request->input('avatar_data'), $user->avatar, 'my_avatar_' . $user->id);
        if (!$newPath) {
            return redirect()->back()->with('error', 'Gagal memproses data gambar foto profil.');
        }

        $user->update(['avatar' => $newPath]);

        return redirect()->back()->with('success', 'Foto profil Anda berhasil disesuaikan dan diperbarui!');
    }

    /**
     * Hapus foto profil akun pengguna yang sedang login.
     */
    public function removeMyAvatar()
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user) {
            return redirect()->back()->with('error', 'Sesi login telah kedaluwarsa.');
        }

        $this->deleteAvatarFile($user->avatar);
        $user->update(['avatar' => null]);

        return redirect()->back()->with('success', 'Foto profil berhasil dihapus dan dikembalikan ke inisial nama.');
    }

    /**
     * Ubah foto pengguna lain (oleh Admin / Super Admin)
     */
    public function updateUserAvatar(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'avatar_data' => ['required', 'string'],
        ], [
            'avatar_data.required' => 'Data gambar foto profil wajib disertakan.',
        ]);

        $newPath = $this->saveAvatarData($request->input('avatar_data'), $user->avatar, 'user_' . $user->id);
        if (!$newPath) {
            return redirect()->back()->with('error', 'Gagal memproses data gambar foto.');
        }

        $user->update(['avatar' => $newPath]);

        return redirect()->back()->with('success', "Foto profil pengguna {$user->name} berhasil diperbarui!");
    }

    /**
     * Hapus foto pengguna lain (oleh Admin / Super Admin)
     */
    public function removeUserAvatar($id)
    {
        $user = User::findOrFail($id);

        $this->deleteAvatarFile($user->avatar);
        $user->update(['avatar' => null]);

        return redirect()->back()->with('success', "Foto profil pengguna {$user->name} berhasil dihapus.");
    }
}
