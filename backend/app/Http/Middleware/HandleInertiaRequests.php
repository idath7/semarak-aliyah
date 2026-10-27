<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => function () use ($request) {
                    $isAdminOrOperasionalRoute = $request->is('admin', 'admin/*', 'operasional', 'operasional/*');
                    if ($isAdminOrOperasionalRoute) {
                        $user = \Illuminate\Support\Facades\Auth::guard('web')->user();
                        if ($user) {
                            $user->loadMissing('roleRelation');
                            return [
                                'id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email,
                                'nip' => $user->nip,
                                'phone' => $user->phone,
                                'avatar' => $user->avatar_url,
                                'role' => $user->role,
                                'role_label' => $user->roleRelation?->label ?? ucfirst(str_replace('_', ' ', $user->role)),
                                'role_badge' => $user->roleRelation?->badge_color ?? 'primary',
                                'division' => $user->division,
                                'is_super_admin' => $user->isSuperAdmin(),
                                'permissions' => $user->allPermissions(),
                            ];
                        }
                        return null;
                    }
                    // Di portal pendaftar dan web publik, gunakan akun guard pendaftar
                    return \Illuminate\Support\Facades\Auth::guard('pendaftar')->user();
                },
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'registrationLoginRequired' => \App\Models\Setting::get('registration_login_required', '0') === '1',
            'reverb' => [
                'key' => config('reverb.apps.apps.0.key') ?? env('REVERB_APP_KEY', 'semarak-key-2026'),
                'host' => $request->getHost(),
                'port' => $request->isSecure() ? 443 : (int) env('REVERB_PORT', 8085),
                'scheme' => $request->isSecure() ? 'https' : 'http',
            ],
            'portal' => function () use ($request) {
                $scheme = $request->getScheme();
                $host = $request->getHost();
                $adminPort = (int) config('portal.admin_port', 8001);
                $publicPort = (int) config('portal.public_port', 8000);

                $adminDomain = config('portal.admin_domain') ?: env('ADMIN_DOMAIN');
                $publicDomain = config('portal.public_domain') ?: env('PUBLIC_DOMAIN');

                // 1. Resolusi URL Publik (Web Pendaftar)
                $publicUrl = \App\Models\Setting::get('public_portal_url');
                if (empty($publicUrl)) {
                    if (!empty($publicDomain)) {
                        $publicUrl = $scheme . '://' . $publicDomain;
                    } elseif (!empty(config('portal.public_url')) && !str_contains(config('portal.public_url'), 'localhost') && !str_contains(config('portal.public_url'), '127.0.0.1')) {
                        $publicUrl = config('portal.public_url');
                    } elseif (str_contains($host, 'sa-panitia.') || str_contains($host, 'admin-semarak.') || str_contains($host, 'admin.')) {
                        $publicHost = str_replace(['sa-panitia.', 'admin-semarak.', 'admin.'], ['semarak.', 'semarak.', ''], $host);
                        $publicUrl = $scheme . '://' . $publicHost;
                    } else {
                        $publicUrl = $scheme . '://' . $host . ($publicPort === 80 || $publicPort === 443 ? '' : ':' . $publicPort);
                    }
                }

                // 2. Resolusi URL Admin (Panel Panitia)
                $adminUrl = \App\Models\Setting::get('admin_portal_url');
                if (empty($adminUrl)) {
                    if (!empty($adminDomain)) {
                        $adminUrl = $scheme . '://' . $adminDomain;
                    } elseif (!empty(config('portal.admin_url')) && !str_contains(config('portal.admin_url'), 'localhost') && !str_contains(config('portal.admin_url'), '127.0.0.1')) {
                        $adminUrl = config('portal.admin_url');
                    } else {
                        $adminUrl = $scheme . '://' . $host . ($adminPort === 80 || $adminPort === 443 ? '' : ':' . $adminPort);
                    }
                }

                return [
                    'publicUrl'  => rtrim($publicUrl, '/'),
                    'adminUrl'   => rtrim($adminUrl, '/'),
                    'publicPort' => $publicPort,
                    'adminPort'  => $adminPort,
                ];
            },
            'schoolProfile' => fn () => [
                'school_name' => \App\Models\Setting::get('school_name', 'MA Unggulan Wahid Hasyim Balung'),
                'school_short_name' => \App\Models\Setting::get('school_short_name', 'MA Wahid Hasyim'),
                'event_edition' => \App\Models\Setting::get('event_edition', 'XIV'),
                'event_year' => \App\Models\Setting::get('event_year', '2026'),
                'event_name' => \App\Models\Setting::get('event_name', 'SEMARAK ALIYAH XIV 2026'),
                'event_edition_badge' => 'Edisi ' . \App\Models\Setting::get('event_edition', 'XIV') . ' • ' . \App\Models\Setting::get('event_year', '2026'),
                'school_tagline' => \App\Models\Setting::get('school_tagline', 'Lembaga Pendidikan Islam Modern Terakreditasi A dengan Program Unggulan Plus Keterampilan, Riset, & Penguasaan Bahasa Asing'),
                'school_address' => \App\Models\Setting::get('school_address', 'Jl. Puger No. 20 Balung Kulon, Kec. Balung, Kabupaten Jember, Jawa Timur 68161'),
                'school_city' => \App\Models\Setting::get('school_city', 'Jember'),
                'contact_phone' => \App\Models\Setting::get('contact_phone', '(0336) 621123'),
                'contact_wa' => \App\Models\Setting::get('contact_wa', '0838 5007 0767'),
                'contact_email' => \App\Models\Setting::get('contact_email', 'semarak.aliyah@gmail.com'),
                'service_hours' => \App\Models\Setting::get('service_hours', 'Senin - Sabtu : 07.00 - 13.00 WIB'),
                'maps_embed_url' => \App\Models\Setting::get('maps_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3947.8872013898194!2d113.5358055!3d-8.2917778!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1s0x2dd68349f7e8b6b1%3A0x6e9f28d8442c7590!2sMA%20Unggulan%20Wahid%20Hasyim!5e0!3m2!1sid!2sid!4v1710000000000!5m2!1sid!2sid'),
                'social_facebook' => \App\Models\Setting::get('social_facebook', 'https://www.facebook.com/maswahas'),
                'social_instagram' => \App\Models\Setting::get('social_instagram', 'https://www.instagram.com/ma_wahas/'),
                'social_youtube' => \App\Models\Setting::get('social_youtube', 'https://www.youtube.com/@maswahas'),
                'social_tiktok' => \App\Models\Setting::get('social_tiktok', 'https://www.tiktok.com/@maswahas'),
                'school_website' => \App\Models\Setting::get('school_website', 'https://maswahas.sch.id'),
            ],
            'kustomisasiWeb' => fn () => [
                'banner_active' => \App\Models\Setting::get('announcement_banner_active', '1') === '1',
                'banner_text' => \App\Models\Setting::get('announcement_banner_text', '📢 Pendaftaran Semarak Aliyah XIV 2026 Resmi Dibuka! Raih Piala Bergilir, Medali Kejuaraan, & Beasiswa Pendidikan.'),
                'banner_type' => \App\Models\Setting::get('announcement_banner_type', 'gold'),
                'banner_btn_text' => \App\Models\Setting::get('announcement_banner_btn_text', 'Daftar Kontingen Sekarang'),
                'banner_btn_url' => \App\Models\Setting::get('announcement_banner_btn_url', '/#biaya'),
                'banner_position' => \App\Models\Setting::get('announcement_banner_position', 'top'),
                'banner_dismissable' => \App\Models\Setting::get('announcement_banner_dismissable', '1') === '1',

                'popup_active' => \App\Models\Setting::get('announcement_popup_active', '0') === '1',
                'popup_frequency' => \App\Models\Setting::get('announcement_popup_frequency', 'once_per_session'),
                'popup_title' => \App\Models\Setting::get('announcement_popup_title', 'Pengumuman Penting: Technical Meeting & Petunjuk Teknis'),
                'popup_badge' => \App\Models\Setting::get('announcement_popup_badge', 'Informasi Resmi Panitia'),
                'popup_image' => \App\Models\Setting::get('announcement_popup_image', '/assets/img/pamflet.jpg'),
                'popup_content' => \App\Models\Setting::get('announcement_popup_content', 'Selamat datang di Portal Resmi Semarak Aliyah XIV 2026! Pastikan delegasi sekolah/madrasah Anda telah mengunduh Buku Panduan Juknis resmi dan melengkapi berkas pendaftaran sebelum kuota masing-masing cabang lomba terpenuhi.'),
                'popup_btn_text' => \App\Models\Setting::get('announcement_popup_btn_text', 'Unduh Juknis Lengkap PDF'),
                'popup_btn_url' => \App\Models\Setting::get('announcement_popup_btn_url', '/assets/doc/JUKNIS-SEMARAK-2026.pdf'),

                'floating_wa_active' => \App\Models\Setting::get('floating_wa_active', '1') === '1',
                'floating_wa_number' => \App\Models\Setting::get('floating_wa_number', '0838 5007 0767'),
                'floating_wa_message' => \App\Models\Setting::get('floating_wa_message', 'Halo Panitia Semarak Aliyah XIV 2026, saya ingin bertanya seputar pendaftaran kontingen sekolah...'),
                'floating_wa_label' => \App\Models\Setting::get('floating_wa_label', 'Bantuan Panitia'),
                'floating_wa_position' => \App\Models\Setting::get('floating_wa_position', 'bottom-right'),
                'floating_wa_animation' => \App\Models\Setting::get('floating_wa_animation', 'pulse'),

                'countdown_active' => \App\Models\Setting::get('countdown_active', '1') === '1',
                'countdown_title' => \App\Models\Setting::get('countdown_title', 'Batas Akhir Pendaftaran & TM Ditutup Dalam:'),
                'countdown_target_date' => \App\Models\Setting::get('countdown_target_date', '2026-10-24T23:59'),
                'countdown_ended_text' => \App\Models\Setting::get('countdown_ended_text', 'Pendaftaran Gelombang Utama Resmi Ditutup! Hubungi Panitia untuk Kuota Susulan.'),
                'countdown_show_seconds' => \App\Models\Setting::get('countdown_show_seconds', '1') === '1',

                'visual_particles_active' => \App\Models\Setting::get('visual_particles_active', '1') === '1',
                'visual_particles_type' => \App\Models\Setting::get('visual_particles_type', 'glitter'),
                'back_to_top_active' => \App\Models\Setting::get('back_to_top_active', '1') === '1',
            ],
            'generalJuknis' => fn () => \App\Models\Setting::getGeneralJuknis(),
            'paymentBankAccounts' => fn () => \App\Models\Setting::getBankAccounts(),
            'footerCompetitions' => fn () => \App\Models\Competition::orderBy('id', 'asc')
                ->get(['id', 'title', 'registration_type', 'status'])
                ->map(function ($comp) {
                    return [
                        'id' => $comp->id,
                        'title' => $comp->title,
                        'name' => $comp->title,
                        'slug' => $comp->id,
                        'registration_type' => $comp->registration_type,
                    ];
                }),
        ];
    }
}
