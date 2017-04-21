<?php

namespace RZP\Events;

use RZP\Events\Event;
use Illuminate\Queue\SerializesModels;

class DifferEvent extends Event
{
    use SerializesModels;

    public $event;

    /**
     * Create a new event instance.
     *
     * @param $event
     */
    public function __construct($event)
    {
        $this->event = $event;
    }
}
