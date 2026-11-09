<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * In-memory request cache for all settings to prevent redundant queries.
     */
    protected static ?array $allSettingsCache = null;

    /**
     * Load all settings into memory once per request lifecycle.
     */
    public static function getAll(): array
    {
        if (static::$allSettingsCache === null) {
            try {
                static::$allSettingsCache = static::pluck('value', 'key')->toArray();
            } catch (\Throwable $e) {
                return [];
            }
        }
        return static::$allSettingsCache;
    }

    /**
     * Get a setting value by key, with optional default fallback.
     */
    public static function get(string $key, $default = null)
    {
        $all = static::getAll();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * Set/update a setting value.
     */
    public static function set(string $key, mixed $value = '')
    {
        static::$allSettingsCache = null; // Invalidate cache
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => (string)($value ?? '')]
        );
    }

    /**
     * Format any Indonesian phone number to international WhatsApp format (e.g., 081254211xxx -> 6281254211xxx).
     */
    public static function formatWaNumber(?string $phone, string $default = '6283850070767'): string
    {
        if (empty($phone)) {
            return $default;
        }

        $raw = trim($phone);
        // Extract phone number pattern if surrounded by text (e.g., "Pak Budi 0812-xxx")
        if (preg_match('/(?:(?:\+?62)|0)?8[0-9\s-]{7,15}/', $raw, $matches)) {
            $raw = $matches[0];
        }

        $digits = preg_replace('/\D/', '', $raw);
        if (empty($digits)) {
            return $default;
        }

        if (str_starts_with($digits, '620')) {
            return '62' . substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            return '62' . ltrim($digits, '0');
        }

        if (!str_starts_with($digits, '62')) {
            return '62' . $digits;
        }

        return $digits;
    }

    /**
     * Build a full WhatsApp click-to-chat URL.
     */
    public static function getWaUrl(?string $phone, string $text = '', string $default = '6283850070767'): string
    {
        $cleanPhone = static::formatWaNumber($phone, $default);
        if (empty($text)) {
            return 'https://wa.me/' . $cleanPhone;
        }
        return 'https://wa.me/' . $cleanPhone . '?text=' . urlencode($text);
    }

    /**
     * Get list of all configured bank accounts for payment.
     */
    public static function getBankAccounts(): array
    {
        $raw = static::get('payment_bank_accounts');
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && count($decoded) > 0) {
                return $decoded;
            }
        }

        // Fallback default from legacy keys
        $accNum = static::get('payment_account_number', '');
        if (empty($accNum)) {
            return [];
        }

        return [
            [
                'id' => 'acc-1',
                'bank_name' => static::get('payment_bank_name', 'Bank'),
                'account_number' => $accNum,
                'account_holder' => static::get('payment_account_holder', 'Panitia Semarak Aliyah'),
                'is_primary' => true,
                'is_active' => true,
                'notes' => 'Rekening Utama Panitia',
            ]
        ];
    }

    public static function getPrimaryBankAccount(): array
    {
        $accounts = static::getBankAccounts();
        foreach ($accounts as $acc) {
            if (!empty($acc['is_primary'])) {
                return $acc;
            }
        }
        return $accounts[0] ?? [
            'id' => 'acc-1',
            'bank_name' => static::get('payment_bank_name', 'Bank'),
            'account_number' => static::get('payment_account_number', ''),
            'account_holder' => static::get('payment_account_holder', 'Panitia Semarak Aliyah'),
            'is_primary' => true,
            'is_active' => true,
            'notes' => 'Rekening Utama Panitia',
        ];
    }

    /**
     * Save list of bank accounts and sync primary account to legacy keys.
     */
    public static function setBankAccounts(array $accounts): void
    {
        $clean = [];
        $primary = null;

        foreach ($accounts as $idx => $acc) {
            $bankName = trim($acc['bank_name'] ?? '');
            $accNum = trim($acc['account_number'] ?? '');
            $accHolder = trim($acc['account_holder'] ?? '');
            if (empty($bankName) || empty($accNum)) continue;

            $isPrimary = !empty($acc['is_primary']);
            $isActive = !isset($acc['is_active']) || !empty($acc['is_active']);

            $item = [
                'id' => $acc['id'] ?? ('acc-' . ($idx + 1) . '-' . substr(md5(uniqid()), 0, 4)),
                'bank_name' => $bankName,
                'account_number' => $accNum,
                'account_holder' => !empty($accHolder) ? $accHolder : 'Semarak Aliyah',
                'is_primary' => $isPrimary,
                'is_active' => $isActive,
                'notes' => trim($acc['notes'] ?? ''),
            ];

            if ($isPrimary && $primary === null) {
                $primary = $item;
            }
            $clean[] = $item;
        }

        if (count($clean) > 0 && $primary === null) {
            $clean[0]['is_primary'] = true;
            $primary = $clean[0];
        }

        static::set('payment_bank_accounts', json_encode(array_values($clean), JSON_UNESCAPED_UNICODE));

        // Sync legacy keys
        if ($primary) {
            static::set('payment_bank_name', $primary['bank_name']);
            static::set('payment_account_number', $primary['account_number']);
            static::set('payment_account_holder', $primary['account_holder']);
        }
    }

    /**
     * Get General Juknis details.
     */
    public static function getGeneralJuknis(): array
    {
        $url = static::get('general_juknis_url', '/assets/JUKNIS-SEMARAK-2026.pdf');
        $filename = static::get('general_juknis_filename', 'JUKNIS-SEMARAK-2026.pdf');
        $size = static::get('general_juknis_size_formatted', '2.5 MB');
        $updatedAt = static::get('general_juknis_updated_at', null);

        return [
            'url' => $url,
            'filename' => $filename,
            'size_formatted' => $size,
            'updated_at' => $updatedAt,
        ];
    }

    /**
     * Set General Juknis file info.
     */
    public static function setGeneralJuknis(string $url, string $filename, ?string $sizeFormatted = null): void
    {
        static::set('general_juknis_url', $url);
        static::set('general_juknis_filename', $filename);
        if ($sizeFormatted) {
            static::set('general_juknis_size_formatted', $sizeFormatted);
        }
        static::set('general_juknis_updated_at', now()->toIso8601String());
        static::set('announcement_popup_btn_url', $url);
    }

    /**
     * Konversi angka nominal rupiah ke format kata terbilang Bahasa Indonesia.
     */
    public static function terbilang(int $number): string
    {
        if ($number === 0) {
            return 'Nol';
        }

        $number = abs($number);
        $words = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];

        if ($number < 12) {
            return $words[$number];
        } elseif ($number < 20) {
            return self::terbilang($number - 10) . " Belas";
        } elseif ($number < 100) {
            return self::terbilang((int)($number / 10)) . " Puluh" . ($number % 10 ? " " . self::terbilang($number % 10) : "");
        } elseif ($number < 200) {
            return "Seratus" . ($number - 100 ? " " . self::terbilang($number - 100) : "");
        } elseif ($number < 1000) {
            return self::terbilang((int)($number / 100)) . " Ratus" . ($number % 100 ? " " . self::terbilang($number % 100) : "");
        } elseif ($number < 2000) {
            return "Seribu" . ($number - 1000 ? " " . self::terbilang($number - 1000) : "");
        } elseif ($number < 1000000) {
            return self::terbilang((int)($number / 1000)) . " Ribu" . ($number % 1000 ? " " . self::terbilang($number % 1000) : "");
        } elseif ($number < 1000000000) {
            return self::terbilang((int)($number / 1000000)) . " Juta" . ($number % 1000000 ? " " . self::terbilang($number % 1000000) : "");
        } elseif ($number < 1000000000000) {
            return self::terbilang((int)($number / 1000000000)) . " Miliar" . ($number % 1000000000 ? " " . self::terbilang($number % 1000000000) : "");
        }

        return (string)$number;
    }

    /**
     * Dapatkan konfigurasi lengkap KOP Surat, Logo, Stempel, dan Penandatangan Invoice / Kwitansi.
     */
    public static function getInvoiceConfig(): array
    {
        return [
            'logo_left' => static::get('invoice_logo_left', '/assets/img/logo.png'),
            'logo_right' => static::get('invoice_logo_right', '/assets/img/logo.png'),
            'show_logo_right' => static::get('invoice_show_logo_right', '1') === '1',
            'logo_size' => (int)static::get('invoice_logo_size', 68),
            'kop_foundation' => static::get('invoice_kop_foundation', 'YAYASAN PONDOK PESANTREN WAHID HASYIM'),
            'kop_committee' => static::get('invoice_kop_committee', 'PANITIA PELAKSANA SEMARAK ALIYAH XIV TAHUN 2026'),
            'kop_school' => static::get('invoice_kop_school', 'MADRASAH ALIYAH UNGGULAN WAHID HASYIM BALUNG'),
            'kop_accreditation' => static::get('invoice_kop_accreditation', 'STATUS TERAKREDITASI "A" • NSM: 131235090014 • NPSN: 69941846'),
            'kop_address' => static::get('invoice_kop_address', 'Jl. Balung - Tutul No. 09 Balung Kidul, Kec. Balung, Kab. Jember, Jawa Timur 68161'),
            'kop_contact' => static::get('invoice_kop_contact', 'Website: semarak.ma-wh.sch.id • Email: semarak@ma-wh.sch.id • Hotline WA: 0838 5007 0767'),
            'stamp_mode' => static::get('invoice_stamp_mode', 'digital'),
            'stamp_top_text' => static::get('invoice_stamp_top_text', 'SEMARAK ALIYAH XIV'),
            'stamp_bottom_text' => static::get('invoice_stamp_bottom_text', 'MA WAHID HASYIM'),
            'stamp_center_text' => static::get('invoice_stamp_center_text', '★'),
            'stamp_color' => static::get('invoice_stamp_color', '#2563eb'),
            'stamp_image' => static::get('invoice_stamp_image', ''),
            'stamp_rotation' => (int)static::get('invoice_stamp_rotation', -8),
            'sig_city' => static::get('invoice_sig_city', 'Balung - Jember'),
            'treasurer_name' => static::get('treasurer_name', 'M. Irfan Zidni, S.Pd.'),
            'treasurer_role' => static::get('treasurer_role', 'Bendahara Pelaksana'),
            'treasurer_subrole' => static::get('treasurer_subrole', 'Semarak Aliyah XIV Tahun 2026'),
            'signature_image' => static::get('treasurer_signature_image', ''),
            'footer_note' => static::get('invoice_footer_note', 'Dokumen ini diterbitkan secara sah dan otomatis oleh Sistem Informasi Semarak Aliyah XIV MA Unggulan Wahid Hasyim Balung.'),
            'theme' => static::get('invoice_theme', 'formal'),
            'theme_mode' => static::get('invoice_theme_mode', 'locked'),
            'stamp_type' => static::get('invoice_stamp_type', 'custom'),
        ];
    }

    /**
     * Dapatkan konfigurasi mandiri untuk salah satu template blangko sertifikat:
     * 'juara', 'peserta', atau 'pendamping'.
     */
    public static function getTemplateConfig(string $type): array
    {
        $prefix = "certificate_{$type}_";
        $defaultBg = static::get("certificate_bg_{$type}", '');

        $defaultNameColor = '#064e3b';
        $defaultPredColor = $type === 'juara' ? '#d97706' : '#064e3b';
        $defaultGiven = $type === 'pendamping'
            ? 'Diberikan dengan rasa terima kasih dan apresiasi setinggi-tingginya kepada:'
            : 'Diberikan dengan rasa bangga dan apresiasi setinggi-tingginya kepada:';
        $defaultPredIntro = $type === 'pendamping' ? 'Sebagai:' : 'Atas partisipasi dan prestasinya sebagai:';

        return [
            'bg' => static::get($prefix . 'bg', $defaultBg),
            'show_kop' => static::get($prefix . 'show_kop', static::get('certificate_show_kop', '1')) === '1',
            'show_signatures' => static::get($prefix . 'show_signatures', static::get('certificate_show_signatures', '1')) === '1',
            'show_sig_ketua' => static::get($prefix . 'show_sig_ketua', static::get('certificate_show_sig_ketua', '1')) === '1',
            'sig_ketua_title' => static::get($prefix . 'sig_ketua_title', static::get('certificate_sig_ketua_title', "Mengetahui,\nKetua Panitia Pelaksana,")),
            'sig_ketua_name' => static::get($prefix . 'sig_ketua_name', static::get('certificate_sig_ketua_name', 'M. FATHUR RAHMAN, S.Pd.')),
            'sig_ketua_nip' => static::get($prefix . 'sig_ketua_nip', static::get('certificate_sig_ketua_nip', 'NIP. -')),
            'sig_ketua_image' => static::get($prefix . 'sig_ketua_image', static::get('certificate_sig_ketua_image', '')),
            'show_sig_kepala' => static::get($prefix . 'show_sig_kepala', static::get('certificate_show_sig_kepala', '1')) === '1',
            'sig_kepala_city_date' => static::get($prefix . 'sig_kepala_city_date', static::get('certificate_sig_kepala_city_date', 'Balung, 25 Oktober 2026')),
            'sig_kepala_title' => static::get($prefix . 'sig_kepala_title', static::get('certificate_sig_kepala_title', 'Kepala Madrasah Aliyah,')),
            'sig_kepala_name' => static::get($prefix . 'sig_kepala_name', static::get('certificate_sig_kepala_name', 'H. NURUL HUDA, S.Ag., M.Pd.I.')),
            'sig_kepala_nip' => static::get($prefix . 'sig_kepala_nip', static::get('certificate_sig_kepala_nip', 'NIP. 197405122005011003')),
            'sig_kepala_image' => static::get($prefix . 'sig_kepala_image', static::get('certificate_sig_kepala_image', '')),
            'show_stamp' => static::get($prefix . 'show_stamp', static::get('certificate_show_stamp', '0')) === '1',
            'stamp_image' => static::get($prefix . 'stamp_image', static::get('certificate_stamp_image', '')),
            'show_qr' => static::get($prefix . 'show_qr', static::get('certificate_show_qr', '1')) === '1',
            'qr_size' => (int)static::get($prefix . 'qr_size', static::get('certificate_qr_size', 68)),
            'qr_position' => static::get($prefix . 'qr_position', static::get('certificate_qr_position', 'center_bottom')),
            'qr_x' => (int)static::get($prefix . 'qr_x', static::get('certificate_qr_x', 50)),
            'qr_y' => (int)static::get($prefix . 'qr_y', static::get('certificate_qr_y', 85)),
            'name_font_family' => static::get($prefix . 'name_font_family', static::get('certificate_font_family', 'Playfair Display')),
            'name_size' => (int)static::get($prefix . 'name_size', static::get('certificate_name_size', 26)),
            'name_color' => static::get($prefix . 'name_color', static::get('certificate_name_color', $defaultNameColor)),
            'name_top' => (int)static::get($prefix . 'name_top', static::get('certificate_name_top', 45)),
            'predicate_font_family' => static::get($prefix . 'predicate_font_family', static::get('certificate_predicate_font_family', 'Cinzel')),
            'predicate_size' => (int)static::get($prefix . 'predicate_size', static::get('certificate_predicate_size', 18)),
            'predicate_color' => static::get($prefix . 'predicate_color', static::get('certificate_predicate_color', $defaultPredColor)),
            'predicate_top' => (int)static::get($prefix . 'predicate_top', static::get('certificate_predicate_top', 58)),
            'comp_top' => (int)static::get($prefix . 'comp_top', static::get('certificate_comp_top', 68)),
            'show_text_given_to' => static::get($prefix . 'show_text_given_to', static::get('certificate_show_text_given_to', '1')) === '1',
            'text_given_to' => static::get($prefix . 'text_given_to', static::get('certificate_text_given_to', $defaultGiven)),
            'show_text_predicate' => static::get($prefix . 'show_text_predicate', static::get('certificate_show_text_predicate', '1')) === '1',
            'text_predicate' => static::get($prefix . 'text_predicate', static::get('certificate_text_predicate', $defaultPredIntro)),
            'show_text_competition' => static::get($prefix . 'show_text_competition', static::get('certificate_show_text_competition', '1')) === '1',
            'text_competition' => static::get($prefix . 'text_competition', static::get('certificate_text_competition', 'Dalam Cabang Lomba:')),
            'show_school' => static::get($prefix . 'show_school', static::get('certificate_show_school', '1')) === '1',
            'text_school' => static::get($prefix . 'text_school', static::get('certificate_text_school', 'Asal Sekolah :')),
            'show_event_subtitle' => static::get($prefix . 'show_event_subtitle', static::get('certificate_show_event_subtitle', '1')) === '1',
        ];
    }

    /**
     * Dapatkan konfigurasi lengkap Kustomisasi Blangko E-Sertifikat Panitia (Mandiri per template).
     */
    public static function getCertificateConfig(): array
    {
        $templates = [
            'juara' => static::getTemplateConfig('juara'),
            'peserta' => static::getTemplateConfig('peserta'),
            'pendamping' => static::getTemplateConfig('pendamping'),
        ];

        return [
            'templates' => $templates,
            'bg_juara' => $templates['juara']['bg'],
            'bg_peserta' => $templates['peserta']['bg'],
            'bg_pendamping' => $templates['pendamping']['bg'],
            'show_kop' => $templates['juara']['show_kop'],
            'show_signatures' => $templates['juara']['show_signatures'],
            'show_sig_ketua' => $templates['juara']['show_sig_ketua'],
            'sig_ketua_title' => $templates['juara']['sig_ketua_title'],
            'sig_ketua_name' => $templates['juara']['sig_ketua_name'],
            'sig_ketua_nip' => $templates['juara']['sig_ketua_nip'],
            'sig_ketua_image' => $templates['juara']['sig_ketua_image'],
            'show_sig_kepala' => $templates['juara']['show_sig_kepala'],
            'sig_kepala_city_date' => $templates['juara']['sig_kepala_city_date'],
            'sig_kepala_title' => $templates['juara']['sig_kepala_title'],
            'sig_kepala_name' => $templates['juara']['sig_kepala_name'],
            'sig_kepala_nip' => $templates['juara']['sig_kepala_nip'],
            'sig_kepala_image' => $templates['juara']['sig_kepala_image'],
            'show_stamp' => $templates['juara']['show_stamp'],
            'stamp_image' => $templates['juara']['stamp_image'],
            'show_qr' => $templates['juara']['show_qr'],
            'qr_size' => $templates['juara']['qr_size'],
            'qr_position' => $templates['juara']['qr_position'],
            'qr_x' => $templates['juara']['qr_x'],
            'qr_y' => $templates['juara']['qr_y'],
            'name_color' => $templates['juara']['name_color'],
            'name_size' => $templates['juara']['name_size'],
            'name_top' => $templates['juara']['name_top'],
            'predicate_top' => $templates['juara']['predicate_top'],
            'comp_top' => $templates['juara']['comp_top'],
            'font_family' => $templates['juara']['name_font_family'],
            'show_text_given_to' => $templates['juara']['show_text_given_to'],
            'text_given_to' => $templates['juara']['text_given_to'],
            'show_text_predicate' => $templates['juara']['show_text_predicate'],
            'text_predicate' => $templates['juara']['text_predicate'],
            'show_text_competition' => $templates['juara']['show_text_competition'],
            'text_competition' => $templates['juara']['text_competition'],
            'show_event_subtitle' => $templates['juara']['show_event_subtitle'],
            'show_school' => $templates['juara']['show_school'],
            'text_school' => $templates['juara']['text_school'],
            'predicate_font_family' => $templates['juara']['predicate_font_family'],
            'predicate_size' => $templates['juara']['predicate_size'],
            'predicate_color' => $templates['juara']['predicate_color'],
        ];
    }

    /**
     * Dapatkan konfigurasi KOP Surat Resmi Lembaga / Kepanitiaan (Non-Invoice).
     * Murni diambil dari master profil sekolah & event resmi (admin/pengaturan/profil-sekolah).
     */
    public static function getOfficialLetterhead(): array
    {
        $schoolName = static::get('school_name', 'MA Unggulan Wahid Hasyim Balung');
        $edition = static::get('event_edition', 'XIV');
        $year = static::get('event_year', '2026');
        $eventName = static::get('event_name', "SEMARAK ALIYAH {$edition} {$year}");
        $address = static::get('school_address', 'Jl. Puger No. 20 Balung Kulon, Kec. Balung, Kabupaten Jember, Jawa Timur 68161');
        $city = static::get('school_city', 'Jember');

        return [
            'committee_name'   => 'PANITIA PELAKSANA ' . strtoupper($eventName),
            'school_name'      => strtoupper($schoolName),
            'address'          => $address,
            'city'             => $city,
            'phone'            => static::get('contact_phone', ''),
            'wa'               => static::get('contact_wa', ''),
            'email'            => static::get('contact_email', ''),
            'website'          => static::get('school_website', ''),
            'logo_url'         => static::get('school_logo', '/assets/img/logo.png'),
        ];
    }

    /**
     * Resolusi URL Portal Publik (Web Pendaftar) secara dinamis & cerdas.
     * Prioritas:
     * 1. Setting database manual 'public_portal_url' (jika diatur admin)
     * 2. config('portal.public_domain') / env('PUBLIC_DOMAIN')
     * 3. config('portal.public_url') / env('PUBLIC_URL') jika bukan localhost
     * 4. config('app.url') jika berisi domain publik production (bukan localhost)
     * 5. Deteksi request saat ini (termasuk X-Forwarded-Host & X-Forwarded-Proto)
     * 6. Fallback development lokal (host + public_port)
     */
    public static function getPublicPortalUrl(?\Illuminate\Http\Request $request = null): string
    {
        // 1. Cek pengaturan manual di database
        $custom = static::get('public_portal_url');
        if (!empty($custom)) {
            return rtrim($custom, '/');
        }

        $isProd = app()->environment('production') || config('app.env') === 'production';

        $req = $request ?? (function_exists('request') ? request() : null);
        $forwardedProto = $req ? $req->header('X-Forwarded-Proto') : null;
        $forwardedHost = $req ? $req->header('X-Forwarded-Host') : null;

        $scheme = $forwardedProto ?: ($req ? $req->getScheme() : ($isProd ? 'https' : 'http'));
        $host = $forwardedHost ? trim(explode(',', $forwardedHost)[0]) : ($req ? $req->getHost() : 'localhost');

        // 2. Cek domain publik dari konfigurasi
        $publicDomain = config('portal.public_domain') ?: env('PUBLIC_DOMAIN');
        if (!empty($publicDomain)) {
            $pScheme = $isProd ? 'https' : $scheme;
            return rtrim($pScheme . '://' . $publicDomain, '/');
        }

        // Cek apakah config portal.public_url diatur ke domain publik
        $configPublicUrl = config('portal.public_url') ?: env('PUBLIC_URL');
        if (!empty($configPublicUrl) && !str_contains($configPublicUrl, 'localhost') && !str_contains($configPublicUrl, '127.0.0.1')) {
            return rtrim($configPublicUrl, '/');
        }

        // Cek apakah config app.url berisi domain publik production (bukan localhost)
        $appUrl = config('app.url') ?: env('APP_URL');
        $appHost = !empty($appUrl) ? parse_url($appUrl, PHP_URL_HOST) : null;
        $appScheme = !empty($appUrl) ? (parse_url($appUrl, PHP_URL_SCHEME) ?: ($isProd ? 'https' : 'http')) : $scheme;
        $appIsDomain = !empty($appHost) && !in_array($appHost, ['localhost', '127.0.0.1', '0.0.0.0']);

        // 3. Deteksi apakah request saat ini berasal dari domain publik
        $isLocalHost = in_array($host, ['localhost', '127.0.0.1', '0.0.0.0']) 
            || str_starts_with($host, '192.168.') 
            || str_starts_with($host, '10.')
            || preg_match('/\.local$/i', $host);

        if (!$isLocalHost) {
            $effectiveScheme = $isProd ? 'https' : $scheme;
            // Jika host saat ini adalah subdomain admin, arahkan ke subdomain web publik atau root domain
            if (str_contains($host, 'sa-panitia.') || str_contains($host, 'admin-semarak.') || str_contains($host, 'panitia-semarak.') || str_contains($host, 'admin.') || str_contains($host, 'panitia.')) {
                $publicHost = str_replace(['sa-panitia.', 'admin-semarak.', 'panitia-semarak.', 'admin.', 'panitia.'], ['semarak.', 'semarak.', 'semarak.', '', ''], $host);
                return rtrim($effectiveScheme . '://' . $publicHost, '/');
            }

            // Jika diakses melalui domain production (port 80/443 standar atau reverse-proxy), jangan tempel port development
            $port = $req ? (int)$req->getPort() : 80;
            $portSuffix = ($port === 80 || $port === 443 || empty($port)) ? '' : (':' . $port);
            return rtrim($effectiveScheme . '://' . $host . $portSuffix, '/');
        }

        // Jika request host lokal/CLI tapi APP_URL diatur ke domain publik
        if ($appIsDomain) {
            return rtrim($appScheme . '://' . $appHost, '/');
        }

        // 4. Jika di lingkungan lokal development
        $publicPort = (int) config('portal.public_port', 8000);
        $currentPort = $req ? (int)$req->getPort() : $publicPort;

        // Jika dev server dijalankan secara single-instance di port khusus (misal 8002 atau 8080)
        if ($currentPort !== 8000 && $currentPort !== 8001 && $currentPort !== 80 && $currentPort !== 443 && $publicPort === 8000) {
            return rtrim($scheme . '://' . $host . ':' . $currentPort, '/');
        }

        return rtrim($scheme . '://' . $host . ($publicPort === 80 || $publicPort === 443 ? '' : ':' . $publicPort), '/');
    }

    /**
     * Resolusi URL Portal Admin (Panel Panitia) secara dinamis & cerdas.
     */
    public static function getAdminPortalUrl(?\Illuminate\Http\Request $request = null): string
    {
        $custom = static::get('admin_portal_url');
        if (!empty($custom)) {
            return rtrim($custom, '/');
        }

        $isProd = app()->environment('production') || config('app.env') === 'production';

        $req = $request ?? (function_exists('request') ? request() : null);
        $forwardedProto = $req ? $req->header('X-Forwarded-Proto') : null;
        $forwardedHost = $req ? $req->header('X-Forwarded-Host') : null;

        $scheme = $forwardedProto ?: ($req ? $req->getScheme() : ($isProd ? 'https' : 'http'));
        $host = $forwardedHost ? trim(explode(',', $forwardedHost)[0]) : ($req ? $req->getHost() : 'localhost');

        $adminDomain = config('portal.admin_domain') ?: env('ADMIN_DOMAIN');
        if (!empty($adminDomain)) {
            $aScheme = $isProd ? 'https' : $scheme;
            return rtrim($aScheme . '://' . $adminDomain, '/');
        }

        // Cek apakah config portal.admin_url diatur ke domain publik
        $configAdminUrl = config('portal.admin_url') ?: env('ADMIN_URL');
        if (!empty($configAdminUrl) && !str_contains($configAdminUrl, 'localhost') && !str_contains($configAdminUrl, '127.0.0.1')) {
            return rtrim($configAdminUrl, '/');
        }

        $isLocalHost = in_array($host, ['localhost', '127.0.0.1', '0.0.0.0']) 
            || str_starts_with($host, '192.168.') 
            || str_starts_with($host, '10.')
            || preg_match('/\.local$/i', $host);

        if (!$isLocalHost) {
            $effectiveScheme = $isProd ? 'https' : $scheme;
            $port = $req ? (int)$req->getPort() : 80;
            $portSuffix = ($port === 80 || $port === 443 || empty($port)) ? '' : (':' . $port);
            return rtrim($effectiveScheme . '://' . $host . $portSuffix, '/');
        }

        $adminPort = (int) config('portal.admin_port', 8001);
        $currentPort = $req ? (int)$req->getPort() : $adminPort;

        // Jika dev server dijalankan secara single-instance di port khusus (misal 8002 atau 8080)
        if ($currentPort !== 8000 && $currentPort !== 8001 && $currentPort !== 80 && $currentPort !== 443 && $adminPort === 8001) {
            return rtrim($scheme . '://' . $host . ':' . $currentPort, '/');
        }

        return rtrim($scheme . '://' . $host . ($adminPort === 80 || $adminPort === 443 ? '' : ':' . $adminPort), '/');
    }
}
