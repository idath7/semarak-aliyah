<?php

namespace App\Events;

use App\Models\TournamentMatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchScoreUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TournamentMatch $match;
    public string $competitionId;

    public function __construct(TournamentMatch $match)
    {
        $this->match = $match->load(['team1', 'team2', 'winner']);
        $this->competitionId = $match->competition_id;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('tournament.' . $this->competitionId),
            new Channel('scoreboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'match.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'match' => $this->match,
            'competitionId' => $this->competitionId,
            'updatedAt' => now()->toIso8601String(),
        ];
    }
}
