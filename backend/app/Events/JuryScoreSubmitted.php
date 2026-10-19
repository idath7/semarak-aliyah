<?php

namespace App\Events;

use App\Models\JuryScore;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JuryScoreSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public JuryScore $score;
    public string $competitionId;

    public function __construct(JuryScore $score)
    {
        $this->score = $score->load('registration');
        $this->competitionId = $score->competition_id;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('jury.' . $this->competitionId),
            new Channel('scoreboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'jury.score.submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'score' => $this->score,
            'competitionId' => $this->competitionId,
            'submittedAt' => now()->toIso8601String(),
        ];
    }
}
