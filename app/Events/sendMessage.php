<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class sendMessage extends Event
{
    use SerializesModels;

    public $messageData;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($messageData)
    {
        $this->messageData = $messageData;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
     public function broadcastOn()
     {
         return ['message-added'];
     }

     public function broadcastWith()
     {
         return ['messageData' => $this->messageData];
     }
}
