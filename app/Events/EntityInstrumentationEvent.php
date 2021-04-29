<?php

namespace RZP\Events;

class EntityInstrumentationEvent extends Event
{
    public $eventId;
    public $eventName;
    public $entityName;

    public function __construct($eventName, $entityName)
    {
        $this->eventId = uniqid();
        $this->eventName = $eventName;
        $this->entityName = $entityName;
    }
}
