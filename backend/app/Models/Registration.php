<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $table = 'registrations';

    protected $fillable = [
        'user_id',
        'registrant_id',
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
        'registration_status',
        'payment_status',
        'payment_method',
        'checkin_status',
        'checkin_at',
        'file_documents',
        'verification_notes',
        'custom_fields',
        'draw_number',
        'draw_notes',
        'assigned_name',
        'assigned_phone',
        'assigned_role',
        'assigned_notes',
    ];

    protected $casts = [
        'total_fee' => 'integer',
        'draw_number' => 'integer',
        'checkin_at' => 'datetime',
        'file_documents' => 'array',
        'custom_fields' => 'array'
    ];

    /**
     * The "booted" method of the model.
     * Automatically synchronize school data into schools database upon registration creation/update.
     */
    protected static function booted()
    {
        static::created(function ($registration) {
            self::syncSchoolRecord($registration);
        });

        static::updated(function ($registration) {
            if ($registration->isDirty('school_name') || $registration->isDirty('mentor_wa')) {
                self::syncSchoolRecord($registration);
            }
        });
    }

    /**
     * Synchronize school record seamlessly without duplicates.
     */
    protected static function syncSchoolRecord($registration)
    {
        try {
            $schoolName = trim($registration->school_name ?? '');
            if (!empty($schoolName) && $schoolName !== '-') {
                $customFields = $registration->custom_fields ?? [];
                $district = $customFields['school_district'] 
                    ?? $customFields['kecamatan'] 
                    ?? $customFields['kecamatan_sekolah'] 
                    ?? $customFields['kecamatan_asal_sekolah'] 
                    ?? null;

                \App\Models\School::firstOrCreateFromRegistration($schoolName, $registration->mentor_wa, $district);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Auto sync school from registration error: ' . $e->getMessage());
        }
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class, 'competition_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function registrant()
    {
        return $this->belongsTo(Registrant::class, 'registrant_id');
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
