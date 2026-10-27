<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'label',
        'icon',
        'color',
        'description',
    ];

    public function competitions()
    {
        return $this->hasMany(Competition::class, 'category', 'id');
    }

    public static function defaultCategories(): array
    {
        return [
            [
                'id' => 'seni',
                'name' => 'Seni & Budaya',
                'label' => 'Bidang Seni & Budaya',
                'icon' => 'ri-palette-line',
                'color' => 'primary',
                'description' => 'Cabang lomba seni, kreasi, vokal, dan budaya islami.',
            ],
            [
                'id' => 'olahraga',
                'name' => 'Olahraga',
                'label' => 'Bidang Olahraga',
                'icon' => 'ri-football-line',
                'color' => 'success',
                'description' => 'Cabang perlombaan fisik, ketangkasan, dan olahraga tim.',
            ],
            [
                'id' => 'akademik',
                'name' => 'Sains & Akademik',
                'label' => 'Bidang Sains & Akademik',
                'icon' => 'ri-book-read-line',
                'color' => 'warning',
                'description' => 'Cabang olimpiade pengetahuan sains, MIPA, dan akademik.',
            ],
            [
                'id' => 'keagamaan',
                'name' => 'Keagamaan & Da\'wah',
                'label' => 'Bidang Keagamaan & Da\'wah',
                'icon' => 'ri-compassion-line',
                'color' => 'info',
                'description' => 'Cabang musabaqah, tilawah, tahfidz, dan syiar keagamaan.',
            ],
            [
                'id' => 'teknologi',
                'name' => 'Teknologi & E-Sport',
                'label' => 'Bidang Teknologi & E-Sport',
                'icon' => 'ri-gamepad-line',
                'color' => 'secondary',
                'description' => 'Cabang kompetisi digital, strategi e-sport, dan teknologi.',
            ],
        ];
    }

    public static function ensureDefaultsExist(): void
    {
        foreach (self::defaultCategories() as $d) {
            self::firstOrCreate(
                ['id' => $d['id']],
                [
                    'name' => $d['name'],
                    'label' => $d['label'],
                    'icon' => $d['icon'],
                    'color' => $d['color'],
                    'description' => $d['description'] ?? null,
                ]
            );
        }
    }
}
