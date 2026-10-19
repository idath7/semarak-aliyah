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
use Illuminate\Support\Facades\Storage;

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

        $allRegs = Registration::all();
        $totalPendaftar = $allRegs->count();
        $terverifikasi = $allRegs->where('payment_status', 'Terverifikasi')->count();
        $pending = $allRegs->where('payment_status', 'Menunggu Verifikasi')->count();
        $totalDana = $allRegs->where('payment_status', 'Terverifikasi')->sum('total_fee');

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
        $recentRegs = Registration::orderBy('updated_at', 'desc')->take(12)->get();
        
        foreach ($recentRegs as $reg) {
            if ($reg->checkin_status === 'checked_in') {
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
        $activities = array_slice($activities, 0, 10);

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

        $allRegs = Registration::all();
        $totalPendaftar = $allRegs->count();
        $terverifikasi = $allRegs->where('payment_status', 'Terverifikasi')->count();
        $pending = $allRegs->where('payment_status', 'Menunggu Verifikasi')->count();
        $rejected = $allRegs->where('payment_status', 'Ditolak')->count();
        $totalDana = $allRegs->where('payment_status', 'Terverifikasi')->sum('total_fee');

        $stats = [
            'total_registrations' => $totalPendaftar,
            'verified_registrations' => $terverifikasi,
            'pending_registrations' => $pending,
            'rejected_registrations' => $rejected,
            'total_fee' => $totalDana,
        ];

        return \Inertia\Inertia::render('Admin/Pendaftar', [
            'registrations' => $registrations,
            'competitions' => $competitions,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'competition']),
        ]);
    }

    public function competitionsIndex(Request $request)
    {
        $query = Competition::withCount('registrations')->orderBy('id', 'asc');

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
        $categories = Category::withCount('competitions')->orderBy('id', 'asc')->get();
        $allComps = Competition::all();

        $totalComps = $allComps->count();
        $openComps = $allComps->filter(function ($c) {
            return !$c->isRegistrationClosed();
        })->count();
        $closedComps = $totalComps - $openComps;
        $totalMaxQuota = $allComps->sum('max_quota');
        $totalCurrentQuota = $allComps->sum('current_quota');
        $quotaPercentage = $totalMaxQuota > 0 ? round(($totalCurrentQuota / $totalMaxQuota) * 100) : 0;

        $stats = [
            'total_competitions' => $totalComps,
            'open_count' => $openComps,
            'closed_count' => $closedComps,
            'total_quota_max' => $totalMaxQuota,
            'total_quota_used' => $totalCurrentQuota,
            'quota_filled_percentage' => $quotaPercentage,
        ];

        return \Inertia\Inertia::render('Admin/Competitions', [
            'competitions' => $competitions,
            'categories' => $categories,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    public function storeCompetition(Request $request)
    {
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
            'max_participants' => 'nullable|integer|min:1',
            'registration_deadline' => 'nullable|string|max:100',
            'participants_rule' => 'nullable|string|max:255',
            'tm_date' => 'nullable|string|max:150',
            'contact_person' => 'nullable|string|max:255',
            'coordinators' => 'nullable|array',
            'description' => 'nullable|string',
            'status' => 'nullable|in:open,closed',
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
        $partRule = $validated['participants_rule'] ?? '';
        if (empty($partRule)) {
            $partRule = $partType === 'tim'
                ? "Tim / Regu (Maks {$maxTeams} Tim) & (Maks {$maxParts} Anggota)"
                : "Individu (Maks {$maxParts} Peserta)";
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
            'participant_type' => $partType,
            'max_teams' => $maxTeams,
            'max_participants' => $maxParts,
            'registration_deadline' => $deadline,
            'participants_rule' => $partRule,
            'tm_date' => $rawTm,
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

        return redirect()->back()->with('success', "Data Cabang Lomba \"{$comp->title}\" berhasil disimpan ke sistem!");
    }

    public function updateCompetition($id, Request $request)
    {
        $comp = Competition::findOrFail($id);

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
            'max_participants' => 'nullable|integer|min:1',
            'registration_deadline' => 'nullable|string|max:100',
            'participants_rule' => 'nullable|string|max:255',
            'tm_date' => 'nullable|string|max:150',
            'contact_person' => 'nullable|string|max:255',
            'coordinators' => 'nullable|array',
            'description' => 'nullable|string',
            'status' => 'nullable|in:open,closed',
        ], $messages);

        $category = strtolower($validated['category']);
        $catModel = Category::find($category);
        $categoryLabel = $catModel ? ($catModel->label ?: 'Bidang ' . $catModel->name) : ('Bidang ' . ucfirst($category));

        $partType = $validated['participant_type'] ?? ($comp->participant_type ?: 'individu');
        $maxTeams = (int) ($validated['max_teams'] ?? ($comp->max_teams ?: 1));
        $maxParts = (int) ($validated['max_participants'] ?? ($comp->max_participants ?: 1));
        $partRule = $validated['participants_rule'] ?? '';
        if (empty($partRule)) {
            $partRule = $partType === 'tim'
                ? "Tim / Regu (Maks {$maxTeams} Tim) & (Maks {$maxParts} Anggota)"
                : "Individu (Maks {$maxParts} Peserta)";
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

        $comp->update([
            'title' => $validated['title'],
            'category' => $category,
            'category_label' => $categoryLabel,
            'fee' => (int) $validated['fee'],
            'max_quota' => (int) $validated['max_quota'],
            'status' => $validated['status'] ?? $comp->status,
            'participant_type' => $partType,
            'max_teams' => $maxTeams,
            'max_participants' => $maxParts,
            'registration_deadline' => $deadline,
            'participants_rule' => $partRule,
            'tm_date' => $newTmDateRaw,
            'contact_person' => $contactPersonStr ?: $comp->contact_person,
            'coordinators' => $cleanCoordinators,
            'description' => $validated['description'] ?? $comp->description,
            'tm_settings' => $updatedTm,
        ]);

        if (Setting::get('package_allin_auto_original_price', '1') === '1') {
            Setting::set('package_allin_original_price', (string)((int)Competition::sum('fee')));
        }

        return redirect()->back()->with('success', "Data Cabang Lomba \"{$comp->title}\" berhasil diperbarui!");
    }

    public function destroyCompetition($id)
    {
        $comp = Competition::findOrFail($id);
        
        $hasRegistrations = Registration::where('competition_id', $id)->count();
        if ($hasRegistrations > 0) {
            return redirect()->back()->with('error', "Cabang Lomba {$comp->title} tidak dapat dihapus karena sudah memiliki {$hasRegistrations} delegasi pendaftar!");
        }

        $title = $comp->title;
        $comp->delete();

        return redirect()->back()->with('success', "Cabang Lomba {$title} berhasil dihapus!");
    }

    public function storeCategory(Request $request)
    {
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

    public function destroyCategory($id)
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

    public function verify($id, Request $request)
    {
        $reg = Registration::findOrFail($id);
        $newStatus = $request->input('status');
        if (!$newStatus) {
            $newStatus = $reg->payment_status === 'Terverifikasi' ? 'Menunggu Verifikasi' : 'Terverifikasi';
        }
        $reg->payment_status = $newStatus;
        if ($request->filled('notes')) {
            $reg->verification_notes = $request->notes;
        }
        $reg->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => "Status pendaftaran {$reg->reg_code} diubah menjadi {$newStatus}!"]);
        }

        return redirect()->back()->with('success', "Status pendaftaran {$reg->reg_code} berhasil diubah menjadi {$newStatus}!");
    }

    public function toggleQuota($id, Request $request)
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

    public function formBuilderIndex(Request $request)
    {
        $competitions = Competition::orderBy('id', 'asc')->get();
        $selectedId = $request->query('comp', $competitions->first()?->id);
        $selectedComp = Competition::find($selectedId) ?: $competitions->first();

        $presets = \Database\Seeders\FormFieldSeeder::getPresets();

        return \Inertia\Inertia::render('Admin/FormBuilder', [
            'competitions' => $competitions,
            'selectedCompetition' => $selectedComp,
            'presets' => $presets,
        ]);
    }

    public function formBuilderShow($competitionId, Request $request)
    {
        $competitions = Competition::orderBy('id', 'asc')->get();
        $selectedComp = Competition::findOrFail($competitionId);
        $presets = \Database\Seeders\FormFieldSeeder::getPresets();

        return \Inertia\Inertia::render('Admin/FormBuilder', [
            'competitions' => $competitions,
            'selectedCompetition' => $selectedComp,
            'presets' => $presets,
        ]);
    }

    public function formBuilderSave($competitionId, Request $request)
    {
        $comp = Competition::findOrFail($competitionId);
        $fields = $request->input('custom_fields', []);
        $settings = $request->input('form_settings', $comp->form_settings ?? []);

        $cleanFields = [];
        if (is_array($fields)) {
            foreach ($fields as $f) {
                if (empty($f['label'])) continue;
                $name = !empty($f['name']) ? \Illuminate\Support\Str::slug($f['name'], '_') : \Illuminate\Support\Str::slug($f['label'], '_');
                $cleanFields[] = [
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
                ];
            }
        }

        $comp->custom_fields = $cleanFields;
        if (is_array($settings)) {
            $comp->form_settings = [
                'show_package_type' => (bool) ($settings['show_package_type'] ?? false),
                'form_title' => $settings['form_title'] ?? '',
                'form_subtitle' => $settings['form_subtitle'] ?? '',
                'submit_text' => $settings['submit_text'] ?? 'Kirim Pendaftaran Delegasi',
            ];
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

    public function formBuilderApplyPreset($competitionId, Request $request)
    {
        $comp = Competition::findOrFail($competitionId);
        $presetKey = $request->input('preset', 'akademik');
        $presets = \Database\Seeders\FormFieldSeeder::getPresets();

        if (!isset($presets[$presetKey])) {
            return redirect()->back()->with('error', "Preset \"{$presetKey}\" tidak ditemukan.");
        }

        $comp->custom_fields = $presets[$presetKey];
        $comp->save();

        return redirect()->back()->with('success', "Preset formulir \"{$presetKey}\" berhasil diterapkan pada cabang {$comp->title}!");
    }

    public function formBuilderCopyFrom($competitionId, Request $request)
    {
        $comp = Competition::findOrFail($competitionId);
        $sourceId = $request->input('source_competition_id');
        $sourceComp = Competition::findOrFail($sourceId);

        $comp->custom_fields = $sourceComp->custom_fields ?? [];
        $comp->form_settings = $sourceComp->form_settings ?? [];
        $comp->save();

        $count = is_array($comp->custom_fields) ? count($comp->custom_fields) : 0;
        return redirect()->back()->with('success', "Berhasil menyalin {$count} field formulir dari \"{$sourceComp->title}\" ke \"{$comp->title}\"!");
    }

    public function formBuilderReset($competitionId)
    {
        $comp = Competition::findOrFail($competitionId);
        $comp->custom_fields = [];
        $comp->form_settings = [];
        $comp->save();

        return redirect()->back()->with('success', "Formulir pendaftaran cabang lomba \"{$comp->title}\" telah direset.");
    }

    public function export()
    {
        $registrations = Registration::orderBy('id', 'desc')->get();
        $csv = "Kode Registrasi,Asal Sekolah,Guru Pendamping,Nomor WA,Cabang Lomba,Paket,Total Biaya,Status Pembayaran,Field Tambahan / Kustom,Tanggal Daftar\n";

        foreach ($registrations as $r) {
            $cleanNames = str_replace(["\r", "\n"], ' | ', $r->participants_names);
            $cleanNames = str_replace('"', '""', $cleanNames);
            $cleanSchool = str_replace('"', '""', $r->school_name);
            $cleanMentor = str_replace('"', '""', $r->mentor_name);

            $customSummary = '';
            if (!empty($r->custom_fields) && is_array($r->custom_fields)) {
                $parts = [];
                foreach ($r->custom_fields as $k => $v) {
                    if (is_array($v)) {
                        if (isset($v['url'])) {
                            $v = $v['url'];
                        } else {
                            $v = implode(', ', $v);
                        }
                    }
                    $parts[] = "{$k}: {$v}";
                }
                $customSummary = implode('; ', $parts);
                $customSummary = str_replace('"', '""', $customSummary);
            }

            $csv .= "\"{$r->reg_code}\",\"{$cleanSchool}\",\"{$cleanMentor}\",\"'{$r->mentor_wa}'\",\"{$r->competition_title}\",\"{$r->package_type}\",\"{$r->total_fee}\",\"{$r->payment_status}\",\"{$customSummary}\",\"{$r->created_at}\"\n";
        }

        return response("\xEF\xBB\xBF" . $csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="data-pendaftar-semarak-2026.csv"'
        ]);
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

    public function updateSponsor(Request $request, $id)
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

    public function destroySponsor($id)
    {
        $sponsor = Sponsor::findOrFail($id);
        $name = $sponsor->name;
        $sponsor->delete();

        return redirect()->back()->with('success', "Mitra sponsor \"{$name}\" berhasil dihapus.");
    }

    public function toggleSponsorActive($id)
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

    public function updateGallery(Request $request, $id)
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

    public function destroyGallery($id)
    {
        $gallery = Gallery::findOrFail($id);
        $title = $gallery->title;
        $gallery->delete();

        return redirect()->back()->with('success', "Foto galeri \"{$title}\" berhasil dihapus.");
    }

    public function toggleGalleryActive($id)
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

    public function updateFaq(Request $request, $id)
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

    public function destroyFaq($id)
    {
        $faq = Faq::findOrFail($id);
        $question = $faq->question;
        $faq->delete();

        return redirect()->back()->with('success', "FAQ \"{$question}\" berhasil dihapus.");
    }

    public function toggleFaqActive($id)
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
        $competitions = Competition::withCount('registrations')->orderBy('id', 'asc')->get();
        
        $activeCompId = $request->input('comp');
        if (!$activeCompId || !$competitions->contains('id', $activeCompId)) {
            $activeCompId = $competitions->first() ? $competitions->first()->id : null;
        }

        $activeComp = $activeCompId ? Competition::withCount('registrations')->find($activeCompId) : null;
        
        // Delegasi / Peserta terdaftar pada cabang lomba ini
        $participants = [];
        if ($activeComp) {
            $participants = Registration::where('competition_id', $activeCompId)
                ->orderByRaw('CASE WHEN draw_number IS NULL THEN 1 ELSE 0 END, draw_number ASC, id ASC')
                ->get();
        }

        // Summary KPI
        $allCompetitions = Competition::all();
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

        return \Inertia\Inertia::render('Admin/TechnicalMeeting', [
            'competitions' => $competitions,
            'activeComp' => $activeComp,
            'participants' => $participants,
            'stats' => $stats,
            'selectedCompId' => $activeCompId,
        ]);
    }

    public function updateTmSettings(Request $request, $competitionId)
    {
        $competition = Competition::findOrFail($competitionId);

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
        ]);

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

    public function randomizeDraw(Request $request, $competitionId)
    {
        $competition = Competition::findOrFail($competitionId);
        
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

    public function updateParticipantOrder(Request $request, $competitionId)
    {
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

    public function resetDraw(Request $request, $competitionId)
    {
        $competition = Competition::findOrFail($competitionId);

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
            'account_number' => Setting::get('payment_account_number', '7123456789'),
            'account_holder' => Setting::get('payment_account_holder', 'Semarak Aliyah'),
            'confirmation_wa' => Setting::get('payment_confirmation_wa', '0838 5007 0767'),
            'instructions' => Setting::get('payment_instructions', '<p>Transfer sesuai nominal total tagihan ke rekening resmi panitia. Simpan tangkapan layar (screenshot) atau foto struk bukti pembayaran asli untuk diunggah pada form pendaftaran atau konfirmasikan ke WhatsApp panitia.</p>'),
            'cash_enabled' => Setting::get('payment_cash_enabled', '1') === '1',
        ];

        return \Inertia\Inertia::render('Admin/BiayaRegistrasi', compact('competitions', 'stats', 'packageSettings', 'paymentSettings'));
    }

    public function updateCompetitionFee($id, Request $request)
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
            'payment_bank_name' => 'required|string|max:150',
            'payment_account_number' => 'required|string|max:100',
            'payment_account_holder' => 'required|string|max:150',
            'payment_confirmation_wa' => 'nullable|string|max:30',
            'payment_instructions' => 'nullable|string',
            'payment_cash_enabled' => 'nullable',
        ]);

        Setting::set('payment_bank_name', (string)$validated['payment_bank_name']);
        Setting::set('payment_account_number', (string)$validated['payment_account_number']);
        Setting::set('payment_account_holder', (string)$validated['payment_account_holder']);
        Setting::set('payment_confirmation_wa', (string)($validated['payment_confirmation_wa'] ?? ''));
        Setting::set('payment_instructions', (string)($validated['payment_instructions'] ?? ''));
        Setting::set('payment_cash_enabled', $request->boolean('payment_cash_enabled') ? '1' : '0');

        return redirect()->back()->with('success', 'Informasi rekening bank dan petunjuk pembayaran berhasil diperbarui.');
    }

    // =========================================================================
    // FITUR KUSTOMISASI LANJUTAN WEB PUBLIK
    // =========================================================================
    public function kustomisasiIndex(Request $request)
    {
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

        // Strings
        $stringKeys = [
            'announcement_banner_text', 'announcement_banner_type', 'announcement_banner_btn_text', 'announcement_banner_btn_url', 'announcement_banner_position',
            'announcement_popup_frequency', 'announcement_popup_title', 'announcement_popup_badge', 'announcement_popup_image', 'announcement_popup_content', 'announcement_popup_btn_text', 'announcement_popup_btn_url',
            'floating_wa_number', 'floating_wa_message', 'floating_wa_label', 'floating_wa_position', 'floating_wa_animation',
            'countdown_title', 'countdown_target_date', 'countdown_ended_text',
            'custom_css', 'custom_head_script', 'custom_body_script',
            'visual_particles_type',
        ];

        foreach ($stringKeys as $key) {
            Setting::set($key, (string)($validated[$key] ?? ''));
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
        ];

        foreach ($defaults as $k => $v) {
            Setting::set($k, $v);
        }

        return redirect()->back()->with('success', 'Konfigurasi Kustomisasi Lanjutan berhasil dikembalikan ke standar awal.');
    }
}


