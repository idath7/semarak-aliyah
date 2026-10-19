<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Competition;
use App\Models\Registration;
use App\Models\TournamentMatch;

class TournamentMatchController extends Controller
{
    /**
     * Arena & Match Management for Knockout Tournament (Sistem Gugur).
     */
    public function arena($competitionId, Request $request)
    {
        $competition = Competition::findOrFail($competitionId);
        $teams = Registration::where('competition_id', $competitionId)
            ->orderBy('school_name')
            ->get();

        $matches = TournamentMatch::where('competition_id', $competitionId)
            ->with(['team1', 'team2', 'winner'])
            ->orderBy('round_order')
            ->orderBy('id')
            ->get();

        // Active match being scored in the operator/referee panel
        $selectedMatchId = $request->input('match');
        if ($selectedMatchId) {
            $selectedMatch = TournamentMatch::with(['team1', 'team2', 'winner'])->find($selectedMatchId);
        } else {
            $selectedMatch = $matches->where('status', 'ongoing')->first() 
                ?? $matches->where('status', 'scheduled')->first() 
                ?? $matches->first();
        }

        // Group matches by round for bracket
        $rounds = $matches->groupBy('round_name');

        $completedMatchesCount = $matches->where('status', 'completed')->count();
        $totalMatchesCount = $matches->count();

        return view('admin.tournament-arena', compact(
            'competition',
            'teams',
            'matches',
            'rounds',
            'selectedMatch',
            'completedMatchesCount',
            'totalMatchesCount'
        ));
    }

