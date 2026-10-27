<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\WaLog;
use App\Models\Registration;
use App\Services\WhatsAppService;
use App\Services\SipresensiService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /**
     * Redirect to the first submenu (School Profile).
     */
    public function index()
    {
        return redirect()->route('admin.settings.school-profile');
    }

    // =========================================================================
    // 1. SUBMENU: KONFIGURASI PROFIL SEKOLAH
    // =========================================================================
    public function schoolProfile(Request $request)
    {
        $settings = [
            'school_name' => Setting::get('school_name', 'MA Unggulan Wahid Hasyim Balung'),
            'school_short_name' => Setting::get('school_short_name', 'MA Wahid Hasyim'),
            'event_edition' => Setting::get('event_edition', 'XIV'),
            'event_year' => Setting::get('event_year', '2026'),
            'event_name' => Setting::get('event_name', 'SEMARAK ALIYAH XIV 2026'),
            'school_tagline' => Setting::get('school_tagline', 'Lembaga Pendidikan Islam Modern Terakreditasi A dengan Program Unggulan Plus Keterampilan, Riset, & Penguasaan Bahasa Asing'),
            'school_address' => Setting::get('school_address', 'Jl. Puger No. 20 Balung Kulon, Kec. Balung, Kabupaten Jember, Jawa Timur 68161'),
            'school_city' => Setting::get('school_city', 'Jember'),
            'school_province' => Setting::get('school_province', 'Jawa Timur'),
            'school_postal_code' => Setting::get('school_postal_code', '68161'),
            'contact_phone' => Setting::get('contact_phone', '(0336) 621123'),
            'contact_wa' => Setting::get('contact_wa', '0838 5007 0767'),
            'contact_email' => Setting::get('contact_email', 'semarak.aliyah@gmail.com'),
            'service_hours' => Setting::get('service_hours', 'Senin - Sabtu : 07.00 - 13.00 WIB'),
            'maps_embed_url' => Setting::get('maps_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3947.8872013898194!2d113.5358055!3d-8.2917778!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd68349f7e8b6b1%3A0x6e9f28d8442c7590!2sMA%20Unggulan%20Wahid%20Hasyim!5e0!3m2!1sid!2sid!4v1710000000000!5m2!1sid!2sid'),
            'social_facebook' => Setting::get('social_facebook', 'https://www.facebook.com/maswahas'),
            'social_instagram' => Setting::get('social_instagram', 'https://www.instagram.com/ma_wahas/'),
            'social_youtube' => Setting::get('social_youtube', 'https://www.youtube.com/@maswahas'),
            'social_tiktok' => Setting::get('social_tiktok', 'https://www.tiktok.com/@maswahas'),
            'school_website' => Setting::get('school_website', 'https://maswahas.sch.id'),
        ];

        return Inertia::render('Admin/Settings/SchoolProfile', compact('settings'));
    }

    public function updateSchoolProfile(Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'school_short_name' => 'required|string|max:100',
            'event_edition' => 'required|string|max:50',
            'event_year' => 'required|string|max:10',
            'event_name' => 'required|string|max:150',
            'school_tagline' => 'nullable|string|max:500',
            'school_address' => 'required|string|max:500',
            'school_city' => 'nullable|string|max:100',
            'school_province' => 'nullable|string|max:100',
            'school_postal_code' => 'nullable|string|max:20',
            'contact_phone' => 'nullable|string|max:50',
            'contact_wa' => 'required|string|max:50',
            'contact_email' => 'required|email|max:150',
            'service_hours' => 'nullable|string|max:150',
            'maps_embed_url' => 'nullable|string|max:1000',
            'social_facebook' => 'nullable|string|max:255',
            'social_instagram' => 'nullable|string|max:255',
            'social_youtube' => 'nullable|string|max:255',
            'social_tiktok' => 'nullable|string|max:255',
            'school_website' => 'nullable|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, (string)($value ?? ''));
        }

        return redirect()->back()->with('success', 'Konfigurasi profil sekolah dan kontak resmi berhasil diperbarui.');
    }

    public function updateEdition(Request $request)
    {
        $validated = $request->validate([
            'event_edition' => 'required|string|max:50',
            'event_year' => 'required|string|max:10',
            'event_name' => 'required|string|max:150',
        ]);

        $edition = strtoupper(trim($validated['event_edition']));
        $year = trim($validated['event_year']);
        $eventName = trim($validated['event_name']);

        Setting::set('event_edition', $edition);
        Setting::set('event_year', $year);
        Setting::set('event_name', $eventName);

        return redirect()->back()->with('success', "Master Event berhasil disetel ke Edisi {$edition} • {$year}!");
    }

    // =========================================================================
    // 2. SUBMENU: SETTING MODE PERAWATAN WEB (MAINTENANCE MODE)
    // =========================================================================
    public function maintenanceMode(Request $request)
    {
        $countdownTarget = Setting::get('maintenance_countdown_target', '');
        $estimatedEnd = Setting::get('maintenance_estimated_end', '');
        if (!empty($countdownTarget)) {
            try {
                $estimatedEnd = \Carbon\Carbon::parse(str_replace('T', ' ', $countdownTarget))->locale('id')->translatedFormat('d F Y, H:i') . ' WIB';
            } catch (\Exception $e) {}
        }

        $rawWa = Setting::get('maintenance_emergency_wa', '0838 5007 0767');
        $rawWaList = Setting::get('maintenance_emergency_wa_list', null);
        $waContacts = [];
        if (!empty($rawWaList)) {
            $decoded = json_decode($rawWaList, true);
            if (is_array($decoded)) {
                $waContacts = array_values(array_filter($decoded, fn($w) => !empty($w['number'])));
            }
        }
        if (empty($waContacts) && !empty($rawWa)) {
            $parts = preg_split('/[\r\n,;\/]+/', $rawWa);
            foreach ($parts as $idx => $part) {
                $trimmed = trim($part);
                if (!empty($trimmed)) {
                    $waContacts[] = [
                        'label' => count($parts) > 1 ? 'Hotline Panitia ' . ($idx + 1) : 'Hotline Panitia',
                        'number' => $trimmed,
                    ];
                }
            }
        }
        if (empty($waContacts)) {
            $waContacts[] = [
                'label' => 'Hotline Panitia',
                'number' => '0838 5007 0767',
            ];
        }

        $settings = [
            'maintenance_mode' => Setting::get('maintenance_mode', '0') === '1',
            'maintenance_title' => Setting::get('maintenance_title', 'Situs Sedang Dalam Pemeliharaan Berkala'),
            'maintenance_message' => Setting::get('maintenance_message', 'Mohon maaf atas ketidaknyamanan ini. Saat ini sistem Semarak Aliyah XIV 2026 sedang dalam proses peningkatan performa dan sinkronisasi data teknis. Website publik akan segera kembali online.'),
            'maintenance_estimated_end' => $estimatedEnd,
            'maintenance_countdown_target' => $countdownTarget,
            'maintenance_emergency_wa' => $waContacts[0]['number'] ?? '0838 5007 0767',
            'maintenance_emergency_wa_list' => $waContacts,
            'maintenance_secret_key' => Setting::get('maintenance_secret_key', 'semarak2026bypass'),
        ];

        return Inertia::render('Admin/Settings/MaintenanceMode', compact('settings'));
    }

    public function updateMaintenanceMode(Request $request)
    {
        $validated = $request->validate([
            'maintenance_mode' => 'nullable',
            'maintenance_title' => 'required|string|max:255',
            'maintenance_message' => 'required|string|max:1000',
            'maintenance_estimated_end' => 'nullable|string|max:150',
            'maintenance_countdown_target' => 'nullable|string|max:100',
            'maintenance_emergency_wa' => 'nullable|string|max:255',
            'maintenance_emergency_wa_list' => 'nullable|array',
            'maintenance_emergency_wa_list.*.label' => 'nullable|string|max:100',
            'maintenance_emergency_wa_list.*.number' => 'nullable|string|max:50',
            'maintenance_secret_key' => 'nullable|string|max:100',
        ]);

        $target = $validated['maintenance_countdown_target'] ?? '';
        $estimatedEnd = $validated['maintenance_estimated_end'] ?? '';

        if (!empty($target)) {
            try {
                $carbonDate = \Carbon\Carbon::parse(str_replace('T', ' ', $target))->locale('id');
                $estimatedEnd = $carbonDate->translatedFormat('d F Y, H:i') . ' WIB';
            } catch (\Exception $e) {}
        }

        $waList = $request->input('maintenance_emergency_wa_list', []);
        $cleanWaList = [];
        if (is_array($waList)) {
            foreach ($waList as $w) {
                if (!empty($w['number'])) {
                    $cleanWaList[] = [
                        'label' => trim($w['label'] ?? 'Hotline Panitia'),
                        'number' => trim($w['number']),
                    ];
                }
            }
        }
        $primaryWa = !empty($cleanWaList) ? $cleanWaList[0]['number'] : ($validated['maintenance_emergency_wa'] ?? '');

        Setting::set('maintenance_mode', $request->boolean('maintenance_mode') ? '1' : '0');
        Setting::set('maintenance_title', (string)$validated['maintenance_title']);
        Setting::set('maintenance_message', (string)$validated['maintenance_message']);
        Setting::set('maintenance_estimated_end', (string)$estimatedEnd);
        Setting::set('maintenance_countdown_target', (string)$target);
        Setting::set('maintenance_emergency_wa', (string)$primaryWa);
        Setting::set('maintenance_emergency_wa_list', json_encode($cleanWaList));
        Setting::set('maintenance_secret_key', (string)($validated['maintenance_secret_key'] ?? 'semarak2026bypass'));

        $statusText = $request->boolean('maintenance_mode') ? 'DIAKTIFKAN' : 'DINONAKTIFKAN';
        return redirect()->back()->with('success', "Mode perawatan website berhasil {$statusText}.");
    }

    // =========================================================================
    // 3. SUBMENU: SETTING SEO & OPEN GRAPH UNTUK WEB
    // =========================================================================
    public function seoOg(Request $request)
    {
        $settings = [
            'seo_meta_title' => Setting::get('seo_meta_title', 'SEMARAK ALIYAH XII 2026 - MA Unggulan Wahid Hasyim Balung'),
            'seo_meta_description' => Setting::get('seo_meta_description', 'Ajang kompetisi tahunan bergengsi siswa/i SMP/MTs sederajat se-Kabupaten Jember. Raih piala juara, piagam resmi, uang pembinaan jutaan rupiah, dan beasiswa tabungan pendidikan.'),
            'seo_meta_keywords' => Setting::get('seo_meta_keywords', 'semarak aliyah, ma wahid hasyim balung, olimpiade mipa jember, olimpiade ips jember, storytelling, festival albanjari, singer lagu islami, voli jember, mobile legends, kompetisi smp mts'),
            'seo_author' => Setting::get('seo_author', 'Panitia Semarak Aliyah XII 2026 • MA Unggulan Wahid Hasyim Balung'),
            'seo_canonical_url' => Setting::get('seo_canonical_url', 'http://localhost:8000'),
            'seo_robots' => Setting::get('seo_robots', 'index, follow'),
            'seo_og_title' => Setting::get('seo_og_title', 'SEMARAK ALIYAH XII 2026 - Panggung Prestasi Pelajar SMP/MTs'),
            'seo_og_description' => Setting::get('seo_og_description', 'Daftarkan delegasi sekolah/madrasah Anda di ajang Semarak Aliyah XII 2026. Kompetisi Sains, Seni, Religi, Olahraga, dan E-Sport!'),
            'seo_og_image' => Setting::get('seo_og_image', '/assets/img/og-thumbnail.jpg'),
            'seo_twitter_card' => Setting::get('seo_twitter_card', 'summary_large_image'),
            'seo_theme_color' => Setting::get('seo_theme_color', '#8b5cf6'),
        ];

        return Inertia::render('Admin/Settings/SeoOpenGraph', compact('settings'));
    }

    public function updateSeoOg(Request $request)
    {
        $validated = $request->validate([
            'seo_meta_title' => 'required|string|max:150',
            'seo_meta_description' => 'required|string|max:350',
            'seo_meta_keywords' => 'nullable|string|max:500',
            'seo_author' => 'nullable|string|max:150',
            'seo_canonical_url' => 'nullable|string|max:255',
            'seo_robots' => ['required', 'string', Rule::in(['index, follow', 'noindex, follow', 'index, nofollow', 'noindex, nofollow'])],
            'seo_og_title' => 'required|string|max:150',
            'seo_og_description' => 'required|string|max:350',
            'seo_og_image' => 'nullable|string|max:500',
            'seo_og_image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'seo_twitter_card' => 'required|string|in:summary,summary_large_image',
            'seo_theme_color' => 'nullable|string|max:30',
        ]);

        if ($request->hasFile('seo_og_image_file')) {
            $file = $request->file('seo_og_image_file');
            $filename = 'og_' . time() . '.jpg';
            $dir = storage_path('app/public/seo');
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            $targetPath = $dir . DIRECTORY_SEPARATOR . $filename;
            
            $optimized = false;
            if (extension_loaded('gd')) {
                $srcImg = null;
                $mime = $file->getMimeType();
                $realPath = $file->getRealPath();

                if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
                    $srcImg = @imagecreatefromjpeg($realPath);
                } elseif ($mime === 'image/png') {
                    $srcImg = @imagecreatefrompng($realPath);
                } elseif ($mime === 'image/webp') {
                    $srcImg = @imagecreatefromwebp($realPath);
                }

                if ($srcImg) {
                    $origW = imagesx($srcImg);
                    $origH = imagesy($srcImg);
                    $targetW = 1200;
                    $targetH = 630;
                    $dstImg = imagecreatetruecolor($targetW, $targetH);

                    // Smooth crop to 1200x630 (standard OG aspect ratio 1.91:1)
                    $srcAspect = $origW / $origH;
                    $targetAspect = $targetW / $targetH;
                    if ($srcAspect > $targetAspect) {
                        $newH = $origH;
                        $newW = (int)($origH * $targetAspect);
                        $srcX = (int)(($origW - $newW) / 2);
                        $srcY = 0;
                    } else {
                        $newW = $origW;
                        $newH = (int)($origW / $targetAspect);
                        $srcX = 0;
                        $srcY = (int)(($origH - $newH) / 2);
                    }

                    imagecopyresampled($dstImg, $srcImg, 0, 0, $srcX, $srcY, $targetW, $targetH, $newW, $newH);
                    imagejpeg($dstImg, $targetPath, 85);
                    imagedestroy($srcImg);
                    imagedestroy($dstImg);
                    $optimized = true;
                }
            }

            if (!$optimized) {
                $file->storeAs('seo', $filename, 'public');
            }

            $validated['seo_og_image'] = '/storage/seo/' . $filename;
        }

        foreach ($validated as $key => $value) {
            if ($key !== 'seo_og_image_file') {
                Setting::set($key, (string)($value ?? ''));
            }
        }

        return redirect()->back()->with('success', 'Pengaturan SEO dan Open Graph pratinjau media sosial berhasil disimpan.');
    }

    // =========================================================================
    // 4. SUBMENU: FITUR WHATSAPP GATEWAY (OPENWA PRODUCTION)
    // =========================================================================
    public function waGateway(Request $request)
    {
        $settings = WhatsAppService::getConfig();

        // Retrieve delivery logs with optional filter
        $query = WaLog::latest();

        if ($request->filled('type') && $request->input('type') !== 'all') {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'ilike', "%{$search}%")
                  ->orWhere('recipient_name', 'ilike', "%{$search}%")
                  ->orWhere('message', 'ilike', "%{$search}%");
            });
        }

        $logs = $query->take(100)->get()->map(function ($log) {
            return [
                'id' => $log->id,
                'phone' => $log->phone,
                'recipient_name' => $log->recipient_name,
                'type' => $log->type,
                'message' => $log->message,
                'status' => $log->status,
                'response_code' => $log->response_code,
                'response_data' => $log->response_data,
                'error_message' => $log->error_message,
                'created_at' => $log->created_at ? $log->created_at->format('d/m/Y H:i') : '-',
            ];
        });

        $stats = [
            'total_messages' => WaLog::count(),
            'success_messages' => WaLog::where('status', 'success')->count(),
            'failed_messages' => WaLog::where('status', 'failed')->count(),
            'pending_messages' => WaLog::where('status', 'pending')->count(),
        ];

        $sessions = WhatsAppService::fetchSessions();
        $groups = WhatsAppService::fetchGroups();

        return Inertia::render('Admin/Settings/WaGateway', compact('settings', 'logs', 'stats', 'sessions', 'groups'));
    }

    public function fetchWaSessions(Request $request)
    {
        $sessions = WhatsAppService::fetchSessions(true);
        return response()->json([
            'status' => 'success',
            'data' => $sessions,
        ]);
    }

    public function fetchWaGroups(Request $request)
    {
        $groups = WhatsAppService::fetchGroups(null, true);
        return response()->json([
            'status' => 'success',
            'data' => $groups,
        ]);
    }

    public function updateWaGateway(Request $request)
    {
        $validated = $request->validate([
            'wa_gateway_enabled' => 'required|in:0,1',
            'wa_gateway_provider' => 'required|string|max:50',
            'wa_gateway_url' => 'required|string|max:255',
            'wa_api_key' => 'nullable|string|max:255',
            'wa_sender_session' => 'nullable|string|max:100',
            'wa_endpoint_send' => 'required|string|max:100',
            'wa_endpoint_status' => 'required|string|max:100',
            'wa_auth_header_type' => 'required|string|in:api_key,bearer,x_api_key',
            'wa_chat_id_format' => 'required|string|in:c_us,number',
            'wa_ssl_bypass' => 'nullable|in:0,1',
            'wa_notify_new_registration' => 'required|in:0,1',
            'wa_notify_admin_new_registration' => 'required|in:0,1',
            'wa_admin_notification_numbers' => 'nullable|string|max:255',
            'wa_admin_group_id' => 'nullable|string|max:150',
            'wa_notify_admin_group' => 'nullable|in:0,1',
            'wa_notify_verified_registration' => 'required|in:0,1',
            'wa_notify_verified_payment' => 'required|in:0,1',
            'wa_tpl_new_registration' => 'required|string',
            'wa_tpl_admin_notification' => 'required|string',
            'wa_tpl_verified_registration' => 'required|string',
            'wa_tpl_verified_payment' => 'required|string',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, (string)($value ?? ''));
        }

        return redirect()->back()->with('success', 'Konfigurasi WhatsApp Gateway OpenWA berhasil disimpan.');
    }

    public function testWaConnection(Request $request)
    {
        $result = WhatsAppService::testConnection();
        return response()->json($result);
    }

    public function sendTestWaMessage(Request $request)
    {
        $validated = $request->validate([
            'target_type' => 'nullable|in:personal,group',
            'phone' => 'nullable|string|max:100',
            'group_id' => 'nullable|string|max:150',
            'message' => 'required|string|max:1000',
        ]);

        $targetType = $validated['target_type'] ?? 'personal';
        $target = ($targetType === 'group') ? ($validated['group_id'] ?: $validated['phone']) : $validated['phone'];
        $recipientName = ($targetType === 'group') ? 'Grup WhatsApp Uji Coba' : 'Penerima Uji Coba';

        if (empty($target)) {
            return response()->json([
                'success' => false,
                'message' => ($targetType === 'group') ? 'Silakan pilih atau masukkan ID Grup WhatsApp tujuan.' : 'Nomor WhatsApp tujuan wajib diisi.',
            ], 422);
        }

        $result = WhatsAppService::sendMessage(
            $target,
            $validated['message'],
            'test',
            $recipientName,
            true // bypass disabled check for testing
        );

        return response()->json($result);
    }

    public function resendWaLog(Request $request, int|string $id)
    {
        $log = WaLog::findOrFail($id);

        $result = WhatsAppService::sendMessage(
            $log->phone,
            $log->message,
            $log->type,
            $log->recipient_name,
            true
        );

        if ($result['success']) {
            return redirect()->back()->with('success', "Pesan berhasil dikirim ulang ke {$log->phone}.");
        }

        return redirect()->back()->with('error', "Gagal mengirim ulang: {$result['message']}");
    }

    public function clearWaLogs(Request $request)
    {
        WaLog::query()->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Seluruh riwayat log WhatsApp berhasil dibersihkan.',
            ]);
        }

        return redirect()->back()->with('success', 'Seluruh riwayat log pengiriman WhatsApp berhasil dibersihkan.');
    }

    public function deleteWaLog(Request $request, int|string $id)
    {
        $log = WaLog::find($id);
        if ($log) {
            $log->delete();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Log WhatsApp berhasil dihapus.',
                ]);
            }
            return redirect()->back()->with('success', 'Log pengiriman pesan WhatsApp berhasil dihapus.');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Data log tidak ditemukan.',
            ], 404);
        }

        return redirect()->back()->with('error', 'Log tidak ditemukan.');
    }

    public function sendManualRegistrantWa(Request $request)
    {
        $validated = $request->validate([
            'registration_id' => 'required|exists:registrations,id',
            'message_type' => 'required|in:custom,tpl_reg,tpl_pay,tpl_welcome',
            'custom_message' => 'nullable|string|max:1000',
        ]);

        $reg = Registration::findOrFail($validated['registration_id']);

        if (empty($reg->mentor_wa) || $reg->mentor_wa === '-') {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp koordinator tidak ditemukan untuk pendaftar ini.',
            ], 422);
        }

        $config = WhatsAppService::getConfig();

        switch ($validated['message_type']) {
            case 'tpl_pay':
                $msg = WhatsAppService::parseTemplate($config['tpl_verified_payment'], $reg);
                $type = 'verification_payment_manual';
                break;
            case 'tpl_reg':
                $msg = WhatsAppService::parseTemplate($config['tpl_verified_registration'], $reg);
                $type = 'verification_registration_manual';
                break;
            case 'tpl_welcome':
                $msg = WhatsAppService::parseTemplate($config['tpl_new_registration'], $reg);
                $type = 'new_registration_manual';
                break;
            case 'custom':
            default:
                $msg = trim($validated['custom_message'] ?? '');
                if (empty($msg)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pesan kustom tidak boleh kosong.',
                    ], 422);
                }
                $type = 'manual_custom';
                break;
        }

        $result = WhatsAppService::sendMessage(
            $reg->mentor_wa,
            $msg,
            $type,
            $reg->mentor_name ?: $reg->school_name,
            true
        );

        return response()->json($result);
    }

    // =========================================================================
    // 6. SUBMENU: KEBIJAKAN PENDAFTARAN & AKUN PENDAFTAR
    // =========================================================================
    public function registrationPolicy(Request $request)
    {
        $scheme = $request->getScheme();
        $host = $request->getHost();

        // 1. Tentukan URL Portal Publik (Web Pendaftar)
        $publicBaseUrl = Setting::get('public_portal_url');
        if (empty($publicBaseUrl)) {
            $publicDomain = config('portal.public_domain') ?: env('PUBLIC_DOMAIN');
            if (!empty($publicDomain)) {
                $publicBaseUrl = $scheme . '://' . $publicDomain;
            } elseif (!empty(config('portal.public_url')) && !str_contains(config('portal.public_url'), 'localhost') && !str_contains(config('portal.public_url'), '127.0.0.1')) {
                $publicBaseUrl = config('portal.public_url');
            } elseif (str_contains($host, 'sa-panitia.') || str_contains($host, 'admin-semarak.') || str_contains($host, 'admin.')) {
                $publicHost = str_replace(['sa-panitia.', 'admin-semarak.', 'admin.'], ['semarak.', 'semarak.', ''], $host);
                $publicBaseUrl = $scheme . '://' . $publicHost;
            } else {
                $publicPort = (int) config('portal.public_port', 8000);
                $publicBaseUrl = $scheme . '://' . $host . ($publicPort === 80 || $publicPort === 443 ? '' : ':' . $publicPort);
            }
        }

        // 2. Tentukan URL Portal Admin (Panel Panitia)
        $adminBaseUrl = Setting::get('admin_portal_url');
        if (empty($adminBaseUrl)) {
            $adminDomain = config('portal.admin_domain') ?: env('ADMIN_DOMAIN');
            if (!empty($adminDomain)) {
                $adminBaseUrl = $scheme . '://' . $adminDomain;
            } elseif (!empty(config('portal.admin_url')) && !str_contains(config('portal.admin_url'), 'localhost') && !str_contains(config('portal.admin_url'), '127.0.0.1')) {
                $adminBaseUrl = config('portal.admin_url');
            } else {
                $adminBaseUrl = $request->getSchemeAndHttpHost();
            }
        }

        $callbackUris = [
            'public'      => rtrim($publicBaseUrl, '/') . '/auth/google/callback',
            'public_base' => rtrim($publicBaseUrl, '/'),
            'admin'       => rtrim($adminBaseUrl, '/') . '/admin/auth/google/callback',
            'admin_base'  => rtrim($adminBaseUrl, '/'),
        ];

        $settings = [
            'registration_login_required' => Setting::get('registration_login_required', '0'),
            'google_client_id' => Setting::get('google_client_id', env('GOOGLE_CLIENT_ID', '')),
            'google_client_secret' => Setting::get('google_client_secret', env('GOOGLE_CLIENT_SECRET', '')),
            'allow_google_login' => Setting::get('allow_google_login', '1'),
            'allow_wa_otp_login' => Setting::get('allow_wa_otp_login', '1'),
            'allow_password_login' => Setting::get('allow_password_login', '1'),
            'public_portal_url' => $publicBaseUrl,
        ];

        $stats = [
            'total_registrants' => \App\Models\Registrant::count(),
            'total_submitted_regs' => \App\Models\Registration::count(),
            'total_regs_with_account' => \App\Models\Registration::where(function ($q) {
                $q->whereNotNull('registrant_id')->orWhereNotNull('user_id');
            })->count(),
        ];

        return Inertia::render('Admin/Settings/RegistrationPolicy', compact('settings', 'stats', 'callbackUris'));
    }

    public function updateRegistrationPolicy(Request $request)
    {
        $validated = $request->validate([
            'registration_login_required' => 'required|in:0,1',
            'google_client_id' => 'nullable|string|max:255',
            'google_client_secret' => 'nullable|string|max:255',
            'public_portal_url' => 'nullable|string|max:255',
            'allow_google_login' => 'nullable|in:0,1',
            'allow_wa_otp_login' => 'nullable|in:0,1',
            'allow_password_login' => 'nullable|in:0,1',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, (string)($value ?? '0'));
        }

        return redirect()->back()->with('success', 'Pengaturan kebijakan pendaftaran dan otentikasi akun berhasil disimpan.');
    }

    public function toggleRegistrationLoginRequired(Request $request)
    {
        $current = Setting::get('registration_login_required', '0');
        $new = $current === '1' ? '0' : '1';
        Setting::set('registration_login_required', $new);

        $statusLabel = $new === '1' ? 'Wajib Login' : 'Bebas Langsung Isi Formulir';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'registration_login_required' => $new,
                'message' => "Pengaturan pendaftaran diubah menjadi: {$statusLabel}",
            ]);
        }

        return redirect()->back()->with('success', "Akses pendaftaran berhasil diubah menjadi: {$statusLabel}");
    }

    // =========================================================================
    // 7. SUBMENU: KUSTOMISASI GAMBAR & TAMPILAN LOGIN ADMIN
    // =========================================================================
    public function adminLoginCustomization(Request $request)
    {
        $settings = [
            'admin_login_bg_image' => Setting::get('admin_login_bg_image', '/assets/images/auth-one-bg.jpg'),
            'admin_login_bg_overlay_type' => Setting::get('admin_login_bg_overlay_type', 'gradient_velzon'),
            'admin_login_bg_overlay_opacity' => (int)Setting::get('admin_login_bg_overlay_opacity', 85),
            'admin_login_bg_blur' => (int)Setting::get('admin_login_bg_blur', 0),
            'admin_login_show_wave' => Setting::get('admin_login_show_wave', '1') === '1',
            'admin_login_particles_active' => Setting::get('admin_login_particles_active', '1') === '1',
            'admin_login_title' => Setting::get('admin_login_title', 'Selamat Datang Kembali !'),
            'admin_login_subtitle' => Setting::get('admin_login_subtitle', 'Masuk untuk melanjutkan ke Panel Semarak Aliyah.'),
            'admin_login_recaptcha_enabled' => Setting::get('admin_login_recaptcha_enabled', '0') === '1',
            'recaptcha_version' => Setting::get('recaptcha_version', 'v2_checkbox'),
            'recaptcha_site_key' => Setting::get('recaptcha_site_key', env('RECAPTCHA_SITE_KEY', '')),
            'recaptcha_secret_key' => Setting::get('recaptcha_secret_key', env('RECAPTCHA_SECRET_KEY', '')),
            'admin_login_recaptcha_bypass_local' => Setting::get('admin_login_recaptcha_bypass_local', '1') === '1',
        ];

        $presets = [
            [
                'id' => 'velzon_default',
                'name' => 'Velzon Theme Original',
                'description' => 'Gambar background bawaan resmi tema Velzon',
                'image_url' => '/assets/images/auth-one-bg.jpg',
                'overlay_type' => 'gradient_velzon',
                'overlay_opacity' => 85,
                'blur' => 0,
                'show_wave' => true,
            ],
            [
                'id' => 'panggung_awarding',
                'name' => 'Panggung & Awarding Semarak',
                'description' => 'Foto dokumentasi megah panggung kehormatan dan piala Semarak Aliyah',
                'image_url' => '/assets/img/gallery/gallery_awarding.jpg',
                'overlay_type' => 'dark',
                'overlay_opacity' => 75,
                'blur' => 1,
                'show_wave' => true,
            ],
            [
                'id' => 'kemeriahan_peserta',
                'name' => 'Kemeriahan Suporter & Kontingen',
                'description' => 'Atmosfer antusiasme peserta didik & suporter delegasi madrasah',
                'image_url' => '/assets/img/gallery/gallery_supporters.jpg',
                'overlay_type' => 'dark',
                'overlay_opacity' => 80,
                'blur' => 0,
                'show_wave' => true,
            ],
            [
                'id' => 'sportivitas_turnamen',
                'name' => 'Sportivitas Turnamen Lapangan',
                'description' => 'Dokumentasi kompetisi olahraga dan sportivitas santri madrasah',
                'image_url' => '/assets/img/gallery/gallery_volleyball.jpg',
                'overlay_type' => 'gradient_velzon',
                'overlay_opacity' => 80,
                'blur' => 0,
                'show_wave' => true,
            ],
            [
                'id' => 'olimpiade_akademik',
                'name' => 'Konsentrasi Ujian & Akademik',
                'description' => 'Suasana serius dan kompetitif ruang olimpiade sains & keilmuan',
                'image_url' => '/assets/img/gallery/gallery_olimpiade.jpg',
                'overlay_type' => 'emerald',
                'overlay_opacity' => 85,
                'blur' => 0,
                'show_wave' => true,
            ],
            [
                'id' => 'pamflet_resmi',
                'name' => 'Artwork Brosur & Pamflet Resmi',
                'description' => 'Desain visual maskot dan identitas resmi Semarak Aliyah',
                'image_url' => '/assets/img/pamflet.jpg',
                'overlay_type' => 'dark',
                'overlay_opacity' => 85,
                'blur' => 2,
                'show_wave' => true,
            ],
        ];

        return Inertia::render('Admin/Settings/AdminLoginCustomization', compact('settings', 'presets'));
    }

    public function updateAdminLoginCustomization(Request $request)
    {
        $validated = $request->validate([
            'admin_login_bg_image' => 'nullable|string|max:500',
            'admin_login_bg_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'admin_login_bg_overlay_type' => 'required|string|in:gradient_velzon,dark,emerald,gold,none',
            'admin_login_bg_overlay_opacity' => 'required|integer|min:0|max:100',
            'admin_login_bg_blur' => 'required|integer|min:0|max:20',
            'admin_login_show_wave' => 'nullable|boolean',
            'admin_login_particles_active' => 'nullable|boolean',
            'admin_login_title' => 'nullable|string|max:100',
            'admin_login_subtitle' => 'nullable|string|max:200',
            'admin_login_recaptcha_enabled' => 'nullable|boolean',
            'recaptcha_version' => 'nullable|string|in:v2_checkbox,v3_invisible',
            'recaptcha_site_key' => 'nullable|string|max:200',
            'recaptcha_secret_key' => 'nullable|string|max:200',
            'admin_login_recaptcha_bypass_local' => 'nullable|boolean',
        ]);

        $bgUrl = $validated['admin_login_bg_image'] ?? '/assets/images/auth-one-bg.jpg';

        if ($request->hasFile('admin_login_bg_file')) {
            $file = $request->file('admin_login_bg_file');
            if ($file->isValid()) {
                $ext = $file->getClientOriginalExtension();
                $safeName = 'admin_login_bg_' . time() . '.' . $ext;
                $path = $file->storeAs('uploads/branding', $safeName, 'public');
                $bgUrl = '/storage/' . $path;
            }
        }

        Setting::set('admin_login_bg_image', $bgUrl);
        Setting::set('admin_login_bg_overlay_type', $validated['admin_login_bg_overlay_type']);
        Setting::set('admin_login_bg_overlay_opacity', (string)$validated['admin_login_bg_overlay_opacity']);
        Setting::set('admin_login_bg_blur', (string)$validated['admin_login_bg_blur']);
        Setting::set('admin_login_show_wave', $request->boolean('admin_login_show_wave') ? '1' : '0');
        Setting::set('admin_login_particles_active', $request->boolean('admin_login_particles_active') ? '1' : '0');
        Setting::set('admin_login_title', $validated['admin_login_title'] ?? 'Selamat Datang Kembali !');
        Setting::set('admin_login_subtitle', $validated['admin_login_subtitle'] ?? 'Masuk untuk melanjutkan ke Panel Semarak Aliyah.');
        Setting::set('admin_login_recaptcha_enabled', $request->boolean('admin_login_recaptcha_enabled') ? '1' : '0');
        Setting::set('recaptcha_version', $request->input('recaptcha_version', 'v2_checkbox'));
        Setting::set('recaptcha_site_key', trim((string)$request->input('recaptcha_site_key', '')));
        Setting::set('recaptcha_secret_key', trim((string)$request->input('recaptcha_secret_key', '')));
        Setting::set('admin_login_recaptcha_bypass_local', $request->boolean('admin_login_recaptcha_bypass_local') ? '1' : '0');

        return redirect()->back()->with('success', 'Tampilan background dan gaya halaman login admin berhasil disimpan.');
    }

    public function resetAdminLoginCustomization(Request $request)
    {
        Setting::set('admin_login_bg_image', '/assets/images/auth-one-bg.jpg');
        Setting::set('admin_login_bg_overlay_type', 'gradient_velzon');
        Setting::set('admin_login_bg_overlay_opacity', '85');
        Setting::set('admin_login_bg_blur', '0');
        Setting::set('admin_login_show_wave', '1');
        Setting::set('admin_login_particles_active', '1');
        Setting::set('admin_login_title', 'Selamat Datang Kembali !');
        Setting::set('admin_login_subtitle', 'Masuk untuk melanjutkan ke Panel Semarak Aliyah.');
        Setting::set('admin_login_recaptcha_enabled', '0');

        return redirect()->back()->with('success', 'Tampilan halaman login admin berhasil di-reset ke standar tema Velzon.');
    }

    // =========================================================================
    // 8. SUBMENU: KONFIGURASI API SIPRESENSI (SSO & INTEGRASI GURU)
    // =========================================================================
    public function sipresensiSettings(Request $request)
    {
        $settings = [
            'sipresensi_mode' => Setting::get('sipresensi_mode', 'auto'),
            'sipresensi_api_url' => Setting::get('sipresensi_api_url', env('SIPRESENSI_API_URL', 'http://localhost:8000')),
            'sipresensi_api_token' => Setting::get('sipresensi_api_token', env('SIPRESENSI_API_TOKEN', 'secret_token_123')),
            'sipresensi_path' => Setting::get('sipresensi_path', env('SIPRESENSI_PATH', 'D:\\1Laravel\\sipresensi')),
            'sipresensi_teachers_endpoint' => Setting::get('sipresensi_teachers_endpoint', '/api/v1/integration/pkl/teachers/all'),
            'sipresensi_verify_endpoint' => Setting::get('sipresensi_verify_endpoint', '/api/v1/integration/pkl/verify-credentials'),
            'sipresensi_auto_sync_on_login' => Setting::get('sipresensi_auto_sync_on_login', '1') === '1',
            'sipresensi_timeout' => (int) Setting::get('sipresensi_timeout', 5),
        ];

        $diagnostic = SipresensiService::testConnection();

        return Inertia::render('Admin/Settings/Sipresensi', compact('settings', 'diagnostic'));
    }

    public function updateSipresensiSettings(Request $request)
    {
        $validated = $request->validate([
            'sipresensi_mode' => 'required|string|in:auto,http_api,local_artisan',
            'sipresensi_api_url' => 'required|string|max:500',
            'sipresensi_api_token' => 'nullable|string|max:500',
            'sipresensi_path' => 'nullable|string|max:500',
            'sipresensi_teachers_endpoint' => 'required|string|max:200',
            'sipresensi_verify_endpoint' => 'required|string|max:200',
            'sipresensi_auto_sync_on_login' => 'nullable|boolean',
            'sipresensi_timeout' => 'required|integer|min:2|max:60',
        ], [
            'sipresensi_mode.required' => 'Mode integrasi wajib dipilih.',
            'sipresensi_api_url.required' => 'Base URL API Sipresensi wajib diisi.',
            'sipresensi_teachers_endpoint.required' => 'Endpoint data guru wajib diisi.',
            'sipresensi_verify_endpoint.required' => 'Endpoint verifikasi SSO wajib diisi.',
            'sipresensi_timeout.required' => 'Timeout koneksi wajib diisi.',
        ]);

        Setting::set('sipresensi_mode', $validated['sipresensi_mode']);
        Setting::set('sipresensi_api_url', rtrim(trim($validated['sipresensi_api_url']), '/'));
        Setting::set('sipresensi_api_token', trim((string)($validated['sipresensi_api_token'] ?? '')));
        Setting::set('sipresensi_path', trim((string)($validated['sipresensi_path'] ?? '')));
        Setting::set('sipresensi_teachers_endpoint', '/' . ltrim(trim($validated['sipresensi_teachers_endpoint']), '/'));
        Setting::set('sipresensi_verify_endpoint', '/' . ltrim(trim($validated['sipresensi_verify_endpoint']), '/'));
        Setting::set('sipresensi_auto_sync_on_login', $request->boolean('sipresensi_auto_sync_on_login') ? '1' : '0');
        Setting::set('sipresensi_timeout', (string)$validated['sipresensi_timeout']);

        return redirect()->back()->with('success', 'Konfigurasi integrasi API Sipresensi berhasil disimpan.');
    }

    public function testSipresensiConnection(Request $request)
    {
        $diagnostic = SipresensiService::testConnection();
        return response()->json([
            'success' => true,
            'diagnostic' => $diagnostic,
        ]);
    }

    public function testSipresensiSync(Request $request)
    {
        $result = SipresensiService::fetchTeachers();
        return response()->json($result);
    }

    // =========================================================================
    // 9. SUBMENU: INFORMASI SISTEM & KINERJA SERVER
    // =========================================================================
    public function systemInformation(Request $request)
    {
        // 1. PHP Runtime
        $opcacheEnabled = extension_loaded('Zend OPcache') && (bool) ini_get('opcache.enable');
        $opcacheStats = null;
        if ($opcacheEnabled && function_exists('opcache_get_status')) {
            $rawOpcache = @opcache_get_status(false);
            if (is_array($rawOpcache)) {
                $opcacheStats = [
                    'used_memory'    => isset($rawOpcache['memory_usage']['used_memory']) ? round($rawOpcache['memory_usage']['used_memory'] / 1024 / 1024, 2) . ' MB' : null,
                    'free_memory'    => isset($rawOpcache['memory_usage']['free_memory']) ? round($rawOpcache['memory_usage']['free_memory'] / 1024 / 1024, 2) . ' MB' : null,
                    'hit_rate'       => isset($rawOpcache['opcache_statistics']['opcache_hit_rate']) ? round($rawOpcache['opcache_statistics']['opcache_hit_rate'], 1) . '%' : null,
                    'cached_scripts' => $rawOpcache['opcache_statistics']['num_cached_scripts'] ?? 0,
                    'jit_enabled'    => isset($rawOpcache['jit']['enabled']) ? (bool) $rawOpcache['jit']['enabled'] : false,
                ];
            }
        }

        $phpInfo = [
            'version'             => PHP_VERSION,
            'zts'                 => (defined('PHP_ZTS') && PHP_ZTS) ? 'ZTS (Zend Thread Safety)' : 'NTS (Non-Thread Safe)',
            'sapi'                => php_sapi_name(),
            'os'                  => PHP_OS_FAMILY . ' (' . php_uname('s') . ' ' . php_uname('r') . ')',
            'architecture'        => PHP_INT_SIZE === 8 ? '64-bit (x86_64)' : '32-bit',
            'memory_limit'        => ini_get('memory_limit') ?: 'N/A',
            'current_memory'      => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'peak_memory'         => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            'max_execution_time'  => (ini_get('max_execution_time') ?: '0') . ' detik',
            'upload_max_filesize' => ini_get('upload_max_filesize') ?: 'N/A',
            'post_max_size'       => ini_get('post_max_size') ?: 'N/A',
            'timezone'            => date_default_timezone_get(),
            'server_time'         => now()->format('d/m/Y H:i:s') . ' WIB',
            'opcache_enabled'     => $opcacheEnabled,
            'opcache_stats'       => $opcacheStats,
        ];

        // 2. Framework & Server Engine
        $frameworkInfo = [
            'laravel_version' => app()->version(),
            'octane_server'   => env('OCTANE_SERVER', config('octane.server', 'None')),
            'app_env'         => config('app.env'),
            'app_debug'       => (bool) config('app.debug'),
            'app_url'         => config('app.url'),
            'app_locale'      => config('app.locale'),
            'app_timezone'    => config('app.timezone', 'Asia/Jakarta'),
            'maintenance'     => app()->isDownForMaintenance(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? (php_sapi_name() === 'frankenphp' ? 'FrankenPHP / Caddy' : (php_sapi_name() === 'cli-server' ? 'PHP Development Server' : 'Web Server Engine')),
            'config_cached'   => app()->configurationIsCached(),
            'routes_cached'   => app()->routesAreCached(),
            'events_cached'   => app()->eventsAreCached(),
        ];

        // 3. Database Status (PostgreSQL)
        $dbConnection = config('database.default');
        $dbStatus = 'Terputus';
        $dbVersion = 'Unknown';
        $dbSize = 'Unknown';
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            $dbStatus = 'Terhubung';
            if ($dbConnection === 'pgsql') {
                $rawVer = \Illuminate\Support\Facades\DB::select("SELECT version() as ver");
                $dbVersion = $rawVer[0]->ver ?? 'PostgreSQL';
                $rawSize = \Illuminate\Support\Facades\DB::select("SELECT pg_size_pretty(pg_database_size(current_database())) as size");
                $dbSize = $rawSize[0]->size ?? 'N/A';
            } elseif ($dbConnection === 'mysql') {
                $rawVer = \Illuminate\Support\Facades\DB::select("SELECT VERSION() as ver");
                $dbVersion = $rawVer[0]->ver ?? 'MySQL';
            }
        } catch (\Throwable $e) {
            $dbStatus = 'Gagal: ' . $e->getMessage();
        }

        $dbInfo = [
            'default_driver' => $dbConnection,
            'status'         => $dbStatus,
            'database_name'  => config("database.connections.{$dbConnection}.database"),
            'host'           => config("database.connections.{$dbConnection}.host"),
            'version'        => $dbVersion,
            'size'           => $dbSize,
        ];

        // 4. Cache, Queue & Redis
        $redisStatus = 'Tidak Dikonfigurasi';
        if (config('cache.default') === 'redis' || config('queue.default') === 'redis' || extension_loaded('redis')) {
            try {
                \Illuminate\Support\Facades\Redis::connection()->ping();
                $redisStatus = 'Aktif & Terhubung';
            } catch (\Throwable $e) {
                $redisStatus = 'Gagal Terhubung: ' . $e->getMessage();
            }
        }

        $cacheInfo = [
            'cache_driver'   => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_driver'   => config('queue.default'),
            'redis_status'   => $redisStatus,
            'broadcast'      => config('broadcasting.default'),
        ];

        // 5. Storage & Disk Status
        $storageWritable = is_writable(storage_path());
        $bootstrapCacheWritable = is_writable(base_path('bootstrap/cache'));
        $publicWritable = is_writable(public_path());

        $diskFree = @disk_free_space(base_path());
        $diskTotal = @disk_total_space(base_path());
        $diskUsedPercent = ($diskTotal && $diskFree) ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 1) : 0;

        $storageInfo = [
            'storage_path_writable'    => $storageWritable,
            'bootstrap_cache_writable' => $bootstrapCacheWritable,
            'public_path_writable'     => $publicWritable,
            'disk_free'                => $diskFree ? round($diskFree / 1024 / 1024 / 1024, 2) . ' GB' : 'N/A',
            'disk_total'               => $diskTotal ? round($diskTotal / 1024 / 1024 / 1024, 2) . ' GB' : 'N/A',
            'disk_used_percent'        => $diskUsedPercent,
        ];

        // 6. Critical PHP Extensions
        $requiredExtensions = [
            'pdo_pgsql' => ['label' => 'PDO PostgreSQL', 'desc' => 'Driver utama koneksi PostgreSQL', 'required' => true],
            'redis'     => ['label' => 'PHPRedis', 'desc' => 'Driver performa tinggi Cache & Antrean', 'required' => false],
            'imagick'   => ['label' => 'ImageMagick', 'desc' => 'Pemrosesan pamflet, piagam & sertifikat', 'required' => false],
            'gd'        => ['label' => 'GD Library', 'desc' => 'Pemrosesan grafis sekunder', 'required' => true],
            'curl'      => ['label' => 'cURL', 'desc' => 'Komunikasi HTTP API & Webhook', 'required' => true],
            'mbstring'  => ['label' => 'Multibyte String', 'desc' => 'Encoding teks UTF-8', 'required' => true],
            'openssl'   => ['label' => 'OpenSSL', 'desc' => 'Enkripsi data & token keamanan', 'required' => true],
            'zip'       => ['label' => 'Zip Archive', 'desc' => 'Ekspor berkas terkompresi & arsip', 'required' => true],
            'bcmath'    => ['label' => 'BCMath', 'desc' => 'Kalkulasi keuangan presisi tinggi', 'required' => true],
            'fileinfo'  => ['label' => 'FileInfo', 'desc' => 'Deteksi MIME type unggahan berkas', 'required' => true],
        ];

        $extensionsList = [];
        foreach ($requiredExtensions as $ext => $meta) {
            $extensionsList[] = [
                'name'      => $ext,
                'label'     => $meta['label'],
                'desc'      => $meta['desc'],
                'required'  => $meta['required'],
                'installed' => extension_loaded($ext),
            ];
        }

        return Inertia::render('Admin/Settings/SystemInformation', [
            'phpInfo'        => $phpInfo,
            'frameworkInfo'  => $frameworkInfo,
            'dbInfo'         => $dbInfo,
            'cacheInfo'      => $cacheInfo,
            'storageInfo'    => $storageInfo,
            'extensionsList' => $extensionsList,
        ]);
    }

    public function clearSystemCache(Request $request)
    {
        $type = $request->input('type', 'all');

        try {
            switch ($type) {
                case 'application':
                    \Illuminate\Support\Facades\Artisan::call('cache:clear');
                    $msg = 'Cache data aplikasi berhasil dibersihkan.';
                    break;
                case 'route':
                    \Illuminate\Support\Facades\Artisan::call('route:clear');
                    $msg = 'Cache rute URL berhasil dibersihkan.';
                    break;
                case 'view':
                    \Illuminate\Support\Facades\Artisan::call('view:clear');
                    $msg = 'Cache template Blade & tampilan berhasil dibersihkan.';
                    break;
                case 'config':
                    \Illuminate\Support\Facades\Artisan::call('config:clear');
                    $msg = 'Cache file konfigurasi berhasil dibersihkan.';
                    break;
                case 'all':
                default:
                    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                    $msg = 'Seluruh cache sistem (Route, Cache, Config, View) berhasil dibersihkan!';
                    break;
            }

            return redirect()->back()->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal membersihkan cache: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 11. SUBMENU: PENYESUAIAN KOP SURAT, LOGO, & STEMPEL INVOICE RESMI
    // =========================================================================
    public function invoiceKopCustomization(Request $request)
    {
        $config = Setting::getInvoiceConfig();
        $sampleRegistration = Registration::latest()->first();
        $sampleId = $sampleRegistration ? $sampleRegistration->id : 1;

        return Inertia::render('Admin/Settings/InvoiceCustomization', [
            'config' => $config,
            'sampleId' => $sampleId,
        ]);
    }

    public function updateInvoiceKopCustomization(Request $request)
    {
        $validated = $request->validate([
            'logo_left' => 'nullable|string',
            'logo_right' => 'nullable|string',
            'show_logo_right' => 'nullable|boolean',
            'logo_size' => 'nullable|integer|min:40|max:120',
            'logo_left_file' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'logo_right_file' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'stamp_image_file' => 'nullable|image|mimes:png,webp|max:2048',
            'signature_image_file' => 'nullable|image|mimes:png,webp|max:2048',
            'kop_foundation' => 'nullable|string|max:255',
            'kop_committee' => 'nullable|string|max:255',
            'kop_school' => 'nullable|string|max:255',
            'kop_accreditation' => 'nullable|string|max:255',
            'kop_address' => 'nullable|string|max:500',
            'kop_contact' => 'nullable|string|max:500',
            'stamp_mode' => 'required|string|in:digital,image',
            'stamp_top_text' => 'nullable|string|max:100',
            'stamp_bottom_text' => 'nullable|string|max:100',
            'stamp_center_text' => 'nullable|string|max:50',
            'stamp_color' => 'nullable|string|max:20',
            'stamp_rotation' => 'nullable|integer|min:-30|max:30',
            'sig_city' => 'required|string|max:100',
            'treasurer_name' => 'required|string|max:150',
            'treasurer_role' => 'required|string|max:150',
            'treasurer_subrole' => 'nullable|string|max:150',
            'footer_note' => 'nullable|string|max:500',
        ]);

        // Upload Logo Kiri
        if ($request->hasFile('logo_left_file')) {
            $file = $request->file('logo_left_file');
            if ($file->isValid()) {
                $ext = $file->getClientOriginalExtension();
                $path = $file->storeAs('uploads/invoice', 'invoice_logo_left_' . time() . '.' . $ext, 'public');
                Setting::set('invoice_logo_left', '/storage/' . $path);
            }
        } elseif ($request->filled('logo_left')) {
            Setting::set('invoice_logo_left', $request->input('logo_left'));
        }

        // Upload Logo Kanan
        if ($request->hasFile('logo_right_file')) {
            $file = $request->file('logo_right_file');
            if ($file->isValid()) {
                $ext = $file->getClientOriginalExtension();
                $path = $file->storeAs('uploads/invoice', 'invoice_logo_right_' . time() . '.' . $ext, 'public');
                Setting::set('invoice_logo_right', '/storage/' . $path);
            }
        } elseif ($request->filled('logo_right')) {
            Setting::set('invoice_logo_right', $request->input('logo_right'));
        }

        // Upload Stempel Gambar Basah (PNG Transparan)
        if ($request->hasFile('stamp_image_file')) {
            $file = $request->file('stamp_image_file');
            if ($file->isValid()) {
                $ext = $file->getClientOriginalExtension();
                $path = $file->storeAs('uploads/invoice', 'invoice_stamp_' . time() . '.' . $ext, 'public');
                Setting::set('invoice_stamp_image', '/storage/' . $path);
            }
        } elseif ($request->has('remove_stamp_image') && $request->boolean('remove_stamp_image')) {
            Setting::set('invoice_stamp_image', '');
        }

        // Upload Tanda Tangan Gambar (PNG Transparan)
        if ($request->hasFile('signature_image_file')) {
            $file = $request->file('signature_image_file');
            if ($file->isValid()) {
                $ext = $file->getClientOriginalExtension();
                $path = $file->storeAs('uploads/invoice', 'invoice_signature_' . time() . '.' . $ext, 'public');
                Setting::set('treasurer_signature_image', '/storage/' . $path);
            }
        } elseif ($request->has('remove_signature_image') && $request->boolean('remove_signature_image')) {
            Setting::set('treasurer_signature_image', '');
        }

        Setting::set('invoice_show_logo_right', $request->boolean('show_logo_right') ? '1' : '0');
        Setting::set('invoice_logo_size', (string)($validated['logo_size'] ?? 68));
        Setting::set('invoice_kop_foundation', $validated['kop_foundation'] ?? '');
        Setting::set('invoice_kop_committee', $validated['kop_committee'] ?? '');
        Setting::set('invoice_kop_school', $validated['kop_school'] ?? '');
        Setting::set('invoice_kop_accreditation', $validated['kop_accreditation'] ?? '');
        Setting::set('invoice_kop_address', $validated['kop_address'] ?? '');
        Setting::set('invoice_kop_contact', $validated['kop_contact'] ?? '');

        Setting::set('invoice_stamp_mode', $validated['stamp_mode']);
        Setting::set('invoice_stamp_top_text', $validated['stamp_top_text'] ?? 'SEMARAK ALIYAH XIV');
        Setting::set('invoice_stamp_bottom_text', $validated['stamp_bottom_text'] ?? 'MA WAHID HASYIM');
        Setting::set('invoice_stamp_center_text', $validated['stamp_center_text'] ?? '★');
        Setting::set('invoice_stamp_color', $validated['stamp_color'] ?? '#2563eb');
        Setting::set('invoice_stamp_rotation', (string)($validated['stamp_rotation'] ?? -8));

        Setting::set('invoice_sig_city', $validated['sig_city']);
        Setting::set('treasurer_name', $validated['treasurer_name']);
        Setting::set('treasurer_role', $validated['treasurer_role']);
        Setting::set('treasurer_subrole', $validated['treasurer_subrole'] ?? '');
        Setting::set('invoice_footer_note', $validated['footer_note'] ?? '');

        return redirect()->back()->with('success', 'Konfigurasi KOP Surat, Logo, dan Stempel Invoice resmi berhasil diperbarui!');
    }

    public function resetInvoiceKopCustomization(Request $request)
    {
        $keys = [
            'invoice_logo_left' => '/assets/img/logo.png',
            'invoice_logo_right' => '/assets/img/logo.png',
            'invoice_show_logo_right' => '1',
            'invoice_logo_size' => '68',
            'invoice_kop_foundation' => 'YAYASAN PONDOK PESANTREN WAHID HASYIM',
            'invoice_kop_committee' => 'PANITIA PELAKSANA SEMARAK ALIYAH XIV TAHUN 2026',
            'invoice_kop_school' => 'MADRASAH ALIYAH UNGGULAN WAHID HASYIM BALUNG',
            'invoice_kop_accreditation' => 'STATUS TERAKREDITASI "A" • NSM: 131235090014 • NPSN: 69941846',
            'invoice_kop_address' => 'Jl. Balung - Tutul No. 09 Balung Kidul, Kec. Balung, Kab. Jember, Jawa Timur 68161',
            'invoice_kop_contact' => 'Website: semarak.ma-wh.sch.id • Email: semarak@ma-wh.sch.id • Hotline WA: 0838 5007 0767',
            'invoice_stamp_mode' => 'digital',
            'invoice_stamp_top_text' => 'SEMARAK ALIYAH XIV',
            'invoice_stamp_bottom_text' => 'MA WAHID HASYIM',
            'invoice_stamp_center_text' => '★',
            'invoice_stamp_color' => '#2563eb',
            'invoice_stamp_image' => '',
            'invoice_stamp_rotation' => '-8',
            'invoice_sig_city' => 'Balung - Jember',
            'treasurer_name' => 'M. Irfan Zidni, S.Pd.',
            'treasurer_role' => 'Bendahara Pelaksana',
            'treasurer_subrole' => 'Semarak Aliyah XIV Tahun 2026',
            'treasurer_signature_image' => '',
            'invoice_footer_note' => 'Dokumen ini diterbitkan secara sah dan otomatis oleh Sistem Informasi Semarak Aliyah XIV MA Unggulan Wahid Hasyim Balung.',
        ];

        foreach ($keys as $k => $v) {
            Setting::set($k, $v);
        }

        return redirect()->back()->with('success', 'Konfigurasi KOP, Logo, dan Stempel berhasil dikembalikan ke standar awal.');
    }
}
