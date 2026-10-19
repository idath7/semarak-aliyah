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
}
