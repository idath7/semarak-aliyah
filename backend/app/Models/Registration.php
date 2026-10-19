<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $table = 'registrations';

    protected $fillable = [
        'reg_code',
        'barcode',
        'school_name',
        'mentor_name',
        'mentor_wa',
        'package_type',
        'competition_id',
        'competition_title',
        'participants_names',
        'notes',
        'total_fee',
        'payment_status',
        'payment_method',
        'checkin_status',
        'checkin_at',
        'file_documents',
        'verification_notes',
        'custom_fields',
        'draw_number',
        'draw_notes'
    ];

    protected $casts = [
        'total_fee' => 'integer',
        'draw_number' => 'integer',
        'checkin_at' => 'datetime',
        'file_documents' => 'array',
        'custom_fields' => 'array'
    ];

    public function competition()
    {
        return $this->belongsTo(Competition::class, 'competition_id', 'id');
    }

    public function juryScores()
    {
        return $this->hasMany(JuryScore::class, 'registration_id');
    }

    /**
     * Get or create a barcode value matching reg_code.
     */
    public function getBarcodeAttribute($value)
    {
        return $value ?: $this->reg_code;
    }
}
