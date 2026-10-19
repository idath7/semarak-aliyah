<?php

namespace App\Events;

use App\Models\Registration;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GateCheckinLogged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Registration $registration;
    public string $action; // 'checkin' | 'undo'

    public function __construct(Registration $registration, string $action = 'checkin')
    {
        $this->registration = $registration;
        $this->action = $action;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('gate.checkin'),
            new Channel('admin.dashboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'gate.logged';
    }

    public function broadcastWith(): array
    {
        return [
            'registration' => $this->registration,
            'action' => $this->action,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
