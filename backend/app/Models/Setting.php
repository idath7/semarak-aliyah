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
    public static function set(string $key, $value)
    {
        static::$allSettingsCache = null; // Invalidate cache
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
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
}
