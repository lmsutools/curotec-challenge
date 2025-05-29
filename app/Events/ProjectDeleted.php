<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $projectId;
    public int $userId;
    /**
     * Create a new event instance.
     */
    public function __construct(int $projectId, int $userId)
    {
        $this->projectId = $projectId;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ProjectDeleted';
    }

    public function broadcastWith(): array
    {
        return ['projectId' => $this->projectId];
    }
}
