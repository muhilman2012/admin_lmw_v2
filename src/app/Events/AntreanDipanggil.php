<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AntreanDipanggil implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public $queueNumber, 
        public $counterNumber, 
        public $name
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('kiosk-channel')];
    }

    public function broadcastAs(): string
    {
        return 'AntreanDipanggil';
    }
}