    /**
     * Update match score, status, and winner progression.
     */
    public function updateScore(Request $request, $matchId)
    {
        $match = TournamentMatch::findOrFail($matchId);

        $request->validate([
            'team1_score' => 'required|integer|min:0',
            'team2_score' => 'required|integer|min:0',
            'team1_set_scores' => 'nullable|string|max:100',
            'team2_set_scores' => 'nullable|string|max:100',
            'status' => 'required|in:scheduled,ongoing,completed',
            'winner_registration_id' => 'nullable|exists:registrations,id',
            'court' => 'nullable|string|max:50',
            'referee_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
        ]);

        $prevWinnerId = $match->winner_registration_id;
        $newWinnerId = $request->winner_registration_id;

        // Auto-detect winner if score difference and status is completed
        if (!$newWinnerId && $request->status === 'completed') {
            if ($request->team1_score > $request->team2_score && $match->team1_registration_id) {
                $newWinnerId = $match->team1_registration_id;
            } elseif ($request->team2_score > $request->team1_score && $match->team2_registration_id) {
                $newWinnerId = $match->team2_registration_id;
            }
        }

        $match->team1_score = $request->team1_score;
        $match->team2_score = $request->team2_score;
        $match->team1_set_scores = $request->team1_set_scores;
        $match->team2_set_scores = $request->team2_set_scores;
        $match->status = $request->status;
        $match->winner_registration_id = $newWinnerId;
        $match->court = $request->court ?? $match->court;
        $match->referee_name = $request->referee_name ?? $match->referee_name;
        $match->notes = $request->notes;
        $match->save();

        // Propagate winner to next match in bracket if configured
        if ($match->next_match_id && $match->next_match_slot) {
            $nextMatch = TournamentMatch::find($match->next_match_id);
            if ($nextMatch) {
                if ($match->next_match_slot === 'team1') {
                    $nextMatch->team1_registration_id = $newWinnerId;
                } elseif ($match->next_match_slot === 'team2') {
                    $nextMatch->team2_registration_id = $newWinnerId;
                }
                $nextMatch->save();
            }
        }

        // Broadcast real-time score update via Reverb WebSocket
        try {
            broadcast(new \App\Events\MatchScoreUpdated($match));
        } catch (\Throwable $e) {}

        $msg = "Skor pertandingan {$match->match_number} ({$match->round_name}) berhasil disimpan!";
        if ($newWinnerId) {
            $winnerReg = Registration::find($newWinnerId);
            $msg .= " Pemenang: {$winnerReg?->school_name} lolos ke babak selanjutnya (Sistem Gugur).";
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'match' => $match->load(['team1', 'team2', 'winner'])
            ]);
        }

        return redirect()->route('admin.tournament.arena', [
            'competitionId' => $match->competition_id,
            'match' => $match->id
        ])->with('success', $msg);
    }

    /**
     * Set teams manual matchmaking for a match.
     */
    public function setTeams(Request $request, $matchId)
    {
        $match = TournamentMatch::findOrFail($matchId);

        $request->validate([
            'team1_registration_id' => 'nullable|exists:registrations,id',
            'team2_registration_id' => 'nullable|exists:registrations,id',
            'team1_placeholder' => 'nullable|string|max:100',
            'team2_placeholder' => 'nullable|string|max:100',
        ]);

        $match->team1_registration_id = $request->team1_registration_id;
        $match->team2_registration_id = $request->team2_registration_id;
        if ($request->filled('team1_placeholder')) $match->team1_placeholder = $request->team1_placeholder;
        if ($request->filled('team2_placeholder')) $match->team2_placeholder = $request->team2_placeholder;
        $match->save();

        return redirect()->route('admin.tournament.arena', [
            'competitionId' => $match->competition_id,
            'match' => $match->id
        ])->with('success', "Peserta pertandingan {$match->match_number} berhasil diperbarui!");
    }

    /**
     * Generate complete single-elimination bracket for 4 or 8 teams.
     */
    public function generateBracket(Request $request, $competitionId)
    {
        $competition = Competition::findOrFail($competitionId);
        $teams = Registration::where('competition_id', $competitionId)->get();

        if ($teams->count() < 2) {
            return redirect()->back()->with('error', 'Minimal harus ada 2 tim terdaftar untuk membuat bagan turnamen sistem gugur.');
        }

        // Delete existing matches for this competition
        TournamentMatch::where('competition_id', $competitionId)->delete();

        // Determine bracket size: 4 or 8 teams
        $shuffled = $teams->shuffle()->values();
        $is8Teams = $shuffled->count() >= 5;

        if ($is8Teams) {
            // 8 Teams Bracket (QF -> SF -> FINAL)
            $final = TournamentMatch::create([
                'competition_id' => $competitionId,
                'match_number' => 'FINAL',
                'round_name' => 'Grand Final',
                'round_order' => 3,
                'team1_placeholder' => 'Pemenang Semi Final 1',
                'team2_placeholder' => 'Pemenang Semi Final 2',
                'status' => 'scheduled',
                'court' => 'Lapangan Utama',
                'scheduled_time' => '15:30 WIB'
            ]);

            $sf1 = TournamentMatch::create([
                'competition_id' => $competitionId,
                'match_number' => 'SF-1',
                'round_name' => 'Semi Final',
                'round_order' => 2,
                'team1_placeholder' => 'Pemenang QF-1',
                'team2_placeholder' => 'Pemenang QF-2',
                'next_match_id' => $final->id,
                'next_match_slot' => 'team1',
                'status' => 'scheduled',
                'court' => 'Lapangan 1',
                'scheduled_time' => '13:00 WIB'
            ]);

            $sf2 = TournamentMatch::create([
                'competition_id' => $competitionId,
                'match_number' => 'SF-2',
                'round_name' => 'Semi Final',
                'round_order' => 2,
                'team1_placeholder' => 'Pemenang QF-3',
                'team2_placeholder' => 'Pemenang QF-4',
                'next_match_id' => $final->id,
                'next_match_slot' => 'team2',
                'status' => 'scheduled',
                'court' => 'Lapangan 2',
                'scheduled_time' => '14:00 WIB'
            ]);

            // 4 Quarter Finals
            TournamentMatch::create([
                'competition_id' => $competitionId,
                'match_number' => 'QF-1',
                'round_name' => 'Perempat Final (8 Besar)',
                'round_order' => 1,
                'team1_registration_id' => $shuffled->get(0)?->id,
                'team2_registration_id' => $shuffled->get(1)?->id,
                'next_match_id' => $sf1->id,
                'next_match_slot' => 'team1',
                'status' => 'scheduled',
                'court' => 'Lapangan 1',
                'scheduled_time' => '08:00 WIB'
            ]);

            TournamentMatch::create([
                'competition_id' => $competitionId,
                'match_number' => 'QF-2',
                'round_name' => 'Perempat Final (8 Besar)',
                'round_order' => 1,
                'team1_registration_id' => $shuffled->get(2)?->id,
                'team2_registration_id' => $shuffled->get(3)?->id,
                'next_match_id' => $sf1->id,
                'next_match_slot' => 'team2',
                'status' => 'scheduled',
                'court' => 'Lapangan 2',
                'scheduled_time' => '09:00 WIB'
            ]);

            TournamentMatch::create([
                'competition_id' => $competitionId,
                'match_number' => 'QF-3',
                'round_name' => 'Perempat Final (8 Besar)',
                'round_order' => 1,
                'team1_registration_id' => $shuffled->get(4)?->id,
                'team2_registration_id' => $shuffled->get(5)?->id,
                'next_match_id' => $sf2->id,
                'next_match_slot' => 'team1',
                'status' => 'scheduled',
                'court' => 'Lapangan 1',
                'scheduled_time' => '10:00 WIB'
            ]);

            TournamentMatch::create([
                'competition_id' => $competitionId,
                'match_number' => 'QF-4',
                'round_name' => 'Perempat Final (8 Besar)',
                'round_order' => 1,
                'team1_registration_id' => $shuffled->get(6)?->id,
                'team2_registration_id' => $shuffled->get(7)?->id,
                'next_match_id' => $sf2->id,
                'next_match_slot' => 'team2',
                'status' => 'scheduled',
                'court' => 'Lapangan 2',
                'scheduled_time' => '11:00 WIB'
            ]);

        } else {
            // 4 Teams Bracket (SF -> FINAL)
            $final = TournamentMatch::create([
                'competition_id' => $competitionId,
                'match_number' => 'FINAL',
                'round_name' => 'Grand Final',
                'round_order' => 2,
                'team1_placeholder' => 'Pemenang Semi Final 1',
                'team2_placeholder' => 'Pemenang Semi Final 2',
                'status' => 'scheduled',
                'court' => 'Lapangan Utama',
                'scheduled_time' => '14:30 WIB'
            ]);

            TournamentMatch::create([
                'competition_id' => $competitionId,
                'match_number' => 'SF-1',
                'round_name' => 'Semi Final',
                'round_order' => 1,
                'team1_registration_id' => $shuffled->get(0)?->id,
                'team2_registration_id' => $shuffled->get(1)?->id,
                'next_match_id' => $final->id,
                'next_match_slot' => 'team1',
                'status' => 'scheduled',
                'court' => 'Lapangan 1',
                'scheduled_time' => '09:00 WIB'
            ]);

            TournamentMatch::create([
                'competition_id' => $competitionId,
                'match_number' => 'SF-2',
                'round_name' => 'Semi Final',
                'round_order' => 1,
                'team1_registration_id' => $shuffled->get(2)?->id,
                'team2_registration_id' => $shuffled->get(3)?->id,
                'next_match_id' => $final->id,
                'next_match_slot' => 'team2',
                'status' => 'scheduled',
                'court' => 'Lapangan 2',
                'scheduled_time' => '10:30 WIB'
            ]);
        }

        return redirect()->route('admin.tournament.arena', $competitionId)
            ->with('success', "Bagan Turnamen Sistem Gugur untuk {$competition->title} berhasil di-generate secara acak & siap dipertandingkan!");
    }
}
