<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TournamentMatch extends Model
{
    use HasFactory;

    protected $table = 'tournament_matches';

    protected $fillable = [
        'competition_id',
        'match_number',
        'round_name',
        'round_order',
        'team1_registration_id',
        'team1_placeholder',
        'team1_score',
        'team1_set_scores',
        'team2_registration_id',
        'team2_placeholder',
        'team2_score',
        'team2_set_scores',
        'winner_registration_id',
        'next_match_id',
        'next_match_slot',
        'status',
        'court',
        'scheduled_time',
        'referee_name',
        'notes'
    ];

    public function competition()
    {
        return $this->belongsTo(Competition::class, 'competition_id');
    }

    public function team1()
    {
        return $this->belongsTo(Registration::class, 'team1_registration_id');
    }

    public function team2()
    {
        return $this->belongsTo(Registration::class, 'team2_registration_id');
    }

    public function winner()
    {
        return $this->belongsTo(Registration::class, 'winner_registration_id');
    }

    public function nextMatch()
    {
        return $this->belongsTo(TournamentMatch::class, 'next_match_id');
    }

    /**
     * Get display title for team 1 (school name or placeholder)
     */
    public function getTeam1DisplayNameAttribute()
    {
        if ($this->team1) {
            return $this->team1->school_name . ' (' . ($this->team1->mentor_name ?? 'Tim') . ')';
        }
        return $this->team1_placeholder ?: 'TBD (Menunggu Pertandingan)';
    }

    /**
     * Get display title for team 2 (school name or placeholder)
     */
    public function getTeam2DisplayNameAttribute()
    {
        if ($this->team2) {
            return $this->team2->school_name . ' (' . ($this->team2->mentor_name ?? 'Tim') . ')';
        }
        return $this->team2_placeholder ?: 'TBD (Menunggu Pertandingan)';
    }
}
