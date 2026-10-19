<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Competition;
use App\Models\JuryScore;
use App\Models\Registration;

class LiveScoreboardController extends Controller
{
    /**
     * Show Live Scoreboard for Stage Projector.
     */
    public function index(Request $request)
    {
        $competitions = Competition::orderBy('title')->get();

        // Persist selected competition in session so user doesn't reset to the first one
        if ($request->filled('competition')) {
            $selectedCompId = $request->input('competition');
            session(['scoreboard_selected_competition' => $selectedCompId]);
        } else {
            $selectedCompId = session('scoreboard_selected_competition', $competitions->first()?->id);
            if ($selectedCompId && !$competitions->contains('id', $selectedCompId)) {
                $selectedCompId = $competitions->first()?->id;
            }
            if ($selectedCompId) {
                session(['scoreboard_selected_competition' => $selectedCompId]);
            }
        }

        $selectedCompetition = $selectedCompId ? Competition::find($selectedCompId) : null;

        // Leaderboard ranked by highest total score (for non-tournament)
        $leaderboard = collect();
        $tournamentMatches = collect();
        $tournamentRounds = collect();
        $isTournament = false;

        if ($selectedCompetition) {
            if ($selectedCompetition->isTournament()) {
                $isTournament = true;
                $tournamentMatches = \App\Models\TournamentMatch::where('competition_id', $selectedCompId)
                    ->with(['team1', 'team2', 'winner'])
                    ->orderBy('round_order')
                    ->orderBy('id')
                    ->get();
                $tournamentRounds = $tournamentMatches->groupBy('round_name');
            } else {
                $leaderboard = JuryScore::where('competition_id', $selectedCompId)
                    ->with('registration')
                    ->orderBy('total_score', 'desc')
                    ->get();
            }
        }

        // Return JSON if polled via standalone AJAX/API (exclude Inertia requests)
        if (!$request->header('X-Inertia') && ($request->ajax() || $request->wantsJson())) {
            return response()->json([
                'status' => 'success',
                'competition' => $selectedCompetition,
                'is_tournament' => $isTournament,
                'leaderboard' => $leaderboard,
                'tournament_matches' => $tournamentMatches
            ]);
        }

        return \Inertia\Inertia::render('Public/Scoreboard', [
            'competitions' => $competitions,
            'selectedCompetition' => $selectedCompetition,
            'leaderboard' => $leaderboard,
            'isTournament' => $isTournament,
            'activeMatch' => $tournamentMatches instanceof \Illuminate\Support\Collection ? $tournamentMatches->first() : null,
            'recentMatches' => $tournamentMatches,
            'topJuryScores' => $leaderboard,
        ]);
    }
}
