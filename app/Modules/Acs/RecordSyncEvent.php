<?php

namespace RZP\Modules\Acs;

use RZP\Events\Event;

class RecordSyncEvent extends Event
{
    public $eventId;
    public $entity;
    public $outboxJobs;

    public function __construct($entity, $outboxJobs)
    {
        $this->eventId = uniqid();
        $this->entity = $entity;
        $this->outboxJobs = $outboxJobs;
    }
}
