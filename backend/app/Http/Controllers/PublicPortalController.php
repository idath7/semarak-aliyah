<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Competition;
use App\Models\Registration;
use App\Models\Sponsor;
use App\Models\Gallery;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Faq;
use Illuminate\Support\Facades\Auth;

class PublicPortalController extends Controller
{
    public function home()
    {
        $competitions = Competition::orderBy('id')->get();
        Category::ensureDefaultsExist();
        $categories = Category::withCount('competitions')
            ->orderByRaw("CASE 
                WHEN id = 'akademik' THEN 1 
                WHEN id = 'seni' THEN 2 
                WHEN id = 'keagamaan' THEN 3 
                WHEN id = 'olahraga' THEN 4 
                WHEN id = 'teknologi' THEN 5 
                ELSE 6 END, id ASC")
            ->get();

        $sponsors = Sponsor::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'short' => $s->short_name ?: $s->name,
                    'tier' => $s->tier ?: 'Mitra Resmi',
                    'category' => $s->category ?: 'Sponsor Pendukung',
                    'logo' => $s->logo,
                    'only_logo' => (bool)$s->only_logo,
                    'is_logo_only' => (bool)($s->only_logo || (!empty($s->logo) && empty($s->name))),
                    'icon' => $s->icon ?: 'ri-shake-hands-line',
                    'color' => $s->color ?: '#6366f1',
                    'bgLight' => $s->bg_color ?: 'rgba(99, 102, 241, 0.1)',
                    'borderLight' => $s->border_color ?: 'rgba(99, 102, 241, 0.3)',
                    'website_url' => $s->website_url,
                ];
            });

        $sponsorSettings = [
            'tag' => Setting::get('sponsor_section_tag', 'Mitra & Kolaborasi'),
            'title' => Setting::get('sponsor_section_title', 'Didukung Oleh Mitra & Sponsor Terpercaya'),
            'desc' => Setting::get('sponsor_section_desc', 'Apresiasi dan terima kasih kepada institusi perbankan syariah, penyedia telekomunikasi, media massa, serta mitra strategis yang turut bersinergi menyukseskan Semarak Aliyah XIV 2026.'),
            'cta_text' => Setting::get('sponsor_cta_text', 'Peluang Sponsorship: Bergabunglah sebagai mitra resmi dan promosikan brand Anda kepada ribuan pelajar, santri & pendidik se-Kabupaten Jember.'),
            'cta_wa' => Setting::get('sponsor_cta_wa', '6283850070767'),
            'cta_btn_text' => Setting::get('sponsor_cta_btn_text', 'Ajukan Kerja Sama Sponsor'),
            'is_active' => Setting::get('sponsor_is_active', '1') === '1',
        ];

        $galleries = Gallery::where('is_active', true)
            ->orderBy('row_position', 'asc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($g) {
                return [
                    'id' => $g->id,
                    'title' => $g->title,
                    'categoryLabel' => $g->category_label ?: 'Dokumentasi',
                    'pillClass' => $g->pill_class ?: 'pill-gold',
                    'iconClass' => $g->icon_class ?: 'ri-image-line',
                    'src' => $g->image_url,
                    'location' => $g->location ?: 'MA Unggulan Wahid Hasyim',
                    'tag' => $g->tag ?: 'Semarak Aliyah',
                    'desc' => $g->description ?: '',
                    'row' => (int)$g->row_position,
                    'sort_order' => (int)$g->sort_order,
                ];
            });

        $gallerySettings = [
            'tag' => Setting::get('gallery_section_tag', 'Galeri Dokumentasi'),
            'title' => Setting::get('gallery_section_title', 'Momen Spektakuler & Kilas Balik'),
            'desc' => Setting::get('gallery_section_desc', 'Saksikan sorak kemeriahan, kobaran semangat sportivitas talenta muda, dan kilas balik keseruan panggung Semarak Aliyah.'),
            'is_active' => Setting::get('gallery_is_active', '1') === '1',
        ];

        $defaultTickerItems = [
            ['text' => 'SEMARAK ALIYAH XIV', 'style' => 'gradient', 'is_active' => true],
            ['text' => 'AJANG TALENTA JUARA', 'style' => 'outline', 'is_active' => true],
            ['text' => 'MA UNGGULAN WAHID HASYIM', 'style' => 'filled', 'is_active' => true],
            ['text' => 'SAINS • SENI • OLAHRAGA • RELIGI', 'style' => 'outline', 'is_active' => true],
            ['text' => 'TOTAL HADIAH JUTAAN RUPIAH', 'style' => 'gradient', 'is_active' => true],
            ['text' => 'FESTIVAL PELAJAR SE-JEMBER', 'style' => 'outline', 'is_active' => true],
        ];

        $tickerItemsRaw = Setting::get('ticker_items', null);
        $tickerItems = $tickerItemsRaw ? json_decode($tickerItemsRaw, true) : null;
        if (empty($tickerItems) || !is_array($tickerItems)) {
            $tickerItems = $defaultTickerItems;
        }

        $tickerSettings = [
            'is_active' => Setting::get('ticker_is_active', '1') === '1',
            'speed' => (int)Setting::get('ticker_speed', 35),
            'separator' => Setting::get('ticker_separator', '✦'),
            'pause_on_hover' => Setting::get('ticker_pause_on_hover', '1') === '1',
            'font_size' => (int)Setting::get('ticker_font_size', 48),
            'font_family' => Setting::get('ticker_font_family', 'syne'),
            'stroke_width' => (float)Setting::get('ticker_stroke_width', 2.0),
            'opacity' => (int)Setting::get('ticker_opacity', 100),
            'outline_opacity' => (int)Setting::get('ticker_outline_opacity', 80),
            'items' => array_values(array_filter($tickerItems, fn($it) => !empty($it['is_active']))),
        ];

        $faqs = Faq::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($f) {
                return [
                    'id' => $f->id,
                    'q' => $f->question,
                    'a' => $f->answer,
                    'category' => $f->category,
                    'sort_order' => $f->sort_order,
                ];
            });

        $faqSettings = [
            'tag' => Setting::get('faq_section_tag', 'Bantuan Informasi'),
            'title' => Setting::get('faq_section_title', 'Pertanyaan Sering Diajukan (FAQ)'),
            'desc' => Setting::get('faq_section_desc', 'Jawaban atas pertanyaan umum seputar pendaftaran dan pelaksanaan Semarak Aliyah XIV 2026.'),
            'is_active' => Setting::get('faq_is_active', '1') === '1',
        ];

        $sumCompFees = (int)Competition::sum('fee');
        $isAutoOriginalPrice = Setting::get('package_allin_auto_original_price', '1') === '1';
        $allinOriginalPrice = $isAutoOriginalPrice ? $sumCompFees : (int)Setting::get('package_allin_original_price', $sumCompFees);
        if ($allinOriginalPrice <= 0) {
            $allinOriginalPrice = $sumCompFees ?: 400000;
        }

        $bankAccounts = Setting::getBankAccounts();
        $primaryAccount = collect($bankAccounts)->firstWhere('is_primary', true) ?? ($bankAccounts[0] ?? null);

        $pricingSettings = [
            'allin_is_active' => Setting::get('package_allin_is_active', '1') === '1',
            'allin_name' => Setting::get('package_allin_name', 'Paket All-In Komplit'),
            'allin_price' => (int)Setting::get('package_allin_price', 200000),
            'allin_original_price' => $allinOriginalPrice,
            'allin_tag' => Setting::get('package_allin_tag', 'Paling Hemat • Rekomendasi'),
            'allin_desc' => Setting::get('package_allin_desc', 'Pilihan terbaik & paling hemat! Daftarkan kontingen sekolah Anda ke seluruh 8 cabang lomba sekaligus hanya dengan setengah harga.'),
            'allin_features' => json_decode(Setting::get('package_allin_features', '[]'), true) ?: [],
            'satuan_name' => Setting::get('package_satuan_name', 'Paket Fleksibel Satuan'),
            'satuan_desc' => Setting::get('package_satuan_desc', 'Pilihan tepat bagi madrasah/sekolah yang ingin mengirimkan delegasi spesifik pada cabang lomba tertentu.'),
            'satuan_features' => json_decode(Setting::get('package_satuan_features', '[]'), true) ?: [],
            'bank_name' => $primaryAccount['bank_name'] ?? Setting::get('payment_bank_name', 'Bank Syariah Indonesia (BSI)'),
            'account_number' => $primaryAccount['account_number'] ?? Setting::get('payment_account_number', '7123456789'),
            'account_holder' => $primaryAccount['account_holder'] ?? Setting::get('payment_account_holder', 'Semarak Aliyah'),
            'bank_accounts' => $bankAccounts,
            'confirmation_wa' => Setting::get('payment_confirmation_wa', '0838 5007 0767'),
            'instructions' => Setting::get('payment_instructions', ''),
            'cash_enabled' => Setting::get('payment_cash_enabled', '1') === '1',
        ];

        $rawTmGuidelines = Setting::get('tm_guidelines_items', null);
        $tmGuidelines = $rawTmGuidelines ? json_decode($rawTmGuidelines, true) : null;
        if (empty($tmGuidelines) || !is_array($tmGuidelines)) {
            $tmGuidelines = AdminPanelController::getDefaultTmGuidelines();
        }

        $tmScheduleSettings = [
            'tag' => Setting::get('tm_section_tag', 'Agenda Pelaksanaan'),
            'title' => Setting::get('tm_section_title', 'Jadwal Technical Meeting & Pelaksanaan'),
            'desc' => Setting::get('tm_section_desc', 'Harap diperhatikan oleh guru pendamping dan ketua delegasi sekolah mengenai tanggal, jam, ruangan, serta agenda pelaksanaan TM tiap cabang lomba.'),
            'guidelines_active' => Setting::get('tm_guidelines_active', '1') === '1',
            'guidelines' => $tmGuidelines,
            'is_active' => Setting::get('tm_section_active', '1') === '1',
        ];

        $heroSettings = [
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
        ];

        $rawStats = Setting::get('stats_items', null);
        $statsItems = $rawStats ? json_decode($rawStats, true) : null;
        if (empty($statsItems) || !is_array($statsItems)) {
            $statsItems = AdminPanelController::getDefaultStatsItems();
        }
        $statsSettings = [
            'is_active' => Setting::get('stats_active', '1') === '1',
            'items' => $statsItems,
        ];

        $rawAbout = Setting::get('about_items', null);
        $aboutItems = $rawAbout ? json_decode($rawAbout, true) : null;
        if (empty($aboutItems) || !is_array($aboutItems)) {
            $aboutItems = AdminPanelController::getDefaultAboutPrizes();
        }
        $aboutSettings = [
            'is_active' => Setting::get('about_active', '1') === '1',
            'tag' => Setting::get('about_tag', 'Apresiasi & Fasilitas'),
            'title' => Setting::get('about_title', 'Dapatkan Hadiah Bergengsi & Beasiswa Pendidikan'),
            'desc' => Setting::get('about_desc', 'Semarak Aliyah diselenggarakan oleh MA Unggulan Wahid Hasyim Balung sebagai bentuk apresiasi nyata bagi generasi muda yang berprestasi dan berkarakter.'),
            'items' => $aboutItems,
        ];

        return Inertia::render('Public/Home', compact(
            'competitions',
            'categories',
            'sponsors',
            'sponsorSettings',
            'galleries',
            'gallerySettings',
            'tickerSettings',
            'faqs',
            'faqSettings',
            'pricingSettings',
            'tmScheduleSettings',
            'heroSettings',
            'statsSettings',
            'aboutSettings'
        ));
    }

    public function register(Request $request)
    {
        $isLoginRequired = Setting::get('registration_login_required', '0') === '1';
        $lomba = $request->query('lomba');
        /** @var \App\Models\Registrant|\App\Models\User|null $user */
        $user = Auth::guard('pendaftar')->user() ?? Auth::user();
        $isRegistrantLoggedIn = Auth::guard('pendaftar')->check() || ($user instanceof \App\Models\Registrant) || ($user instanceof \App\Models\User && $user->isRegistrant());

        // Jika pendaftaran diset Wajib Login dan pengguna belum login sebagai pendaftar:
        if ($isLoginRequired && !$isRegistrantLoggedIn) {
            $params = [
                'redirect_to' => 'register',
                'notice' => 'required',
            ];
            if ($lomba) {
                $params['lomba'] = $lomba;
            }
            return redirect()->route('pendaftar.login', $params)
                ->with('info', 'Pendaftaran mewajibkan login akun pendaftar terlebih dahulu. Silakan masuk atau daftar akun baru.');
        }

        $competitions = Competition::orderBy('id')->get();
        $preselectedLomba = $request->query('lomba', null);

        $sumCompFees = (int)Competition::sum('fee');
        $isAutoOriginalPrice = Setting::get('package_allin_auto_original_price', '1') === '1';
        $allinOriginalPrice = $isAutoOriginalPrice ? $sumCompFees : (int)Setting::get('package_allin_original_price', $sumCompFees);
        if ($allinOriginalPrice <= 0) {
            $allinOriginalPrice = $sumCompFees ?: 400000;
        }

        $bankAccounts = Setting::getBankAccounts();
        $primaryAccount = collect($bankAccounts)->firstWhere('is_primary', true) ?? ($bankAccounts[0] ?? null);

        $pricingSettings = [
            'allin_is_active' => Setting::get('package_allin_is_active', '1') === '1',
            'allin_name' => Setting::get('package_allin_name', 'Paket All-In Komplit'),
            'allin_price' => (int)Setting::get('package_allin_price', 200000),
            'allin_original_price' => $allinOriginalPrice,
            'allin_tag' => Setting::get('package_allin_tag', 'Paling Hemat • Rekomendasi'),
            'allin_desc' => Setting::get('package_allin_desc', 'Pilihan terbaik & paling hemat! Daftarkan kontingen sekolah Anda ke seluruh 8 cabang lomba sekaligus hanya dengan setengah harga.'),
            'allin_features' => json_decode(Setting::get('package_allin_features', '[]'), true) ?: [],
            'bank_name' => $primaryAccount['bank_name'] ?? Setting::get('payment_bank_name', 'Bank Syariah Indonesia (BSI)'),
            'account_number' => $primaryAccount['account_number'] ?? Setting::get('payment_account_number', '7123456789'),
            'account_holder' => $primaryAccount['account_holder'] ?? Setting::get('payment_account_holder', 'Semarak Aliyah'),
            'bank_accounts' => $bankAccounts,
            'confirmation_wa' => Setting::get('payment_confirmation_wa', '0838 5007 0767'),
            'instructions' => Setting::get('payment_instructions', ''),
            'cash_enabled' => Setting::get('payment_cash_enabled', '1') === '1',
        ];

        $schools = \App\Models\School::active()->orderBy('name', 'asc')->get();

        $authRegistrant = null;
        if ($isRegistrantLoggedIn) {
            $u = Auth::user();
            $authRegistrant = [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role' => $u->role,
                'registrant_role' => $u->registrant_role,
                'gender' => $u->gender,
                'school_name' => $u->school_name,
                'school_district' => $u->school_district,
            ];
        }

        return Inertia::render('Public/Register', compact(
            'competitions',
            'preselectedLomba',
            'pricingSettings',
            'schools',
            'isLoginRequired',
            'authRegistrant'
        ));
    }

    public function storeRegistration(Request $request)
    {
        $isLoginRequired = Setting::get('registration_login_required', '0') === '1';
        /** @var \App\Models\Registrant|\App\Models\User|null $user */
        $user = Auth::guard('pendaftar')->user() ?? Auth::user();
        $isRegistrantLoggedIn = Auth::guard('pendaftar')->check() || ($user instanceof \App\Models\Registrant) || ($user instanceof \App\Models\User && $user->isRegistrant());
        if ($isLoginRequired && !$isRegistrantLoggedIn) {
            return redirect()->route('pendaftar.login', ['redirect_to' => 'register', 'notice' => 'required'])
                ->withErrors(['login' => 'Pendaftaran mewajibkan login akun pendaftar. Silakan masuk terlebih dahulu.']);
        }

        $validated = $request->validate([
            'competition_id' => 'required|string',
            'package_type' => 'nullable|string',
            'notes' => 'nullable|string',
            'payment_method' => 'nullable|string',
        ]);

        $competition = Competition::find($validated['competition_id']);
        if ($competition) {
            if ($competition->isDirectRegistration()) {
                $coordName = $competition->primary_coordinator['name'] ?? 'Koordinator Lomba';
                $coordPhone = $competition->primary_coordinator['contact'] ?? '';
                $contactInfo = $coordPhone ? "{$coordName} ({$coordPhone})" : $coordName;
                return redirect()->back()->withErrors([
                    'competition_id' => "Pendaftaran cabang lomba {$competition->title} hanya dapat dilakukan melalui kontak langsung ke Koordinator Lomba: {$contactInfo}."
                ]);
            }
            if ($competition->isDeadlinePassed()) {
                return redirect()->back()->withErrors([
                    'competition_id' => "Pendaftaran untuk cabang {$competition->title} telah ditutup karena melewati batas tanggal pendaftaran ({$competition->registration_deadline})."
                ]);
            }
            if ($competition->isQuotaFull()) {
                return redirect()->back()->withErrors([
                    'competition_id' => "Kuota pendaftaran untuk cabang {$competition->title} telah penuh ({$competition->max_quota} pendaftar)."
                ]);
            }
            if ($competition->status === 'closed') {
                return redirect()->back()->withErrors([
                    'competition_id' => "Pendaftaran untuk cabang {$competition->title} telah ditutup oleh panitia."
                ]);
            }
        }
        $compTitle = $competition ? $competition->title : 'Semua Cabang Lomba (Paket All In)';

        // Process Custom Dynamic Form Fields
        $customFieldsDef = $competition && is_array($competition->custom_fields) ? $competition->custom_fields : [];
        $rawCustomFields = $request->input('custom_fields', []);
        if (is_string($rawCustomFields)) {
            $rawCustomFields = json_decode($rawCustomFields, true) ?: [];
        }
        if (!is_array($rawCustomFields)) {
            $rawCustomFields = [];
        }

        $processedCustomFields = [];
        $fileDocuments = [];
        $customErrors = [];

        // Generate unique registration code upfront so files can be organized nicely
        $regCode = 'SA26-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

        $isCustomSubmission = $request->has('custom_fields') || $request->has('custom_files');

        // If standard registration form is submitted, validate standard fields
        if (!$isCustomSubmission) {
            $stdValidated = $request->validate([
                'school_name' => 'required|string',
                'mentor_name' => 'required|string',
                'mentor_wa' => 'required|string',
                'participants_names' => $validated['competition_id'] === 'all-in' ? 'nullable|string' : 'required|string',
            ]);
        } else {
            // If form has custom_fields defined, validate based on those fields!
            foreach ($customFieldsDef as $field) {
                $fName = $field['name'] ?? null;
                if (!$fName) continue;

                $fLabel = $field['label'] ?? $fName;
                $fType = $field['type'] ?? 'text';
                $isRequired = !empty($field['required']);

                if ($fType === 'file') {
                    $rawFiles = $request->file("custom_files.{$fName}") 
                        ?: ($request->file("custom_fields.{$fName}") ?: $request->file($fName));

                    $maxFiles = !empty($field['max_files']) ? (int)$field['max_files'] : 1;
                    $maxMb = !empty($field['max_size_mb']) ? (int)$field['max_size_mb'] : 10;
                    $defaultAllowed = ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'doc', 'zip'];
                    $allowedExts = !empty($field['allowed_extensions']) && is_array($field['allowed_extensions'])
                        ? array_map('strtolower', $field['allowed_extensions'])
                        : $defaultAllowed;
                    $dangerousExtensions = ['php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'phps', 'sh', 'bash', 'exe', 'bat', 'cmd', 'cgi', 'pl', 'py', 'js', 'html', 'htm', 'htaccess'];

                    $fileList = [];
                    if (is_array($rawFiles)) {
                        $fileList = array_values(array_filter($rawFiles, fn($f) => $f instanceof \Illuminate\Http\UploadedFile));
                    } elseif ($rawFiles instanceof \Illuminate\Http\UploadedFile) {
                        $fileList = [$rawFiles];
                    }

                    if (!empty($fileList)) {
                        if ($maxFiles > 0 && count($fileList) > $maxFiles) {
                            $customErrors["cf_{$fName}"] = "Jumlah berkas \"{$fLabel}\" melebihi batas maksimal ({$maxFiles} berkas).";
                            continue;
                        }

                        $processedFiles = [];
                        $hasFileError = false;

                        foreach ($fileList as $uFile) {
                            if (!$uFile->isValid()) {
                                $customErrors["cf_{$fName}"] = "Berkas \"{$fLabel}\" tidak valid atau gagal diunggah.";
                                $hasFileError = true;
                                break;
                            }

                            if ($uFile->getSize() > ($maxMb * 1024 * 1024)) {
                                $customErrors["cf_{$fName}"] = "Ukuran berkas \"{$uFile->getClientOriginalName()}\" pada \"{$fLabel}\" melebihi batas maksimal {$maxMb}MB.";
                                $hasFileError = true;
                                break;
                            }

                            $ext = strtolower($uFile->getClientOriginalExtension());
                            if (in_array($ext, $dangerousExtensions)) {
                                $customErrors["cf_{$fName}"] = "Tipe file \"{$ext}\" tidak diizinkan demi keamanan sistem.";
                                $hasFileError = true;
                                break;
                            }

                            if (!in_array($ext, $allowedExts)) {
                                $customErrors["cf_{$fName}"] = "Format berkas \"{$uFile->getClientOriginalName()}\" harus berupa: " . implode(', ', $allowedExts) . ".";
                                $hasFileError = true;
                                break;
                            }

                            $safeOriginalName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $uFile->getClientOriginalName());
                            $storagePath = $uFile->storeAs("uploads/registrations/{$regCode}", time() . "_" . uniqid() . "_{$safeOriginalName}", 'public');
                            $fileUrl = '/storage/' . $storagePath;

                            $fileMeta = [
                                'type' => 'file',
                                'label' => $fLabel,
                                'name' => $uFile->getClientOriginalName(),
                                'url' => $fileUrl,
                                'size' => $uFile->getSize(),
                                'extension' => $ext,
                            ];

                            $processedFiles[] = $fileMeta;
                            $fileDocuments[] = [
                                'title' => $fLabel,
                                'url' => $fileUrl,
                                'filename' => $uFile->getClientOriginalName(),
                                'type' => $ext,
                                'uploaded_at' => now()->toIso8601String(),
                            ];
                        }

                        if ($hasFileError) {
                            continue;
                        }

                        if ($maxFiles > 1) {
                            $processedCustomFields[$fName] = $processedFiles;
                        } else {
                            $processedCustomFields[$fName] = $processedFiles[0] ?? null;
                        }
                    } elseif ($isRequired) {
                        $customErrors["cf_{$fName}"] = "Dokumen berkas \"{$fLabel}\" wajib diunggah.";
                    }
                } else {
                    $val = $rawCustomFields[$fName] ?? $request->input($fName);
                    if ($isRequired && (is_null($val) || $val === '' || (is_array($val) && empty($val)))) {
                        $customErrors["cf_{$fName}"] = "Kolom \"{$fLabel}\" wajib diisi.";
                    } else {
                        // Email validation
                        if ($fType === 'email' && !empty($val) && !is_array($val)) {
                            if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
                                $customErrors["cf_{$fName}"] = "Format email pada \"{$fLabel}\" tidak valid (contoh: nama@domain.sch.id).";
                                continue;
                            }
                        }

                        // Number validation & WhatsApp cleaning
                        if ($fType === 'number' && !empty($val) && !is_array($val)) {
                            if ($fName === 'mentor_wa' || str_contains($fName, 'wa') || str_contains($fName, 'whatsapp') || str_contains($fName, 'telepon') || str_contains($fName, 'kontak')) {
                                $val = preg_replace('/[^0-9]/', '', (string)$val);
                            }
                        }

                        $processedCustomFields[$fName] = $val;
                    }
                }
            }

            if (!empty($customErrors)) {
                return redirect()->back()->withErrors($customErrors)->withInput();
            }
        }

        // Resolve core summary columns gracefully from custom fields or inputs
        $schoolName = $request->input('school_name') 
            ?? ($processedCustomFields['school_name'] ?? null) 
            ?? ($processedCustomFields['nama_sekolah'] ?? null) 
            ?? ($processedCustomFields['nama_lembaga'] ?? null) 
            ?? ($processedCustomFields['asal_sekolah'] ?? null)
            ?? ($processedCustomFields['nama_squad_ml'] ?? null)
            ?? ($processedCustomFields['nama_tim_voli'] ?? null)
            ?? '-';

        $mentorName = $request->input('mentor_name') 
            ?? ($processedCustomFields['mentor_name'] ?? null) 
            ?? ($processedCustomFields['nama_pendamping'] ?? null) 
            ?? ($processedCustomFields['nama_pembina'] ?? null) 
            ?? ($processedCustomFields['nama_pelatih_official'] ?? null)
            ?? '-';

        $mentorWa = $request->input('mentor_wa') 
            ?? ($processedCustomFields['mentor_wa'] ?? null) 
            ?? ($processedCustomFields['nomor_wa'] ?? null) 
            ?? ($processedCustomFields['whatsapp'] ?? null) 
            ?? ($processedCustomFields['nomor_kontak_pelatih'] ?? null)
            ?? ($processedCustomFields['nomor_wa_peserta'] ?? null)
            ?? '-';
        if ($mentorWa !== '-') {
            $mentorWa = preg_replace('/[^0-9]/', '', (string)$mentorWa);
        }

        $contactEmail = $request->input('mentor_email')
            ?? $request->input('contact_email')
            ?? ($processedCustomFields['mentor_email'] ?? null)
            ?? ($processedCustomFields['contact_email'] ?? null)
            ?? ($processedCustomFields['email'] ?? null)
            ?? ($processedCustomFields['email_pendamping'] ?? null)
            ?? ($processedCustomFields['alamat_email'] ?? null)
            ?? ($processedCustomFields['email_kontak'] ?? null)
            ?? null;
        if ($contactEmail) {
            if (empty($processedCustomFields['mentor_email'])) {
                $processedCustomFields['mentor_email'] = $contactEmail;
            }
            if (empty($processedCustomFields['contact_email'])) {
                $processedCustomFields['contact_email'] = $contactEmail;
            }
        }

        $schoolDistrict = $request->input('school_district')
            ?? ($processedCustomFields['school_district'] ?? null)
            ?? ($processedCustomFields['kecamatan'] ?? null)
            ?? ($processedCustomFields['kecamatan_sekolah'] ?? null)
            ?? ($processedCustomFields['kecamatan_asal_sekolah'] ?? null)
            ?? ($processedCustomFields['kecamatan_asal_sekolah_madrasah'] ?? null)
            ?? ($processedCustomFields['wilayah_kecamatan'] ?? null)
            ?? null;
        if ($schoolDistrict && empty($processedCustomFields['school_district'])) {
            $processedCustomFields['school_district'] = $schoolDistrict;
        }

        $schoolNpsn = $request->input('school_npsn')
            ?? $request->input('npsn')
            ?? ($processedCustomFields['school_npsn'] ?? null)
            ?? ($processedCustomFields['npsn'] ?? null)
            ?? null;
        if ($schoolNpsn && empty($processedCustomFields['school_npsn'])) {
            $processedCustomFields['school_npsn'] = $schoolNpsn;
        }

        $schoolAddress = $request->input('school_address')
            ?? $request->input('alamat')
            ?? ($processedCustomFields['school_address'] ?? null)
            ?? ($processedCustomFields['alamat_sekolah'] ?? null)
            ?? ($processedCustomFields['alamat'] ?? null)
            ?? null;
        if ($schoolAddress && empty($processedCustomFields['school_address'])) {
            $processedCustomFields['school_address'] = $schoolAddress;
        }

        $participantsNames = $request->input('participants_names') 
            ?? ($processedCustomFields['participants_names'] ?? null) 
            ?? ($processedCustomFields['nama_peserta'] ?? null) 
            ?? ($processedCustomFields['daftar_peserta'] ?? null) 
            ?? ($processedCustomFields['roster_anggota_dan_id'] ?? null)
            ?? ($processedCustomFields['roster_posisi_dan_nomor'] ?? null)
            ?? '-';

        if ($participantsNames === '-' || empty(trim($participantsNames))) {
            foreach ($processedCustomFields as $key => $val) {
                if (is_array($val) && !empty($val) && isset($val[0]) && is_array($val[0]) && isset($val[0]['name'])) {
                    $formattedList = [];
                    foreach ($val as $pIdx => $p) {
                        $pName = trim($p['name'] ?? '');
                        if (empty($pName)) continue;
                        $details = [];
                        if (!empty($p['is_captain'])) $details[] = 'KAPTEN TIM';
                        if (!empty($p['captain_wa'])) $details[] = 'WA Kapten: ' . $p['captain_wa'];
                        if (!empty($p['wa_number'])) $details[] = 'WA: ' . $p['wa_number'];
                        if (!empty($p['custom_id'])) $details[] = 'ID: ' . $p['custom_id'];
                        if (!empty($p['gender'])) $details[] = ($p['gender'] === 'L' || $p['gender'] === 'Laki-laki') ? 'L' : 'P';
                        if (!empty($p['grade'])) $details[] = $p['grade'];
                        if (!empty($p['birth_date'])) $details[] = $p['birth_date'];
                        if (!empty($p['nisn'])) $details[] = 'NISN: ' . $p['nisn'];
                        $detailStr = !empty($details) ? ' (' . implode(', ', $details) . ')' : '';
                        $formattedList[] = ($pIdx + 1) . '. ' . $pName . $detailStr;
                    }
                    if (!empty($formattedList)) {
                        $participantsNames = implode("\n", $formattedList);
                        break;
                    }
                }
            }
        }

        // Handle Payment Proof File Upload if provided
        if ($request->hasFile('payment_proof')) {
            $proof = $request->file('payment_proof');
            if ($proof && $proof->isValid()) {
                $ext = strtolower($proof->getClientOriginalExtension());
                $safeOriginalName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $proof->getClientOriginalName());
                $storagePath = $proof->storeAs("uploads/registrations/{$regCode}", time() . "_bukti_{$safeOriginalName}", 'public');
                $fileUrl = '/storage/' . $storagePath;
                $fileDocuments[] = [
                    'title' => 'Bukti Transfer Pembayaran Bank',
                    'url' => $fileUrl,
                    'filename' => $proof->getClientOriginalName(),
                    'type' => $ext,
                    'uploaded_at' => now()->toIso8601String(),
                ];
                $processedCustomFields['payment_proof'] = [
                    'type' => 'file',
                    'label' => 'Bukti Transfer Pembayaran Bank',
                    'name' => $proof->getClientOriginalName(),
                    'url' => $fileUrl,
                ];
            }
        }

        // Handle specific Paket All-In fields: coordinators list & count, npsn
        if ($request->filled('npsn') && empty($processedCustomFields['npsn'])) {
            $processedCustomFields['npsn'] = $request->input('npsn');
        }
        if ($request->has('coordinators')) {
            $rawCoordinators = $request->input('coordinators');
            if (is_string($rawCoordinators)) {
                $rawCoordinators = json_decode($rawCoordinators, true) ?: [];
            }
            $processedCustomFields['coordinators'] = $rawCoordinators;
            $processedCustomFields['coordinators_count'] = count($rawCoordinators);

            // Generate participants_names from coordinators if competition_id is all-in
            if ($validated['competition_id'] === 'all-in' && ($participantsNames === '-' || empty(trim($participantsNames)))) {
                $cLines = [];
                foreach ($rawCoordinators as $cIdx => $c) {
                    $cName = trim($c['name'] ?? $c['nama'] ?? '');
                    if (empty($cName)) continue;
                    $cRole = trim($c['role'] ?? $c['jabatan'] ?? 'Guru Pendamping');
                    $cWa = trim($c['wa'] ?? $c['phone'] ?? '');
                    $cLines[] = ($cIdx + 1) . ". {$cName} - {$cRole}" . ($cWa ? " (WA: {$cWa})" : '');
                }
                if (!empty($cLines)) {
                    $participantsNames = "Kontingen All-In (" . count($cLines) . " Pendamping):\n" . implode("\n", $cLines);
                }
            }
        }

        if ($validated['competition_id'] === 'all-in' && ($participantsNames === '-' || empty(trim($participantsNames)))) {
            $participantsNames = "Kontingen Resmi Paket All-In ({$mentorName})";
        }

        // Check if this school has already registered & verified Paket All-In (via manual code or school name)
        $allInCodeInput = trim(
            $request->input('custom_fields.allin_code') 
            ?? $request->input('allin_code') 
            ?? $request->input('package_code') 
            ?? ''
        );

        $existingAllIn = null;
        $isAllInFromCode = false;

        // 1. Prioritas Utama: Cek jika pendamping memasukkan Kode Paket Komplit secara manual atau auto-filled
        if (!empty($allInCodeInput)) {
            $cleanCode = strtoupper($allInCodeInput);
            $existingAllIn = Registration::where('package_type', 'Paket All-In')
                ->where(function ($q) use ($cleanCode) {
                    $q->whereRaw('UPPER(reg_code) = ?', [$cleanCode])
                      ->orWhereRaw('UPPER(barcode) = ?', [$cleanCode]);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingAllIn) {
                $isAllInFromCode = true;
            }
        }

        // 2. Fallback: Deteksi otomatis berdasarkan nama sekolah jika kode belum diinput
        if (!$existingAllIn) {
            $cleanTargetSchool = \App\Models\School::normalizeSchoolName($schoolName);
            $existingAllIn = Registration::where('package_type', 'Paket All-In')
                ->get()
                ->first(function ($r) use ($schoolName, $cleanTargetSchool) {
                    if (empty($r->school_name)) return false;
                    if (strcasecmp(trim($r->school_name), trim($schoolName)) === 0) return true;
                    return \App\Models\School::normalizeSchoolName($r->school_name) === $cleanTargetSchool;
                });
        }

        $isAllInVerified = false;
        if ($existingAllIn) {
            $pStatus = strtolower($existingAllIn->payment_status ?? '');
            $rStatus = strtolower($existingAllIn->registration_status ?? 'terverifikasi');
            // Terverifikasi jika pembayaran terkonfirmasi atau kode paket komplit valid
            $isAllInVerified = in_array($pStatus, ['terverifikasi', 'lunas', 'verified']) || $isAllInFromCode;

            // Simpan kode all-in ke processedCustomFields agar tersimpan di database
            if (!is_array($processedCustomFields)) {
                $processedCustomFields = [];
            }
            $processedCustomFields['allin_code'] = $existingAllIn->reg_code;
        }

        $packageType = $request->input('package_type') ?? ($isAllInVerified ? 'Paket All-In' : 'Paket Satuan');

        // Total fee calculation & payment status algorithm
        if ($isAllInVerified && $validated['competition_id'] !== 'all-in') {
            // Already paid & verified under Paket All-In Komplit! Free for all competitions!
            $totalFee = 0;
            $paymentStatus = 'Terverifikasi';
            $regStatus = 'Menunggu Verifikasi';
            $packageType = 'Paket All-In';
        } elseif ($packageType === 'Paket All-In') {
            // Initial Paket All-In registration
            $totalFee = (int)Setting::get('package_allin_price', 200000);
            $paymentStatus = 'Menunggu Verifikasi';
            $regStatus = 'Menunggu Verifikasi';
        } else {
            // Paket Satuan
            $totalFee = $competition ? (int)$competition->fee : 50000;
            $paymentStatus = 'Menunggu Verifikasi';
            $regStatus = 'Menunggu Verifikasi';
        }

        // Tambahan fallback komprehensif jika nomor WA, nama, atau email belum terdeteksi (misal pendaftaran tim/kapten)
        if ($mentorWa === '-' || empty($mentorWa)) {
            $possibleWaKeys = [
                'wa_kapten', 'captain_wa', 'nomor_wa_kapten', 'kontak_kapten', 'no_wa_kapten', 'kapten_wa',
                'nomor_wa_peserta', 'wa_peserta', 'telepon_peserta', 'kontak_peserta', 'nomor_hp_peserta',
                'nomor_kontak_pelatih', 'kontak_pendamping', 'nomor_hp', 'no_hp', 'telepon', 'phone'
            ];
            foreach ($possibleWaKeys as $pwk) {
                if (!empty($processedCustomFields[$pwk])) {
                    $cWa = preg_replace('/[^0-9]/', '', (string)$processedCustomFields[$pwk]);
                    if (!empty($cWa)) {
                        $mentorWa = $cWa;
                        break;
                    }
                }
            }
        }

        if ($mentorName === '-' || empty($mentorName)) {
            $possibleNameKeys = [
                'nama_kapten', 'captain_name', 'kapten_tim', 'nama_ketua_tim', 'nama_ketua',
                'nama_koordinator', 'koordinator', 'nama_official', 'nama_pelatih', 'nama_peserta', 'nama_lengkap'
            ];
            foreach ($possibleNameKeys as $pnk) {
                if (!empty($processedCustomFields[$pnk]) && is_string($processedCustomFields[$pnk])) {
                    $mentorName = trim($processedCustomFields[$pnk]);
                    break;
                }
            }
        }

        if (empty($contactEmail)) {
            $possibleEmailKeys = [
                'mentor_email', 'contact_email', 'email', 'email_pendamping', 
                'email_kapten', 'captain_email', 'email_koordinator', 'email_pendaftar',
                'alamat_email', 'email_kontak', 'email_resmi'
            ];
            foreach ($possibleEmailKeys as $pek) {
                if (!empty($processedCustomFields[$pek]) && filter_var($processedCustomFields[$pek], FILTER_VALIDATE_EMAIL)) {
                    $contactEmail = strtolower(trim($processedCustomFields[$pek]));
                    break;
                }
            }
        }

        // Cek juga di array delegasi / peserta siswa repeater jika ada kapten
        if (($mentorWa === '-' || empty($mentorWa)) || ($mentorName === '-' || empty($mentorName))) {
            foreach ($processedCustomFields as $key => $val) {
                if (is_array($val) && !empty($val) && isset($val[0]) && is_array($val[0])) {
                    foreach ($val as $p) {
                        if (!empty($p['is_captain']) || !empty($p['captain_wa']) || !empty($p['wa_number'])) {
                            if (($mentorWa === '-' || empty($mentorWa)) && !empty($p['captain_wa'] ?? $p['wa_number'])) {
                                $cDigits = preg_replace('/[^0-9]/', '', (string)($p['captain_wa'] ?? $p['wa_number']));
                                if (!empty($cDigits)) {
                                    $mentorWa = $cDigits;
                                }
                            }
                            if (($mentorName === '-' || empty($mentorName)) && !empty($p['name'] ?? $p['nama_lengkap'])) {
                                $mentorName = trim($p['name'] ?? $p['nama_lengkap']);
                            }
                            if (empty($contactEmail) && !empty($p['email']) && filter_var($p['email'], FILTER_VALIDATE_EMAIL)) {
                                $contactEmail = strtolower(trim($p['email']));
                            }
                            break 2;
                        }
                    }
                }
            }
        }

        // Simpan email kontak ke processedCustomFields agar tersimpan di database
        if (!empty($contactEmail)) {
            $processedCustomFields['mentor_email'] = $contactEmail;
            $processedCustomFields['contact_email'] = $contactEmail;
        }

        // =========================================================================
        // OTOMATIS MEMBUAT / MENGHUBUNGKAN AKUN PENDAFTAR (Registrant)
        // Saat Kebijakan Pendaftaran Bebas (Tanpa Wajib Login), akun baru otomatis dibuat
        // di tabel terpisah 'registrants' berdasarkan kontak & email koordinator/pendamping/kapten.
        // =========================================================================
        $registrantUser = null;
        $cleanWa = ($mentorWa !== '-' && !empty($mentorWa)) ? Setting::formatWaNumber($mentorWa) : null;
        $rawWaDigits = ($mentorWa !== '-' && !empty($mentorWa)) ? preg_replace('/\D/', '', (string)$mentorWa) : null;

        if (Auth::guard('pendaftar')->check()) {
            $registrantUser = Auth::guard('pendaftar')->user();
        } else {

            if (!empty($cleanWa) || !empty($contactEmail)) {
                // 1. Cari apakah akun Registrant sudah ada sebelumnya berdasarkan nomor WA atau Email
                $registrantUser = \App\Models\Registrant::where(function ($q) use ($cleanWa, $rawWaDigits, $contactEmail) {
                    if (!empty($cleanWa)) {
                        $q->where('phone', $cleanWa)
                          ->orWhere('phone', $rawWaDigits);
                    }
                    if (!empty($contactEmail)) {
                        $q->orWhere('email', strtolower($contactEmail));
                    }
                })->first();

                if ($registrantUser) {
                    // Update data profil jika ada email atau sekolah baru yang valid
                    $isUpdated = false;
                    if (!empty($contactEmail) && (empty($registrantUser->email) || str_ends_with($registrantUser->email, '@semarak.id'))) {
                        if (!\App\Models\Registrant::where('email', strtolower($contactEmail))->where('id', '!=', $registrantUser->id)->exists()) {
                            $registrantUser->email = strtolower($contactEmail);
                            $isUpdated = true;
                        }
                    }
                    if (empty($registrantUser->school_name) && $schoolName !== '-') {
                        $registrantUser->school_name = $schoolName;
                        $isUpdated = true;
                    }
                    if (empty($registrantUser->school_district) && !empty($schoolDistrict)) {
                        $registrantUser->school_district = $schoolDistrict;
                        $isUpdated = true;
                    }
                    if ($isUpdated) {
                        $registrantUser->save();
                    }
                } else if (!empty($cleanWa)) {
                    // 2. Buat akun baru pendaftar secara otomatis di tabel registrants
                    $roleLabel = (stripos($mentorName, 'kapten') !== false) ? 'Siswa Peserta' : 'Pendamping';
                    $generatedEmail = !empty($contactEmail) 
                        ? strtolower($contactEmail) 
                        : ('pendaftar_' . preg_replace('/\D/', '', $cleanWa) . '@semarak.id');

                    if (\App\Models\Registrant::where('email', $generatedEmail)->exists()) {
                        $generatedEmail = 'pendaftar_' . preg_replace('/\D/', '', $cleanWa) . '_' . rand(100, 999) . '@semarak.id';
                    }

                    $displayName = ($mentorName !== '-' && !empty($mentorName)) 
                        ? $mentorName 
                        : ($schoolName !== '-' ? 'Koordinator ' . $schoolName : 'Pendaftar ' . substr($cleanWa, -4));

                    $registrantUser = \App\Models\Registrant::create([
                        'name' => $displayName,
                        'phone' => $cleanWa,
                        'email' => $generatedEmail,
                        'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16)),
                        'registrant_role' => $roleLabel,
                        'school_name' => $schoolName !== '-' ? $schoolName : null,
                        'school_district' => $schoolDistrict ?: null,
                        'gender' => 'Laki-laki',
                        'is_active' => true,
                    ]);
                }
            }
        }

        $registration = Registration::create([
            'registrant_id' => $registrantUser ? $registrantUser->id : null,
            'user_id' => $registrantUser ? $registrantUser->id : null,
            'reg_code' => $regCode,
            'barcode' => $regCode,
            'school_name' => $schoolName,
            'mentor_name' => $mentorName,
            'mentor_wa' => $cleanWa ?: $mentorWa,
            'package_type' => $packageType,
            'competition_id' => $validated['competition_id'],
            'competition_title' => $compTitle,
            'participants_names' => $participantsNames,
            'notes' => $validated['notes'] ?? null,
            'total_fee' => $totalFee,
            'registration_status' => $regStatus,
            'payment_status' => $paymentStatus,
            'checkin_status' => 'belum_hadir',
            'payment_method' => $validated['payment_method'] ?? ('Transfer ' . (collect(Setting::getBankAccounts())->firstWhere('is_primary', true)['bank_name'] ?? Setting::get('payment_bank_name', 'Bank Syariah Indonesia (BSI)'))),
            'custom_fields' => !empty($processedCustomFields) ? $processedCustomFields : null,
            'file_documents' => !empty($fileDocuments) ? $fileDocuments : null,
        ]);

        // Tautkan pendaftaran orphan lama dengan nomor WA atau email yang sama ke akun pendaftar ini
        if ($registrantUser) {
            try {
                $cleanWaDigits = preg_replace('/\D/', '', (string)$registrantUser->phone);
                Registration::where(function ($q) {
                        $q->whereNull('registrant_id')->orWhereNull('user_id');
                    })
                    ->where(function ($q) use ($registrantUser, $cleanWaDigits) {
                        $q->where('mentor_wa', $registrantUser->phone)
                          ->orWhere('mentor_wa', $cleanWaDigits);
                        if (!empty($registrantUser->email) && !str_ends_with($registrantUser->email, '@semarak.id')) {
                            $q->orWhere('custom_fields->mentor_email', $registrantUser->email)
                              ->orWhere('custom_fields->contact_email', $registrantUser->email)
                              ->orWhere('custom_fields->email', $registrantUser->email);
                        }
                    })
                    ->update([
                        'registrant_id' => $registrantUser->id,
                        'user_id' => $registrantUser->id,
                    ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Link orphan registrations to user error: ' . $e->getMessage());
            }
        }

        // Increment competition current quota & auto-close if full
        if ($competition) {
            $competition->increment('current_quota');
            if ($competition->current_quota >= $competition->max_quota) {
                $competition->status = 'closed';
                $competition->save();
            }
        }

        // Auto-sync school name to schools database
        try {
            \App\Models\School::firstOrCreateFromRegistration($schoolName, $mentorWa, $schoolDistrict, $schoolNpsn, $schoolAddress);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Auto sync school error: ' . $e->getMessage());
        }

        // Auto-send WhatsApp notification via OpenWA if enabled
        try {
            \App\Services\WhatsAppService::sendNewRegistrationNotification($registration);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Auto WA notification error: ' . $e->getMessage());
        }

        return redirect()->back()->with([
            'success' => 'Pendaftaran berhasil dikirim!',
            'registered' => $registration,
        ]);
    }
}
