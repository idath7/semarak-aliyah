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
        return [
            [
                'id' => 'acc-1',
                'bank_name' => static::get('payment_bank_name', 'Bank Syariah Indonesia (BSI)'),
                'account_number' => static::get('payment_account_number', '7123456789'),
                'account_holder' => static::get('payment_account_holder', 'Semarak Aliyah'),
                'is_primary' => true,
                'is_active' => true,
                'notes' => 'Rekening Utama Panitia (Bebas Biaya Transfer Antar BSI)',
            ]
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
        ];
    }

    /**
     * Resolusi URL Portal Publik (Web Pendaftar) secara dinamis & cerdas.
     * Prioritas:
     * 1. Setting database manual 'public_portal_url' (jika diatur admin)
     * 2. config('portal.public_domain') / env('PUBLIC_DOMAIN')
     * 3. Jika diakses melalui domain production (bukan localhost/127.0.0.1/private IP):
     *    - Jika host adalah subdomain admin (sa-panitia.*, admin-semarak.*, admin.*), alihkan ke domain publik
     *    - Jika host adalah domain utama/biasa, gunakan host & scheme saat ini (tanpa port lokal development)
     * 4. Jika di lingkungan lokal development:
     *    - Menggunakan config('portal.public_url') atau host:public_port (default: 8000)
     */
    public static function getPublicPortalUrl(?\Illuminate\Http\Request $request = null): string
    {
        // 1. Cek pengaturan manual di database
        $custom = static::get('public_portal_url');
        if (!empty($custom)) {
            return rtrim($custom, '/');
        }

        $req = $request ?? (function_exists('request') ? request() : null);
        $scheme = $req ? $req->getScheme() : (env('APP_ENV') === 'production' ? 'https' : 'http');
        $host = $req ? $req->getHost() : 'localhost';

        // 2. Cek domain publik dari konfigurasi / env
        $publicDomain = config('portal.public_domain') ?: env('PUBLIC_DOMAIN');
        if (!empty($publicDomain)) {
            return rtrim($scheme . '://' . $publicDomain, '/');
        }

        // Cek apakah env PUBLIC_URL diatur ke domain publik
        $configPublicUrl = config('portal.public_url') ?: env('PUBLIC_URL');
        if (!empty($configPublicUrl) && !str_contains($configPublicUrl, 'localhost') && !str_contains($configPublicUrl, '127.0.0.1')) {
            return rtrim($configPublicUrl, '/');
        }

        // Cek apakah env APP_URL berisi domain publik production (bukan localhost)
        $appUrl = env('APP_URL');
        $appHost = !empty($appUrl) ? parse_url($appUrl, PHP_URL_HOST) : null;
        $appScheme = !empty($appUrl) ? parse_url($appUrl, PHP_URL_SCHEME) : $scheme;
        $appIsDomain = !empty($appHost) && !in_array($appHost, ['localhost', '127.0.0.1', '0.0.0.0']);

        // 3. Deteksi apakah request saat ini berasal dari domain publik
        $isLocalHost = in_array($host, ['localhost', '127.0.0.1', '0.0.0.0']) 
            || str_starts_with($host, '192.168.') 
            || str_starts_with($host, '10.')
            || preg_match('/\.local$/i', $host);

        if (!$isLocalHost) {
            // Jika host saat ini adalah subdomain admin, arahkan ke subdomain web publik atau root domain
            if (str_contains($host, 'sa-panitia.') || str_contains($host, 'admin-semarak.') || str_contains($host, 'panitia-semarak.') || str_contains($host, 'admin.') || str_contains($host, 'panitia.')) {
                $publicHost = str_replace(['sa-panitia.', 'admin-semarak.', 'panitia-semarak.', 'admin.', 'panitia.'], ['semarak.', 'semarak.', 'semarak.', '', ''], $host);
                return rtrim($scheme . '://' . $publicHost, '/');
            }

            // Jika diakses melalui domain production (port 80/443 standar atau reverse-proxy), jangan tempel port development
            $port = $req ? (int)$req->getPort() : 80;
            $portSuffix = ($port === 80 || $port === 443 || empty($port)) ? '' : (':' . $port);
            return rtrim($scheme . '://' . $host . $portSuffix, '/');
        }

        // Jika request host lokal tapi APP_URL diatur ke domain publik
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

        $req = $request ?? (function_exists('request') ? request() : null);
        $scheme = $req ? $req->getScheme() : (env('APP_ENV') === 'production' ? 'https' : 'http');
        $host = $req ? $req->getHost() : 'localhost';

        $adminDomain = config('portal.admin_domain') ?: env('ADMIN_DOMAIN');
        if (!empty($adminDomain)) {
            return rtrim($scheme . '://' . $adminDomain, '/');
        }

        // Cek apakah env ADMIN_URL diatur ke domain publik
        $configAdminUrl = config('portal.admin_url') ?: env('ADMIN_URL');
        if (!empty($configAdminUrl) && !str_contains($configAdminUrl, 'localhost') && !str_contains($configAdminUrl, '127.0.0.1')) {
            return rtrim($configAdminUrl, '/');
        }

        $isLocalHost = in_array($host, ['localhost', '127.0.0.1', '0.0.0.0']) 
            || str_starts_with($host, '192.168.') 
            || str_starts_with($host, '10.')
            || preg_match('/\.local$/i', $host);

        if (!$isLocalHost) {
            $port = $req ? (int)$req->getPort() : 80;
            $portSuffix = ($port === 80 || $port === 443 || empty($port)) ? '' : (':' . $port);
            return rtrim($scheme . '://' . $host . $portSuffix, '/');
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
