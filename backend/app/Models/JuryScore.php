<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JuryScore extends Model
{
    protected $table = 'jury_scores';

    protected $fillable = [
        'registration_id',
        'competition_id',
        'jury_name',
        'criteria_scores',
        'total_score',
        'performance_duration',
        'is_locked',
        'notes'
    ];

    protected $casts = [
        'criteria_scores' => 'array',
        'total_score' => 'float',
        'is_locked' => 'boolean'
    ];

    public function registration()
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class, 'competition_id', 'id');
    }
}
