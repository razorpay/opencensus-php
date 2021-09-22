<?php

namespace RZP\Modules\Acs;

use RZP\Events\Event;

class RecordSyncEvent extends Event
{
    public $eventId;
    public $entity;

    public function __construct($entity)
    {
        $this->eventId = uniqid();
        $this->entity = $entity;
    }
}
