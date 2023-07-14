<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HandDividendEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $hand;

    /**
     * Create a new event instance.
     * @param $order
     * @param int $hand
     * @return void
     */
    public function __construct($order,$hand=1)
    {
        $this->order = $order;
        $this->hand = $hand;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
