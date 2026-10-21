<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Competition;
use App\Models\Registration;
use App\Models\Sponsor;
use App\Models\Gallery;
use App\Models\Setting;
use App\Models\Faq;

class PublicPortalController extends Controller
{
    public function home()
    {
        $competitions = Competition::orderBy('id')->get();

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
            'bank_name' => Setting::get('payment_bank_name', 'Bank Syariah Indonesia (BSI)'),
            'account_number' => Setting::get('payment_account_number', '7123456789'),
            'account_holder' => Setting::get('payment_account_holder', 'Semarak Aliyah'),
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

        return Inertia::render('Public/Home', compact('competitions', 'sponsors', 'sponsorSettings', 'galleries', 'gallerySettings', 'tickerSettings', 'faqs', 'faqSettings', 'pricingSettings', 'tmScheduleSettings'));
    }

    public function register(Request $request)
    {
        $competitions = Competition::orderBy('id')->get();
        $preselectedLomba = $request->query('lomba', 'mipa');

        $sumCompFees = (int)Competition::sum('fee');
        $isAutoOriginalPrice = Setting::get('package_allin_auto_original_price', '1') === '1';
        $allinOriginalPrice = $isAutoOriginalPrice ? $sumCompFees : (int)Setting::get('package_allin_original_price', $sumCompFees);
        if ($allinOriginalPrice <= 0) {
            $allinOriginalPrice = $sumCompFees ?: 400000;
        }

        $pricingSettings = [
            'allin_is_active' => Setting::get('package_allin_is_active', '1') === '1',
            'allin_name' => Setting::get('package_allin_name', 'Paket All-In Komplit'),
            'allin_price' => (int)Setting::get('package_allin_price', 200000),
            'allin_original_price' => $allinOriginalPrice,
            'allin_tag' => Setting::get('package_allin_tag', 'Paling Hemat • Rekomendasi'),
            'allin_desc' => Setting::get('package_allin_desc', 'Pilihan terbaik & paling hemat! Daftarkan kontingen sekolah Anda ke seluruh 8 cabang lomba sekaligus hanya dengan setengah harga.'),
            'allin_features' => json_decode(Setting::get('package_allin_features', '[]'), true) ?: [],
            'bank_name' => Setting::get('payment_bank_name', 'Bank Syariah Indonesia (BSI)'),
            'account_number' => Setting::get('payment_account_number', '7123456789'),
            'account_holder' => Setting::get('payment_account_holder', 'Semarak Aliyah'),
            'confirmation_wa' => Setting::get('payment_confirmation_wa', '0838 5007 0767'),
            'instructions' => Setting::get('payment_instructions', ''),
            'cash_enabled' => Setting::get('payment_cash_enabled', '1') === '1',
        ];

        return Inertia::render('Public/Register', compact('competitions', 'preselectedLomba', 'pricingSettings'));
    }

    public function storeRegistration(Request $request)
    {
        $validated = $request->validate([
            'competition_id' => 'required|string',
            'package_type' => 'nullable|string',
            'notes' => 'nullable|string',
            'payment_method' => 'nullable|string',
        ]);

        $competition = Competition::find($validated['competition_id']);
        if ($competition) {
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
                'participants_names' => 'required|string',
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
                    $uploadedFile = $request->file("custom_files.{$fName}") 
                        ?: ($request->file("custom_fields.{$fName}") ?: $request->file($fName));

                    if ($uploadedFile && $uploadedFile->isValid()) {
                        $maxMb = !empty($field['max_size_mb']) ? (int)$field['max_size_mb'] : 10;
                        if ($uploadedFile->getSize() > ($maxMb * 1024 * 1024)) {
                            $customErrors["cf_{$fName}"] = "Ukuran file \"{$fLabel}\" melebihi batas maksimal {$maxMb}MB.";
                            continue;
                        }

                        $ext = strtolower($uploadedFile->getClientOriginalExtension());
                        $dangerousExtensions = ['php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'phps', 'sh', 'bash', 'exe', 'bat', 'cmd', 'cgi', 'pl', 'py', 'js', 'html', 'htm', 'htaccess'];
                        if (in_array($ext, $dangerousExtensions)) {
                            $customErrors["cf_{$fName}"] = "Tipe file \"{$ext}\" tidak diizinkan demi keamanan sistem.";
                            continue;
                        }

                        $defaultAllowed = ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'doc', 'zip'];
                        $allowedExts = !empty($field['allowed_extensions']) && is_array($field['allowed_extensions'])
                            ? array_map('strtolower', $field['allowed_extensions'])
                            : $defaultAllowed;

                        if (!in_array($ext, $allowedExts)) {
                            $customErrors["cf_{$fName}"] = "Format berkas \"{$fLabel}\" harus berupa: " . implode(', ', $allowedExts) . ".";
                            continue;
                        }

                        $safeOriginalName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $uploadedFile->getClientOriginalName());
                        $storagePath = $uploadedFile->storeAs("uploads/registrations/{$regCode}", time() . "_{$safeOriginalName}", 'public');
                        $fileUrl = '/storage/' . $storagePath;

                        $fileMeta = [
                            'type' => 'file',
                            'label' => $fLabel,
                            'name' => $uploadedFile->getClientOriginalName(),
                            'url' => $fileUrl,
                            'size' => $uploadedFile->getSize(),
                            'extension' => $ext,
                        ];

                        $processedCustomFields[$fName] = $fileMeta;
                        $fileDocuments[] = [
                            'title' => $fLabel,
                            'url' => $fileUrl,
                            'filename' => $uploadedFile->getClientOriginalName(),
                            'type' => $ext,
                            'uploaded_at' => now()->toIso8601String(),
                        ];
                    } elseif ($isRequired) {
                        $customErrors["cf_{$fName}"] = "Dokumen berkas \"{$fLabel}\" wajib diunggah.";
                    }
                } else {
                    $val = $rawCustomFields[$fName] ?? $request->input($fName);
                    if ($isRequired && (is_null($val) || $val === '' || (is_array($val) && empty($val)))) {
                        $customErrors["cf_{$fName}"] = "Kolom \"{$fLabel}\" wajib diisi.";
                    } else {
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

        $packageType = $request->input('package_type') ?? 'Paket Satuan';

        // Total fee calculation
        $totalFee = $packageType === 'Paket All-In' 
            ? (int)Setting::get('package_allin_price', 200000) 
            : ($competition ? (int)$competition->fee : 50000);

        $registration = Registration::create([
            'reg_code' => $regCode,
            'barcode' => $regCode,
            'school_name' => $schoolName,
            'mentor_name' => $mentorName,
            'mentor_wa' => $mentorWa,
            'package_type' => $packageType,
            'competition_id' => $validated['competition_id'],
            'competition_title' => $compTitle,
            'participants_names' => $participantsNames,
            'notes' => $validated['notes'] ?? null,
            'total_fee' => $totalFee,
            'payment_status' => 'Menunggu Verifikasi',
            'checkin_status' => 'belum_hadir',
            'payment_method' => $validated['payment_method'] ?? 'Transfer Bank BSI',
            'custom_fields' => !empty($processedCustomFields) ? $processedCustomFields : null,
            'file_documents' => !empty($fileDocuments) ? $fileDocuments : null,
        ]);

        // Increment competition current quota & auto-close if full
        if ($competition) {
            $competition->increment('current_quota');
            if ($competition->current_quota >= $competition->max_quota) {
                $competition->status = 'closed';
                $competition->save();
            }
        }

        return redirect()->back()->with([
            'success' => 'Pendaftaran berhasil dikirim!',
            'registered' => $registration,
        ]);
    }
}
