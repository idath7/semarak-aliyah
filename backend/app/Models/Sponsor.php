<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sponsor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'tier',
        'category',
        'logo',
        'only_logo',
        'icon',
        'color',
        'bg_color',
        'border_color',
        'website_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'only_logo' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'is_logo_only',
    ];

    /**
     * Check if sponsor is configured to display logo only.
     * If true, frontend will automatically hide icon, name, and category.
     */
    public function getIsLogoOnlyAttribute(): bool
    {
        return (bool) ($this->only_logo || (!empty($this->logo) && empty($this->name)));
    }
}
