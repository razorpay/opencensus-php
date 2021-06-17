<?php

namespace RZP\Modules\Acs;

use RZP\Events\Event;

class TriggerSyncEvent extends Event
{
    public $eventId;

    public function __construct()
    {
        $this->eventId = uniqid();
    }
}
