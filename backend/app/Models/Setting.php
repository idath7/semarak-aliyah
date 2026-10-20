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
}
