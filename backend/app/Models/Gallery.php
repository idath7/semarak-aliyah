<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category_label',
        'pill_class',
        'icon_class',
        'image_path',
        'location',
        'tag',
        'description',
        'row_position',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'row_position' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get accessible image URL
     */
    public function getImageUrlAttribute(): string
    {
        if (empty($this->image_path)) {
            return '/assets/img/gallery/gallery_awarding.jpg';
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://') || str_starts_with($this->image_path, '/')) {
            return $this->image_path;
        }

        return '/storage/' . $this->image_path;
    }
}
