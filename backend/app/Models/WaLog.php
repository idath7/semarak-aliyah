<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class WaLog extends Model
{
    use HasFactory;

    protected $table = 'wa_logs';

    protected $fillable = [
        'phone',
        'recipient_name',
        'type',
        'message',
        'status',
        'response_code',
        'response_data',
        'error_message',
    ];

    protected $casts = [
        'response_data' => 'array',
        'response_code' => 'integer',
    ];

    /**
     * Scope for filtering by status or type.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }
}
