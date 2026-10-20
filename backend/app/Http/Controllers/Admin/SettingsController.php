<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
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
            'event_name' => Setting::get('event_name', 'SEMARAK ALIYAH XII 2026'),
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
}
