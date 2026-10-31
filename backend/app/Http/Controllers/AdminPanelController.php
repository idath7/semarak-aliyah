<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Registration;
use App\Models\Competition;
use App\Models\Category;
use App\Models\Sponsor;
use App\Models\Gallery;
use App\Models\Setting;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class AdminPanelController extends Controller
{
    public function index(Request $request)
    {
        $query = Registration::orderBy('id', 'desc');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('school_name', 'like', "%{$s}%")
                  ->orWhere('mentor_name', 'like', "%{$s}%")
                  ->orWhere('reg_code', 'like', "%{$s}%")
                  ->orWhere('competition_title', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        if ($request->filled('competition')) {
            $query->where('competition_id', $request->competition);
        }

        $registrations = $query->get();
        $competitions = Competition::orderBy('id', 'asc')->get();

        $statsData = Registration::toBase()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN payment_status = 'Terverifikasi' THEN 1 ELSE 0 END) as terverifikasi,
                SUM(CASE WHEN payment_status = 'Menunggu Verifikasi' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN payment_status = 'Terverifikasi' THEN total_fee ELSE 0 END) as total_dana
            ")
            ->first();

        $totalPendaftar = (int)($statsData->total ?? 0);
        $terverifikasi = (int)($statsData->terverifikasi ?? 0);
        $pending = (int)($statsData->pending ?? 0);
        $totalDana = (int)($statsData->total_dana ?? 0);

        $totalMaxQuota = $competitions->sum('max_quota');
        $totalCurrentQuota = $competitions->sum('current_quota');
        $quotaPercentage = $totalMaxQuota > 0 ? round(($totalCurrentQuota / $totalMaxQuota) * 100) : 0;

        $stats = [
            'total_registrations' => $totalPendaftar,
            'verified_registrations' => $terverifikasi,
            'verified_count' => $terverifikasi,
            'pending_registrations' => $pending,
            'pending_count' => $pending,
            'total_fee' => $totalDana,
            'total_quota_max' => $totalMaxQuota,
            'total_quota_used' => $totalCurrentQuota,
            'quota_filled_percentage' => $quotaPercentage,
        ];

        // Build Recent Activity Timeline (Themesbrand Velzon Activity Design)
        $activities = [];

        // 1. Log Aktivitas Panitia (Login, Logout, Verifikasi, Operasional) dari ActivityLog
        try {
            $panitiaLogs = \App\Models\ActivityLog::orderBy('created_at', 'desc')->take(25)->get();
            foreach ($panitiaLogs as $log) {
                $activities[] = [
                    'id'          => 'log-' . $log->id,
                    'type'        => $log->activity_type ?: ($log->action === 'login' || $log->action === 'logout' ? 'auth' : 'panitia'),
                    'action'      => $log->action,
                    'title'       => $log->title,
                    'highlight'   => $log->highlight ?: $log->user_name,
                    'desc'        => $log->description,
                    'badge_icon'  => $log->badge_icon ?: ($log->action === 'login' ? 'ri-login-box-line' : ($log->action === 'logout' ? 'ri-logout-box-r-line' : 'ri-shield-user-line')),
                    'badge_class' => $log->badge_class ?: ($log->action === 'login' ? 'bg-success-subtle text-success' : ($log->action === 'logout' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary')),
                    'time_str'    => $log->created_at ? $log->created_at->diffForHumans() : 'Baru saja',
                    'timestamp'   => $log->created_at ? $log->created_at->timestamp : time(),
                    'reg_code'    => $log->properties['reg_code'] ?? ($log->action === 'login' ? 'LOGIN' : ($log->action === 'logout' ? 'LOGOUT' : 'PANITIA')),
                    'user_name'   => $log->user_name,
                    'role'        => $log->role,
                    'ip_address'  => $log->ip_address,
                ];
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Recent Activity Log load error: ' . $e->getMessage());
        }

        // 2. Aktivitas Pendaftaran & Delegasi Peserta
        $recentRegs = Registration::orderBy('updated_at', 'desc')->take(15)->get();
        
        foreach ($recentRegs as $reg) {
            if ($reg->checkin_status === 'checked_in' || $reg->checkin_status === 'hadir') {
                $activities[] = [
                    'id' => 'checkin-' . $reg->id,
                    'type' => 'checkin',
                    'title' => 'Gate Check-in Kontingen',
                    'highlight' => $reg->school_name,
                    'desc' => "Kontingen telah scan barcode dan diverifikasi masuk gate arena ({$reg->competition_title})",
                    'badge_icon' => 'ri-qr-code-line',
                    'badge_class' => 'bg-info-subtle text-info',
                    'time_str' => $reg->updated_at ? $reg->updated_at->diffForHumans() : 'Hari ini',
                    'timestamp' => ($reg->updated_at ? $reg->updated_at->timestamp : time()) + 2,
                    'reg_code' => $reg->reg_code,
                ];
            }

            if ($reg->payment_status === 'Terverifikasi') {
                $activities[] = [
                    'id' => 'verify-' . $reg->id,
                    'type' => 'verification',
                    'title' => 'Verifikasi Pembayaran Sukses',
                    'highlight' => $reg->school_name,
                    'desc' => "Berkas dan transfer Rp " . number_format($reg->total_fee, 0, ',', '.') . " telah diverifikasi sah",
                    'badge_icon' => 'ri-shield-check-line',
                    'badge_class' => 'bg-success-subtle text-success',
                    'time_str' => $reg->updated_at ? $reg->updated_at->diffForHumans() : 'Hari ini',
                    'timestamp' => ($reg->updated_at ? $reg->updated_at->timestamp : time()) + 1,
                    'reg_code' => $reg->reg_code,
                ];
            }

            $activities[] = [
                'id' => 'reg-' . $reg->id,
                'type' => 'registration',
                'title' => 'Pendaftaran Delegasi Baru',
                'highlight' => $reg->school_name,
                'desc' => "Mendaftarkan delegasi cabang {$reg->competition_title} (#{$reg->reg_code})",
                'badge_icon' => 'ri-user-add-line',
                'badge_class' => 'bg-primary-subtle text-primary',
                'time_str' => $reg->created_at ? $reg->created_at->diffForHumans() : 'Baru saja',
                'timestamp' => $reg->created_at ? $reg->created_at->timestamp : time(),
                'reg_code' => $reg->reg_code,
            ];
        }

        $activities[] = [
            'id' => 'system-juknis',
            'type' => 'system',
            'title' => 'Juknis & Sistem Pendaftaran Dibuka',
            'highlight' => 'Panitia Semarak XIV',
            'desc' => 'Portal resmi Semarak Aliyah XIV 2026 telah dibuka untuk seluruh kontingen se-Jawa Timur',
            'badge_icon' => 'ri-megaphone-line',
            'badge_class' => 'bg-warning-subtle text-warning',
            'time_str' => 'Edisi XIV',
            'timestamp' => strtotime('2026-10-01 08:00:00'),
            'reg_code' => 'SEMARAK-2026',
        ];

        usort($activities, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });
        $activities = array_slice($activities, 0, 25);

        return \Inertia\Inertia::render('Admin/Dashboard', [
            'registrations' => $registrations,
            'competitions' => $competitions,
            'stats' => $stats,
            'activities' => $activities,
        ]);
    }

    public function pendaftar(Request $request)
    {
        $query = Registration::orderBy('id', 'desc');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('school_name', 'like', "%{$s}%")
                  ->orWhere('mentor_name', 'like', "%{$s}%")
                  ->orWhere('reg_code', 'like', "%{$s}%")
                  ->orWhere('competition_title', 'like', "%{$s}%")
                  ->orWhere('participants_names', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('payment_status', $request->status);
        }

        if ($request->filled('competition') && $request->competition !== 'all') {
            $query->where('competition_id', $request->competition);
        }

        $registrations = $query->get();
        $competitions = Competition::orderBy('id', 'asc')->get();

        $statsData = Registration::toBase()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN payment_status = 'Terverifikasi' THEN 1 ELSE 0 END) as terverifikasi,
                SUM(CASE WHEN payment_status = 'Menunggu Verifikasi' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN payment_status = 'Ditolak' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN payment_status = 'Terverifikasi' THEN total_fee ELSE 0 END) as total_dana
            ")
            ->first();

        $totalPendaftar = (int)($statsData->total ?? 0);
        $terverifikasi = (int)($statsData->terverifikasi ?? 0);
        $pending = (int)($statsData->pending ?? 0);
        $rejected = (int)($statsData->rejected ?? 0);
        $totalDana = (int)($statsData->total_dana ?? 0);

        $stats = [
            'total_registrations' => $totalPendaftar,
            'verified_registrations' => $terverifikasi,
            'pending_registrations' => $pending,
            'rejected_registrations' => $rejected,
            'total_fee' => $totalDana,
        ];

        $masterSchools = \App\Models\School::orderBy('name', 'asc')->get(['id', 'npsn', 'name', 'jenjang', 'kecamatan', 'alamat']);
        $packageAllinPrice = (int)\App\Models\Setting::get('package_allin_price', 200000);

        return \Inertia\Inertia::render('Admin/Pendaftar', [
            'registrations' => $registrations,
            'competitions' => $competitions,
            'master_schools' => $masterSchools,
            'package_allin_price' => $packageAllinPrice,
            'bank_accounts' => Setting::getBankAccounts(),
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'competition']),
        ]);
    }

    public function pendaftarDetail(string|int $id)
    {
        $registration = Registration::with(['competition', 'user'])->findOrFail($id);
        $competition = $registration->competition;

        // Skema Form Builder cabang lomba (jika ada form fields dinamis)
        $formFields = is_array($competition?->custom_fields) ? $competition->custom_fields : [];
        $formSettings = is_array($competition?->form_settings) ? $competition->form_settings : [];

        // Navigasi cepat: pendaftar sebelumnya dan berikutnya
        $prevReg = Registration::where('id', '<', $registration->id)
            ->orderBy('id', 'desc')
            ->select('id', 'reg_code', 'school_name', 'competition_title')
            ->first();

        $nextReg = Registration::where('id', '>', $registration->id)
            ->orderBy('id', 'asc')
            ->select('id', 'reg_code', 'school_name', 'competition_title')
            ->first();

        // Jika pendaftaran ini bagian dari Paket All-In, ambil pendaftaran kontingen yang sama
        $relatedRegistrations = [];
        if ($registration->package_type === 'Paket All-In') {
            $relatedRegistrations = Registration::where('package_type', 'Paket All-In')
                ->where(function ($q) use ($registration) {
                    $ownerId = $registration->registrant_id ?: $registration->user_id;
                    if ($ownerId) {
                        $q->where('registrant_id', $ownerId)->orWhere('user_id', $ownerId);
                    } else {
                        $q->where('school_name', $registration->school_name);
                    }
                })
                ->where('id', '!=', $registration->id)
                ->orderBy('competition_title', 'asc')
                ->get(['id', 'reg_code', 'competition_title', 'participants_names', 'registration_status', 'payment_status', 'total_fee']);
        }

        return \Inertia\Inertia::render('Admin/PendaftarDetail', [
            'registration' => $registration,
            'competition' => $competition,
            'formFields' => $formFields,
            'formSettings' => $formSettings,
            'prevRegistration' => $prevReg,
            'nextRegistration' => $nextReg,
            'relatedRegistrations' => $relatedRegistrations,
        ]);
    }

    /**
     * Cek otorisasi apakah user yang sedang login berhak mengubah / mengelola cabang lomba spesifik.
     */
    protected function authorizeCompetitionAccess(string|int|null $competitionId = null, ?string $requiredPermission = null): bool
    {
        /** @var User|null $user */
        $user = Auth::guard('web')->user();
        if (!$user) {
            return false;
        }

        // 1. Super Admin memiliki akses mutlak ke seluruh cabang lomba
        if ($user->isSuperAdmin()) {
            return true;
        }

        // 2. Pimpinan Panitia Utama (Admin, Ketua, Wakil Ketua, Sekretaris) berhak atas seluruh lomba
        if (in_array($user->role, ['admin', 'ketua_panitia', 'wakil_ketua_panitia', 'sekretaris', 'kesekretariatan'], true)) {
            if ($requiredPermission && !$user->hasPermission($requiredPermission)) {
                return false;
            }
            return true;
        }

        // 3. Jika aksi membutuhkan izin modul spesifik dan user tidak memilikinya (misal dicabut di pengaturan hak akses), tolak segera!
        if ($requiredPermission && !$user->hasPermission($requiredPermission)) {
            return false;
        }

        if (!$competitionId) {
            return false;
        }

        // 4. Untuk Koordinator Lomba atau panitia lainnya:
        // HANYA berhak jika cabang lomba ini adalah cabang lomba yang dikoordinasikan oleh user ini
        return $user->isCoordinatorFor($competitionId);
    }

    public function competitionsIndex(Request $request)
    {
        /** @var User|null $authUser */
        $authUser = Auth::guard('web')->user();
        $query = Competition::with([
                'coordinator:id,name,phone,email,role',
                'assignedCoordinators:id,name,phone,email,role,competition_id'
            ])
            ->withCount('registrations')
            ->orderBy('id', 'asc');

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('category', 'like', "%{$s}%")
                  ->orWhere('id', 'like', "%{$s}%");
            });
        }

        $competitions = $query->get();
        Category::ensureDefaultsExist();
        $categories = Category::withCount('competitions')->orderBy('id', 'asc')->get();
        $allComps = Competition::orderBy('id', 'asc')->get();

        $totalComps = $allComps->count();
        $openComps = $allComps->filter(function ($c) {
            return !$c->isRegistrationClosed();
        })->count();
        $closedComps = $totalComps - $openComps;
        $totalMaxQuota = (int)$allComps->sum('max_quota');
        $totalCurrentQuota = (int)$allComps->sum('current_quota');
        $quotaPercentage = $totalMaxQuota > 0 ? round(($totalCurrentQuota / $totalMaxQuota) * 100) : 0;

        $stats = [
            'total_competitions' => $totalComps,
            'open_count' => $openComps,
            'closed_count' => $closedComps,
            'total_quota_max' => $totalMaxQuota,
            'total_quota_used' => $totalCurrentQuota,
            'quota_filled_percentage' => $quotaPercentage,
        ];

        // Struktur Kepanitiaan Terkait Lomba & TM
        $ketuaPanitia = User::where('role', 'ketua_panitia')->select('id', 'name', 'phone', 'email', 'role')->get();
        $wakilKetuaPanitia = User::where('role', 'wakil_ketua_panitia')->select('id', 'name', 'phone', 'email', 'role')->get();
        $sekretaris = User::whereIn('role', ['sekretaris', 'kesekretariatan'])->select('id', 'name', 'phone', 'email', 'role')->get();
        
        $committeeUsers = User::where('is_active', true)
            ->whereIn('role', ['koordinator_lomba', 'panitia', 'kesekretariatan', 'sekretaris', 'admin', 'super_admin', 'ketua_panitia', 'wakil_ketua_panitia'])
            ->select('id', 'name', 'phone', 'email', 'role', 'division', 'competition_id')
            ->orderBy('name')
            ->get();

        $committeeStructure = [
            'ketua_panitia'       => $ketuaPanitia,
            'wakil_ketua_panitia' => $wakilKetuaPanitia,
            'sekretaris'          => $sekretaris,
            'committee_users'     => $committeeUsers,
            'current_user_id'     => $authUser?->id,
            'current_user_role'   => $authUser?->role,
            'current_user_comp'   => $authUser?->competition_id,
            'is_coordinator'      => $authUser?->role === 'koordinator_lomba' || !empty($authUser?->getCoordinatedCompetitionIds()),
        ];

        return \Inertia\Inertia::render('Admin/Competitions', [
            'competitions' => $competitions,
            'categories' => $categories,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category']),
            'committeeStructure' => $committeeStructure,
        ]);
    }

    public function storeCompetition(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::guard('web')->user();
        if ($user && $user->role === 'koordinator_lomba') {
            return redirect()->back()->with('error', 'Akses Ditolak: Pembuatan cabang lomba baru merupakan wewenang Ketua Panitia, Wakil Ketua, Sekretaris, atau Administrator.');
        }

        $messages = [
            'title.required' => 'Nama cabang lomba wajib diisi.',
            'title.max' => 'Nama cabang lomba tidak boleh lebih dari 150 karakter.',
            'id.unique' => 'Kode unik cabang lomba ini sudah terdaftar. Silakan gunakan kode lain.',
            'category.required' => 'Kategori bidang cabang lomba wajib dipilih.',
            'fee.required' => 'Biaya pendaftaran wajib ditentukan (masukkan angka 0 jika pendaftaran gratis).',
            'fee.numeric' => 'Biaya pendaftaran harus berupa angka nominal yang sah.',
            'fee.min' => 'Biaya pendaftaran tidak boleh bernilai negatif.',
            'max_quota.required' => 'Kapasitas kuota maksimal delegasi wajib ditentukan.',
            'max_quota.integer' => 'Kapasitas kuota harus berupa angka bilangan bulat.',
            'max_quota.min' => 'Kapasitas kuota minimal adalah 1 delegasi.',
            'participant_type.in' => 'Aturan peserta harus berupa pilihan Individu atau Tim/Regu.',
            'max_teams.integer' => 'Jumlah maksimal tim harus berupa bilangan bulat.',
            'max_teams.min' => 'Jumlah maksimal tim minimal 1 tim.',
            'min_participants.integer' => 'Jumlah minimal anggota harus berupa bilangan bulat.',
            'min_participants.min' => 'Jumlah minimal anggota minimal 1 orang.',
            'reserve_participants.integer' => 'Jumlah pemain cadangan harus berupa bilangan bulat.',
            'reserve_participants.min' => 'Jumlah pemain cadangan minimal 0 orang.',
            'max_participants.integer' => 'Jumlah maksimal peserta harus berupa bilangan bulat.',
            'max_participants.min' => 'Jumlah maksimal peserta minimal 1 orang.',
            'registration_deadline.max' => 'Tanggal tutup pendaftaran tidak boleh melebihi 100 karakter.',
        ];

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'id' => 'nullable|string|max:50|unique:competitions,id',
            'category' => 'required|string|max:50',
            'fee' => 'required|numeric|min:0',
            'max_quota' => 'required|integer|min:1',
            'participant_type' => 'nullable|in:individu,tim',
            'max_teams' => 'nullable|integer|min:1',
            'min_participants' => 'nullable|integer|min:1',
            'reserve_participants' => 'nullable|integer|min:0',
            'max_participants' => 'nullable|integer|min:1',
            'registration_deadline' => 'nullable|string|max:100',
            'participants_rule' => 'nullable|string|max:255',
            'tm_date' => 'nullable|string|max:150',
            'coordinator_id' => 'nullable|exists:users,id',
            'contact_person' => 'nullable|string|max:255',
            'coordinators' => 'nullable|array',
            'description' => 'nullable|string',
            'status' => 'nullable|in:open,closed',
            'registration_type' => 'nullable|in:online,direct_contact',
            'direct_registration_note' => 'nullable|string|max:1000',
            'direct_registration_template' => 'nullable|string',
        ], $messages);

        $id = !empty($validated['id']) ? \Illuminate\Support\Str::slug($validated['id']) : \Illuminate\Support\Str::slug($validated['title']);
        
        $originalId = $id;
        $counter = 1;
        while (Competition::where('id', $id)->exists()) {
            $id = $originalId . '-' . $counter;
            $counter++;
        }

        $category = strtolower($validated['category']);
        $catModel = Category::find($category);
        $categoryLabel = $catModel ? ($catModel->label ?: 'Bidang ' . $catModel->name) : ('Bidang ' . ucfirst($category));

        $partType = $validated['participant_type'] ?? 'individu';
        $maxTeams = (int) ($validated['max_teams'] ?? 1);
        $maxParts = (int) ($validated['max_participants'] ?? ($partType === 'tim' ? 10 : 1));
        $minParts = (int) ($validated['min_participants'] ?? ($partType === 'tim' ? min(5, $maxParts) : 1));
        $reserveParts = isset($validated['reserve_participants']) && $validated['reserve_participants'] !== '' ? (int)$validated['reserve_participants'] : 0;

        if ($minParts > $maxParts) {
            $maxParts = $minParts;
        }

        $maxReserveAllowed = max(0, $maxParts - $minParts);
        if ($reserveParts > $maxReserveAllowed) {
            return back()->withErrors([
                'reserve_participants' => "Jumlah pemain cadangan ({$reserveParts} orang) tidak boleh melebihi sisa kuota maksimal anggota tim ({$maxReserveAllowed} orang, dari {$maxParts} maksimal dikurangi {$minParts} minimal)."
            ])->withInput();
        }

        $partRule = $validated['participants_rule'] ?? '';
        if (empty($partRule)) {
            if ($partType === 'tim') {
                if ($reserveParts > 0) {
                    $partRule = "{$maxTeams} Tim ({$minParts} Inti + {$reserveParts} Cadangan)";
                } else if ($minParts === $maxParts) {
                    $partRule = "{$maxTeams} Tim ({$maxParts} Orang)";
                } else {
                    $partRule = "{$maxTeams} Tim ({$minParts}-{$maxParts} Anggota)";
                }
            } else {
                $partRule = $minParts === $maxParts ? "Individu ({$maxParts} Peserta)" : "Individu ({$minParts}-{$maxParts} Peserta)";
            }
        }

        // Format deadline strictly to dd/mm/yyyy
        $deadline = $validated['registration_deadline'] ?? null;
        if (!empty($deadline)) {
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $deadline, $m)) {
                $deadline = "{$m[3]}/{$m[2]}/{$m[1]}";
            }
        }

        $coordinators = $request->input('coordinators', []);
        $cleanCoordinators = array_values(array_filter($coordinators, function($c) {
            return !empty(trim($c['name'] ?? '')) || !empty(trim($c['contact'] ?? ''));
        }));
        foreach ($cleanCoordinators as &$c) {
            $c['role'] = 'Koordinator Lomba';
        }
        unset($c);

        $coordinatorId = $validated['coordinator_id'] ?? null;
        if (!$coordinatorId && !empty($cleanCoordinators)) {
            foreach ($cleanCoordinators as $c) {
                if (!empty($c['user_id']) && is_numeric($c['user_id'])) {
                    $coordinatorId = (int)$c['user_id'];
                    break;
                }
            }
        }

        if ($coordinatorId) {
            $coordUser = User::find($coordinatorId);
            if ($coordUser) {
                if (empty($cleanCoordinators)) {
                    $cleanCoordinators[] = [
                        'user_id' => $coordUser->id,
                        'name'    => $coordUser->name,
                        'contact' => $coordUser->phone ?: '',
                        'role'    => 'Koordinator Lomba',
                    ];
                } elseif (empty($cleanCoordinators[0]['user_id'])) {
                    $cleanCoordinators[0]['user_id'] = $coordUser->id;
                }
            }
        }

        $contactPersonStr = $validated['contact_person'] ?? '';
        if (!empty($cleanCoordinators)) {
            $contacts = [];
            foreach ($cleanCoordinators as $c) {
                $name = $c['name'] ?? '';
                $phone = $c['contact'] ?? '';
                $contacts[] = $phone ? "{$name} ({$phone})" : $name;
            }
            $contactPersonStr = implode(', ', $contacts);
        }

        // Helper to parse TM string
        $rawTm = !empty($validated['tm_date']) ? $validated['tm_date'] : '-';
        $initTmTime = '08:00 WIB';
        if (preg_match('/(\d{1,2})[:.](\d{2})/', $rawTm, $tmM)) {
            $initTmTime = sprintf('%02d:%02d WIB', (int)$tmM[1], (int)$tmM[2]);
        }
        $initTmDate = '26/10/2026';
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $rawTm, $tmM)) {
            $initTmDate = sprintf('%02d/%02d/%04d', (int)$tmM[1], (int)$tmM[2], (int)$tmM[3]);
        }
        $firstCoord = !empty($cleanCoordinators) ? $cleanCoordinators[0] : null;

        $comp = Competition::create([
            'id' => $id,
            'title' => $validated['title'],
            'category' => $category,
            'category_label' => $categoryLabel,
            'icon' => 'ri-medal-line',
            'fee' => (int) $validated['fee'],
            'max_quota' => (int) $validated['max_quota'],
            'current_quota' => 0,
            'status' => $validated['status'] ?? 'open',
            'registration_type' => $validated['registration_type'] ?? 'online',
            'direct_registration_note' => $validated['direct_registration_note'] ?? null,
            'direct_registration_template' => $validated['direct_registration_template'] ?? null,
            'participant_type' => $partType,
            'min_participants' => $minParts,
            'reserve_participants' => $reserveParts,
            'max_teams' => $maxTeams,
            'max_participants' => $maxParts,
            'registration_deadline' => $deadline,
            'participants_rule' => $partRule,
            'tm_date' => $rawTm,
            'coordinator_id' => $coordinatorId,
            'contact_person' => $contactPersonStr ?: 'Panitia Semarak XIV',
            'coordinators' => $cleanCoordinators,
            'description' => $validated['description'] ?? null,
            'scoring_rubrics' => [],
            'tm_settings' => [
                'tm_date' => $initTmDate,
                'tm_time' => $initTmTime,
                'location' => $category === 'olahraga' ? 'Gedung Olahraga / Lapangan Madrasah' : 'Aula Utama Madrasah Lt. 2',
                'meeting_type' => $id === 'mobile-legends' ? 'hybrid' : 'offline',
                'meeting_link' => '',
                'whatsapp_group' => '',
                'status' => 'upcoming',
                'pic_name' => $firstCoord['name'] ?? ('Koordinator ' . $validated['title']),
                'pic_contact' => $firstCoord['contact'] ?? '0838 5007 0767',
                'notes' => '',
                'rules_summary' => $partRule,
                'updated_at' => now()->toDateTimeString(),
            ]
        ]);

        // Sinkronisasi seluruh koordinator lomba (koordinator ke-1, ke-2, ke-3, dst)
        $allCoordUserIds = $comp->getAllCoordinatorUserIds();
        $coordRole = \App\Models\Role::where('name', 'koordinator_lomba')->first();
        if (!empty($allCoordUserIds)) {
            foreach ($allCoordUserIds as $uId) {
                $u = User::find($uId);
                if ($u) {
                    $updates = ['competition_id' => $comp->id];
                    if (in_array($u->role, ['panitia', 'koordinator_lomba']) || empty($u->role)) {
                        $updates['role'] = 'koordinator_lomba';
                        if ($coordRole) {
                            $updates['role_id'] = $coordRole->id;
                        }
                    }
                    $u->update($updates);
                }
            }
        }

        User::syncAllCoordinatorRoles();

        return redirect()->back()->with('success', "Data Cabang Lomba \"{$comp->title}\" berhasil disimpan ke sistem!");
    }

    public function updateCompetition(string|int $id, Request $request)
    {
        $comp = Competition::findOrFail($id);

        if (!$this->authorizeCompetitionAccess($id)) {
            return redirect()->back()->with('error', "Akses Ditolak: Anda hanya berwenang mengelola cabang lomba yang Anda koordinasi.");
        }

        $messages = [
            'title.required' => 'Nama cabang lomba wajib diisi.',
            'title.max' => 'Nama cabang lomba tidak boleh lebih dari 150 karakter.',
            'category.required' => 'Kategori bidang cabang lomba wajib dipilih.',
            'fee.required' => 'Biaya pendaftaran wajib ditentukan.',
            'fee.numeric' => 'Biaya pendaftaran harus berupa angka nominal yang sah.',
            'fee.min' => 'Biaya pendaftaran tidak boleh bernilai negatif.',
            'max_quota.required' => 'Kapasitas kuota maksimal delegasi wajib ditentukan.',
            'max_quota.integer' => 'Kapasitas kuota harus berupa angka bilangan bulat.',
            'max_quota.min' => 'Kapasitas kuota minimal adalah 1 delegasi.',
            'participant_type.in' => 'Aturan peserta harus berupa pilihan Individu atau Tim/Regu.',
            'max_teams.integer' => 'Jumlah maksimal tim harus berupa bilangan bulat.',
            'max_teams.min' => 'Jumlah maksimal tim minimal 1 tim.',
            'min_participants.integer' => 'Jumlah minimal anggota harus berupa bilangan bulat.',
            'min_participants.min' => 'Jumlah minimal anggota minimal 1 orang.',
            'reserve_participants.integer' => 'Jumlah pemain cadangan harus berupa bilangan bulat.',
            'reserve_participants.min' => 'Jumlah pemain cadangan minimal 0 orang.',
            'max_participants.integer' => 'Jumlah maksimal peserta harus berupa bilangan bulat.',
            'max_participants.min' => 'Jumlah maksimal peserta minimal 1 orang.',
            'registration_deadline.max' => 'Tanggal tutup pendaftaran tidak boleh melebihi 100 karakter.',
        ];

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'category' => 'required|string|max:50',
            'fee' => 'required|numeric|min:0',
            'max_quota' => 'required|integer|min:1',
            'participant_type' => 'nullable|in:individu,tim',
            'max_teams' => 'nullable|integer|min:1',
            'min_participants' => 'nullable|integer|min:1',
            'reserve_participants' => 'nullable|integer|min:0',
            'max_participants' => 'nullable|integer|min:1',
            'registration_deadline' => 'nullable|string|max:100',
            'participants_rule' => 'nullable|string|max:255',
            'tm_date' => 'nullable|string|max:150',
            'coordinator_id' => 'nullable|exists:users,id',
            'contact_person' => 'nullable|string|max:255',
            'coordinators' => 'nullable|array',
            'description' => 'nullable|string',
            'status' => 'nullable|in:open,closed',
            'registration_type' => 'nullable|in:online,direct_contact',
            'direct_registration_note' => 'nullable|string|max:1000',
            'direct_registration_template' => 'nullable|string',
        ], $messages);

        $category = strtolower($validated['category']);
        $catModel = Category::find($category);
        $categoryLabel = $catModel ? ($catModel->label ?: 'Bidang ' . $catModel->name) : ('Bidang ' . ucfirst($category));

        $partType = $validated['participant_type'] ?? ($comp->participant_type ?: 'individu');
        $maxTeams = (int) ($validated['max_teams'] ?? ($comp->max_teams ?: 1));
        $maxParts = (int) ($validated['max_participants'] ?? ($comp->max_participants ?: 1));
        $minParts = (int) ($validated['min_participants'] ?? ($comp->min_participants ?: ($partType === 'tim' ? min(5, $maxParts) : 1)));
        $reserveParts = isset($validated['reserve_participants']) && $validated['reserve_participants'] !== '' 
            ? (int)$validated['reserve_participants'] 
            : ($partType === 'tim' ? (int)($comp->reserve_participants ?? 0) : 0);

        if ($minParts > $maxParts) {
            $maxParts = $minParts;
        }

        $maxReserveAllowed = max(0, $maxParts - $minParts);
        if ($reserveParts > $maxReserveAllowed) {
            return back()->withErrors([
                'reserve_participants' => "Jumlah pemain cadangan ({$reserveParts} orang) tidak boleh melebihi sisa kuota maksimal anggota tim ({$maxReserveAllowed} orang, dari {$maxParts} maksimal dikurangi {$minParts} minimal)."
            ])->withInput();
        }

        $partRule = $validated['participants_rule'] ?? '';
        if (empty($partRule)) {
            if ($partType === 'tim') {
                if ($reserveParts > 0) {
                    $partRule = "{$maxTeams} Tim ({$minParts} Inti + {$reserveParts} Cadangan)";
                } else if ($minParts === $maxParts) {
                    $partRule = "{$maxTeams} Tim ({$maxParts} Orang)";
                } else {
                    $partRule = "{$maxTeams} Tim ({$minParts}-{$maxParts} Anggota)";
                }
            } else {
                $partRule = $minParts === $maxParts ? "Individu ({$maxParts} Peserta)" : "Individu ({$minParts}-{$maxParts} Peserta)";
            }
        }

        // Format deadline strictly to dd/mm/yyyy
        $deadline = $validated['registration_deadline'] ?? $comp->registration_deadline;
        if (!empty($deadline)) {
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $deadline, $m)) {
                $deadline = "{$m[3]}/{$m[2]}/{$m[1]}";
            }
        }

        $coordinators = $request->input('coordinators', []);
        $cleanCoordinators = array_values(array_filter($coordinators, function($c) {
            return !empty(trim($c['name'] ?? '')) || !empty(trim($c['contact'] ?? ''));
        }));
        foreach ($cleanCoordinators as &$c) {
            $c['role'] = 'Koordinator Lomba';
        }
        unset($c);

        $coordinatorId = array_key_exists('coordinator_id', $validated) ? $validated['coordinator_id'] : $comp->coordinator_id;
        if (!$coordinatorId && !empty($cleanCoordinators)) {
            foreach ($cleanCoordinators as $c) {
                if (!empty($c['user_id']) && is_numeric($c['user_id'])) {
                    $coordinatorId = (int)$c['user_id'];
                    break;
                }
            }
        }

        if ($coordinatorId) {
            $coordUser = User::find($coordinatorId);
            if ($coordUser) {
                if (empty($cleanCoordinators)) {
                    $cleanCoordinators[] = [
                        'user_id' => $coordUser->id,
                        'name'    => $coordUser->name,
                        'contact' => $coordUser->phone ?: '',
                        'role'    => 'Koordinator Lomba',
                    ];
                } elseif (empty($cleanCoordinators[0]['user_id'])) {
                    $cleanCoordinators[0]['user_id'] = $coordUser->id;
                }
            }
        }

        $contactPersonStr = $validated['contact_person'] ?? '';
        if (!empty($cleanCoordinators)) {
            $contacts = [];
            foreach ($cleanCoordinators as $c) {
                $name = $c['name'] ?? '';
                $phone = $c['contact'] ?? '';
                $contacts[] = $phone ? "{$name} ({$phone})" : $name;
            }
            $contactPersonStr = implode(', ', $contacts);
        }

        // Synchronize TM Settings with updated competition data
        $existingTm = $comp->tm_settings ?? [];
        $newTmDateRaw = array_key_exists('tm_date', $validated) ? ($validated['tm_date'] ?: '-') : $comp->tm_date;
        
        $syncTime = null;
        if (preg_match('/(\d{1,2})[:.](\d{2})/', $newTmDateRaw, $m)) {
            $syncTime = sprintf('%02d:%02d WIB', (int)$m[1], (int)$m[2]);
        }
        $syncDate = null;
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $newTmDateRaw, $m)) {
            $syncDate = sprintf('%02d/%02d/%04d', (int)$m[1], (int)$m[2], (int)$m[3]);
        } elseif (preg_match('/(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})/', $newTmDateRaw, $m)) {
            $months = [
                'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
                'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
                'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12',
            ];
            $mName = strtolower($m[2]);
            $syncDate = sprintf('%02d/%s/%04d', (int)$m[1], $months[$mName] ?? '10', (int)$m[3]);
        }

        $firstCoord = !empty($cleanCoordinators) ? $cleanCoordinators[0] : null;
        $updatedTm = array_merge($existingTm, [
            'tm_date' => $syncDate ?: ($existingTm['tm_date'] ?? '26/10/2026'),
            'tm_time' => $syncTime ?: ($existingTm['tm_time'] ?? '08:00 WIB'),
            'pic_name' => $firstCoord['name'] ?? ($existingTm['pic_name'] ?? ('Koordinator ' . $validated['title'])),
            'pic_contact' => $firstCoord['contact'] ?? ($existingTm['pic_contact'] ?? '0838 5007 0767'),
            'rules_summary' => $partRule . (!empty($validated['description']) ? "\n" . $validated['description'] : ''),
            'updated_at' => now()->toDateTimeString(),
        ]);

        // Manage User competition_id relationship
        if ($comp->coordinator_id && (int)$comp->coordinator_id !== (int)$coordinatorId) {
            User::where('id', $comp->coordinator_id)->where('competition_id', $comp->id)->update(['competition_id' => null]);
        }
        if ($coordinatorId) {
            User::where('id', $coordinatorId)->update(['competition_id' => $comp->id]);
        }

        $comp->update([
            'title' => $validated['title'],
            'category' => $category,
            'category_label' => $categoryLabel,
            'fee' => (int) $validated['fee'],
            'max_quota' => (int) $validated['max_quota'],
            'status' => $validated['status'] ?? $comp->status,
            'registration_type' => $validated['registration_type'] ?? ($comp->registration_type ?: 'online'),
            'direct_registration_note' => array_key_exists('direct_registration_note', $validated) ? $validated['direct_registration_note'] : $comp->direct_registration_note,
            'direct_registration_template' => array_key_exists('direct_registration_template', $validated) ? $validated['direct_registration_template'] : $comp->direct_registration_template,
            'participant_type' => $partType,
            'min_participants' => $minParts,
            'reserve_participants' => $reserveParts,
            'max_teams' => $maxTeams,
            'max_participants' => $maxParts,
            'registration_deadline' => $deadline,
            'participants_rule' => $partRule,
            'tm_date' => $newTmDateRaw,
            'coordinator_id' => $coordinatorId,
            'contact_person' => $contactPersonStr ?: $comp->contact_person,
            'coordinators' => $cleanCoordinators,
            'description' => $validated['description'] ?? $comp->description,
            'tm_settings' => $updatedTm,
        ]);

        // Auto-synchronize custom_fields repeater with updated competition rules
        if (is_array($comp->custom_fields) && !empty($comp->custom_fields)) {
            $comp->custom_fields = self::syncRepeaterWithCompetition($comp->custom_fields, $comp);
            $comp->save();
        }

        // Sinkronisasi seluruh koordinator lomba (koordinator ke-1, ke-2, ke-3, dst)
        $newCoordUserIds = $comp->getAllCoordinatorUserIds();
        $coordRole = \App\Models\Role::where('name', 'koordinator_lomba')->first();
        $panitiaRole = \App\Models\Role::where('name', 'panitia')->first();

        // 1. Lepas user yang sebelumnya ditugaskan ke lomba ini tapi sudah tidak lagi ada di daftar koordinator
        $previousCoordinators = User::where('competition_id', $comp->id)
            ->where('role', 'koordinator_lomba')
            ->whereNotIn('id', $newCoordUserIds)
            ->get();

        foreach ($previousCoordinators as $prevUser) {
            $otherComps = Competition::where('id', '!=', $comp->id)
                ->where(function($q) use ($prevUser) {
                    $q->where('coordinator_id', $prevUser->id)
                      ->orWhereNotNull('coordinators');
                })->get()->filter(fn($c) => $c->isUserCoordinator($prevUser->id));

            if ($otherComps->isEmpty()) {
                $prevUser->update([
                    'competition_id' => null,
                    'role' => 'panitia',
                    'role_id' => $panitiaRole?->id,
                ]);
            }
        }

        // 2. Terapkan hak akses koordinator untuk seluruh koordinator aktif
        foreach ($newCoordUserIds as $uId) {
            $u = User::find($uId);
            if ($u) {
                $updates = ['competition_id' => $comp->id];
                if (in_array($u->role, ['panitia', 'koordinator_lomba']) || empty($u->role)) {
                    $updates['role'] = 'koordinator_lomba';
                    if ($coordRole) {
                        $updates['role_id'] = $coordRole->id;
                    }
                }
                $u->update($updates);
            }
        }

        if (Setting::get('package_allin_auto_original_price', '1') === '1') {
            Setting::set('package_allin_original_price', (string)((int)Competition::sum('fee')));
        }

        User::syncAllCoordinatorRoles();

        return redirect()->back()->with('success', "Data Cabang Lomba \"{$comp->title}\" berhasil diperbarui!");
    }

    public function destroyCompetition(string|int $id)
    {
        $comp = Competition::findOrFail($id);
        
        /** @var User|null $currentUser */
        $currentUser = Auth::guard('web')->user();
        if (!$this->authorizeCompetitionAccess($id) || $currentUser?->role === 'koordinator_lomba') {
            return redirect()->back()->with('error', "Akses Ditolak: Penghapusan cabang lomba hanya dapat dilakukan oleh Ketua Panitia, Wakil Ketua, atau Administrator.");
        }

        $hasRegistrations = Registration::where('competition_id', $id)->count();
        if ($hasRegistrations > 0) {
            return redirect()->back()->with('error', "Cabang Lomba {$comp->title} tidak dapat dihapus karena sudah memiliki {$hasRegistrations} delegasi pendaftar!");
        }

        // Unlink users associated with this competition
        User::where('competition_id', $comp->id)->update(['competition_id' => null]);

        $title = $comp->title;
        $comp->delete();

        return redirect()->back()->with('success', "Cabang Lomba {$title} berhasil dihapus!");
    }

    public function storeCategory(Request $request)
    {
        Category::ensureDefaultsExist();

        $isUpdate = $request->filled('old_id');
        $oldId = $request->input('old_id');

        $messages = [
            'name.required' => 'Nama kategori bidang lomba wajib diisi.',
            'name.max' => 'Nama kategori bidang maksimal 100 karakter.',
            'id.required' => 'Kode / ID slug kategori wajib diisi.',
            'id.unique' => 'Kode unik kategori ini sudah digunakan. Silakan gunakan kode lain.',
        ];

        $validated = $request->validate([
            'id' => 'required|string|max:50' . ($isUpdate ? '' : '|unique:categories,id'),
            'name' => 'required|string|max:100',
            'label' => 'nullable|string|max:150',
            'color' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ], $messages);

        $slugId = \Illuminate\Support\Str::slug($validated['id']);
        $label = $validated['label'] ?: ('Bidang ' . $validated['name']);
        $color = $validated['color'] ?: 'primary';
        $icon = $validated['icon'] ?: 'ri-price-tag-3-line';

        if ($isUpdate && $oldId) {
            $cat = Category::findOrFail($oldId);
            $cat->update([
                'name' => $validated['name'],
                'label' => $label,
                'color' => $color,
                'icon' => $icon,
                'description' => $validated['description'] ?? null,
            ]);
            return redirect()->back()->with('success', "Kategori Bidang \"{$cat->name}\" berhasil diperbarui!");
        } else {
            if (Category::where('id', $slugId)->exists()) {
                return redirect()->back()->with('error', "Kode kategori \"{$slugId}\" sudah terdaftar. Silakan pilih kode lain.");
            }
            $cat = Category::create([
                'id' => $slugId,
                'name' => $validated['name'],
                'label' => $label,
                'color' => $color,
                'icon' => $icon,
                'description' => $validated['description'] ?? null,
            ]);
            return redirect()->back()->with('success', "Kategori Bidang \"{$cat->name}\" berhasil ditambahkan!");
        }
    }

    public function destroyCategory(string|int $id)
    {
        $cat = Category::findOrFail($id);
        $usedCount = Competition::where('category', $id)->count();
        if ($usedCount > 0) {
            return redirect()->back()->with('error', "Kategori \"{$cat->name}\" tidak dapat dihapus karena masih digunakan oleh {$usedCount} cabang lomba!");
        }

        $name = $cat->name;
        $cat->delete();
        return redirect()->back()->with('success', "Kategori Bidang \"{$name}\" berhasil dihapus!");
    }

    public function verify(string|int $id, Request $request)
    {
        $reg = Registration::findOrFail($id);

        // Validasi: Cegah verifikasi jika pendaftaran berstatus "Menunggu Pengisian Peserta" (belum final / ditunda)
        $isAttemptingVerify = (
            $request->input('registration_status') === 'Terverifikasi' || 
            $request->input('status') === 'Terverifikasi' ||
            (!$request->has('registration_status') && !$request->has('payment_status') && !$request->has('status'))
        );

        if ($reg->registration_status === 'Menunggu Pengisian Peserta' && $isAttemptingVerify) {
            $delegates = $reg->custom_fields['delegates'] ?? [];
            $hasRealDelegates = !empty($delegates) && is_array($delegates) && count(array_filter($delegates, fn($d) => !empty($d['name'] ?? null))) > 0;
            if (!$hasRealDelegates && ($reg->participants_names === 'Pengisian Ditunda (Diisi Nanti)' || $reg->participants_names === 'Belum Ada Delegasi (Perlu Input)' || empty($reg->participants_names))) {
                return redirect()->back()->with('error', 'Pendaftaran kontingen ini belum bisa diverifikasi karena pengisian delegasi peserta masih ditunda (belum lengkap). Silakan lengkapi data peserta terlebih dahulu melalui menu Kelola Delegasi.');
            }
        }

        if ($request->has('registration_status')) {
            $reg->registration_status = $request->input('registration_status');
        }

        if ($request->has('payment_status')) {
            $reg->payment_status = $request->input('payment_status');
        }

        // Backward compatibility if only 'status' is passed
        if ($request->has('status')) {
            $targetType = $request->input('type', 'all'); // 'registration', 'payment', 'all'
            if ($targetType === 'registration') {
                $reg->registration_status = $request->input('status');
            } elseif ($targetType === 'payment') {
                $reg->payment_status = $request->input('status');
            } else {
                $reg->payment_status = $request->input('status');
                $reg->registration_status = $request->input('status');
            }
        } elseif (!$request->has('registration_status') && !$request->has('payment_status')) {
            // Quick toggle default: toggle both
            $next = ($reg->payment_status === 'Terverifikasi' && $reg->registration_status === 'Terverifikasi') 
                ? 'Menunggu Verifikasi' 
                : 'Terverifikasi';
            $reg->payment_status = $next;
            $reg->registration_status = $next;
        }

        if ($request->filled('notes') || $request->filled('verification_notes')) {
            $reg->verification_notes = $request->input('notes', $request->input('verification_notes'));
        }
        $reg->save();

        // Jika pendaftaran ini adalah bagian dari Paket All-In, sinkronkan status pembayaran ke seluruh cabang All-In kontingen yang sama
        if ($reg->package_type === 'Paket All-In') {
            $updateData = ['payment_status' => $reg->payment_status];
            if ($reg->verification_notes) {
                $updateData['verification_notes'] = $reg->verification_notes;
            }
            Registration::where('package_type', 'Paket All-In')
                ->where(function ($q) use ($reg) {
                    $ownerId = $reg->registrant_id ?: $reg->user_id;
                    if ($ownerId) {
                        $q->where('registrant_id', $ownerId)->orWhere('user_id', $ownerId);
                    } else {
                        $q->where('school_name', $reg->school_name);
                    }
                })
                ->where('id', '!=', $reg->id)
                ->update($updateData);
        }

        // Auto-send WhatsApp verification notification via OpenWA if enabled
        try {
            if ($reg->wasChanged('payment_status') && in_array(strtolower((string)$reg->payment_status), ['terverifikasi', 'lunas', 'verified'])) {
                \App\Services\WhatsAppService::sendVerificationNotification($reg, 'payment');
            } elseif ($reg->wasChanged('registration_status') && in_array(strtolower((string)$reg->registration_status), ['terverifikasi', 'verified'])) {
                \App\Services\WhatsAppService::sendVerificationNotification($reg, 'registration');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Auto WA verification notification error: ' . $e->getMessage());
        }

        // Catat Log Aktivitas Panitia Verifikasi
        /** @var User|null $authUser */
        $authUser = Auth::guard('web')->user();
        \App\Models\ActivityLog::record(
            'verification',
            'Verifikasi Berkas & Pembayaran',
            "Panitia " . ($authUser ? $authUser->name : 'Sistem') . " memverifikasi pendaftaran {$reg->school_name} ({$reg->competition_title}): {$reg->payment_status}.",
            [
                'user' => $authUser,
                'highlight' => $reg->school_name,
                'activity_type' => 'verification',
                'properties' => ['reg_code' => $reg->reg_code, 'status' => $reg->payment_status],
            ]
        );

        if (!$request->header('X-Inertia') && ($request->ajax() || $request->wantsJson())) {
            return response()->json([
                'status' => 'success', 
                'message' => "Verifikasi pendaftaran & pembayaran {$reg->reg_code} berhasil diperbarui!",
                'data' => $reg
            ]);
        }

        return redirect()->back()->with('success', "Status verifikasi {$reg->reg_code} berhasil diperbarui!");
    }

    public function toggleQuota(string|int $id, Request $request)
    {
        $comp = Competition::findOrFail($id);
        $newStatus = $request->input('status', $comp->status === 'open' ? 'closed' : 'open');
        $quotaExpanded = false;

        if ($request->filled('max_quota')) {
            $newMaxQuota = (int) $request->input('max_quota');
            if ($newMaxQuota > $comp->current_quota) {
                $comp->max_quota = $newMaxQuota;
                $quotaExpanded = true;
            }
        }

        // Jika mencoba membuka padahal batas waktu penutupan pendaftaran telah lewat
        if ($newStatus === 'open' && $comp->isDeadlinePassed()) {
            return redirect()->back()->with('error', "Pendaftaran cabang lomba \"{$comp->title}\" tidak dapat dibuka karena telah melewati batas waktu pendaftaran ({$comp->registration_deadline}). Silakan perbarui tanggal batas pendaftaran melalui menu Edit terlebih dahulu.");
        }

        // Jika mencoba membuka padahal kuota penuh dan tidak diperluas
        if ($newStatus === 'open' && $comp->isQuotaFull() && !$quotaExpanded) {
            return redirect()->back()->with('error', "Pendaftaran cabang lomba \"{$comp->title}\" tidak dapat dibuka karena kapasitas kuota telah penuh ({$comp->max_quota} delegasi). Silakan perluas kuota maksimal terlebih dahulu.");
        }

        $comp->status = $newStatus;
        $comp->save();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'status' => 'success',
                'message' => "Status pendaftaran {$comp->title} diperbarui menjadi {$newStatus}!",
                'competition' => $comp
            ]);
        }

        if ($newStatus === 'open') {
            if ($quotaExpanded) {
                $message = "Pendaftaran cabang lomba \"{$comp->title}\" berhasil dibuka kembali dengan kuota baru {$comp->max_quota} delegasi!";
            } else {
                $sisa = max(0, $comp->max_quota - $comp->current_quota);
                $message = "Pendaftaran cabang lomba \"{$comp->title}\" berhasil dibuka kembali (Tersedia {$sisa} slot delegasi)!";
            }
        } else {
            $message = "Pendaftaran cabang lomba \"{$comp->title}\" berhasil ditutup sementara oleh panitia!";
        }

        return redirect()->back()->with('success', $message);
    }

    public static function getDefaultPresetsList(): array
    {
        $rawPresets = \Database\Seeders\FormFieldSeeder::getPresets();
        
        $meta = [
            'template_1_siswa' => [
                'title' => 'Template 1 Siswa (Individu / Tunggal)',
                'category' => 'Individu',
                'badge' => '1 Siswa',
                'badge_class' => 'bg-info-subtle text-info',
                'icon' => 'ri-user-line',
                'description' => 'Format untuk 1 peserta individu lengkap dengan identitas sekolah, pembina, biodata siswa, NISN, dan scan Kartu Pelajar (KTS).',
            ],
            'template_2_siswa' => [
                'title' => 'Template 2 Siswa (Tim / Duo)',
                'category' => 'Tim 2 Siswa',
                'badge' => '2 Siswa',
                'badge_class' => 'bg-primary-subtle text-primary',
                'icon' => 'ri-team-line',
                'description' => 'Format untuk tim 2 siswa (Ketua tim & Anggota tim) dengan biodata lengkap masing-masing peserta dan berkas persyaratan.',
            ],
            'template_3_siswa' => [
                'title' => 'Template 3 Siswa (Regu / Trio)',
                'category' => 'Regu 3 Siswa',
                'badge' => '3 Siswa',
                'badge_class' => 'bg-success-subtle text-success',
                'icon' => 'ri-group-line',
                'description' => 'Format untuk regu 3 siswa delegasi (Ketua regu & 2 Anggota) dengan biodata lengkap masing-masing peserta.',
            ],
            'template_4_siswa' => [
                'title' => 'Template 4 Siswa (Tim 4 Anggota)',
                'category' => 'Tim 4 Siswa',
                'badge' => '4 Siswa',
                'badge_class' => 'bg-warning-subtle text-warning',
                'icon' => 'ri-team-line',
                'description' => 'Format untuk tim 4 siswa delegasi dengan biodata lengkap masing-masing anggota tim.',
            ],
            'template_5_siswa' => [
                'title' => 'Template 5 Siswa (Tim 5 Anggota)',
                'category' => 'Tim 5 Siswa',
                'badge' => '5 Siswa',
                'badge_class' => 'bg-purple-subtle text-primary',
                'icon' => 'ri-team-line',
                'description' => 'Format untuk tim 5 siswa delegasi dengan biodata lengkap seluruh anggota tim.',
            ],
            'template_6_siswa' => [
                'title' => 'Template 6 Siswa (5 Inti + 1 Cadangan)',
                'category' => 'Tim 6 Siswa',
                'badge' => '6 Siswa',
                'badge_class' => 'bg-danger-subtle text-danger',
                'icon' => 'ri-shield-user-line',
                'description' => 'Format skuad tim 6 siswa (5 pemain inti + 1 cadangan) dengan biodata lengkap seluruh anggota tim.',
            ],
            'template_10_siswa' => [
                'title' => 'Template 10 Siswa (Grup / Tim 10 Siswa)',
                'category' => 'Grup 10 Siswa',
                'badge' => '10 Siswa',
                'badge_class' => 'bg-teal-subtle text-info',
                'icon' => 'ri-group-2-line',
                'description' => 'Format delegasi grup hingga 10 siswa (contoh: 6 personil inti + 4 cadangan) lengkap dengan berkas kolektif.',
            ],
            'template_12_siswa' => [
                'title' => 'Template 12 Siswa (Roster Skuad 12 Siswa)',
                'category' => 'Skuad 12 Siswa',
                'badge' => '12 Siswa',
                'badge_class' => 'bg-secondary-subtle text-secondary',
                'icon' => 'ri-football-line',
                'description' => 'Format susunan pemain tim hingga 12 siswa (pemain inti dan cadangan) lengkap dengan scan KTS kolektif PDF.',
            ],
            'template_dinamis' => [
                'title' => 'Template Multi Siswa (Jumlah Bebas / Dinamis)',
                'category' => 'Fleksibel',
                'badge' => 'Bebas / Multi Siswa',
                'badge_class' => 'bg-dark-subtle text-dark',
                'icon' => 'ri-user-settings-line',
                'description' => 'Format kontingen dengan jumlah siswa bebas/dinamis (1 s.d. 20 siswa) yang ditentukan langsung oleh pendaftar.',
            ],
        ];

        $presets = [];
        foreach ($meta as $key => $m) {
            $fields = $rawPresets[$key] ?? [];
            foreach ($fields as &$f) {
                if (($f['type'] ?? '') === 'participant_repeater') {
                    $f['sub_fields'] = self::normalizeRepeaterSubFields($f['sub_fields'] ?? null);
                }
            }
            unset($f);

            $presets[$key] = [
                'key' => $key,
                'title' => $m['title'],
                'category' => $m['category'],
                'badge' => $m['badge'],
                'badge_class' => $m['badge_class'],
                'icon' => $m['icon'],
                'description' => $m['description'],
                'fields' => $fields,
                'is_custom' => false,
            ];
        }

        return $presets;
    }

    public static function normalizeRepeaterSubFields(?array $subFields): array
    {
        $defaults = [
            'name' => ['active' => true, 'required' => true, 'label' => 'Nama Lengkap Siswa'],
            'gender' => ['active' => true, 'required' => true, 'label' => 'Jenis Kelamin (L/P)'],
            'birth_date' => ['active' => true, 'required' => false, 'label' => 'Tanggal Lahir Siswa'],
            'grade' => ['active' => true, 'required' => true, 'label' => 'Kelas / Tingkat'],
            'nisn' => ['active' => true, 'required' => true, 'label' => 'Nomor Induk Siswa Nasional (NISN)'],
            'custom_id' => ['active' => false, 'required' => false, 'label' => 'ID Siswa / No. Identitas'],
            'wa_number' => ['active' => false, 'required' => false, 'label' => 'Nomor WhatsApp Siswa'],
            'captain_option' => ['active' => false, 'required' => false, 'label' => 'Tandai Kapten Tim & Nomor WA Khusus Kapten'],
            'team_name' => ['active' => false, 'required' => true, 'label' => 'Nama Tim / Skuad', 'placeholder' => 'Contoh: Garuda Squad / Tim Voli Utama'],
            'photo_formal' => ['active' => false, 'required' => false, 'label' => 'Upload Pas Foto Formal Siswa', 'placeholder' => 'Pilih Pas Foto Formal Siswa (JPG/PNG, Maks. 5 MB)'],
            'photo_freestyle' => ['active' => false, 'required' => false, 'label' => 'Upload Foto Pose Gaya Bebas (Bisa >1 Foto)', 'placeholder' => 'Pilih Satu atau Beberapa Foto Pose Bebas', 'multiple' => true],
            'custom_columns' => [],
        ];

        if (empty($subFields) || !is_array($subFields)) {
            return $defaults;
        }

        foreach ($defaults as $k => $defVal) {
            if ($k === 'custom_columns') {
                $subFields['custom_columns'] = isset($subFields['custom_columns']) && is_array($subFields['custom_columns']) ? $subFields['custom_columns'] : [];
            } elseif (!isset($subFields[$k]) || !is_array($subFields[$k])) {
                $subFields[$k] = $defVal;
            } else {
                $merged = array_merge($defVal, $subFields[$k]);
                if (isset($subFields[$k]['active'])) {
                    $merged['active'] = (bool)$subFields[$k]['active'];
                }
                if (isset($subFields[$k]['required'])) {
                    $merged['required'] = (bool)$subFields[$k]['required'];
                }
                $subFields[$k] = $merged;
            }
        }

        return $subFields;
    }

    public static function syncRepeaterWithCompetition(array $fields, Competition $comp): array
    {
        $isTeam = $comp->participant_type === 'tim';
        $rule = trim((string)($comp->participants_rule ?? ''));
        $compId = strtolower((string)$comp->id);
        $compTitle = strtolower((string)$comp->title);

        // 1. Prioritaskan langsung data numerik dari kolom model Competition
        $max = !empty($comp->max_participants) ? (int)$comp->max_participants : ($isTeam ? 10 : 1);
        $min = !empty($comp->min_participants) ? (int)$comp->min_participants : ($isTeam ? min($max, 5) : 1);
        $reserve = isset($comp->reserve_participants) && $comp->reserve_participants !== null && $comp->reserve_participants !== ''
            ? (int)$comp->reserve_participants
            : 0;
        $main = $min;

        // Fallback regex ke participants_rule HANYA jika data numerik min_participants belum terisi di database
        if (empty($comp->min_participants) && !empty($rule)) {
            if (preg_match('/(\d+)\s*(?:pemain|orang|siswa|anggota|personel)?\s*inti/i', $rule, $mInti)) {
                $main = (int)$mInti[1];
                $min = $main;
                if (preg_match('/(\d+)\s*(?:pemain|orang|siswa|anggota|personel)?\s*cadangan/i', $rule, $mCadangan)) {
                    $reserve = (int)$mCadangan[1];
                }
                if (empty($comp->max_participants)) {
                    $max = $main + $reserve;
                }
            } elseif (preg_match('/(\d+)\s*(?:s\.d|sampai|-)\s*(\d+)\s*(?:siswa|orang|pemain|peserta|personel)?/i', $rule, $mRange)) {
                $min = (int)$mRange[1];
                if (empty($comp->max_participants)) {
                    $max = (int)$mRange[2];
                }
                $main = $min;
                $reserve = max(0, $max - $min);
            } elseif (preg_match('/maksimal\s*(\d+)\s*(?:siswa|orang|pemain|peserta|personel)?/i', $rule, $mMaksimal)) {
                if (empty($comp->max_participants)) {
                    $max = (int)$mMaksimal[1];
                }
                $min = $isTeam ? min($max, 5) : 1;
                $main = $min;
                $reserve = 0;
            }
        }

        if ($min > $max) $min = $max;
        if ($min < 1) $min = 1;
        $maxReserveAllowed = max(0, $max - $min);
        if ($reserve > $maxReserveAllowed) {
            $reserve = $maxReserveAllowed;
        }
        $main = $min;

        foreach ($fields as &$f) {
            if (($f['type'] ?? '') === 'participant_repeater') {
                $f['min_items'] = $min;
                $f['max_items'] = $max;
                $f['main_members'] = $main;
                $f['reserve_members'] = $reserve;
                $f['name'] = $isTeam ? 'roster_tim' : 'biodata_peserta';
                $f['auto_synced'] = true;
                $f['synced_rule'] = $comp->participants_rule;

                // Hanya beri default label jika label belum diset sebelumnya
                if (empty($f['label'])) {
                    if ($isTeam) {
                        $f['label'] = $compId === 'mobile-legends'
                            ? "Roster Skuad Tim Mobile Legends ({$main} Inti + {$reserve} Cadangan)"
                            : (str_contains($compId, 'volleyball') || str_contains($compTitle, 'voli')
                                ? "Roster Skuad Tim Bola Voli ({$main} Inti + {$reserve} Cadangan)"
                                : "Biodata Personel Grup {$comp->title} (Maksimal {$max} Personel)");
                    } else {
                        $f['label'] = "Biodata Peserta {$comp->title} (Maksimal {$max} Siswa)";
                    }
                }
                if (empty($f['help_text'])) {
                    $f['help_text'] = "Aturan: {$comp->participants_rule} (Minimal {$min}, Maksimal {$max} peserta)";
                }

                // Normalisasikan sub_fields TANPA merusak pengaturan kustomisasi user
                $existingSubs = isset($f['sub_fields']) && is_array($f['sub_fields']) ? $f['sub_fields'] : [];
                $hasExistingConfig = !empty($existingSubs);
                $f['sub_fields'] = self::normalizeRepeaterSubFields($existingSubs);

                // Jika repeater baru pertama kali dibuat (belum pernah dikustomisasi admin), terapkan preset default cerdas
                if (!$hasExistingConfig) {
                    if ($isTeam) {
                        $f['sub_fields']['team_name']['active'] = true;
                        $f['sub_fields']['team_name']['required'] = true;
                        $f['sub_fields']['captain_option']['active'] = true;

                        if ($compId === 'mobile-legends') {
                            $f['sub_fields']['team_name']['label'] = 'Nama Tim / Skuad MLBB';
                            $f['sub_fields']['team_name']['placeholder'] = 'Contoh: Garuda Squad / RRQ Reborn';
                            $f['sub_fields']['captain_option']['label'] = 'Tandai Kapten Tim & No. WhatsApp';
                            $f['sub_fields']['captain_option']['placeholder'] = '08xxxxxxxxxx (Untuk koordinasi panitia turnamen)';
                            $f['sub_fields']['custom_id']['active'] = true;
                            $f['sub_fields']['custom_id']['required'] = true;
                            $f['sub_fields']['custom_id']['label'] = 'ID Akun MLBB';
                            $f['sub_fields']['custom_id']['placeholder'] = 'ID Game (Server ID)';
                        } elseif (str_contains($compId, 'volleyball') || str_contains($compTitle, 'voli')) {
                            $f['sub_fields']['team_name']['label'] = 'Nama Tim Bola Voli';
                            $f['sub_fields']['team_name']['placeholder'] = 'Contoh: Spiker Utama / Tim Voli';
                            $f['sub_fields']['captain_option']['label'] = 'Tandai Kapten Tim & No. WhatsApp';
                            $f['sub_fields']['captain_option']['placeholder'] = '08xxxxxxxxxx (Untuk koordinasi kapten voli)';
                            $f['sub_fields']['custom_id']['active'] = true;
                            $f['sub_fields']['custom_id']['required'] = false;
                            $f['sub_fields']['custom_id']['label'] = 'No. Punggung / Posisi';
                            $f['sub_fields']['custom_id']['placeholder'] = 'Contoh: 10 / Spiker';
                        } elseif ($compId === 'albanjari' || str_contains($compTitle, 'banjari')) {
                            $f['sub_fields']['team_name']['label'] = 'Nama Grup Albanjari';
                            $f['sub_fields']['team_name']['placeholder'] = 'Contoh: Syauqul Habib / Al-Fath';
                            $f['sub_fields']['captain_option']['label'] = 'Tandai Ketua Grup & No. WhatsApp';
                            $f['sub_fields']['captain_option']['placeholder'] = '08xxxxxxxxxx (Untuk koordinasi ketua grup banjari)';
                            $f['sub_fields']['custom_id']['active'] = true;
                            $f['sub_fields']['custom_id']['required'] = false;
                            $f['sub_fields']['custom_id']['label'] = 'Peran / Alat Musik';
                            $f['sub_fields']['custom_id']['placeholder'] = 'Contoh: Vokal Utama / Terbang 1 / Bass';
                        }
                    }
                }
            }
        }
        unset($f);

        return $fields;
    }

    public static function getMandatoryIdentityFields(): array
    {
        return \Database\Seeders\FormFieldSeeder::getMandatoryIdentityFields();
    }

    public static function ensureMandatoryFields(array $existingFields): array
    {
        $mandatory = self::getMandatoryIdentityFields();
        $clean = [];

        // Map existing fields by canonical name or alias
        $nameMap = [
            'school_name' => ['school_name', 'asal_sekolah', 'nama_sekolah', 'nama_lembaga', 'nama_sekolah_madrasah', 'nama_sekolah_madrasah_pengirim', 'nama_sekolah_madrasah_instansi_pengirim'],
            'school_district' => ['school_district', 'kecamatan', 'kecamatan_sekolah', 'kecamatan_asal_sekolah', 'kecamatan_asal_sekolah_madrasah', 'wilayah_kecamatan'],
            'school_npsn' => ['school_npsn', 'npsn', 'npsn_sekolah', 'nomor_pokok_sekolah_nasional', 'nomor_pokok_sekolah_nasional_npsn'],
            'school_address' => ['school_address', 'alamat_sekolah', 'alamat_lengkap_sekolah', 'alamat_sekolah_madrasah', 'alamat_lengkap_sekolah_madrasah', 'alamat'],
            'mentor_name' => ['mentor_name', 'nama_pendamping', 'nama_guru_pendamping', 'nama_pembina', 'nama_guru_pembina_pendamping', 'nama_pelatih_official'],
            'mentor_wa' => ['mentor_wa', 'nomor_wa', 'whatsapp', 'nomor_whatsapp', 'nomor_whatsapp_pendamping', 'nomor_whatsapp_kontak', 'nomor_whatsapp_pembina', 'no_whatsapp_pelatih_official', 'no_whatsapp_kapten_kontak_tim'],
            'mentor_email' => ['mentor_email', 'contact_email', 'email', 'alamat_email', 'email_pendamping', 'alamat_email_aktif', 'email_kontak', 'alamat_email_resmi_sekolah_pembina'],
        ];

        // Find existing field objects matching each mandatory key
        $found = [];
        $remainingFields = [];

        foreach ($existingFields as $f) {
            $fName = $f['name'] ?? '';
            $matchedKey = null;

            foreach ($nameMap as $targetKey => $aliases) {
                if (!isset($found[$targetKey]) && in_array($fName, $aliases, true)) {
                    $matchedKey = $targetKey;
                    break;
                }
            }

            if ($matchedKey) {
                $found[$matchedKey] = $f;
            } else {
                $remainingFields[] = $f;
            }
        }

        // Build the 7 canonical fields
        foreach ($mandatory as $m) {
            $key = $m['name'];
            if (isset($found[$key])) {
                $f = $found[$key];
                $clean[] = [
                    'id' => $f['id'] ?? $m['id'],
                    'name' => $m['name'],
                    'label' => !empty($f['label']) ? $f['label'] : $m['label'],
                    'type' => $m['type'],
                    'placeholder' => !empty($f['placeholder']) ? $f['placeholder'] : $m['placeholder'],
                    'help_text' => !empty($f['help_text']) ? $f['help_text'] : $m['help_text'],
                    'required' => isset($f['required']) ? (bool)$f['required'] : (bool)$m['required'],
                    'options' => $m['type'] === 'select' ? $m['options'] : ($f['options'] ?? []),
                    'allowed_extensions' => $f['allowed_extensions'] ?? [],
                    'max_size_mb' => $f['max_size_mb'] ?? null,
                    'max_files' => isset($f['max_files']) && $f['max_files'] !== null && $f['max_files'] !== '' ? max(1, (int)$f['max_files']) : null,
                    'min_items' => $f['min_items'] ?? null,
                    'max_items' => $f['max_items'] ?? null,
                    'main_members' => $f['main_members'] ?? null,
                    'reserve_members' => $f['reserve_members'] ?? null,
                    'sub_fields' => ($f['type'] === 'participant_repeater') ? self::normalizeRepeaterSubFields($f['sub_fields'] ?? null) : ($f['sub_fields'] ?? null),
                    'conditional_logic' => $f['conditional_logic'] ?? null,
                    'is_mandatory_identity' => true,
                ];
            } else {
                $m['is_mandatory_identity'] = true;
                $clean[] = $m;
            }
        }

        // Append remaining custom fields
        $hasAllIn = false;
        foreach ($remainingFields as $rf) {
            if (($rf['type'] ?? '') === 'participant_repeater') {
                $rf['sub_fields'] = self::normalizeRepeaterSubFields($rf['sub_fields'] ?? null);
            }
            if (($rf['type'] ?? '') === 'file') {
                $rf['max_files'] = isset($rf['max_files']) && $rf['max_files'] !== null && $rf['max_files'] !== ''
                    ? max(1, (int)$rf['max_files'])
                    : 1;
            }
            $rfName = $rf['name'] ?? '';
            if (in_array($rfName, ['allin_code', 'kode_paket_komplit', 'kode_paket', 'kode_allin', 'package_code', 'all_in_code', 'kode_paket_allin'], true)) {
                $rf['is_mandatory_identity'] = false;
                $hasAllIn = true;
            }
            $clean[] = $rf;
        }

        // Pastikan field allin_code selalu tersedia dalam urutan builder
        if (!$hasAllIn) {
            $clean[] = [
                'id' => 'cf_allin_code',
                'name' => 'allin_code',
                'label' => 'Kode Paket Komplit (All-In)',
                'type' => 'text',
                'placeholder' => 'Contoh: SA26-XXXXXX (Otomatis jika sekolah terverifikasi All-In)',
                'help_text' => 'Masukkan kode paket jika sekolah Anda mendaftar Paket Komplit agar pendaftaran cabang mandiri ini bebas biaya (Rp 0).',
                'required' => false,
                'options' => [],
                'allowed_extensions' => [],
                'max_size_mb' => null,
                'is_mandatory_identity' => false,
            ];
        }

        return $clean;
    }

    public static function getAllFormPresets(): array
    {
        $defaults = self::getDefaultPresetsList();

        try {
            $setting = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'form_builder_presets')->first();
            if ($setting && !empty($setting->value)) {
                $saved = json_decode($setting->value, true);
                if (is_array($saved)) {
                    foreach ($saved as $k => $customPreset) {
                        if (is_array($customPreset) && !empty($customPreset['title'])) {
                            $customPreset['key'] = $k;
                            $customPreset['is_custom'] = $customPreset['is_custom'] ?? true;
                            $defaults[$k] = $customPreset;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fallback to defaults
        }

        return $defaults;
    }

    public function formBuilderIndex(Request $request)
    {
        /** @var User|null $authUser */
        $authUser = Auth::guard('web')->user();
        $isGlobalAdmin = $authUser?->isSuperAdmin() || in_array($authUser?->role, ['admin', 'ketua_panitia', 'wakil_ketua_panitia', 'sekretaris', 'kesekretariatan'], true);
        $hasManagePermission = $authUser ? $authUser->hasPermission('form_builder.manage') : false;
        $coordinatedIds = $authUser ? $authUser->getCoordinatedCompetitionIds() : [];

        $competitions = Competition::orderBy('id', 'asc')->get();
        foreach ($competitions as $comp) {
            $comp->custom_fields = self::syncRepeaterWithCompetition(self::ensureMandatoryFields($comp->custom_fields ?? []), $comp);
            $isCoordinated = in_array((string)$comp->id, $coordinatedIds, true);
            $comp->is_coordinated = $isCoordinated;
            $comp->is_form_inactive = $comp->isFormInactive();
            $comp->can_manage = $hasManagePermission && ($isGlobalAdmin || $isCoordinated) && (!$comp->is_form_inactive || $isGlobalAdmin);
        }

        $selectedId = $request->query('comp');
        if (!$selectedId && !$isGlobalAdmin && !empty($coordinatedIds)) {
            $selectedId = $coordinatedIds[0];
        }
        if (!$selectedId || !$competitions->contains('id', $selectedId)) {
            $selectedId = $competitions->first()?->id;
        }

        $selectedComp = $selectedId ? $competitions->firstWhere('id', $selectedId) : $competitions->first();
        if ($selectedComp) {
            $selectedComp->custom_fields = self::syncRepeaterWithCompetition(self::ensureMandatoryFields($selectedComp->custom_fields ?? []), $selectedComp);
        }

        $isCoordinatedSelected = $selectedComp ? in_array((string)$selectedComp->id, $coordinatedIds, true) : false;
        $isFormInactiveSelected = $selectedComp ? $selectedComp->isFormInactive() : false;
        $canManageForm = $hasManagePermission && ($isGlobalAdmin || $isCoordinatedSelected) && (!$isFormInactiveSelected || $isGlobalAdmin);

        $presets = self::getAllFormPresets();

        return \Inertia\Inertia::render('Admin/FormBuilder', [
            'competitions' => $competitions,
            'selectedCompetition' => $selectedComp,
            'presets' => $presets,
            'canManageForm' => $canManageForm,
            'isFormInactive' => $isFormInactiveSelected,
            'isCoordinator' => $isCoordinatedSelected,
            'isGlobalAdmin' => $isGlobalAdmin,
            'hasManagePermission' => $hasManagePermission,
            'paymentBankAccounts' => Setting::getBankAccounts(),
            'packageAllinPrice' => (int)Setting::get('package_allin_price', 200000),
        ]);
    }

    public function formBuilderShow(string|int $competitionId, Request $request)
    {
        /** @var User|null $authUser */
        $authUser = Auth::guard('web')->user();
        $isGlobalAdmin = $authUser?->isSuperAdmin() || in_array($authUser?->role, ['admin', 'ketua_panitia', 'wakil_ketua_panitia', 'sekretaris', 'kesekretariatan'], true);
        $hasManagePermission = $authUser ? $authUser->hasPermission('form_builder.manage') : false;
        $coordinatedIds = $authUser ? $authUser->getCoordinatedCompetitionIds() : [];

        $competitions = Competition::orderBy('id', 'asc')->get();
        foreach ($competitions as $comp) {
            $comp->custom_fields = self::syncRepeaterWithCompetition(self::ensureMandatoryFields($comp->custom_fields ?? []), $comp);
            $isCoordinated = in_array((string)$comp->id, $coordinatedIds, true);
            $comp->is_coordinated = $isCoordinated;
            $comp->is_form_inactive = $comp->isFormInactive();
            $comp->can_manage = $hasManagePermission && ($isGlobalAdmin || $isCoordinated) && (!$comp->is_form_inactive || $isGlobalAdmin);
        }

        $selectedComp = Competition::findOrFail($competitionId);
        $selectedComp->custom_fields = self::syncRepeaterWithCompetition(self::ensureMandatoryFields($selectedComp->custom_fields ?? []), $selectedComp);

        $isCoordinatedSelected = in_array((string)$selectedComp->id, $coordinatedIds, true);
        $isFormInactiveSelected = $selectedComp->isFormInactive();
        $canManageForm = $hasManagePermission && ($isGlobalAdmin || $isCoordinatedSelected) && (!$isFormInactiveSelected || $isGlobalAdmin);

        $presets = self::getAllFormPresets();

        return \Inertia\Inertia::render('Admin/FormBuilder', [
            'competitions' => $competitions,
            'selectedCompetition' => $selectedComp,
            'presets' => $presets,
            'canManageForm' => $canManageForm,
            'isFormInactive' => $isFormInactiveSelected,
            'isCoordinator' => $isCoordinatedSelected,
            'isGlobalAdmin' => $isGlobalAdmin,
            'hasManagePermission' => $hasManagePermission,
            'paymentBankAccounts' => Setting::getBankAccounts(),
            'packageAllinPrice' => (int)Setting::get('package_allin_price', 200000),
        ]);
    }

    public function formBuilderSave(string|int $competitionId, Request $request)
    {
        $comp = Competition::findOrFail($competitionId);
        /** @var User|null $authUser */
        $authUser = Auth::guard('web')->user();

        // 1. Otorisasi wewenang cabang lomba & hak akses form_builder.manage
        if (!$this->authorizeCompetitionAccess($competitionId, 'form_builder.manage')) {
            $msg = "Akses Ditolak: Anda tidak memiliki wewenang atau hak akses untuk mengubah formulir pendaftaran cabang \"{$comp->title}\".";
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json(['status' => 'error', 'message' => $msg], 403);
            }
            return redirect()->back()->with('error', $msg);
        }

        // 2. Proteksi jika formulir berstatus nonaktif (hanya Super Admin / Admin Utama yang dapat mengubah atau mengaktifkan kembali)
        $isGlobalAdmin = $authUser?->isSuperAdmin() || in_array($authUser?->role, ['admin', 'ketua_panitia', 'wakil_ketua_panitia', 'sekretaris', 'kesekretariatan'], true);
        if ($comp->isFormInactive() && !$isGlobalAdmin) {
            $msg = "Aksi Ditolak: Formulir pendaftaran cabang lomba \"{$comp->title}\" sedang nonaktif (status ditutup / pendaftaran kontak langsung).";
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json(['status' => 'error', 'message' => $msg], 403);
            }
            return redirect()->back()->with('error', $msg);
        }

        $fields = $request->input('custom_fields', []);
        $settings = $request->input('form_settings', $comp->form_settings ?? []);

        $cleanFields = $this->cleanFormFields($fields);
        $cleanFields = self::ensureMandatoryFields($cleanFields);
        $cleanFields = self::syncRepeaterWithCompetition($cleanFields, $comp);

        $comp->custom_fields = $cleanFields;
        if (is_array($settings)) {
            $currentSettings = is_array($comp->form_settings) ? $comp->form_settings : [];
            $comp->form_settings = array_merge($currentSettings, [
                'is_active' => isset($settings['is_active']) ? (bool)$settings['is_active'] : true,
                'show_package_type' => isset($settings['show_package_type']) ? (bool)$settings['show_package_type'] : true,
                'form_title' => $settings['form_title'] ?? '',
                'form_subtitle' => $settings['form_subtitle'] ?? '',
                'submit_text' => $settings['submit_text'] ?? 'Kirim Pendaftaran Delegasi',
                'section_1_title' => $settings['section_1_title'] ?? '',
                'section_1_subtitle' => $settings['section_1_subtitle'] ?? '',
                'section_2_title' => $settings['section_2_title'] ?? '',
                'section_2_subtitle' => $settings['section_2_subtitle'] ?? '',
                'section_3_title' => $settings['section_3_title'] ?? '',
                'section_3_subtitle' => $settings['section_3_subtitle'] ?? '',
                'section_4_title' => $settings['section_4_title'] ?? '',
                'section_4_subtitle' => $settings['section_4_subtitle'] ?? '',
            ]);
        }
        $comp->save();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'status' => 'success',
                'message' => "Formulir pendaftaran cabang lomba {$comp->title} berhasil disimpan!",
                'custom_fields' => $cleanFields,
                'form_settings' => $comp->form_settings,
            ]);
        }

        return redirect()->back()->with('success', "Formulir pendaftaran cabang lomba \"{$comp->title}\" berhasil diperbarui dengan " . count($cleanFields) . " field formulir!");
    }

    public function formBuilderApplyPreset(string|int $competitionId, Request $request)
    {
        $comp = Competition::findOrFail($competitionId);
        /** @var User|null $authUser */
        $authUser = Auth::guard('web')->user();

        if (!$this->authorizeCompetitionAccess($competitionId, 'form_builder.manage')) {
            return redirect()->back()->with('error', "Akses Ditolak: Anda tidak memiliki wewenang atau hak akses untuk mengubah formulir cabang \"{$comp->title}\".");
        }

        $isGlobalAdmin = $authUser?->isSuperAdmin() || in_array($authUser?->role, ['admin', 'ketua_panitia', 'wakil_ketua_panitia', 'sekretaris', 'kesekretariatan'], true);
        if ($comp->isFormInactive() && !$isGlobalAdmin) {
            return redirect()->back()->with('error', "Aksi Ditolak: Formulir pendaftaran cabang lomba \"{$comp->title}\" sedang nonaktif.");
        }

        $presetKey = $request->input('preset', 'template_1_siswa');
        $presets = self::getAllFormPresets();

        $legacyMap = [
            'akademik' => 'template_1_siswa',
            'tahfidz' => 'template_1_siswa',
            'seni' => 'template_10_siswa',
            'esport' => 'template_6_siswa',
            'olahraga' => 'template_12_siswa',
            'umum' => 'template_dinamis',
        ];
        if (!isset($presets[$presetKey]) && isset($legacyMap[$presetKey])) {
            $presetKey = $legacyMap[$presetKey];
        }

        if (!isset($presets[$presetKey])) {
            return redirect()->back()->with('error', "Preset \"{$presetKey}\" tidak ditemukan.");
        }

        $presetData = $presets[$presetKey];
        $fields = is_array($presetData) && isset($presetData['fields']) ? $presetData['fields'] : $presetData;
        $cleanFields = $this->cleanFormFields($fields);
        $cleanFields = self::ensureMandatoryFields($cleanFields);
        $cleanFields = self::syncRepeaterWithCompetition($cleanFields, $comp);
        $comp->custom_fields = $cleanFields;
        $comp->save();

        return redirect()->back()->with('success', "Preset formulir \"{$presetData['title']}\" berhasil diterapkan pada cabang {$comp->title}!");
    }

    public function formBuilderSavePreset(Request $request)
    {
        $presetKey = $request->input('key');
        $title = $request->input('title');
        $category = $request->input('category', 'Kustom');
        $description = $request->input('description', '');
        $icon = $request->input('icon', 'ri-magic-line');
        $badge = $request->input('badge', 'Kustom');
        $badgeClass = $request->input('badge_class', 'bg-warning-subtle text-warning');
        $fields = $request->input('fields', []);

        if (empty($title)) {
            return redirect()->back()->with('error', 'Nama/Judul Template Preset wajib diisi.');
        }

        if (empty($presetKey)) {
            $presetKey = \Illuminate\Support\Str::slug($title, '_');
        } else {
            $presetKey = \Illuminate\Support\Str::slug($presetKey, '_');
        }

        $cleanFields = $this->cleanFormFields($fields);

        // Fetch current custom presets from settings
        $allPresets = self::getAllFormPresets();
        $customOnly = [];

        try {
            $setting = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'form_builder_presets')->first();
            if ($setting && !empty($setting->value)) {
                $customOnly = json_decode($setting->value, true) ?: [];
            }
        } catch (\Throwable $e) {}

        $customOnly[$presetKey] = [
            'key' => $presetKey,
            'title' => $title,
            'category' => $category,
            'description' => $description,
            'icon' => $icon,
            'badge' => $badge,
            'badge_class' => $badgeClass,
            'fields' => $cleanFields,
            'is_custom' => true,
            'updated_at' => now()->toIso8601String(),
        ];

        \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
            ['key' => 'form_builder_presets'],
            ['value' => json_encode($customOnly), 'updated_at' => now()]
        );

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'status' => 'success',
                'message' => "Template preset \"{$title}\" berhasil disimpan!",
                'preset' => $customOnly[$presetKey],
            ]);
        }

        return redirect()->back()->with('success', "Template preset \"{$title}\" berhasil disimpan & dapat digunakan untuk cabang lomba mana pun!");
    }

    public function formBuilderDeletePreset(Request $request)
    {
        $presetKey = $request->input('key');

        try {
            $setting = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'form_builder_presets')->first();
            if ($setting && !empty($setting->value)) {
                $customOnly = json_decode($setting->value, true) ?: [];
                if (isset($customOnly[$presetKey])) {
                    unset($customOnly[$presetKey]);
                    \Illuminate\Support\Facades\DB::table('settings')->where('key', 'form_builder_presets')->update([
                        'value' => json_encode($customOnly),
                        'updated_at' => now()
                    ]);
                    return redirect()->back()->with('success', "Template preset kustom berhasil dihapus.");
                }
            }
        } catch (\Throwable $e) {}

        return redirect()->back()->with('info', "Preset bawaan sistem di-reset ke nilai default.");
    }

    public function formBuilderResetPresets()
    {
        try {
            \Illuminate\Support\Facades\DB::table('settings')->where('key', 'form_builder_presets')->delete();
        } catch (\Throwable $e) {}

        return redirect()->back()->with('success', "Semua template preset telah dikembalikan ke standar bawaan sistem.");
    }

    public function formBuilderApplyHeadlineAll(Request $request)
    {
        /** @var User|null $authUser */
        $authUser = Auth::guard('web')->user();
        $isGlobalAdmin = $authUser?->isSuperAdmin() || in_array($authUser?->role, ['admin', 'ketua_panitia', 'wakil_ketua_panitia', 'sekretaris', 'kesekretariatan'], true);
        $hasManagePermission = $authUser ? $authUser->hasPermission('form_builder.manage') : false;

        if (!$hasManagePermission || !$isGlobalAdmin) {
            $msg = "Akses Ditolak: Hanya Admin Utama yang memiliki hak akses untuk menerapkan headline ke semua cabang lomba.";
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json(['status' => 'error', 'message' => $msg], 403);
            }
            return redirect()->back()->with('error', $msg);
        }

        $showPackageType = $request->boolean('show_package_type', true);
        $formTitle = $request->input('form_title', '');
        $formSubtitle = $request->input('form_subtitle', '');
        $submitText = $request->input('submit_text', 'Kirim Pendaftaran Delegasi');
        $section1Title = $request->input('section_1_title', '');
        $section1Subtitle = $request->input('section_1_subtitle', '');
        $section2Title = $request->input('section_2_title', '');
        $section2Subtitle = $request->input('section_2_subtitle', '');
        $section3Title = $request->input('section_3_title', '');
        $section3Subtitle = $request->input('section_3_subtitle', '');
        $section4Title = $request->input('section_4_title', '');
        $section4Subtitle = $request->input('section_4_subtitle', '');

        $competitions = Competition::all();
        $count = 0;
        foreach ($competitions as $c) {
            $settings = is_array($c->form_settings) ? $c->form_settings : [];
            $settings['show_package_type'] = $showPackageType;
            $settings['form_title'] = $formTitle;
            $settings['form_subtitle'] = $formSubtitle;
            $settings['submit_text'] = $submitText;
            $settings['section_1_title'] = $section1Title;
            $settings['section_1_subtitle'] = $section1Subtitle;
            $settings['section_2_title'] = $section2Title;
            $settings['section_2_subtitle'] = $section2Subtitle;
            $settings['section_3_title'] = $section3Title;
            $settings['section_3_subtitle'] = $section3Subtitle;
            $settings['section_4_title'] = $section4Title;
            $settings['section_4_subtitle'] = $section4Subtitle;
            $c->form_settings = $settings;
            $c->save();
            $count++;
        }

        $msg = "Seluruh headline utama dan headline pemisah bagian formulir berhasil diterapkan ke seluruh ({$count}) cabang lomba!";
        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['status' => 'success', 'message' => $msg]);
        }
        return redirect()->back()->with('success', $msg);
    }

    private function cleanFormFields(mixed $fields): array
    {
        $clean = [];
        if (!is_array($fields)) return $clean;

        foreach ($fields as $f) {
            if (empty($f['label'])) continue;
            $name = !empty($f['name']) ? \Illuminate\Support\Str::slug($f['name'], '_') : \Illuminate\Support\Str::slug($f['label'], '_');
            $clean[] = [
                'id' => $f['id'] ?? ('cf_' . uniqid()),
                'name' => $name,
                'label' => $f['label'],
                'type' => $f['type'] ?? 'text',
                'placeholder' => $f['placeholder'] ?? '',
                'help_text' => $f['help_text'] ?? '',
                'required' => (bool) ($f['required'] ?? false),
                'options' => is_array($f['options'] ?? null) ? array_values(array_filter($f['options'])) : [],
                'allowed_extensions' => is_array($f['allowed_extensions'] ?? null) ? array_values(array_filter($f['allowed_extensions'])) : [],
                'max_size_mb' => !empty($f['max_size_mb']) ? (int) $f['max_size_mb'] : 5,
                'max_files' => isset($f['max_files']) && $f['max_files'] !== null && $f['max_files'] !== '' ? max(1, (int) $f['max_files']) : (($f['type'] ?? '') === 'file' ? 1 : null),
                'min_items' => isset($f['min_items']) && $f['min_items'] !== null && $f['min_items'] !== '' ? (int) $f['min_items'] : null,
                'max_items' => isset($f['max_items']) && $f['max_items'] !== null && $f['max_items'] !== '' ? (int) $f['max_items'] : null,
                'main_members' => isset($f['main_members']) && $f['main_members'] !== null && $f['main_members'] !== '' ? (int) $f['main_members'] : null,
                'reserve_members' => isset($f['reserve_members']) && $f['reserve_members'] !== null && $f['reserve_members'] !== '' ? (int) $f['reserve_members'] : null,
                'sub_fields' => ($f['type'] ?? '') === 'participant_repeater' ? self::normalizeRepeaterSubFields($f['sub_fields'] ?? null) : (is_array($f['sub_fields'] ?? null) ? $f['sub_fields'] : null),
                'min_date' => !empty($f['min_date']) ? (string)$f['min_date'] : null,
                'max_date' => !empty($f['max_date']) ? (string)$f['max_date'] : null,
                'date_default' => !empty($f['date_default']) ? (string)$f['date_default'] : null,
                'conditional_logic' => is_array($f['conditional_logic'] ?? null) ? $f['conditional_logic'] : null,
                'is_mandatory_identity' => in_array($name, ['allin_code', 'kode_paket_komplit', 'kode_paket', 'kode_allin', 'package_code', 'all_in_code', 'kode_paket_allin'], true) ? false : !empty($f['is_mandatory_identity']),
            ];
        }

        return $clean;
    }

    public function formBuilderCopyFrom(string|int $competitionId, Request $request)
    {
        $comp = Competition::findOrFail($competitionId);
        /** @var User|null $authUser */
        $authUser = Auth::guard('web')->user();

        if (!$this->authorizeCompetitionAccess($competitionId, 'form_builder.manage')) {
            return redirect()->back()->with('error', "Akses Ditolak: Anda tidak memiliki wewenang atau hak akses untuk mengubah formulir cabang \"{$comp->title}\".");
        }

        $isGlobalAdmin = $authUser?->isSuperAdmin() || in_array($authUser?->role, ['admin', 'ketua_panitia', 'wakil_ketua_panitia', 'sekretaris', 'kesekretariatan'], true);
        if ($comp->isFormInactive() && !$isGlobalAdmin) {
            return redirect()->back()->with('error', "Aksi Ditolak: Formulir pendaftaran cabang lomba \"{$comp->title}\" sedang nonaktif.");
        }

        $sourceId = $request->input('source_competition_id');
        $sourceComp = Competition::findOrFail($sourceId);

        $copiedFields = $sourceComp->custom_fields ?? [];
        $copiedFields = self::ensureMandatoryFields($copiedFields);
        $copiedFields = self::syncRepeaterWithCompetition($copiedFields, $comp);

        $comp->custom_fields = $copiedFields;
        $comp->form_settings = $sourceComp->form_settings ?? [];
        $comp->save();

        $count = is_array($comp->custom_fields) ? count($comp->custom_fields) : 0;
        return redirect()->back()->with('success', "Berhasil menyalin {$count} field formulir dari \"{$sourceComp->title}\" ke \"{$comp->title}\"!");
    }

    public function formBuilderReset(string|int $competitionId)
    {
        $comp = Competition::findOrFail($competitionId);
        /** @var User|null $authUser */
        $authUser = Auth::guard('web')->user();

        if (!$this->authorizeCompetitionAccess($competitionId, 'form_builder.manage')) {
            return redirect()->back()->with('error', "Akses Ditolak: Anda tidak memiliki wewenang atau hak akses untuk mengosongkan formulir cabang \"{$comp->title}\".");
        }

        $isGlobalAdmin = $authUser?->isSuperAdmin() || in_array($authUser?->role, ['admin', 'ketua_panitia', 'wakil_ketua_panitia', 'sekretaris', 'kesekretariatan'], true);
        if ($comp->isFormInactive() && !$isGlobalAdmin) {
            return redirect()->back()->with('error', "Aksi Ditolak: Formulir pendaftaran cabang lomba \"{$comp->title}\" sedang nonaktif.");
        }

        $comp->custom_fields = [];
        $comp->form_settings = [];
        $comp->save();

        return redirect()->back()->with('success', "Formulir pendaftaran cabang lomba \"{$comp->title}\" telah direset.");
    }

    public function export(Request $request)
    {
        $query = Registration::orderBy('id', 'desc');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('school_name', 'like', "%{$s}%")
                  ->orWhere('mentor_name', 'like', "%{$s}%")
                  ->orWhere('reg_code', 'like', "%{$s}%")
                  ->orWhere('competition_title', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        if ($request->filled('competition')) {
            $query->where('competition_id', $request->competition);
        }

        $registrations = $query->get();

        // Optional CSV fallback if specifically requested via ?format=csv
        if ($request->query('format') === 'csv') {
            $csv = "No,Kode Registrasi,Asal Sekolah,Guru Pendamping,Nomor WA,Cabang Lomba,Paket,Peserta / Delegasi,Total Biaya,Status Pembayaran,Field Tambahan / Kustom,Tanggal Daftar\n";

            foreach ($registrations as $idx => $r) {
                $cleanNames = str_replace(["\r", "\n"], ' | ', $r->participants_names ?: '-');
                $cleanNames = str_replace('"', '""', $cleanNames);
                $cleanSchool = str_replace('"', '""', $r->school_name ?: '-');
                $cleanMentor = str_replace('"', '""', $r->mentor_name ?: '-');

                $customSummary = '';
                if (!empty($r->custom_fields) && is_array($r->custom_fields)) {
                    $parts = [];
                    foreach ($r->custom_fields as $k => $v) {
                        $formattedV = $this->formatCustomFieldValue($v);
                        $parts[] = "{$k}: {$formattedV}";
                    }
                    $customSummary = implode('; ', $parts);
                    $customSummary = str_replace('"', '""', $customSummary);
                }

                $csv .= ($idx + 1) . ",\"{$r->reg_code}\",\"{$cleanSchool}\",\"{$cleanMentor}\",\"'{$r->mentor_wa}'\",\"{$r->competition_title}\",\"{$r->package_type}\",\"{$cleanNames}\",\"{$r->total_fee}\",\"{$r->payment_status}\",\"{$customSummary}\",\"{$r->created_at}\"\n";
            }

            return response("\xEF\xBB\xBF" . $csv, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="data-pendaftar-semarak-2026.csv"'
            ]);
        }

        // Native Microsoft Excel (.xlsx) generation via PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Panitia Semarak Aliyah XIV')
            ->setLastModifiedBy('Admin Semarak Aliyah')
            ->setTitle('Rekapitulasi Pendaftaran Semarak Aliyah XIV')
            ->setSubject('Data Pendaftar')
            ->setDescription('Rekapitulasi lengkap data pendaftaran dan peserta lomba Semarak Aliyah XIV.')
            ->setCategory('Laporan Pendaftaran');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pendaftar');
        $sheet->setShowGridLines(true);

        // Title Block (Rows 1 - 2)
        $sheet->setCellValue('A1', 'SEMARAK ALIYAH XIV - REKAPITULASI PENDAFTARAN PESERTA');
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));
        $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(26);

        $wibTime = now()->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y - H:i') . ' WIB';
        $sheet->setCellValue('A2', 'Diekspor pada: ' . $wibTime . ' | Total: ' . $registrations->count() . ' Pendaftaran');
        $sheet->mergeCells('A2:L2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
        $sheet->getRowDimension(2)->setRowHeight(18);

        // Header Row (Row 4)
        $headers = [
            'A4' => 'No',
            'B4' => 'Kode Registrasi',
            'C4' => 'Asal Sekolah / Madrasah',
            'D4' => 'Guru Pendamping',
            'E4' => 'Nomor WhatsApp',
            'F4' => 'Cabang Lomba',
            'G4' => 'Paket',
            'H4' => 'Peserta / Delegasi',
            'I4' => 'Total Biaya (Rp)',
            'J4' => 'Status Pembayaran',
            'K4' => 'Field Kustom / Formulir',
            'L4' => 'Waktu Pendaftaran'
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerRange = 'A4:L4';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
                'name' => 'Calibri',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'], // Slate 800
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '334155'],
                ],
            ],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(28);

        // Data Rows
        $row = 5;
        $totalNominal = 0;

        foreach ($registrations as $idx => $r) {
            $cleanNames = str_replace(["\r\n", "\r", "\n"], "\n", $r->participants_names ?: '-');
            $cleanSchool = $r->school_name ?: '-';
            $cleanMentor = $r->mentor_name ?: '-';

            $customSummary = '';
            if (!empty($r->custom_fields) && is_array($r->custom_fields)) {
                $parts = [];
                foreach ($r->custom_fields as $k => $v) {
                    $formattedV = $this->formatCustomFieldValue($v);
                    $parts[] = "{$k}: {$formattedV}";
                }
                $customSummary = implode("\n", $parts);
            }

            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->setCellValue("B{$row}", $r->reg_code);
            $sheet->setCellValue("C{$row}", $cleanSchool);
            $sheet->setCellValue("D{$row}", $cleanMentor);
            // Explicitly set phone as string to preserve leading zero / format
            $sheet->setCellValueExplicit("E{$row}", (string) $r->mentor_wa, DataType::TYPE_STRING);
            $sheet->setCellValue("F{$row}", $r->competition_title ?: '-');
            $sheet->setCellValue("G{$row}", $r->package_type ?: 'Reguler');
            $sheet->setCellValue("H{$row}", $cleanNames);
            $sheet->setCellValue("I{$row}", (int) $r->total_fee);
            $sheet->setCellValue("J{$row}", strtoupper($r->payment_status ?: 'PENDING'));
            $sheet->setCellValue("K{$row}", $customSummary ?: '-');
            $sheet->setCellValue("L{$row}", $r->created_at ? $r->created_at->format('d/m/Y H:i') : '-');

            // Style data row
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getFont()->setBold(true);
            $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$row}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("K{$row}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Highlight payment status cell
            $statusUpper = strtoupper($r->payment_status ?: '');
            if (in_array($statusUpper, ['PAID', 'VERIFIED', 'LUNAS', 'BERHASIL'])) {
                $sheet->getStyle("J{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCFCE7'); // Soft green
                $sheet->getStyle("J{$row}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('166534'))->setBold(true);
            } elseif (in_array($statusUpper, ['PENDING', 'MENUNGGU'])) {
                $sheet->getStyle("J{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF9C3'); // Soft yellow
                $sheet->getStyle("J{$row}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('854D0E'));
            } elseif (in_array($statusUpper, ['REJECTED', 'DITOLAK', 'EXPIRED'])) {
                $sheet->getStyle("J{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2'); // Soft red
                $sheet->getStyle("J{$row}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('991B1B'));
            }

            // Alternating row background for subtle readability
            if ($idx % 2 === 1) {
                $sheet->getStyle("A{$row}:I{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                $sheet->getStyle("K{$row}:L{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            $totalNominal += (int) $r->total_fee;
            $row++;
        }

        $lastDataRow = $row - 1;

        // Total Summary Row
        if ($registrations->count() > 0) {
            $sheet->setCellValue("A{$row}", 'TOTAL');
            $sheet->mergeCells("A{$row}:H{$row}");
            $sheet->setCellValue("I{$row}", "=SUM(I5:I{$lastDataRow})");
            $sheet->setCellValue("J{$row}", $registrations->count() . ' Pendaftar');
            $sheet->mergeCells("J{$row}:L{$row}");

            $sheet->getStyle("A{$row}:L{$row}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'], // Slate 200
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '94A3B8'],
                    ],
                ],
            ]);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension($row)->setRowHeight(22);
        }

        // Set borders for all data rows
        if ($lastDataRow >= 5) {
            $sheet->getStyle("A5:L{$lastDataRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Auto filter on table range
            $sheet->setAutoFilter("A4:L{$lastDataRow}");
        }

        // Freeze panes below header
        $sheet->freezePane('A5');

        // Auto-size columns with sensible min/max width
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('H')->setAutoSize(false)->setWidth(30);
        $sheet->getColumnDimension('K')->setAutoSize(false)->setWidth(35);

        $filename = 'Data-Pendaftar-Semarak-Aliyah-XIV_' . date('Y-m-d_His') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Recursively format complex or nested custom fields to a clean human-readable string.
     */
    private function formatCustomFieldValue(mixed $val): string
    {
        if (is_array($val)) {
            if (isset($val['url'])) {
                return (string) $val['url'];
            }
            $items = [];
            foreach ($val as $subK => $subV) {
                if (is_array($subV)) {
                    $subStr = $this->formatCustomFieldValue($subV);
                    $items[] = is_numeric($subK) ? $subStr : "{$subK}: [{$subStr}]";
                } else {
                    $items[] = is_numeric($subK) ? (string) $subV : "{$subK}: {$subV}";
                }
            }
            return implode('; ', $items);
        }

        return (string) ($val ?? '-');
    }

    // =========================================================================
    // MANAJEMEN MITRA & SPONSOR SLIDER
    // =========================================================================
    public function sponsorIndex(Request $request)
    {
        $sponsors = Sponsor::orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();

        $settings = [
            'sponsor_section_tag' => Setting::get('sponsor_section_tag', 'Mitra & Kolaborasi'),
            'sponsor_section_title' => Setting::get('sponsor_section_title', 'Didukung Oleh Mitra & Sponsor Terpercaya'),
            'sponsor_section_desc' => Setting::get('sponsor_section_desc', 'Apresiasi dan terima kasih kepada institusi perbankan syariah, penyedia telekomunikasi, media massa, serta mitra strategis yang turut bersinergi menyukseskan Semarak Aliyah XII 2026.'),
            'sponsor_cta_text' => Setting::get('sponsor_cta_text', 'Peluang Sponsorship: Bergabunglah sebagai mitra resmi dan promosikan brand Anda kepada ribuan pelajar, santri & pendidik se-Kabupaten Jember.'),
            'sponsor_cta_wa' => Setting::get('sponsor_cta_wa', '6283850070767'),
            'sponsor_cta_btn_text' => Setting::get('sponsor_cta_btn_text', 'Ajukan Kerja Sama Sponsor'),
            'sponsor_is_active' => Setting::get('sponsor_is_active', '1') === '1',
        ];

        return \Inertia\Inertia::render('Admin/Sponsor', compact('sponsors', 'settings'));
    }

    public function storeSponsor(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'tier' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:255',
            'only_logo' => 'nullable',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'bg_color' => 'nullable|string|max:50',
            'border_color' => 'nullable|string|max:50',
            'website_url' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
            'logo_file' => 'nullable|image|max:3072',
            'logo_url' => 'nullable|string|max:500',
        ]);

        $logoPath = $request->input('logo_url');
        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('sponsors', 'public');
            $logoPath = '/storage/' . $path;
        }

        $color = $validated['color'] ?? '#6366f1';
        $bgColor = $validated['bg_color'] ?? 'rgba(99, 102, 241, 0.1)';
        $borderColor = $validated['border_color'] ?? 'rgba(99, 102, 241, 0.3)';

        $sponsor = Sponsor::create([
            'name' => $validated['name'],
            'short_name' => $validated['short_name'] ?? null,
            'tier' => $validated['tier'] ?? 'Mitra Resmi',
            'category' => $validated['category'] ?? 'Sponsor Pendukung',
            'logo' => $logoPath,
            'only_logo' => $request->boolean('only_logo'),
            'icon' => $validated['icon'] ?: 'ri-shake-hands-line',
            'color' => $color,
            'bg_color' => $bgColor,
            'border_color' => $borderColor,
            'website_url' => $validated['website_url'] ?? null,
            'sort_order' => (int)($validated['sort_order'] ?? (Sponsor::max('sort_order') + 1)),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return redirect()->back()->with('success', "Mitra sponsor \"{$sponsor->name}\" berhasil ditambahkan.");
    }

    public function updateSponsor(Request $request, string|int $id)
    {
        $sponsor = Sponsor::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'tier' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:255',
            'only_logo' => 'nullable',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'bg_color' => 'nullable|string|max:50',
            'border_color' => 'nullable|string|max:50',
            'website_url' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
            'logo_file' => 'nullable|image|max:3072',
            'logo_url' => 'nullable|string|max:500',
        ]);

        $logoPath = $sponsor->logo;
        if ($request->has('logo_url') && $request->input('logo_url') !== '') {
            $logoPath = $request->input('logo_url');
        }

        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('sponsors', 'public');
            $logoPath = '/storage/' . $path;
        }

        $sponsor->update([
            'name' => $validated['name'],
            'short_name' => $validated['short_name'] ?? $sponsor->short_name,
            'tier' => $validated['tier'] ?? $sponsor->tier,
            'category' => $validated['category'] ?? $sponsor->category,
            'logo' => $logoPath,
            'only_logo' => $request->boolean('only_logo'),
            'icon' => $validated['icon'] ?: $sponsor->icon,
            'color' => $validated['color'] ?? $sponsor->color,
            'bg_color' => $validated['bg_color'] ?? $sponsor->bg_color,
            'border_color' => $validated['border_color'] ?? $sponsor->border_color,
            'website_url' => $validated['website_url'] ?? $sponsor->website_url,
            'sort_order' => isset($validated['sort_order']) ? (int)$validated['sort_order'] : $sponsor->sort_order,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $sponsor->is_active,
        ]);

        return redirect()->back()->with('success', "Data mitra sponsor \"{$sponsor->name}\" berhasil diperbarui.");
    }

    public function destroySponsor(string|int $id)
    {
        $sponsor = Sponsor::findOrFail($id);
        $name = $sponsor->name;
        $sponsor->delete();

        return redirect()->back()->with('success', "Mitra sponsor \"{$name}\" berhasil dihapus.");
    }

    public function toggleSponsorActive(string|int $id)
    {
        $sponsor = Sponsor::findOrFail($id);
        $sponsor->is_active = !$sponsor->is_active;
        $sponsor->save();

        $statusStr = $sponsor->is_active ? 'ditampilkan' : 'disembunyikan';
        return redirect()->back()->with('success', "Mitra sponsor \"{$sponsor->name}\" kini {$statusStr} di website.");
    }

    public function reorderSponsors(Request $request)
    {
        $orders = $request->input('orders', []);
        foreach ($orders as $item) {
            if (isset($item['id']) && isset($item['sort_order'])) {
                Sponsor::where('id', $item['id'])->update(['sort_order' => (int)$item['sort_order']]);
            }
        }
        return redirect()->back()->with('success', 'Urutan mitra sponsor berhasil diperbarui.');
    }

    public function updateSponsorSettings(Request $request)
    {
        $validated = $request->validate([
            'sponsor_section_tag' => 'nullable|string|max:100',
            'sponsor_section_title' => 'required|string|max:255',
            'sponsor_section_desc' => 'required|string|max:1000',
            'sponsor_cta_text' => 'nullable|string|max:500',
            'sponsor_cta_wa' => 'nullable|string|max:30',
            'sponsor_cta_btn_text' => 'nullable|string|max:100',
            'sponsor_is_active' => 'nullable',
        ]);

        foreach ($validated as $k => $v) {
            Setting::set($k, (string)$v);
        }

        if ($request->has('sponsor_is_active')) {
            Setting::set('sponsor_is_active', $request->boolean('sponsor_is_active') ? '1' : '0');
        }

        return redirect()->back()->with('success', 'Pengaturan teks judul dan deskripsi elemen sponsor berhasil disimpan.');
    }

    // =========================================================================
    // MANAJEMEN GALERI DOKUMENTASI (2-ROW COUNTER-SLIDER)
    // =========================================================================
    public function galleryIndex(Request $request)
    {
        $galleries = Gallery::orderBy('row_position', 'asc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $settings = [
            'gallery_section_tag' => Setting::get('gallery_section_tag', 'Galeri Dokumentasi'),
            'gallery_section_title' => Setting::get('gallery_section_title', 'Momen Spektakuler & Kilas Balik'),
            'gallery_section_desc' => Setting::get('gallery_section_desc', 'Saksikan sorak kemeriahan, kobaran semangat sportivitas talenta muda, dan kilas balik keseruan panggung Semarak Aliyah.'),
            'gallery_is_active' => Setting::get('gallery_is_active', '1') === '1',
        ];

        return \Inertia\Inertia::render('Admin/Gallery', compact('galleries', 'settings'));
    }

    public function storeGallery(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_label' => 'nullable|string|max:100',
            'pill_class' => 'nullable|string|max:50',
            'icon_class' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
            'tag' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'row_position' => 'required|integer|in:1,2',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
            'image_file' => 'nullable|image|max:6144',
            'image_url' => 'nullable|string|max:500',
        ]);

        $imagePath = $request->input('image_url');
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('gallery', 'public');
            $imagePath = '/storage/' . $path;
        }

        if (empty($imagePath)) {
            $imagePath = '/assets/img/gallery/gallery_awarding.jpg';
        }

        $gallery = Gallery::create([
            'title' => $validated['title'],
            'category_label' => $validated['category_label'] ?? 'Dokumentasi',
            'pill_class' => $validated['pill_class'] ?: 'pill-gold',
            'icon_class' => $validated['icon_class'] ?: 'ri-image-line',
            'image_path' => $imagePath,
            'location' => $validated['location'] ?? null,
            'tag' => $validated['tag'] ?? null,
            'description' => $validated['description'] ?? null,
            'row_position' => (int)$validated['row_position'],
            'sort_order' => (int)($validated['sort_order'] ?? (Gallery::where('row_position', $validated['row_position'])->max('sort_order') + 1)),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return redirect()->back()->with('success', "Foto galeri \"{$gallery->title}\" berhasil ditambahkan.");
    }

    public function updateGallery(Request $request, string|int $id)
    {
        $gallery = Gallery::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_label' => 'nullable|string|max:100',
            'pill_class' => 'nullable|string|max:50',
            'icon_class' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
            'tag' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'row_position' => 'required|integer|in:1,2',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
            'image_file' => 'nullable|image|max:6144',
            'image_url' => 'nullable|string|max:500',
        ]);

        $imagePath = $gallery->image_path;
        if ($request->has('image_url') && $request->input('image_url') !== '') {
            $imagePath = $request->input('image_url');
        }

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('gallery', 'public');
            $imagePath = '/storage/' . $path;
        }

        $gallery->update([
            'title' => $validated['title'],
            'category_label' => $validated['category_label'] ?? $gallery->category_label,
            'pill_class' => $validated['pill_class'] ?: $gallery->pill_class,
            'icon_class' => $validated['icon_class'] ?: $gallery->icon_class,
            'image_path' => $imagePath,
            'location' => $validated['location'] ?? $gallery->location,
            'tag' => $validated['tag'] ?? $gallery->tag,
            'description' => $validated['description'] ?? $gallery->description,
            'row_position' => (int)$validated['row_position'],
            'sort_order' => (int)($validated['sort_order'] ?? $gallery->sort_order),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $gallery->is_active,
        ]);

        return redirect()->back()->with('success', "Foto galeri \"{$gallery->title}\" berhasil diperbarui.");
    }

    public function destroyGallery(string|int $id)
    {
        $gallery = Gallery::findOrFail($id);
        $title = $gallery->title;
        $gallery->delete();

        return redirect()->back()->with('success', "Foto galeri \"{$title}\" berhasil dihapus.");
    }

    public function toggleGalleryActive(string|int $id)
    {
        $gallery = Gallery::findOrFail($id);
        $gallery->is_active = !$gallery->is_active;
        $gallery->save();

        $statusStr = $gallery->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Status foto \"{$gallery->title}\" berhasil {$statusStr}.");
    }

    public function reorderGalleries(Request $request)
    {
        $orders = $request->input('orders', []);
        foreach ($orders as $item) {
            if (isset($item['id'])) {
                $updateData = [];
                if (isset($item['sort_order'])) {
                    $updateData['sort_order'] = (int)$item['sort_order'];
                }
                if (isset($item['row_position'])) {
                    $updateData['row_position'] = (int)$item['row_position'];
                }
                if (!empty($updateData)) {
                    Gallery::where('id', $item['id'])->update($updateData);
                }
            }
        }
        return redirect()->back()->with('success', 'Tata letak dan urutan galeri foto berhasil disimpan.');
    }

    public function updateGallerySettings(Request $request)
    {
        $validated = $request->validate([
            'gallery_section_tag' => 'nullable|string|max:100',
            'gallery_section_title' => 'required|string|max:255',
            'gallery_section_desc' => 'required|string|max:1000',
            'gallery_is_active' => 'nullable',
        ]);

        foreach ($validated as $k => $v) {
            Setting::set($k, (string)$v);
        }

        if ($request->has('gallery_is_active')) {
            Setting::set('gallery_is_active', $request->boolean('gallery_is_active') ? '1' : '0');
        }

        return redirect()->back()->with('success', 'Pengaturan teks judul dan deskripsi galeri dokumentasi berhasil disimpan.');
    }

    // =========================================================================
    // MANAJEMEN RUNNING TEXT TICKER (KONFER MARQUEE)
    // =========================================================================
    public function tickerIndex(Request $request)
    {
        $defaultItems = [
            ['id' => 1, 'text' => 'SEMARAK ALIYAH XII', 'style' => 'gradient', 'is_active' => true],
            ['id' => 2, 'text' => 'AJANG TALENTA JUARA', 'style' => 'outline', 'is_active' => true],
            ['id' => 3, 'text' => 'MA UNGGULAN WAHID HASYIM', 'style' => 'filled', 'is_active' => true],
            ['id' => 4, 'text' => 'SAINS • SENI • OLAHRAGA • RELIGI', 'style' => 'outline', 'is_active' => true],
            ['id' => 5, 'text' => 'TOTAL HADIAH JUTAAN RUPIAH', 'style' => 'gradient', 'is_active' => true],
            ['id' => 6, 'text' => 'FESTIVAL PELAJAR SE-JEMBER', 'style' => 'outline', 'is_active' => true],
        ];

        $itemsRaw = Setting::get('ticker_items', null);
        $items = $itemsRaw ? json_decode($itemsRaw, true) : null;
        if (empty($items) || !is_array($items)) {
            $items = $defaultItems;
        }

        $settings = [
            'ticker_is_active' => Setting::get('ticker_is_active', '1') === '1',
            'ticker_speed' => (int)Setting::get('ticker_speed', 35),
            'ticker_separator' => Setting::get('ticker_separator', '✦'),
            'ticker_pause_on_hover' => Setting::get('ticker_pause_on_hover', '1') === '1',
            'ticker_font_size' => (int)Setting::get('ticker_font_size', 48),
            'ticker_font_family' => Setting::get('ticker_font_family', 'syne'),
            'ticker_stroke_width' => (float)Setting::get('ticker_stroke_width', 2.0),
            'ticker_opacity' => (int)Setting::get('ticker_opacity', 100),
            'ticker_outline_opacity' => (int)Setting::get('ticker_outline_opacity', 80),
            'ticker_items' => $items,
        ];

        return \Inertia\Inertia::render('Admin/Ticker', compact('settings'));
    }

    public function updateTickerSettings(Request $request)
    {
        $validated = $request->validate([
            'ticker_is_active' => 'nullable',
            'ticker_speed' => 'required|integer|min:10|max:150',
            'ticker_separator' => 'required|string|max:10',
            'ticker_pause_on_hover' => 'nullable',
            'ticker_font_size' => 'required|integer|min:20|max:200',
            'ticker_font_family' => 'required|string|in:syne,outfit,oswald,jakarta,montserrat',
            'ticker_stroke_width' => 'required|numeric|min:1|max:5',
            'ticker_opacity' => 'required|integer|min:20|max:100',
            'ticker_outline_opacity' => 'nullable|integer|min:20|max:100',
            'ticker_items' => 'required|array|min:1',
            'ticker_items.*.text' => 'required|string|max:200',
            'ticker_items.*.style' => 'required|string|in:filled,outline,gradient',
            'ticker_items.*.is_active' => 'nullable',
        ]);

        Setting::set('ticker_is_active', $request->boolean('ticker_is_active') ? '1' : '0');
        Setting::set('ticker_speed', (string)$validated['ticker_speed']);
        Setting::set('ticker_separator', (string)$validated['ticker_separator']);
        Setting::set('ticker_pause_on_hover', $request->boolean('ticker_pause_on_hover') ? '1' : '0');
        Setting::set('ticker_font_size', (string)$validated['ticker_font_size']);
        Setting::set('ticker_font_family', (string)$validated['ticker_font_family']);
        Setting::set('ticker_stroke_width', (string)$validated['ticker_stroke_width']);
        Setting::set('ticker_opacity', (string)$validated['ticker_opacity']);
        Setting::set('ticker_outline_opacity', (string)($validated['ticker_outline_opacity'] ?? 80));

        $cleanItems = [];
        foreach ($validated['ticker_items'] as $index => $item) {
            $cleanItems[] = [
                'id' => $item['id'] ?? ($index + 1),
                'text' => trim($item['text']),
                'style' => $item['style'],
                'is_active' => !empty($item['is_active']),
            ];
        }

        Setting::set('ticker_items', json_encode($cleanItems, JSON_UNESCAPED_UNICODE));

        return redirect()->back()->with('success', 'Pengaturan teks berjalan (marquee) berhasil disimpan.');
    }

    // =========================================================================
    // MANAJEMEN TANYA JAWAB (FAQ)
    // =========================================================================
    public function faqIndex(Request $request)
    {
        $faqs = Faq::orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $settings = [
            'faq_section_tag' => Setting::get('faq_section_tag', 'Bantuan Informasi'),
            'faq_section_title' => Setting::get('faq_section_title', 'Pertanyaan Sering Diajukan (FAQ)'),
            'faq_section_desc' => Setting::get('faq_section_desc', 'Jawaban atas pertanyaan umum seputar pendaftaran dan pelaksanaan Semarak Aliyah XIV 2026.'),
            'faq_is_active' => Setting::get('faq_is_active', '1') === '1',
        ];

        return \Inertia\Inertia::render('Admin/Faq', compact('faqs', 'settings'));
    }

    public function storeFaq(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
        ]);

        $maxSort = Faq::max('sort_order') ?? 0;

        $faq = Faq::create([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'category' => $validated['category'] ?: 'Umum',
            'sort_order' => isset($validated['sort_order']) ? (int)$validated['sort_order'] : ($maxSort + 1),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return redirect()->back()->with('success', "FAQ \"{$faq->question}\" berhasil ditambahkan.");
    }

    public function updateFaq(Request $request, string|int $id)
    {
        $faq = Faq::findOrFail($id);

        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
        ]);

        $faq->update([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'category' => $validated['category'] ?: 'Umum',
            'sort_order' => isset($validated['sort_order']) ? (int)$validated['sort_order'] : $faq->sort_order,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $faq->is_active,
        ]);

        return redirect()->back()->with('success', "FAQ \"{$faq->question}\" berhasil diperbarui.");
    }

    public function destroyFaq(string|int $id)
    {
        $faq = Faq::findOrFail($id);
        $question = $faq->question;
        $faq->delete();

        return redirect()->back()->with('success', "FAQ \"{$question}\" berhasil dihapus.");
    }

    public function toggleFaqActive(string|int $id)
    {
        $faq = Faq::findOrFail($id);
        $faq->is_active = !$faq->is_active;
        $faq->save();

        $statusStr = $faq->is_active ? 'ditampilkan' : 'disembunyikan';
        return redirect()->back()->with('success', "FAQ kini {$statusStr} di website.");
    }

    public function reorderFaqs(Request $request)
    {
        $orders = $request->input('orders', []);
        foreach ($orders as $item) {
            if (isset($item['id']) && isset($item['sort_order'])) {
                Faq::where('id', $item['id'])->update(['sort_order' => (int)$item['sort_order']]);
            }
        }
        return redirect()->back()->with('success', 'Urutan FAQ berhasil diperbarui.');
    }

    public function updateFaqSettings(Request $request)
    {
        $validated = $request->validate([
            'faq_section_tag' => 'nullable|string|max:100',
            'faq_section_title' => 'required|string|max:255',
            'faq_section_desc' => 'required|string|max:1000',
            'faq_is_active' => 'nullable',
        ]);

        foreach ($validated as $k => $v) {
            Setting::set($k, (string)$v);
        }

        if ($request->has('faq_is_active')) {
            Setting::set('faq_is_active', $request->boolean('faq_is_active') ? '1' : '0');
        }

        return redirect()->back()->with('success', 'Pengaturan teks judul dan deskripsi FAQ berhasil disimpan.');
    }

    // =========================================================================
    // FITUR MANAJEMEN TECHNICAL MEETING (TM) & PENGUNDIAN NOMOR URUT
    // =========================================================================
    public function tmIndex(Request $request)
    {
        /** @var User|null $authUser */
        $authUser = Auth::guard('web')->user();
        $isGlobalAdmin = $authUser?->isSuperAdmin() || in_array($authUser?->role, ['admin', 'ketua_panitia', 'wakil_ketua_panitia', 'sekretaris', 'kesekretariatan'], true);
        $hasTmManage = $authUser ? $authUser->hasPermission('tm.manage') : false;
        $coordinatedIds = $authUser ? $authUser->getCoordinatedCompetitionIds() : [];

        $competitions = Competition::with(['coordinator:id,name,phone,email,role'])->withCount('registrations')->orderBy('id', 'asc')->get();
        foreach ($competitions as $c) {
            $isCoordinated = in_array((string)$c->id, $coordinatedIds, true);
            $c->is_coordinated = $isCoordinated;
            $c->can_modify = $hasTmManage && ($isGlobalAdmin || $isCoordinated);
        }
        
        $activeCompId = $request->input('comp');
        // Jika koordinator lomba membuka halaman TM tanpa query string comp, prioritaskan cabang lombanya
        if (!$activeCompId && !$isGlobalAdmin && !empty($coordinatedIds)) {
            $activeCompId = $coordinatedIds[0];
        } elseif (!$activeCompId && $authUser?->role === 'koordinator_lomba' && $authUser->competition_id) {
            $activeCompId = $authUser->competition_id;
        }

        if (!$activeCompId || !$competitions->contains('id', $activeCompId)) {
            $activeCompId = $competitions->first() ? $competitions->first()->id : null;
        }

        $activeComp = $activeCompId ? Competition::with(['coordinator:id,name,phone,email,role'])->withCount('registrations')->find($activeCompId) : null;
        
        // Delegasi / Peserta terdaftar pada cabang lomba ini
        $participants = [];
        if ($activeComp) {
            $participants = Registration::where('competition_id', $activeCompId)
                ->orderByRaw('CASE WHEN draw_number IS NULL THEN 1 ELSE 0 END, draw_number ASC, id ASC')
                ->get();
        }

        // Summary KPI
        $allCompetitions = Competition::orderBy('id', 'asc')->get();
        $totalCompetitions = $allCompetitions->count();
        $scheduledCount = 0;
        $ongoingCount = 0;
        $completedCount = 0;

        foreach ($allCompetitions as $c) {
            $tm = $c->resolved_tm_settings ?? [];
            $st = $tm['status'] ?? 'upcoming';
            if ($st === 'completed') {
                $completedCount++;
            } elseif ($st === 'ongoing') {
                $ongoingCount++;
            } else {
                $scheduledCount++;
            }
        }

        $totalDrawed = Registration::whereNotNull('draw_number')->count();
        $totalParticipants = Registration::count();

        $stats = [
            'total_competitions' => $totalCompetitions,
            'scheduled_count' => $scheduledCount,
            'ongoing_count' => $ongoingCount,
            'completed_count' => $completedCount,
            'total_drawed' => $totalDrawed,
            'total_participants' => $totalParticipants,
        ];

        $rawTmGuidelines = Setting::get('tm_guidelines_items', null);
        $tmGuidelines = $rawTmGuidelines ? json_decode($rawTmGuidelines, true) : null;
        if (empty($tmGuidelines) || !is_array($tmGuidelines)) {
            $tmGuidelines = self::getDefaultTmGuidelines();
        }

        $sectionSettings = [
            'tag' => Setting::get('tm_section_tag', 'Agenda Pelaksanaan'),
            'title' => Setting::get('tm_section_title', 'Jadwal Technical Meeting & Pelaksanaan'),
            'desc' => Setting::get('tm_section_desc', 'Harap diperhatikan oleh guru pendamping dan ketua delegasi sekolah mengenai tanggal, jam, ruangan, serta agenda pelaksanaan TM tiap cabang lomba.'),
            'guidelines_active' => Setting::get('tm_guidelines_active', '1') === '1',
            'guidelines' => $tmGuidelines,
            'is_active' => Setting::get('tm_section_active', '1') === '1',
        ];

        // Struktur Kepanitiaan Terkait
        $ketuaPanitia = User::where('role', 'ketua_panitia')->select('id', 'name', 'phone', 'email', 'role')->get();
        $wakilKetuaPanitia = User::where('role', 'wakil_ketua_panitia')->select('id', 'name', 'phone', 'email', 'role')->get();
        $sekretaris = User::whereIn('role', ['sekretaris', 'kesekretariatan'])->select('id', 'name', 'phone', 'email', 'role')->get();

        $committeeStructure = [
            'ketua_panitia'          => $ketuaPanitia,
            'wakil_ketua_panitia'    => $wakilKetuaPanitia,
            'sekretaris'             => $sekretaris,
            'current_user_id'        => $authUser?->id,
            'current_user_role'      => $authUser?->role,
            'current_user_comp'      => $authUser?->competition_id,
            'can_modify_active_comp' => $this->authorizeCompetitionAccess($activeCompId, 'tm.manage'),
            'is_coordinator'         => $authUser?->role === 'koordinator_lomba' || ($activeComp && $activeComp->isUserCoordinator($authUser?->id)) || !empty($coordinatedIds),
            'is_global_admin'        => $isGlobalAdmin,
            'has_tm_manage'          => $hasTmManage,
        ];

        return \Inertia\Inertia::render('Admin/TechnicalMeeting', [
            'competitions' => $competitions,
            'activeComp' => $activeComp,
            'participants' => $participants,
            'stats' => $stats,
            'selectedCompId' => $activeCompId,
            'sectionSettings' => $sectionSettings,
            'committeeStructure' => $committeeStructure,
        ]);
    }

    public static function getDefaultTmGuidelines(): array
    {
        return [
            [
                'title' => 'Delegasi Resmi',
                'desc' => 'Setiap madrasah/sekolah wajib mengutus minimal 1 guru pembina atau kapten/ketua tim.',
                'icon' => 'ri-user-star-line',
                'color' => '#8b5cf6',
                'bg' => 'rgba(139, 92, 246, 0.1)',
                'border' => 'rgba(139, 92, 246, 0.25)',
            ],
            [
                'title' => 'Verifikasi Berkas',
                'desc' => 'Membawa cetak bukti registrasi & Kartu Tanda Siswa (KTS) / delegasi resmi untuk validasi.',
                'icon' => 'ri-file-shield-line',
                'color' => '#f97316',
                'bg' => 'rgba(249, 115, 22, 0.1)',
                'border' => 'rgba(249, 115, 22, 0.25)',
            ],
            [
                'title' => 'Akses Hybrid (Zoom)',
                'desc' => 'Tersedia tautan Zoom Meeting khusus bagi delegasi luar daerah yang berhalangan hadir offline.',
                'icon' => 'ri-vidicon-line',
                'color' => '#10b981',
                'bg' => 'rgba(16, 185, 129, 0.1)',
                'border' => 'rgba(16, 185, 129, 0.25)',
            ],
            [
                'title' => 'Tepat Waktu & Tertib',
                'desc' => 'Hadir 15 menit sebelum pembukaan dimulai, mengenakan seragam rapi atau jas almamater.',
                'icon' => 'ri-time-line',
                'color' => '#ec4899',
                'bg' => 'rgba(236, 72, 153, 0.1)',
                'border' => 'rgba(236, 72, 153, 0.25)',
            ],
        ];
    }

    public function updateTmSectionSettings(Request $request)
    {
        /** @var User|null $authUser */
        $authUser = Auth::guard('web')->user();
        if (!$authUser?->isSuperAdmin() && !in_array($authUser?->role, ['admin', 'ketua_panitia', 'wakil_ketua_panitia', 'sekretaris', 'kesekretariatan'], true)) {
            return redirect()->back()->with('error', 'Akses Ditolak: Hanya Panitia Inti (Admin/Ketua/Sekretaris) yang berwenang mengubah pengaturan global agenda Technical Meeting.');
        }

        $validated = $request->validate([
            'tag' => 'required|string|max:100',
            'title' => 'required|string|max:150',
            'desc' => 'nullable|string|max:500',
            'guidelines_active' => 'nullable',
            'is_active' => 'nullable',
            'guidelines' => 'nullable|array',
            'guidelines.*.title' => 'required|string|max:100',
            'guidelines.*.desc' => 'required|string|max:300',
            'guidelines.*.icon' => 'nullable|string|max:50',
            'guidelines.*.color' => 'nullable|string|max:30',
        ]);

        Setting::set('tm_section_tag', $validated['tag']);
        Setting::set('tm_section_title', $validated['title']);
        Setting::set('tm_section_desc', $validated['desc'] ?? '');
        Setting::set('tm_guidelines_active', !empty($validated['guidelines_active']) ? '1' : '0');
        Setting::set('tm_section_active', !empty($validated['is_active']) ? '1' : '0');

        if (isset($validated['guidelines'])) {
            Setting::set('tm_guidelines_items', json_encode($validated['guidelines']));
        }

        return back()->with('success', 'Pengaturan teks & panduan jadwal Technical Meeting berhasil disimpan.');
    }

    public function updateTmSettings(Request $request, string|int $competitionId)
    {
        $competition = Competition::findOrFail($competitionId);

        if (!$this->authorizeCompetitionAccess($competitionId, 'tm.manage')) {
            return redirect()->back()->with('error', "Akses Ditolak: Anda tidak memiliki wewenang atau hak akses untuk mengelola Technical Meeting cabang lomba \"{$competition->title}\".");
        }

        $validated = $request->validate([
            'tm_date' => 'nullable|string|max:100',
            'tm_time' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:150',
            'meeting_type' => 'nullable|string|in:offline,online,hybrid',
            'meeting_link' => 'nullable|string|max:255',
            'whatsapp_group' => 'nullable|string|max:255',
            'status' => 'required|string|in:upcoming,ongoing,completed',
            'pic_name' => 'nullable|string|max:100',
            'pic_contact' => 'nullable|string|max:50',
            'agenda' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
            'rules_summary' => 'nullable|string',
            'juknis_file' => 'nullable|file|mimes:pdf,docx,doc|max:30720',
        ]);

        if ($request->hasFile('juknis_file')) {
            $file = $request->file('juknis_file');
            $originalName = $file->getClientOriginalName();
            $ext = $file->getClientOriginalExtension();
            $safeName = 'JUKNIS_' . strtoupper(preg_replace('/[^a-zA-Z0-9]/', '_', $competition->id)) . '_' . date('Ymd_His') . '.' . $ext;
            $path = $file->storeAs('uploads/juknis', $safeName, 'public');
            $url = '/storage/' . $path;
            $competition->juknis_file = $url;
            $competition->juknis_original_name = $originalName;
            $competition->juknis_size = $file->getSize();
            $validated['juknis_file'] = $url;
            $validated['juknis_filename'] = $originalName;
        }

        $currentSettings = $competition->tm_settings ?? [];
        $merged = array_merge($currentSettings, $validated, [
            'updated_at' => now()->toDateTimeString(),
        ]);

        $competition->tm_settings = $merged;

        // Keep tm_date on competition synchronized for backward compatibility & public web
        if (!empty($validated['tm_date'])) {
            $timePart = !empty($validated['tm_time']) ? " • " . $validated['tm_time'] : "";
            $competition->tm_date = $validated['tm_date'] . $timePart;
        }

        // Synchronize coordinators and contact person back to Competition if updated in TM
        if (!empty($validated['pic_name'])) {
            $existingCoords = $competition->coordinators ?? [];
            if (!empty($existingCoords) && is_array($existingCoords)) {
                $existingCoords[0]['name'] = $validated['pic_name'];
                if (!empty($validated['pic_contact'])) {
                    $existingCoords[0]['contact'] = $validated['pic_contact'];
                }
            } else {
                $existingCoords = [[
                    'name' => $validated['pic_name'],
                    'contact' => $validated['pic_contact'] ?? '',
                    'role' => 'Koordinator Lomba'
                ]];
            }
            $competition->coordinators = $existingCoords;
            $competition->contact_person = !empty($validated['pic_contact'])
                ? "{$validated['pic_name']} ({$validated['pic_contact']})"
                : $validated['pic_name'];
        }

        $competition->save();

        return redirect()->back()->with('success', "Pengaturan Technical Meeting (TM) untuk {$competition->title} berhasil diperbarui.");
    }

    public function randomizeDraw(Request $request, string|int $competitionId)
    {
        $competition = Competition::findOrFail($competitionId);
        
        if (!$this->authorizeCompetitionAccess($competitionId, 'tm.manage')) {
            return redirect()->back()->with('error', "Akses Ditolak: Anda tidak memiliki wewenang atau hak akses untuk mengacak nomor undian cabang \"{$competition->title}\".");
        }

        // Ambil semua pendaftar pada cabang lomba ini
        $participants = Registration::where('competition_id', $competitionId)->get();

        if ($participants->isEmpty()) {
            return redirect()->back()->with('error', "Belum ada delegasi terdaftar pada cabang lomba {$competition->title}.");
        }

        // Acak urutan pendaftar (Fisher-Yates shuffle)
        $shuffled = $participants->shuffle();
        
        $number = 1;
        foreach ($shuffled as $reg) {
            $reg->draw_number = $number;
            $reg->draw_notes = "Undian Acak TM (Nomor Tampil {$number})";
            $reg->save();
            $number++;
        }

        // Update status TM menjadi ongoing jika sebelumnya upcoming
        $settings = $competition->tm_settings ?? [];
        if (($settings['status'] ?? 'upcoming') === 'upcoming') {
            $settings['status'] = 'ongoing';
            $competition->tm_settings = $settings;
            $competition->save();
        }

        return redirect()->back()->with('success', "Pengundian nomor urut TM untuk {$competition->title} berhasil diacak kepada {$participants->count()} delegasi.");
    }

    public function updateParticipantOrder(Request $request, string|int $competitionId)
    {
        $competition = Competition::findOrFail($competitionId);

        if (!$this->authorizeCompetitionAccess($competitionId, 'tm.manage')) {
            return redirect()->back()->with('error', "Akses Ditolak: Anda tidak memiliki wewenang atau hak akses untuk mengubah urutan peserta cabang \"{$competition->title}\".");
        }

        $request->validate([
            'registration_id' => 'required|exists:registrations,id',
            'draw_number' => 'nullable|integer|min:1|max:999',
            'draw_notes' => 'nullable|string|max:255',
        ]);

        $reg = Registration::where('competition_id', $competitionId)
            ->where('id', $request->registration_id)
            ->firstOrFail();

        $reg->draw_number = $request->draw_number;
        $reg->draw_notes = $request->draw_notes;
        $reg->save();

        return redirect()->back()->with('success', "Nomor undian untuk {$reg->school_name} berhasil diperbarui menjadi #{$request->draw_number}.");
    }

    public function resetDraw(Request $request, string|int $competitionId)
    {
        $competition = Competition::findOrFail($competitionId);

        if (!$this->authorizeCompetitionAccess($competitionId, 'tm.manage')) {
            return redirect()->back()->with('error', "Akses Ditolak: Anda tidak memiliki wewenang atau hak akses untuk mereset undian cabang \"{$competition->title}\".");
        }

        Registration::where('competition_id', $competitionId)->update([
            'draw_number' => null,
            'draw_notes' => null,
        ]);

        return redirect()->back()->with('success', "Seluruh nomor urut undian TM cabang {$competition->title} berhasil direset.");
    }

    // =========================================================================
    // FITUR KELOLA BIAYA REGISTRASI & PAKET PEMBAYARAN
    // =========================================================================
    public function biayaIndex(Request $request)
    {
        $competitions = Competition::withCount('registrations')->orderBy('id', 'asc')->get();

        $sumFees = (int)$competitions->sum('fee');
        $minFee = (int)($competitions->min('fee') ?? 0);
        $maxFee = (int)($competitions->max('fee') ?? 0);
        $avgFee = $competitions->count() > 0 ? (int)round($competitions->avg('fee')) : 0;
        $totalCompetitions = $competitions->count();

        // Revenue & Registration Stats
        $totalRegistrations = Registration::count();
        $paidRegistrationsCount = Registration::whereIn('payment_status', ['Lunas', 'Terverifikasi', 'Diterima'])->count();
        $totalPaidRevenue = (int)Registration::whereIn('payment_status', ['Lunas', 'Terverifikasi', 'Diterima'])->sum('total_fee');
        $pendingRevenue = (int)Registration::whereNotIn('payment_status', ['Lunas', 'Terverifikasi', 'Diterima', 'Ditolak', 'Batal'])->sum('total_fee');

        $isAutoOriginalPrice = Setting::get('package_allin_auto_original_price', '1') === '1';
        $allinPrice = (int)Setting::get('package_allin_price', 200000);
        $allinOriginalPrice = $isAutoOriginalPrice ? $sumFees : (int)Setting::get('package_allin_original_price', $sumFees);
        if ($allinOriginalPrice <= 0) {
            $allinOriginalPrice = $sumFees;
        }
        if ($isAutoOriginalPrice && (string)Setting::get('package_allin_original_price') !== (string)$sumFees) {
            Setting::set('package_allin_original_price', (string)$sumFees);
        }
        $allinSavings = max(0, $allinOriginalPrice - $allinPrice);
        $allinSavingsPercent = $allinOriginalPrice > 0 ? (int)round(($allinSavings / $allinOriginalPrice) * 100) : 0;

        $stats = [
            'total_competitions' => $totalCompetitions,
            'sum_competition_fees' => $sumFees,
            'min_fee' => $minFee,
            'max_fee' => $maxFee,
            'avg_fee' => $avgFee,
            'allin_price' => $allinPrice,
            'allin_original_price' => $allinOriginalPrice,
            'allin_savings' => $allinSavings,
            'allin_savings_percent' => $allinSavingsPercent,
            'total_registrations' => $totalRegistrations,
            'paid_registrations_count' => $paidRegistrationsCount,
            'total_paid_revenue' => $totalPaidRevenue,
            'pending_revenue' => $pendingRevenue,
        ];

        // All-in & Satuan features json parsing
        $defaultAllinFeatures = [
            'Bebas daftarkan kontingen di seluruh 8 cabang lomba',
            'Hemat biaya pendaftaran total hingga Rp 200.000,- (50%)',
            'Prioritas verifikasi berkas cepat & reservasi kuota kontingen',
            'Berhak memperebutkan Piala Bergilir Juara Umum Semarak',
            'E-Sertifikat resmi ber-barcode untuk seluruh siswa & guru pembimbing',
        ];
        $allinRaw = Setting::get('package_allin_features');
        $allinFeatures = $allinRaw ? json_decode($allinRaw, true) : null;
        if (empty($allinFeatures) || !is_array($allinFeatures)) {
            $allinFeatures = $defaultAllinFeatures;
        }

        $defaultSatuanFeatures = [
            'Bebas memilih cabang lomba sesuai minat & bakat siswa',
            'Sudah termasuk kuota maksimal peserta sesuai ketentuan juknis',
            'E-Sertifikat resmi ber-barcode untuk seluruh peserta & guru pembimbing',
            'Fasilitas ID Card resmi dan akses Technical Meeting (Offline & Zoom)',
            'Konsultasi dan panduan teknis langsung via Helpdesk Panitia',
        ];
        $satuanRaw = Setting::get('package_satuan_features');
        $satuanFeatures = $satuanRaw ? json_decode($satuanRaw, true) : null;
        if (empty($satuanFeatures) || !is_array($satuanFeatures)) {
            $satuanFeatures = $defaultSatuanFeatures;
        }

        $packageSettings = [
            'allin_is_active' => Setting::get('package_allin_is_active', '1') === '1',
            'allin_auto_original_price' => $isAutoOriginalPrice,
            'allin_name' => Setting::get('package_allin_name', 'Paket All-In Komplit'),
            'allin_price' => $allinPrice,
            'allin_original_price' => $allinOriginalPrice,
            'allin_tag' => Setting::get('package_allin_tag', 'Paling Hemat • Rekomendasi'),
            'allin_desc' => Setting::get('package_allin_desc', 'Pilihan terbaik & paling hemat! Daftarkan kontingen sekolah Anda ke seluruh 8 cabang lomba sekaligus hanya dengan setengah harga.'),
            'allin_features' => $allinFeatures,
            'satuan_name' => Setting::get('package_satuan_name', 'Paket Fleksibel Satuan'),
            'satuan_desc' => Setting::get('package_satuan_desc', 'Pilihan tepat bagi madrasah/sekolah yang ingin mengirimkan delegasi spesifik pada cabang lomba tertentu.'),
            'satuan_features' => $satuanFeatures,
        ];

        $paymentSettings = [
            'bank_name' => Setting::get('payment_bank_name', 'Bank Syariah Indonesia (BSI)'),
            'account_number' => Setting::get('payment_account_number', ''),
            'account_holder' => Setting::get('payment_account_holder', 'Panitia Semarak Aliyah'),
            'confirmation_wa' => Setting::get('payment_confirmation_wa', '0838 5007 0767'),
            'instructions' => Setting::get('payment_instructions', '<p>Transfer sesuai nominal total tagihan ke rekening resmi panitia. Simpan tangkapan layar (screenshot) atau foto struk bukti pembayaran asli untuk diunggah pada form pendaftaran atau konfirmasikan ke WhatsApp panitia.</p>'),
            'cash_enabled' => Setting::get('payment_cash_enabled', '1') === '1',
            'bank_accounts' => Setting::getBankAccounts(),
        ];

        return \Inertia\Inertia::render('Admin/BiayaRegistrasi', compact('competitions', 'stats', 'packageSettings', 'paymentSettings'));
    }

    public function updateCompetitionFee(string|int $id, Request $request)
    {
        $comp = Competition::findOrFail($id);

        $validated = $request->validate([
            'fee' => 'required|numeric|min:0',
        ]);

        $comp->update(['fee' => (int)$validated['fee']]);

        if (Setting::get('package_allin_auto_original_price', '1') === '1') {
            Setting::set('package_allin_original_price', (string)((int)Competition::sum('fee')));
        }

        $formattedFee = number_format($comp->fee, 0, ',', '.');
        return redirect()->back()->with('success', "Biaya registrasi cabang \"{$comp->title}\" berhasil diubah menjadi Rp {$formattedFee}.");
    }

    public function batchUpdateCompetitionFee(Request $request)
    {
        if ($request->has('uniform_fee')) {
            $validated = $request->validate([
                'uniform_fee' => 'required|numeric|min:0',
            ]);
            $fee = (int)$validated['uniform_fee'];
            Competition::query()->update(['fee' => $fee]);

            if (Setting::get('package_allin_auto_original_price', '1') === '1') {
                Setting::set('package_allin_original_price', (string)((int)Competition::sum('fee')));
            }

            $formattedFee = number_format($fee, 0, ',', '.');
            return redirect()->back()->with('success', "Biaya seluruh cabang perlombaan berhasil diseragamkan menjadi Rp {$formattedFee}.");
        }

        if ($request->has('items') && is_array($request->input('items'))) {
            $items = $request->input('items');
            foreach ($items as $item) {
                if (isset($item['id']) && isset($item['fee'])) {
                    Competition::where('id', $item['id'])->update(['fee' => (int)$item['fee']]);
                }
            }

            if (Setting::get('package_allin_auto_original_price', '1') === '1') {
                Setting::set('package_allin_original_price', (string)((int)Competition::sum('fee')));
            }

            return redirect()->back()->with('success', 'Rincian biaya seluruh cabang lomba berhasil diperbarui.');
        }

        return redirect()->back()->with('error', 'Tidak ada data perubahan biaya yang dikirimkan.');
    }

    public function updatePackageSettings(Request $request)
    {
        $validated = $request->validate([
            'package_allin_is_active' => 'nullable',
            'package_allin_auto_original_price' => 'nullable',
            'package_allin_name' => 'required|string|max:150',
            'package_allin_price' => 'required|numeric|min:0',
            'package_allin_original_price' => 'nullable|numeric|min:0',
            'package_allin_tag' => 'nullable|string|max:100',
            'package_allin_desc' => 'nullable|string|max:1000',
            'package_allin_features' => 'nullable|array',
            'package_satuan_name' => 'required|string|max:150',
            'package_satuan_desc' => 'nullable|string|max:1000',
            'package_satuan_features' => 'nullable|array',
        ]);

        $isAutoOriginal = $request->boolean('package_allin_auto_original_price');
        Setting::set('package_allin_is_active', $request->boolean('package_allin_is_active') ? '1' : '0');
        Setting::set('package_allin_auto_original_price', $isAutoOriginal ? '1' : '0');
        Setting::set('package_allin_name', (string)$validated['package_allin_name']);
        Setting::set('package_allin_price', (string)$validated['package_allin_price']);

        $sumFees = (int)Competition::sum('fee');
        $finalOriginalPrice = $isAutoOriginal ? $sumFees : (int)($validated['package_allin_original_price'] ?? $sumFees);
        Setting::set('package_allin_original_price', (string)$finalOriginalPrice);

        Setting::set('package_allin_tag', (string)($validated['package_allin_tag'] ?? 'Paling Hemat • Rekomendasi'));
        Setting::set('package_allin_desc', (string)($validated['package_allin_desc'] ?? ''));

        if (isset($validated['package_allin_features']) && is_array($validated['package_allin_features'])) {
            $cleanFeatures = array_values(array_filter(array_map('trim', $validated['package_allin_features'])));
            Setting::set('package_allin_features', json_encode($cleanFeatures, JSON_UNESCAPED_UNICODE));
        }

        Setting::set('package_satuan_name', (string)$validated['package_satuan_name']);
        Setting::set('package_satuan_desc', (string)($validated['package_satuan_desc'] ?? ''));

        if (isset($validated['package_satuan_features']) && is_array($validated['package_satuan_features'])) {
            $cleanSatuanFeatures = array_values(array_filter(array_map('trim', $validated['package_satuan_features'])));
            Setting::set('package_satuan_features', json_encode($cleanSatuanFeatures, JSON_UNESCAPED_UNICODE));
        }

        return redirect()->back()->with('success', 'Konfigurasi paket pendaftaran All-In dan Satuan berhasil disimpan.');
    }

    public function updatePaymentSettings(Request $request)
    {
        $validated = $request->validate([
            'payment_bank_name' => 'nullable|string|max:150',
            'payment_account_number' => 'nullable|string|max:100',
            'payment_account_holder' => 'nullable|string|max:150',
            'payment_confirmation_wa' => 'nullable|string|max:30',
            'payment_instructions' => 'nullable|string',
            'payment_cash_enabled' => 'nullable',
            'bank_accounts' => 'nullable|array',
            'bank_accounts.*.id' => 'nullable|string|max:50',
            'bank_accounts.*.bank_name' => 'required|string|max:150',
            'bank_accounts.*.account_number' => 'required|string|max:100',
            'bank_accounts.*.account_holder' => 'required|string|max:150',
            'bank_accounts.*.notes' => 'nullable|string|max:255',
            'bank_accounts.*.is_primary' => 'nullable',
            'bank_accounts.*.is_active' => 'nullable',
        ]);

        if (!empty($validated['bank_accounts']) && is_array($validated['bank_accounts'])) {
            Setting::setBankAccounts($validated['bank_accounts']);
        } elseif (!empty($validated['payment_account_number'])) {
            Setting::set('payment_bank_name', (string)$validated['payment_bank_name']);
            Setting::set('payment_account_number', (string)$validated['payment_account_number']);
            Setting::set('payment_account_holder', (string)$validated['payment_account_holder']);
        }

        Setting::set('payment_confirmation_wa', (string)($validated['payment_confirmation_wa'] ?? ''));
        Setting::set('payment_instructions', (string)($validated['payment_instructions'] ?? ''));
        Setting::set('payment_cash_enabled', $request->boolean('payment_cash_enabled') ? '1' : '0');

        return redirect()->back()->with('success', 'Daftar nomor rekening dan konfigurasi pembayaran berhasil disimpan.');
    }

    // =========================================================================
    // FITUR KELOLA BUKU PANDUAN JUKNIS (JUKNIS UMUM & SPESIFIK CABANG LOMBA)
    // =========================================================================
    public function juknisIndex()
    {
        $generalJuknis = Setting::getGeneralJuknis();
        $competitions = Competition::orderBy('id', 'asc')->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'title' => $c->title,
                'category_label' => $c->category_label ?: 'Umum',
                'category' => $c->category,
                'juknis_file' => $c->juknis_file,
                'juknis_original_name' => $c->juknis_original_name,
                'juknis_size' => $c->juknis_size,
                'juknis_url' => $c->juknis_url,
                'juknis_filename' => $c->juknis_filename,
            ];
        });

        return \Inertia\Inertia::render('Admin/Juknis', [
            'generalJuknis' => $generalJuknis,
            'competitions' => $competitions,
        ]);
    }

    public function updateGeneralJuknis(Request $request)
    {
        $request->validate([
            'juknis_file' => 'nullable|file|mimes:pdf,docx,doc|max:30720',
            'external_url' => 'nullable|url|max:255',
        ]);

        if ($request->hasFile('juknis_file')) {
            $file = $request->file('juknis_file');
            $originalName = $file->getClientOriginalName();
            $ext = $file->getClientOriginalExtension();
            $safeName = 'JUKNIS_UMUM_SEMARAK_ALIYAH_' . date('Ymd_His') . '.' . $ext;
            $path = $file->storeAs('uploads/juknis', $safeName, 'public');
            $url = '/storage/' . $path;
            $bytes = $file->getSize();
            $formattedSize = number_format($bytes / (1024 * 1024), 2) . ' MB';

            Setting::setGeneralJuknis($url, $originalName, $formattedSize);
            return redirect()->back()->with('success', "File Juknis Umum [{$originalName}] berhasil diperbarui dan dipasang di seluruh tombol unduh web!");
        }

        if ($request->filled('external_url')) {
            $url = $request->input('external_url');
            Setting::setGeneralJuknis($url, 'Juknis Umum (Google Drive / Cloud)', 'Cloud Link');
            return redirect()->back()->with('success', 'Tautan Juknis Umum Cloud/Google Drive berhasil diperbarui!');
        }

        return redirect()->back()->with('error', 'Silakan pilih file dokumen PDF Juknis atau masukkan URL eksternal.');
    }

    public function resetGeneralJuknis()
    {
        Setting::setGeneralJuknis('/assets/JUKNIS-SEMARAK-2026.pdf', 'JUKNIS-SEMARAK-2026.pdf', '2.50 MB');
        return redirect()->back()->with('success', 'File Juknis Umum berhasil dikembalikan ke berkas master bawaan sistem.');
    }

    public function updateCompetitionJuknis(Request $request, string|int $id)
    {
        $comp = Competition::findOrFail($id);
        $request->validate([
            'juknis_file' => 'required|file|mimes:pdf,docx,doc|max:30720',
        ]);

        $file = $request->file('juknis_file');
        $originalName = $file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension();
        $safeName = 'JUKNIS_' . strtoupper(preg_replace('/[^a-zA-Z0-9]/', '_', $comp->id)) . '_' . date('Ymd_His') . '.' . $ext;
        $path = $file->storeAs('uploads/juknis', $safeName, 'public');
        $url = '/storage/' . $path;
        $bytes = $file->getSize();

        $comp->juknis_file = $url;
        $comp->juknis_original_name = $originalName;
        $comp->juknis_size = $bytes;

        // Merge into tm_settings for full redundancy
        $tm = $comp->tm_settings ?: [];
        $tm['juknis_file'] = $url;
        $tm['juknis_filename'] = $originalName;
        $comp->tm_settings = $tm;

        $comp->save();

        return redirect()->back()->with('success', "Berkas Juknis khusus cabang [{$comp->title}] berhasil diunggah!");
    }

    public function deleteCompetitionJuknis(string|int $id)
    {
        $comp = Competition::findOrFail($id);
        $comp->juknis_file = null;
        $comp->juknis_original_name = null;
        $comp->juknis_size = null;

        $tm = $comp->tm_settings ?: [];
        unset($tm['juknis_file'], $tm['juknis_filename']);
        $comp->tm_settings = $tm;

        $comp->save();

        return redirect()->back()->with('success', "Berkas Juknis khusus cabang [{$comp->title}] berhasil dihapus. Cabang ini kini menggunakan rujukan Juknis Umum.");
    }

    public static function getDefaultStatsItems(): array
    {
        return [
            [
                'icon' => 'ri-focus-2-line',
                'number' => '8 Cabang',
                'label' => 'Kompetisi & Olahraga',
            ],
            [
                'icon' => 'ri-building-line',
                'number' => '50+ Sekolah',
                'label' => 'SMP/MTs se-Jember',
            ],
            [
                'icon' => 'ri-hand-coin-line',
                'number' => 'Jutaan Rupiah',
                'label' => 'Uang Pembinaan & Hadiah',
            ],
            [
                'icon' => 'ri-award-line',
                'number' => '100% Sertifikat',
                'label' => 'Peserta & Guru Pendamping',
            ],
        ];
    }

    public static function getDefaultAboutPrizes(): array
    {
        return [
            [
                'icon' => 'ri-trophy-line',
                'title' => 'Piala Juara',
                'desc' => 'Trophy eksklusif dan bergengsi untuk Juara 1, Juara 2, dan Juara 3 pada setiap cabang kompetisi.',
            ],
            [
                'icon' => 'ri-money-dollar-circle-line',
                'title' => 'Uang Pembinaan',
                'desc' => 'Bantuan dana pembinaan jutaan rupiah untuk mendukung pengembangan bakat dan prestasi sang juara.',
            ],
            [
                'icon' => 'ri-file-shield-line',
                'title' => 'E-Piagam & Sertifikat',
                'desc' => 'Piagam penghargaan resmi ber-barcode untuk seluruh Peserta, Juara, dan Guru Pembimbing / Pendamping.',
            ],
            [
                'icon' => 'ri-graduation-cap-line',
                'title' => 'Beasiswa Pendidikan',
                'desc' => 'Jalur beasiswa penuh dan tabungan pendidikan masuk di MA Unggulan Wahid Hasyim Balung, Jember.',
            ],
        ];
    }

    // =========================================================================
    // FITUR KUSTOMISASI LANJUTAN WEB PUBLIK
    // =========================================================================
    public function kustomisasiIndex(Request $request)
    {
        $rawStats = Setting::get('stats_items', null);
        $statsItems = $rawStats ? json_decode($rawStats, true) : null;
        if (empty($statsItems) || !is_array($statsItems)) {
            $statsItems = self::getDefaultStatsItems();
        }

        $rawAbout = Setting::get('about_items', null);
        $aboutItems = $rawAbout ? json_decode($rawAbout, true) : null;
        if (empty($aboutItems) || !is_array($aboutItems)) {
            $aboutItems = self::getDefaultAboutPrizes();
        }

        $settings = [
            // 1. Banner Pengumuman Mengambang (Floating Announcement Ribbon)
            'announcement_banner_active' => Setting::get('announcement_banner_active', '1') === '1',
            'announcement_banner_text' => Setting::get('announcement_banner_text', '📢 Pendaftaran Semarak Aliyah XIV 2026 Resmi Dibuka! Raih Piala Bergilir, Medali Kejuaraan, & Beasiswa Pendidikan.'),
            'announcement_banner_type' => Setting::get('announcement_banner_type', 'gold'),
            'announcement_banner_btn_text' => Setting::get('announcement_banner_btn_text', 'Daftar Kontingen Sekarang'),
            'announcement_banner_btn_url' => Setting::get('announcement_banner_btn_url', '/#biaya'),
            'announcement_banner_position' => Setting::get('announcement_banner_position', 'top'),
            'announcement_banner_dismissable' => Setting::get('announcement_banner_dismissable', '1') === '1',

            // 2. Pop-up Pengumuman Otomatis (Entrance Modal Popup)
            'announcement_popup_active' => Setting::get('announcement_popup_active', '0') === '1',
            'announcement_popup_frequency' => Setting::get('announcement_popup_frequency', 'once_per_session'),
            'announcement_popup_title' => Setting::get('announcement_popup_title', 'Pengumuman Penting: Technical Meeting & Petunjuk Teknis'),
            'announcement_popup_badge' => Setting::get('announcement_popup_badge', 'Informasi Resmi Panitia'),
            'announcement_popup_image' => Setting::get('announcement_popup_image', '/assets/img/pamflet.jpg'),
            'announcement_popup_content' => Setting::get('announcement_popup_content', 'Selamat datang di Portal Resmi Semarak Aliyah XIV 2026! Pastikan delegasi sekolah/madrasah Anda telah mengunduh Buku Panduan Juknis resmi dan melengkapi berkas pendaftaran sebelum kuota masing-masing cabang lomba terpenuhi.'),
            'announcement_popup_btn_text' => Setting::get('announcement_popup_btn_text', 'Unduh Juknis Lengkap PDF'),
            'announcement_popup_btn_url' => Setting::get('announcement_popup_btn_url', '/assets/doc/JUKNIS-SEMARAK-2026.pdf'),

            // 3. Tombol Mengambang WhatsApp & Helpdesk Cepat (Floating WA Support)
            'floating_wa_active' => Setting::get('floating_wa_active', '1') === '1',
            'floating_wa_number' => Setting::get('floating_wa_number', '0838 5007 0767'),
            'floating_wa_message' => Setting::get('floating_wa_message', 'Halo Panitia Semarak Aliyah XIV 2026, saya ingin bertanya seputar pendaftaran kontingen sekolah...'),
            'floating_wa_label' => Setting::get('floating_wa_label', 'Bantuan Panitia'),
            'floating_wa_position' => Setting::get('floating_wa_position', 'bottom-right'),
            'floating_wa_animation' => Setting::get('floating_wa_animation', 'pulse'),

            // 4. Countdown Timer & Target Acara
            'countdown_active' => Setting::get('countdown_active', '1') === '1',
            'countdown_title' => Setting::get('countdown_title', 'Batas Akhir Pendaftaran & TM Ditutup Dalam:'),
            'countdown_target_date' => Setting::get('countdown_target_date', '2026-10-24T23:59'),
            'countdown_ended_text' => Setting::get('countdown_ended_text', 'Pendaftaran Gelombang Utama Resmi Ditutup! Hubungi Panitia untuk Kuota Susulan.'),
            'countdown_show_seconds' => Setting::get('countdown_show_seconds', '1') === '1',

            // 5. Injeksi Kode Kustom (Custom CSS & Scripts)
            'custom_css' => Setting::get('custom_css', ''),
            'custom_head_script' => Setting::get('custom_head_script', ''),
            'custom_body_script' => Setting::get('custom_body_script', ''),

            // 6. Efek Visual & Interaksi Web
            'visual_particles_active' => Setting::get('visual_particles_active', '1') === '1',
            'visual_particles_type' => Setting::get('visual_particles_type', 'glitter'),
            'back_to_top_active' => Setting::get('back_to_top_active', '1') === '1',
            'preloader_active' => Setting::get('preloader_active', '1') === '1',
            'preloader_text' => Setting::get('preloader_text', 'Memuat keseruan Semarak Aliyah...'),

            // 7. Hero Section Customization
            'hero_badge_text' => Setting::get('hero_badge_text', 'Ajang Kompetisi Pelajar SMP/MTs Se-Kabupaten Jember'),
            'hero_badge_pulse' => Setting::get('hero_badge_pulse', '1') === '1',
            'hero_headline_prefix' => Setting::get('hero_headline_prefix', 'Selamat Datang di Laman Official'),
            'hero_headline_highlight' => Setting::get('hero_headline_highlight', ''),
            'hero_subheadline' => Setting::get('hero_subheadline', 'Panggung unjuk prestasi, bakat seni, kecerdasan sains, dan sportivitas pelajar SMP/MTs sederajat se-Kabupaten Jember. Raih Piala Juara, Bantuan Uang Pembinaan, serta Beasiswa Tabungan Pendidikan di MA Unggulan Wahid Hasyim Balung.'),
            'hero_cta1_text' => Setting::get('hero_cta1_text', 'Daftar Sekarang'),
            'hero_cta1_url' => Setting::get('hero_cta1_url', ''),
            'hero_cta1_active' => Setting::get('hero_cta1_active', '1') === '1',
            'hero_cta2_text' => Setting::get('hero_cta2_text', 'Unduh Juknis (PDF)'),
            'hero_cta2_url' => Setting::get('hero_cta2_url', ''),
            'hero_cta2_active' => Setting::get('hero_cta2_active', '1') === '1',
            'hero_cta3_text' => Setting::get('hero_cta3_text', 'Lihat Cabang Lomba'),
            'hero_cta3_url' => Setting::get('hero_cta3_url', '#kompetisi'),
            'hero_cta3_active' => Setting::get('hero_cta3_active', '1') === '1',
            'hero_poster_image' => Setting::get('hero_poster_image', '/assets/img/pamflet.jpg'),
            'hero_mascot_image' => Setting::get('hero_mascot_image', '/assets/img/kadita.png'),
            'hero_mascot_active' => Setting::get('hero_mascot_active', '1') === '1',
            'hero_float_badge_active' => Setting::get('hero_float_badge_active', '1') === '1',
            'hero_float_badge_icon' => Setting::get('hero_float_badge_icon', 'ri-trophy-line'),
            'hero_float_badge_title' => Setting::get('hero_float_badge_title', 'Hadiah Jutaan Rupiah'),
            'hero_float_badge_desc' => Setting::get('hero_float_badge_desc', '+ Beasiswa Pendidikan'),

            // 8. Stats Section Customization
            'stats_active' => Setting::get('stats_active', '1') === '1',
            'stats_items' => $statsItems,

            // 9. Tentang & Hadiah Section Customization
            'about_active' => Setting::get('about_active', '1') === '1',
            'about_tag' => Setting::get('about_tag', 'Apresiasi & Fasilitas'),
            'about_title' => Setting::get('about_title', 'Dapatkan Hadiah Bergengsi & Beasiswa Pendidikan'),
            'about_desc' => Setting::get('about_desc', 'Semarak Aliyah diselenggarakan oleh MA Unggulan Wahid Hasyim Balung sebagai bentuk apresiasi nyata bagi generasi muda yang berprestasi dan berkarakter.'),
            'about_items' => $aboutItems,
        ];

        return \Inertia\Inertia::render('Admin/KustomisasiLanjutan', compact('settings'));
    }

    public function updateKustomisasi(Request $request)
    {
        $validated = $request->validate([
            // Banner
            'announcement_banner_active' => 'nullable',
            'announcement_banner_text' => 'nullable|string|max:500',
            'announcement_banner_type' => 'required|string|in:gold,primary,success,danger,info',
            'announcement_banner_btn_text' => 'nullable|string|max:100',
            'announcement_banner_btn_url' => 'nullable|string|max:255',
            'announcement_banner_position' => 'required|string|in:top,bottom',
            'announcement_banner_dismissable' => 'nullable',

            // Popup
            'announcement_popup_active' => 'nullable',
            'announcement_popup_frequency' => 'required|string|in:once_per_session,always',
            'announcement_popup_title' => 'required|string|max:200',
            'announcement_popup_badge' => 'nullable|string|max:100',
            'announcement_popup_image' => 'nullable|string|max:255',
            'announcement_popup_content' => 'nullable|string|max:2000',
            'announcement_popup_btn_text' => 'nullable|string|max:100',
            'announcement_popup_btn_url' => 'nullable|string|max:255',

            // Floating WA
            'floating_wa_active' => 'nullable',
            'floating_wa_number' => 'required|string|max:50',
            'floating_wa_message' => 'nullable|string|max:500',
            'floating_wa_label' => 'nullable|string|max:50',
            'floating_wa_position' => 'required|string|in:bottom-right,bottom-left',
            'floating_wa_animation' => 'required|string|in:pulse,bounce,none',

            // Countdown
            'countdown_active' => 'nullable',
            'countdown_title' => 'nullable|string|max:200',
            'countdown_target_date' => 'nullable|string|max:50',
            'countdown_ended_text' => 'nullable|string|max:255',
            'countdown_show_seconds' => 'nullable',

            // Custom Code
            'custom_css' => 'nullable|string',
            'custom_head_script' => 'nullable|string',
            'custom_body_script' => 'nullable|string',

            // Visual
            'visual_particles_active' => 'nullable',
            'visual_particles_type' => 'required|string|in:glitter,confetti,snow',
            'back_to_top_active' => 'nullable',
            'preloader_active' => 'nullable',
            'preloader_text' => 'nullable|string|max:100',

            // Hero Section
            'hero_badge_text' => 'nullable|string|max:255',
            'hero_badge_pulse' => 'nullable',
            'hero_headline_prefix' => 'nullable|string|max:255',
            'hero_headline_highlight' => 'nullable|string|max:255',
            'hero_subheadline' => 'nullable|string|max:1000',
            'hero_cta1_text' => 'nullable|string|max:100',
            'hero_cta1_url' => 'nullable|string|max:255',
            'hero_cta1_active' => 'nullable',
            'hero_cta2_text' => 'nullable|string|max:100',
            'hero_cta2_url' => 'nullable|string|max:255',
            'hero_cta2_active' => 'nullable',
            'hero_cta3_text' => 'nullable|string|max:100',
            'hero_cta3_url' => 'nullable|string|max:255',
            'hero_cta3_active' => 'nullable',
            'hero_poster_image' => 'nullable|string|max:255',
            'hero_poster_file' => 'nullable|file|mimes:jpeg,png,jpg,webp,svg|max:10240',
            'hero_mascot_image' => 'nullable|string|max:255',
            'hero_mascot_file' => 'nullable|file|mimes:jpeg,png,jpg,webp,svg|max:10240',
            'hero_mascot_active' => 'nullable',
            'hero_float_badge_active' => 'nullable',
            'hero_float_badge_icon' => 'nullable|string|max:100',
            'hero_float_badge_title' => 'nullable|string|max:150',
            'hero_float_badge_desc' => 'nullable|string|max:150',

            // Stats Section
            'stats_active' => 'nullable',
            'stats_items' => 'nullable',

            // Tentang Section
            'about_active' => 'nullable',
            'about_tag' => 'nullable|string|max:100',
            'about_title' => 'nullable|string|max:255',
            'about_desc' => 'nullable|string|max:1000',
            'about_items' => 'nullable',
        ]);

        // Booleans
        Setting::set('announcement_banner_active', $request->boolean('announcement_banner_active') ? '1' : '0');
        Setting::set('announcement_banner_dismissable', $request->boolean('announcement_banner_dismissable') ? '1' : '0');
        Setting::set('announcement_popup_active', $request->boolean('announcement_popup_active') ? '1' : '0');
        Setting::set('floating_wa_active', $request->boolean('floating_wa_active') ? '1' : '0');
        Setting::set('countdown_active', $request->boolean('countdown_active') ? '1' : '0');
        Setting::set('countdown_show_seconds', $request->boolean('countdown_show_seconds') ? '1' : '0');
        Setting::set('visual_particles_active', $request->boolean('visual_particles_active') ? '1' : '0');
        Setting::set('back_to_top_active', $request->boolean('back_to_top_active') ? '1' : '0');
        Setting::set('preloader_active', $request->boolean('preloader_active') ? '1' : '0');

        // Hero Booleans
        Setting::set('hero_badge_pulse', $request->boolean('hero_badge_pulse') ? '1' : '0');
        Setting::set('hero_cta1_active', $request->boolean('hero_cta1_active') ? '1' : '0');
        Setting::set('hero_cta2_active', $request->boolean('hero_cta2_active') ? '1' : '0');
        Setting::set('hero_cta3_active', $request->boolean('hero_cta3_active') ? '1' : '0');
        Setting::set('hero_mascot_active', $request->boolean('hero_mascot_active') ? '1' : '0');
        Setting::set('hero_float_badge_active', $request->boolean('hero_float_badge_active') ? '1' : '0');

        // Section Toggles
        Setting::set('stats_active', $request->boolean('stats_active') ? '1' : '0');
        Setting::set('about_active', $request->boolean('about_active') ? '1' : '0');

        // Strings
        $stringKeys = [
            'announcement_banner_text', 'announcement_banner_type', 'announcement_banner_btn_text', 'announcement_banner_btn_url', 'announcement_banner_position',
            'announcement_popup_frequency', 'announcement_popup_title', 'announcement_popup_badge', 'announcement_popup_image', 'announcement_popup_content', 'announcement_popup_btn_text', 'announcement_popup_btn_url',
            'floating_wa_number', 'floating_wa_message', 'floating_wa_label', 'floating_wa_position', 'floating_wa_animation',
            'countdown_title', 'countdown_target_date', 'countdown_ended_text',
            'custom_css', 'custom_head_script', 'custom_body_script',
            'visual_particles_type', 'preloader_text',
            'hero_badge_text', 'hero_headline_prefix', 'hero_headline_highlight', 'hero_subheadline',
            'hero_cta1_text', 'hero_cta1_url', 'hero_cta2_text', 'hero_cta2_url', 'hero_cta3_text', 'hero_cta3_url',
            'hero_poster_image', 'hero_mascot_image',
            'hero_float_badge_icon', 'hero_float_badge_title', 'hero_float_badge_desc',
            'about_tag', 'about_title', 'about_desc',
        ];

        foreach ($stringKeys as $key) {
            Setting::set($key, (string)($validated[$key] ?? ''));
        }

        // Upload Berkas Poster Pamflet Hero
        if ($request->hasFile('hero_poster_file')) {
            $path = $request->file('hero_poster_file')->store('hero', 'public');
            Setting::set('hero_poster_image', '/storage/' . $path);
        }

        // Upload Berkas Maskot Karakter Hero
        if ($request->hasFile('hero_mascot_file')) {
            $path = $request->file('hero_mascot_file')->store('hero', 'public');
            Setting::set('hero_mascot_image', '/storage/' . $path);
        }

        // Stats Items JSON
        if ($request->has('stats_items')) {
            $statsData = is_array($request->stats_items) ? $request->stats_items : json_decode($request->stats_items, true);
            Setting::set('stats_items', json_encode(array_values($statsData ?? [])));
        }

        // About Items JSON
        if ($request->has('about_items')) {
            $aboutData = is_array($request->about_items) ? $request->about_items : json_decode($request->about_items, true);
            Setting::set('about_items', json_encode(array_values($aboutData ?? [])));
        }

        return redirect()->back()->with('success', 'Pengaturan Kustomisasi Lanjutan Web Publik berhasil disimpan!');
    }

    public function resetKustomisasi(Request $request)
    {
        $defaults = [
            'announcement_banner_active' => '1',
            'announcement_banner_text' => '📢 Pendaftaran Semarak Aliyah XIV 2026 Resmi Dibuka! Raih Piala Bergilir, Medali Kejuaraan, & Beasiswa Pendidikan.',
            'announcement_banner_type' => 'gold',
            'announcement_banner_btn_text' => 'Daftar Kontingen Sekarang',
            'announcement_banner_btn_url' => '/#biaya',
            'announcement_banner_position' => 'top',
            'announcement_banner_dismissable' => '1',
            'announcement_popup_active' => '0',
            'announcement_popup_frequency' => 'once_per_session',
            'announcement_popup_title' => 'Pengumuman Penting: Technical Meeting & Petunjuk Teknis',
            'announcement_popup_badge' => 'Informasi Resmi Panitia',
            'announcement_popup_image' => '/assets/img/pamflet.jpg',
            'announcement_popup_content' => 'Selamat datang di Portal Resmi Semarak Aliyah XIV 2026! Pastikan delegasi sekolah/madrasah Anda telah mengunduh Buku Panduan Juknis resmi dan melengkapi berkas pendaftaran sebelum kuota masing-masing cabang lomba terpenuhi.',
            'announcement_popup_btn_text' => 'Unduh Juknis Lengkap PDF',
            'announcement_popup_btn_url' => '/assets/doc/JUKNIS-SEMARAK-2026.pdf',
            'floating_wa_active' => '1',
            'floating_wa_number' => '0838 5007 0767',
            'floating_wa_message' => 'Halo Panitia Semarak Aliyah XIV 2026, saya ingin bertanya seputar pendaftaran kontingen sekolah...',
            'floating_wa_label' => 'Bantuan Panitia',
            'floating_wa_position' => 'bottom-right',
            'floating_wa_animation' => 'pulse',
            'countdown_active' => '1',
            'countdown_title' => 'Batas Akhir Pendaftaran & TM Ditutup Dalam:',
            'countdown_target_date' => '2026-10-24T23:59',
            'countdown_ended_text' => 'Pendaftaran Gelombang Utama Resmi Ditutup! Hubungi Panitia untuk Kuota Susulan.',
            'countdown_show_seconds' => '1',
            'custom_css' => '',
            'custom_head_script' => '',
            'custom_body_script' => '',
            'visual_particles_active' => '1',
            'visual_particles_type' => 'glitter',
            'back_to_top_active' => '1',
            'preloader_active' => '1',
            'preloader_text' => 'Memuat keseruan Semarak Aliyah...',

            // Hero Section Defaults
            'hero_badge_text' => 'Ajang Kompetisi Pelajar SMP/MTs Se-Kabupaten Jember',
            'hero_badge_pulse' => '1',
            'hero_headline_prefix' => 'Selamat Datang di Laman Official',
            'hero_headline_highlight' => '',
            'hero_subheadline' => 'Panggung unjuk prestasi, bakat seni, kecerdasan sains, dan sportivitas pelajar SMP/MTs sederajat se-Kabupaten Jember. Raih Piala Juara, Bantuan Uang Pembinaan, serta Beasiswa Tabungan Pendidikan di MA Unggulan Wahid Hasyim Balung.',
            'hero_cta1_text' => 'Daftar Sekarang',
            'hero_cta1_url' => '',
            'hero_cta1_active' => '1',
            'hero_cta2_text' => 'Unduh Juknis (PDF)',
            'hero_cta2_url' => '',
            'hero_cta2_active' => '1',
            'hero_cta3_text' => 'Lihat Cabang Lomba',
            'hero_cta3_url' => '#kompetisi',
            'hero_cta3_active' => '1',
            'hero_poster_image' => '/assets/img/pamflet.jpg',
            'hero_mascot_image' => '/assets/img/kadita.png',
            'hero_mascot_active' => '1',
            'hero_float_badge_active' => '1',
            'hero_float_badge_icon' => 'ri-trophy-line',
            'hero_float_badge_title' => 'Hadiah Jutaan Rupiah',
            'hero_float_badge_desc' => '+ Beasiswa Pendidikan',

            // Stats Section Defaults
            'stats_active' => '1',
            'stats_items' => json_encode(self::getDefaultStatsItems()),

            // Tentang Section Defaults
            'about_active' => '1',
            'about_tag' => 'Apresiasi & Fasilitas',
            'about_title' => 'Dapatkan Hadiah Bergengsi & Beasiswa Pendidikan',
            'about_desc' => 'Semarak Aliyah diselenggarakan oleh MA Unggulan Wahid Hasyim Balung sebagai bentuk apresiasi nyata bagi generasi muda yang berprestasi dan berkarakter.',
            'about_items' => json_encode(self::getDefaultAboutPrizes()),
        ];

        foreach ($defaults as $k => $v) {
            Setting::set($k, $v);
        }

        return redirect()->back()->with('success', 'Konfigurasi Kustomisasi Lanjutan berhasil dikembalikan ke standar awal.');
    }

    /**
     * Halaman Rekapitulasi Sekolah / Madrasah Terdaftar Beserta Cabang Lomba yang Diikuti
     */
    public function registeredSchoolsIndex(Request $request)
    {
        $allCompetitions = Competition::orderBy('id', 'asc')->get();
        $masterSchools = \App\Models\School::orderBy('name', 'asc')->get(['id', 'npsn', 'name', 'jenjang', 'kecamatan', 'alamat']);
        $districts = \App\Models\School::getKecamatanList();
        $packageAllinPrice = (int)Setting::get('package_allin_price', 200000);

        // Ambil semua pendaftaran dengan relasi competition
        $registrationsQuery = Registration::with('competition')->orderBy('id', 'desc');

        if ($request->filled('competition_id') && $request->competition_id !== 'all') {
            $compId = $request->competition_id;
            $schoolNamesWithComp = Registration::where('competition_id', $compId)
                ->pluck('school_name')
                ->filter()
                ->unique()
                ->toArray();
            $registrationsQuery->whereIn('school_name', $schoolNamesWithComp);
        }

        $allRegistrations = $registrationsQuery->get();

        // Grouping berdasarkan school_name
        $grouped = $allRegistrations->groupBy(function($item) {
            return trim($item->school_name ?: 'Tanpa Sekolah / Mandiri');
        });

        $schoolsData = [];
        $totalAllInSchools = 0;
        $totalUnitSchools = 0;
        $globalTotalStudents = 0;
        $globalTotalFee = 0;
        $globalVerifiedFee = 0;

        // Ambil lookup tabel School untuk mencocokkan NPSN & Kecamatan
        $masterLookup = [];
        foreach ($masterSchools as $ms) {
            $norm = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $ms->name));
            $masterLookup[$norm] = $ms;
        }

        foreach ($grouped as $schoolName => $regs) {
            $hasAllIn = $regs->contains(fn($r) => $r->package_type === 'Paket All-In');
            $packageType = $hasAllIn ? 'Paket All-In' : 'Paket Satuan';

            if ($hasAllIn) {
                $totalAllInSchools++;
            } else {
                $totalUnitSchools++;
            }

            // Info pembina / kontak
            $mentorName = $regs->pluck('mentor_name')->filter()->first() ?: '-';
            $mentorWa = $regs->pluck('mentor_wa')->filter()->first() ?: '-';

            // Lookup ke database master sekolah
            $normName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $schoolName));
            $matchedMaster = $masterLookup[$normName] ?? null;
            $npsn = $matchedMaster?->npsn ?? '-';
            $jenjang = $matchedMaster?->jenjang ?? (stripos($schoolName, 'mts') !== false ? 'MTs' : (stripos($schoolName, 'smp') !== false ? 'SMP' : '-'));
            $kecamatan = $matchedMaster?->kecamatan ?? '-';

            // Jika di pendaftaran ada field custom kecamatan
            if ($kecamatan === '-' || empty($kecamatan)) {
                foreach ($regs as $r) {
                    $cf = $r->custom_fields ?? [];
                    $k = $cf['school_district'] ?? $cf['kecamatan'] ?? $cf['kecamatan_sekolah'] ?? null;
                    if (!empty($k)) {
                        $kecamatan = $k;
                        break;
                    }
                }
            }

            // Breakdown Cabang Lomba yang Diikuti
            $competitionsFollowed = [];
            $schoolStudentCount = 0;
            $schoolTotalFee = 0;
            $schoolPaidFee = 0;
            $allVerified = true;
            $hasPending = false;

            foreach ($regs as $reg) {
                $schoolTotalFee += (int)$reg->total_fee;
                if ($reg->payment_status === 'Terverifikasi') {
                    $schoolPaidFee += (int)$reg->total_fee;
                } else {
                    $allVerified = false;
                }
                if ($reg->payment_status === 'Menunggu Verifikasi') {
                    $hasPending = true;
                }

                // Hitung delegasi siswa
                $studentsList = [];
                $cf = $reg->custom_fields ?? [];

                // 1. Cek dari field delegates manual
                if (!empty($cf['delegates']) && is_array($cf['delegates'])) {
                    $studentsList = $cf['delegates'];
                } else {
                    // 2. Cek dari form builder repeater
                    foreach ($cf as $k => $val) {
                        if (is_array($val) && count($val) > 0 && isset($val[0]) && (isset($val[0]['nama_lengkap']) || isset($val[0]['nama_siswa']) || isset($val[0]['name']))) {
                            foreach ($val as $row) {
                                $studentsList[] = [
                                    'name' => $row['nama_lengkap'] ?? $row['nama_siswa'] ?? $row['name'] ?? 'Siswa Delegasi',
                                    'nisn' => $row['nisn'] ?? '-',
                                    'gender' => $row['jenis_kelamin'] ?? $row['gender'] ?? '-',
                                    'grade' => $row['kelas'] ?? $row['tingkat'] ?? '-',
                                ];
                            }
                        }
                    }
                }

                // 3. Fallback parsing dari participants_names
                if (empty($studentsList) && !empty($reg->participants_names)) {
                    $pParts = preg_split('/[\r\n,;]+/', $reg->participants_names);
                    foreach ($pParts as $pName) {
                        $cleanP = trim(preg_replace('/^\d+[\.\)\-]\s*/', '', $pName));
                        if (!empty($cleanP)) {
                            $studentsList[] = [
                                'name' => $cleanP,
                                'nisn' => '-',
                                'gender' => '-',
                                'grade' => '-',
                            ];
                        }
                    }
                }

                $cCount = max(1, count($studentsList));
                $schoolStudentCount += $cCount;

                $competitionsFollowed[] = [
                    'registration_id' => $reg->id,
                    'reg_code' => $reg->reg_code,
                    'competition_id' => $reg->competition_id,
                    'competition_title' => $reg->competition_title,
                    'category_name' => $reg->competition?->category?->name ?? 'Umum',
                    'package_type' => $reg->package_type,
                    'total_fee' => (int)$reg->total_fee,
                    'payment_status' => $reg->payment_status,
                    'registration_status' => $reg->registration_status,
                    'checkin_status' => $reg->checkin_status,
                    'participants_count' => $cCount,
                    'students' => $studentsList,
                    'participants_names' => $reg->participants_names,
                    'custom_fields' => $reg->custom_fields ?? [],
                    'created_at' => $reg->created_at?->format('d/m/Y H:i'),
                ];
            }

            $globalTotalStudents += $schoolStudentCount;
            $globalTotalFee += $schoolTotalFee;
            $globalVerifiedFee += $schoolPaidFee;

            // Status ringkasan pembayaran sekolah
            $paymentSummary = 'Belum Lunas';
            if ($allVerified && count($regs) > 0) {
                $paymentSummary = 'Lunas';
            } elseif ($hasPending) {
                $paymentSummary = 'Menunggu Verifikasi';
            } elseif ($schoolPaidFee > 0) {
                $paymentSummary = 'Sebagian';
            }

            $schoolsData[] = [
                'school_name' => $schoolName,
                'npsn' => $npsn,
                'jenjang' => $jenjang,
                'kecamatan' => $kecamatan,
                'mentor_name' => $mentorName,
                'mentor_wa' => $mentorWa,
                'package_type' => $packageType,
                'has_all_in' => $hasAllIn,
                'total_competitions' => count($competitionsFollowed),
                'total_students' => $schoolStudentCount,
                'total_fee' => $schoolTotalFee,
                'paid_fee' => $schoolPaidFee,
                'payment_summary' => $paymentSummary,
                'competitions' => $competitionsFollowed,
                'registrations_count' => count($regs),
            ];
        }

        // Apply filtering Search, Package, and Payment Status
        if ($request->filled('search')) {
            $s = strtolower($request->search);
            $schoolsData = array_filter($schoolsData, function($item) use ($s) {
                return str_contains(strtolower($item['school_name']), $s)
                    || str_contains(strtolower($item['kecamatan']), $s)
                    || str_contains(strtolower($item['npsn']), $s)
                    || str_contains(strtolower($item['mentor_name']), $s);
            });
        }

        if ($request->filled('package') && $request->package !== 'all') {
            $schoolsData = array_filter($schoolsData, fn($item) => $item['package_type'] === $request->package);
        }

        if ($request->filled('payment_status') && $request->payment_status !== 'all') {
            $schoolsData = array_filter($schoolsData, fn($item) => $item['payment_summary'] === $request->payment_status);
        }

        // Re-index array
        $schoolsData = array_values($schoolsData);

        // Sorting: pendaftaran dengan lomba terbanyak di atas
        usort($schoolsData, fn($a, $b) => $b['total_competitions'] <=> $a['total_competitions']);

        // Daftar unik nama sekolah terdaftar saat ini (untuk sumber merge sekolah)
        $allRegisteredSchoolNames = Registration::select('school_name')
            ->whereNotNull('school_name')
            ->where('school_name', '!=', '')
            ->distinct()
            ->orderBy('school_name', 'asc')
            ->pluck('school_name')
            ->toArray();

        return \Inertia\Inertia::render('Admin/RegisteredSchools', [
            'schools' => $schoolsData,
            'competitions' => $allCompetitions,
            'master_schools' => $masterSchools,
            'districts' => $districts,
            'all_registered_school_names' => $allRegisteredSchoolNames,
            'package_allin_price' => $packageAllinPrice,
            'bank_accounts' => Setting::getBankAccounts(),
            'stats' => [
                'total_schools' => count($grouped),
                'total_all_in' => $totalAllInSchools,
                'total_unit' => $totalUnitSchools,
                'total_students' => $globalTotalStudents,
                'total_revenue' => $globalTotalFee,
                'total_verified_revenue' => $globalVerifiedFee,
            ],
            'filters' => $request->only(['search', 'package', 'payment_status', 'competition_id']),
        ]);
    }

    /**
     * Input Manual Data Pendaftaran dan Delegasi oleh Panitia (On The Spot di Meja Panitia)
     */
    public function storeManualRegistration(Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'jenjang' => 'nullable|string|in:SMP,MTs,Lainnya',
            'kecamatan' => 'nullable|string|max:100',
            'npsn' => 'nullable|string|max:50',
            'alamat' => 'nullable|string|max:500',
            'mentor_name' => 'required|string|max:255',
            'mentor_wa' => 'required|string|max:30',
            'mentor_email' => 'nullable|email|max:255',
            'team_name' => 'nullable|string|max:255',
            'package_type' => 'required|string|in:Paket Satuan,Paket All-In',
            'competition_id' => 'nullable|exists:competitions,id',
            'competition_ids' => 'nullable|array',
            'competition_ids.*' => 'exists:competitions,id',
            'custom_fee' => 'nullable|numeric|min:0',
            'package_price' => 'nullable|numeric|min:0',
            'students' => 'nullable|array',
            'delegates_by_comp' => 'nullable|array',
            'save_mode' => 'nullable|string|in:draft,final',
            'participants_text' => 'nullable|string',
            'custom_fields' => 'nullable|array',
            'payment_status' => 'required|string|in:Terverifikasi,Menunggu Verifikasi,Belum Bayar',
            'payment_method' => 'required|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'target_action' => 'nullable|string|in:manage_delegates,save_only',
        ]);

        $cleanWa = Setting::formatWaNumber($validated['mentor_wa']);
        $schoolName = trim($validated['school_name']);
        $saveMode = $validated['save_mode'] ?? ($validated['target_action'] === 'save_only' ? 'draft' : 'final');

        // Cari atau buat akun di tabel registrants untuk pembina ini agar tercatat di sistem
        $user = \App\Models\Registrant::where('phone', $cleanWa)->first();
        if (!$user) {
            $email = !empty($validated['mentor_email']) 
                ? $validated['mentor_email'] 
                : 'kontingen_' . preg_replace('/\D/', '', $cleanWa) . '@semarak.id';
            
            if (\App\Models\Registrant::where('email', $email)->exists()) {
                $email = 'kontingen_' . preg_replace('/\D/', '', $cleanWa) . '_' . rand(100, 999) . '@semarak.id';
            }

            $user = \App\Models\Registrant::create([
                'name' => $validated['mentor_name'],
                'phone' => $cleanWa,
                'email' => $email,
                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16)),
                'registrant_role' => 'Pendamping',
                'school_name' => $schoolName,
                'gender' => 'Laki-laki',
                'is_active' => true,
            ]);
        }

        // Susun delegasi siswa untuk single/default
        $students = $validated['students'] ?? [];
        $namesList = [];
        $cleanedStudents = [];
        if (!empty($students) && is_array($students)) {
            foreach ($students as $idx => $st) {
                $stName = trim($st['name'] ?? '');
                if (!empty($stName)) {
                    $nisnText = !empty($st['nisn']) && $st['nisn'] !== '-' ? " (NISN: {$st['nisn']})" : "";
                    $customIdText = !empty($st['custom_id']) && $st['custom_id'] !== '-' ? " [ID: {$st['custom_id']}]" : "";
                    $roleText = !empty($st['is_captain']) ? (" [Kapten" . (!empty($st['captain_wa']) ? " - WA: {$st['captain_wa']}" : "") . "]") : "";
                    $namesList[] = ($idx + 1) . ". " . $stName . $roleText . $customIdText . $nisnText;
                    $cleanedStudents[] = [
                        'name' => $stName,
                        'gender' => $st['gender'] ?? 'Laki-laki',
                        'grade' => $st['grade'] ?? '7',
                        'nisn' => $st['nisn'] ?? '',
                        'birth_date' => $st['birth_date'] ?? '',
                        'custom_id' => $st['custom_id'] ?? '',
                        'wa_number' => $st['wa_number'] ?? '',
                        'is_captain' => !empty($st['is_captain']),
                        'captain_wa' => $st['captain_wa'] ?? '',
                        'custom_columns' => isset($st['custom_columns']) && is_array($st['custom_columns']) ? $st['custom_columns'] : [],
                    ];
                }
            }
        }

        if (empty($namesList) && !empty($validated['participants_text'])) {
            $rawLines = preg_split('/[\r\n,]+/', $validated['participants_text']);
            foreach ($rawLines as $i => $line) {
                $c = trim($line);
                if (!empty($c)) {
                    $namesList[] = ($i + 1) . ". " . $c;
                    $cleanedStudents[] = [
                        'name' => $c,
                        'nisn' => '-',
                        'gender' => '-',
                        'grade' => '-',
                    ];
                }
            }
        }

        $createdRegistrations = [];

        $customFieldsBase = array_merge($validated['custom_fields'] ?? [], [
            'kecamatan' => $validated['kecamatan'] ?? null,
            'school_district' => $validated['kecamatan'] ?? null,
            'jenjang' => $validated['jenjang'] ?? null,
            'npsn' => $validated['npsn'] ?? null,
            'school_npsn' => $validated['npsn'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
            'school_address' => $validated['alamat'] ?? null,
            'team_name' => $validated['team_name'] ?? null,
            'nama_tim' => $validated['team_name'] ?? null,
            'is_draft_delegates' => ($saveMode === 'draft'),
            'registered_by_admin' => Auth::guard('web')->user()?->name ?? 'Panitia Admin',
            'registered_at_desk' => now()->toIso8601String(),
        ]);

        if ($validated['package_type'] === 'Paket Satuan') {
            if (empty($validated['competition_id'])) {
                return redirect()->back()->with('error', 'Cabang lomba wajib dipilih untuk pendaftaran Paket Satuan.');
            }

            $competition = Competition::findOrFail($validated['competition_id']);
            $fee = isset($validated['custom_fee']) && $validated['custom_fee'] !== null 
                ? (int)$validated['custom_fee'] 
                : (int)($competition->price ?? $competition->fee ?? 0);

            $regCode = 'SA26-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

            if ($saveMode === 'draft') {
                $participantsString = 'Pengisian Ditunda (Diisi Nanti)';
                $regStatus = 'Menunggu Pengisian Peserta';
                $notesPrefix = '[Pengisian Delegasi Ditunda / Diisi Nanti] ';
            } else {
                $participantsString = !empty($namesList) ? implode("\n", $namesList) : 'Belum Ada Delegasi (Perlu Input)';
                $regStatus = ($validated['payment_status'] === 'Terverifikasi') ? 'Terverifikasi' : 'Menunggu Verifikasi';
                $notesPrefix = '';
            }

            $compCustomFields = array_merge($customFieldsBase, [
                'delegates' => $cleanedStudents,
                'biodata_peserta' => $cleanedStudents,
            ]);

            $reg = Registration::create([
                'registrant_id' => $user->id,
                'user_id' => $user->id,
                'reg_code' => $regCode,
                'barcode' => $regCode,
                'school_name' => $schoolName,
                'mentor_name' => $validated['mentor_name'],
                'mentor_wa' => $cleanWa,
                'package_type' => 'Paket Satuan',
                'competition_id' => $competition->id,
                'competition_title' => $competition->title,
                'participants_names' => $participantsString,
                'total_fee' => $fee,
                'registration_status' => $regStatus,
                'payment_status' => $validated['payment_status'],
                'payment_method' => $validated['payment_method'],
                'notes' => $notesPrefix . ($validated['notes'] ?? 'Pendaftaran di tempat (Meja Panitia)'),
                'custom_fields' => $compCustomFields,
            ]);

            $createdRegistrations[] = $reg;

        } else {
            // Paket All-In
            $compIds = $validated['competition_ids'] ?? [];
            if (empty($compIds)) {
                return redirect()->back()->with('error', 'Pilih minimal 1 cabang lomba untuk Paket Komplit (All-In).');
            }

            $competitions = Competition::whereIn('id', $compIds)->orderBy('id', 'asc')->get();
            $defaultAllinPrice = (int)Setting::get('package_allin_price', 200000);
            $packagePrice = isset($validated['package_price']) && $validated['package_price'] !== null 
                ? (int)$validated['package_price'] 
                : $defaultAllinPrice;

            $delegatesByComp = $validated['delegates_by_comp'] ?? [];

            $isFirst = true;
            foreach ($competitions as $comp) {
                $regCode = 'SA26-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
                $fee = $isFirst ? $packagePrice : 0; // Hanya master cabang pertama yang memegang tagihan biaya all-in

                $compStudents = [];
                $compNames = [];

                if ($saveMode === 'draft') {
                    $compParticipantsString = 'Pengisian Ditunda (Diisi Nanti)';
                    $compRegStatus = 'Menunggu Pengisian Peserta';
                    $compNotesPrefix = '[Pengisian Delegasi Ditunda / Diisi Nanti] ';

                    // Jika ada sebagian delegates yang sudah sempat diinput
                    $rawCompSt = $delegatesByComp[$comp->id] ?? ($isFirst ? $cleanedStudents : []);
                    if (!empty($rawCompSt) && is_array($rawCompSt)) {
                        foreach ($rawCompSt as $idx => $st) {
                            $stName = trim($st['name'] ?? '');
                            if (!empty($stName)) {
                                $compStudents[] = [
                                    'name' => $stName,
                                    'gender' => $st['gender'] ?? 'Laki-laki',
                                    'grade' => $st['grade'] ?? '7',
                                    'nisn' => $st['nisn'] ?? '',
                                    'birth_date' => $st['birth_date'] ?? '',
                                    'custom_id' => $st['custom_id'] ?? '',
                                    'wa_number' => $st['wa_number'] ?? '',
                                    'is_captain' => !empty($st['is_captain']),
                                    'captain_wa' => $st['captain_wa'] ?? '',
                                    'custom_columns' => isset($st['custom_columns']) && is_array($st['custom_columns']) ? $st['custom_columns'] : [],
                                ];
                            }
                        }
                    }
                } else {
                    // Mode Final: ambil dari delegates_by_comp jika ada, atau default $cleanedStudents untuk cabang pertama
                    $rawCompSt = $delegatesByComp[$comp->id] ?? ($isFirst ? $cleanedStudents : []);
                    if (!empty($rawCompSt) && is_array($rawCompSt)) {
                        foreach ($rawCompSt as $idx => $st) {
                            $stName = trim($st['name'] ?? '');
                            if (!empty($stName)) {
                                $nisnText = !empty($st['nisn']) && $st['nisn'] !== '-' ? " (NISN: {$st['nisn']})" : "";
                                $customIdText = !empty($st['custom_id']) && $st['custom_id'] !== '-' ? " [ID: {$st['custom_id']}]" : "";
                                $roleText = !empty($st['is_captain']) ? (" [Kapten" . (!empty($st['captain_wa']) ? " - WA: {$st['captain_wa']}" : "") . "]") : "";
                                $compNames[] = ($idx + 1) . ". " . $stName . $roleText . $customIdText . $nisnText;
                                $compStudents[] = [
                                    'name' => $stName,
                                    'gender' => $st['gender'] ?? 'Laki-laki',
                                    'grade' => $st['grade'] ?? '7',
                                    'nisn' => $st['nisn'] ?? '',
                                    'birth_date' => $st['birth_date'] ?? '',
                                    'custom_id' => $st['custom_id'] ?? '',
                                    'wa_number' => $st['wa_number'] ?? '',
                                    'is_captain' => !empty($st['is_captain']),
                                    'captain_wa' => $st['captain_wa'] ?? '',
                                    'custom_columns' => isset($st['custom_columns']) && is_array($st['custom_columns']) ? $st['custom_columns'] : [],
                                ];
                            }
                        }
                    }

                    if (!empty($compNames)) {
                        $compParticipantsString = implode("\n", $compNames);
                        $compRegStatus = ($validated['payment_status'] === 'Terverifikasi') ? 'Terverifikasi' : 'Menunggu Verifikasi';
                        $compNotesPrefix = '';
                    } else {
                        // Jika cabang ini belum diisi peserta pada saat simpan final paket all-in
                        $compParticipantsString = 'Pengisian Ditunda (Diisi Nanti)';
                        $compRegStatus = 'Menunggu Pengisian Peserta';
                        $compNotesPrefix = '[Delegasi Belum Diisi] ';
                    }
                }

                $compCustomFields = array_merge($customFieldsBase, [
                    'delegates' => $compStudents,
                    'biodata_peserta' => $compStudents,
                ]);

                $reg = Registration::create([
                    'registrant_id' => $user->id,
                    'user_id' => $user->id,
                    'reg_code' => $regCode,
                    'barcode' => $regCode,
                    'school_name' => $schoolName,
                    'mentor_name' => $validated['mentor_name'],
                    'mentor_wa' => $cleanWa,
                    'package_type' => 'Paket All-In',
                    'competition_id' => $comp->id,
                    'competition_title' => $comp->title,
                    'participants_names' => $compParticipantsString,
                    'total_fee' => $fee,
                    'registration_status' => $compRegStatus,
                    'payment_status' => $validated['payment_status'],
                    'payment_method' => $validated['payment_method'],
                    'notes' => $compNotesPrefix . ($validated['notes'] ?? 'Pendaftaran di tempat Paket Komplit (All-In)'),
                    'custom_fields' => $compCustomFields,
                ]);

                $createdRegistrations[] = $reg;
                $isFirst = false;
            }
        }

        // Auto-sinkronisasi ke tabel master School
        try {
            \App\Models\School::firstOrCreateFromRegistration(
                $schoolName, 
                $cleanWa, 
                $validated['kecamatan'] ?? null,
                $validated['npsn'] ?? null,
                $validated['alamat'] ?? null
            );
        } catch (\Throwable $e) {}

        $count = count($createdRegistrations);
        $firstReg = $createdRegistrations[0] ?? null;
        $targetAction = $validated['target_action'] ?? ($saveMode === 'draft' ? 'save_only' : 'manage_delegates');

        $statusFeedback = ($saveMode === 'draft')
            ? "sebagai DRAF / Pengisian Ditunda ({$count} cabang lomba). Status belum final dan belum bisa diverifikasi sampai data peserta dilengkapi."
            : "secara FINAL ({$count} cabang lomba terdata)!";

        return redirect()->back()->with([
            'success' => "Pendaftaran kontingen {$schoolName} berhasil disimpan {$statusFeedback}",
            'save_mode' => $saveMode,
            'newly_registered' => $firstReg ? [
                'registration_id' => $firstReg->id,
                'reg_code' => $firstReg->reg_code,
                'competition_id' => $firstReg->competition_id,
                'competition_title' => $firstReg->competition_title,
                'school_name' => $firstReg->school_name,
                'students' => $firstReg->custom_fields['delegates'] ?? [],
                'custom_fields' => $firstReg->custom_fields ?? [],
            ] : null,
            'newly_registered_all' => array_map(fn($r) => [
                'registration_id' => $r->id,
                'reg_code' => $r->reg_code,
                'competition_id' => $r->competition_id,
                'competition_title' => $r->competition_title,
                'school_name' => $r->school_name,
                'package_type' => $r->package_type,
                'total_fee' => (int)$r->total_fee,
                'payment_status' => $r->payment_status,
                'registration_status' => $r->registration_status,
                'students' => $r->custom_fields['delegates'] ?? [],
                'custom_fields' => $r->custom_fields ?? [],
            ], $createdRegistrations),
            'target_action' => $targetAction,
        ]);
    }

    /**
     * Update data peserta delegasi & custom fields khusus cabang lomba (sesuai aturan Form Builder)
     */
    public function updateRegistrationParticipants(Request $request, string|int $id)
    {
        $registration = Registration::with('competition')->findOrFail($id);

        $validated = $request->validate([
            'delegates' => 'required|array|min:1',
            'delegates.*.name' => 'required|string|max:255',
            'delegates.*.gender' => 'nullable|string|max:50',
            'delegates.*.grade' => 'nullable|string|max:50',
            'delegates.*.nisn' => 'nullable|string|max:50',
            'delegates.*.birth_date' => 'nullable|string|max:50',
            'delegates.*.custom_id' => 'nullable|string|max:100',
            'delegates.*.wa_number' => 'nullable|string|max:50',
            'delegates.*.is_captain' => 'nullable|boolean',
            'delegates.*.captain_wa' => 'nullable|string|max:50',
            'delegates.*.custom_columns' => 'nullable|array',
            'additional_fields' => 'nullable|array',
        ]);

        $namesList = [];
        $customFields = is_array($registration->custom_fields) ? $registration->custom_fields : [];
        $existingDelegates = $customFields['delegates'] ?? ($customFields['biodata_peserta'] ?? []);

        $cleanedDelegates = [];
        foreach ($validated['delegates'] as $idx => $st) {
            $name = trim($st['name'] ?? '');
            if (empty($name)) continue;

            $nisn = !empty($st['nisn']) && $st['nisn'] !== '-' ? " (NISN: {$st['nisn']})" : "";
            $role = !empty($st['is_captain']) ? " [Kapten]" : "";
            $namesList[] = ($idx + 1) . ". " . $name . $role . $nisn;

            $cleanedDelegates[] = [
                'name' => $name,
                'gender' => $st['gender'] ?? 'Laki-laki',
                'grade' => $st['grade'] ?? '7',
                'nisn' => $st['nisn'] ?? '',
                'birth_date' => $st['birth_date'] ?? '',
                'custom_id' => $st['custom_id'] ?? '',
                'wa_number' => $st['wa_number'] ?? '',
                'is_captain' => !empty($st['is_captain']),
                'captain_wa' => $st['captain_wa'] ?? '',
                'photo_formal' => $st['photo_formal'] ?? ($st['photo'] ?? ($existingDelegates[$idx]['photo_formal'] ?? ($existingDelegates[$idx]['photo'] ?? null))),
                'photo' => $st['photo_formal'] ?? ($st['photo'] ?? ($existingDelegates[$idx]['photo'] ?? ($existingDelegates[$idx]['photo_formal'] ?? null))),
                'photo_freestyle' => $st['photo_freestyle'] ?? ($existingDelegates[$idx]['photo_freestyle'] ?? null),
                'custom_columns' => $st['custom_columns'] ?? [],
            ];
        }

        if (empty($namesList)) {
            return redirect()->back()->with('error', 'Minimal harus mengisi 1 nama peserta delegasi yang valid.');
        }

        $customFields['delegates'] = $cleanedDelegates;
        if (isset($customFields['biodata_peserta'])) {
            $customFields['biodata_peserta'] = $cleanedDelegates;
        }

        if (!empty($validated['additional_fields'])) {
            foreach ($validated['additional_fields'] as $k => $v) {
                $customFields[$k] = $v;
            }
        }

        $customFields['participants_updated_by'] = Auth::guard('web')->user()?->name ?? 'Panitia Admin';
        $customFields['participants_updated_at'] = now()->toIso8601String();

        $updatePayload = [
            'participants_names' => implode("\n", $namesList),
            'custom_fields' => $customFields,
        ];

        // Jika pendaftaran ini sebelumnya berstatus "Menunggu Pengisian Peserta" (ditunda / draf),
        // maka setelah delegasi diisi, otomatis promosikan statusnya sehingga dapat diverifikasi
        if ($registration->registration_status === 'Menunggu Pengisian Peserta') {
            $updatePayload['registration_status'] = ($registration->payment_status === 'Terverifikasi')
                ? 'Terverifikasi'
                : 'Menunggu Verifikasi';
        }

        $registration->update($updatePayload);

        return redirect()->back()->with('success', "Data peserta delegasi {$registration->competition_title} ({$registration->reg_code}) berhasil diperbarui sesuai aturan form builder!");
    }

    /**
     * Fitur Merge Sekolah yang Sama (Menyatukan variasi penulisan nama sekolah pendaftar)
     */
    public function mergeSchools(Request $request)
    {
        $validated = $request->validate([
            'target_school_name' => 'required|string|max:255',
            'source_school_names' => 'required|array|min:1',
            'source_school_names.*' => 'string|max:255',
        ]);

        $targetSchool = trim($validated['target_school_name']);
        $sourceSchools = array_values(array_filter(array_map('trim', $validated['source_school_names']), fn($s) => !empty($s) && $s !== $targetSchool));

        if (empty($sourceSchools)) {
            return redirect()->back()->with('error', 'Pilih minimal 1 nama sekolah sumber yang berbeda dengan sekolah target.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // Hitung pendaftaran yang akan di-update
            $affectedCount = Registration::whereIn('school_name', $sourceSchools)->count();

            if ($affectedCount === 0) {
                \Illuminate\Support\Facades\DB::rollBack();
                return redirect()->back()->with('error', 'Tidak ditemukan pendaftaran yang cocok dengan nama sekolah sumber.');
            }

            // Update nama sekolah di registrations
            Registration::whereIn('school_name', $sourceSchools)->update([
                'school_name' => $targetSchool
            ]);

            // Sinkronisasi Paket All-In jika kontingen sekarang memiliki pendaftaran All-In
            $targetAllInRegs = Registration::where('school_name', $targetSchool)
                ->where('package_type', 'Paket All-In')
                ->orderBy('id', 'asc')
                ->get();

            if ($targetAllInRegs->count() > 0) {
                // Pastikan hanya 1 pendaftaran induk yang memegang total_fee > 0
                $defaultAllinPrice = (int)Setting::get('package_allin_price', 200000);
                $firstMaster = $targetAllInRegs->firstWhere('total_fee', '>', 0) ?: $targetAllInRegs->first();
                $masterFee = $firstMaster->total_fee > 0 ? $firstMaster->total_fee : $defaultAllinPrice;

                // Cek apakah ada pendaftaran yang sudah terverifikasi/lunas
                $hasVerified = $targetAllInRegs->contains(fn($r) => $r->payment_status === 'Terverifikasi');

                foreach ($targetAllInRegs as $r) {
                    if ($r->id === $firstMaster->id) {
                        $r->total_fee = $masterFee;
                    } else {
                        $r->total_fee = 0;
                    }
                    if ($hasVerified) {
                        $r->payment_status = 'Terverifikasi';
                        $r->registration_status = 'Terverifikasi';
                    }
                    $r->save();
                }
            }

            // Update juga di tabel School jika perlu
            try {
                \App\Models\School::firstOrCreateFromRegistration($targetSchool);
            } catch (\Throwable $e) {}

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->back()->with('success', "Berhasil menggabungkan {$affectedCount} pendaftaran dari " . count($sourceSchools) . " variasi nama sekolah ke [{$targetSchool}]!");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menggabungkan sekolah: ' . $e->getMessage());
        }
    }

    /**
     * Hapus satu data delegasi pendaftar masuk (Super Admin / Izin registrants.delete)
     */
    public function destroyRegistration(string|int $id, Request $request)
    {
        /** @var User|null $authUser */
        $authUser = Auth::guard('web')->user();
        if (!$authUser || (!$authUser->isSuperAdmin() && !$authUser->hasPermission('registrants.delete'))) {
            abort(403, 'Akses Ditolak: Hanya Super Admin atau panitia berwenang yang dapat menghapus data delegasi pendaftar.');
        }

        $reg = Registration::findOrFail($id);
        $regCode = $reg->reg_code;
        $schoolName = $reg->school_name;
        $compTitle = $reg->competition_title;

        // Bersihkan berkas fisik yang tersimpan di storage jika ada
        if (!empty($reg->file_documents) && is_array($reg->file_documents)) {
            foreach ($reg->file_documents as $doc) {
                $path = is_array($doc) ? ($doc['path'] ?? null) : $doc;
                if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                }
            }
        }
        if (!empty($reg->custom_fields) && is_array($reg->custom_fields)) {
            foreach ($reg->custom_fields as $val) {
                if (is_string($val) && (str_starts_with($val, 'uploads/') || str_starts_with($val, 'payment_proofs/')) && \Illuminate\Support\Facades\Storage::disk('public')->exists($val)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($val);
                }
            }
        }

        // Catat Log Audit Aktivitas
        \App\Models\ActivityLog::record(
            'registration_delete',
            'Hapus Delegasi Pendaftar Masuk',
            "Super Admin {$authUser->name} menghapus data delegasi {$schoolName} pada cabang {$compTitle} (Kode: {$regCode}).",
            [
                'user' => $authUser,
                'highlight' => $schoolName,
                'activity_type' => 'delete',
                'properties' => [
                    'reg_code' => $regCode,
                    'school_name' => $schoolName,
                    'competition_title' => $compTitle,
                ]
            ]
        );

        $reg->delete();

        if (!$request->header('X-Inertia') && ($request->ajax() || $request->wantsJson())) {
            return response()->json([
                'status' => 'success',
                'message' => "Data delegasi {$compTitle} ({$regCode}) dari {$schoolName} berhasil dihapus permanen!"
            ]);
        }

        // Jika dipanggil dari halaman detail pendaftar yang bersangkutan, alihkan ke index pendaftar
        $referer = $request->header('referer', '');
        if (str_contains($referer, '/admin/pendaftar/' . $id)) {
            return redirect()->route('admin.pendaftar')->with('success', "Data delegasi {$compTitle} ({$regCode}) dari {$schoolName} berhasil dihapus permanen!");
        }

        return redirect()->back()->with('success', "Data delegasi {$compTitle} ({$regCode}) dari {$schoolName} berhasil dihapus permanen!");
    }

    /**
     * Hapus massal beberapa data delegasi pendaftar masuk sekaligus (Super Admin)
     */
    public function bulkDestroyRegistrations(Request $request)
    {
        /** @var User|null $authUser */
        $authUser = Auth::guard('web')->user();
        if (!$authUser || (!$authUser->isSuperAdmin() && !$authUser->hasPermission('registrants.delete'))) {
            abort(403, 'Akses Ditolak: Hanya Super Admin yang berwenang menghapus delegasi pendaftar secara massal.');
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $regs = Registration::whereIn('id', $validated['ids'])->get();
        $count = $regs->count();

        foreach ($regs as $reg) {
            if (!empty($reg->file_documents) && is_array($reg->file_documents)) {
                foreach ($reg->file_documents as $doc) {
                    $path = is_array($doc) ? ($doc['path'] ?? null) : $doc;
                    if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                    }
                }
            }
            $reg->delete();
        }

        \App\Models\ActivityLog::record(
            'registration_bulk_delete',
            'Hapus Massal Delegasi Pendaftar',
            "Super Admin {$authUser->name} menghapus massal {$count} delegasi pendaftar.",
            [
                'user' => $authUser,
                'activity_type' => 'delete',
                'properties' => ['deleted_count' => $count, 'ids' => $validated['ids']]
            ]
        );

        return redirect()->back()->with('success', "Sebanyak {$count} data delegasi pendaftar berhasil dihapus permanen!");
    }

    /**
     * Hapus seluruh data sekolah terdaftar beserta seluruh pendaftaran lombanya (Super Admin / Izin registered_schools.delete)
     */
    public function destroyRegisteredSchool(Request $request)
    {
        /** @var User|null $authUser */
        $authUser = Auth::guard('web')->user();
        if (!$authUser || (!$authUser->isSuperAdmin() && !$authUser->hasPermission('registered_schools.delete') && !$authUser->hasPermission('schools.manage'))) {
            abort(403, 'Akses Ditolak: Hanya Super Admin yang berwenang menghapus data sekolah terdaftar.');
        }

        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
        ]);

        $schoolName = trim($validated['school_name']);
        $registrations = Registration::where('school_name', $schoolName)->get();
        if ($registrations->isEmpty()) {
            $registrations = Registration::whereRaw('LOWER(TRIM(school_name)) = ?', [strtolower($schoolName)])->get();
        }

        if ($registrations->isEmpty()) {
            return redirect()->back()->with('error', "Data pendaftaran untuk sekolah '{$schoolName}' tidak ditemukan.");
        }

        $count = $registrations->count();
        foreach ($registrations as $reg) {
            if (!empty($reg->file_documents) && is_array($reg->file_documents)) {
                foreach ($reg->file_documents as $doc) {
                    $path = is_array($doc) ? ($doc['path'] ?? null) : $doc;
                    if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                    }
                }
            }
            if (!empty($reg->custom_fields) && is_array($reg->custom_fields)) {
                foreach ($reg->custom_fields as $val) {
                    if (is_string($val) && (str_starts_with($val, 'uploads/') || str_starts_with($val, 'payment_proofs/')) && \Illuminate\Support\Facades\Storage::disk('public')->exists($val)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($val);
                    }
                }
            }
            $reg->delete();
        }

        \App\Models\ActivityLog::record(
            'school_registration_delete',
            'Hapus Data Kontingen Sekolah Terdaftar',
            "Super Admin {$authUser->name} menghapus data kontingen sekolah {$schoolName} ({$count} cabang lomba delegasi).",
            [
                'user' => $authUser,
                'highlight' => $schoolName,
                'activity_type' => 'delete',
                'properties' => [
                    'school_name' => $schoolName,
                    'deleted_registrations_count' => $count,
                ]
            ]
        );

        if (!$request->header('X-Inertia') && ($request->ajax() || $request->wantsJson())) {
            return response()->json([
                'status' => 'success',
                'message' => "Data sekolah {$schoolName} beserta {$count} cabang lomba delegasi berhasil dihapus permanen!"
            ]);
        }

        return redirect()->back()->with('success', "Data kontingen sekolah {$schoolName} beserta {$count} cabang lomba delegasi berhasil dihapus permanen!");
    }
}


