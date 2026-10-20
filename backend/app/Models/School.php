<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'npsn',
        'name',
        'jenjang',
        'status',
        'kecamatan',
        'kabupaten',
        'alamat',
        'phone',
        'email',
        'principal_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope query to active schools.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query for keyword search across name, npsn, kecamatan, or address.
     */
    public function scopeSearch($query, ?string $keyword)
    {
        if (empty($keyword)) {
            return $query;
        }

        $term = '%' . trim($keyword) . '%';
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', $term)
              ->orWhere('npsn', 'like', $term)
              ->orWhere('kecamatan', 'like', $term)
              ->orWhere('alamat', 'like', $term);
        });
    }

    /**
     * Scope query by Jenjang (SMP / MTs).
     */
    public function scopeJenjang($query, ?string $jenjang)
    {
        if (!empty($jenjang) && in_array(strtoupper($jenjang), ['SMP', 'MTS'])) {
            return $query->where('jenjang', strtoupper($jenjang));
        }
        return $query;
    }

    /**
     * Scope query by Status (Negeri / Swasta).
     */
    public function scopeStatus($query, ?string $status)
    {
        if (!empty($status) && in_array(ucfirst(strtolower($status)), ['Negeri', 'Swasta'])) {
            return $query->where('status', ucfirst(strtolower($status)));
        }
        return $query;
    }

    /**
     * Scope query by Kecamatan.
     */
    public function scopeKecamatan($query, ?string $kecamatan)
    {
        if (!empty($kecamatan)) {
            return $query->where('kecamatan', $kecamatan);
        }
        return $query;
    }
}
