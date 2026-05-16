<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SiteStatusUpdated implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param array{
     *     site_id:int,
     *     configuration_id:int,
     *     status:string,
     *     response_time_ms:int|null,
     *     checked_at:string
     * } $payload
     */
    public function __construct(
        public int $userId,
        public array $payload,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'site.status.updated';
    }

    /**
     * @return array{
     *     site_id:int,
     *     configuration_id:int,
     *     status:string,
     *     response_time_ms:int|null,
     *     checked_at:string
     * }
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
