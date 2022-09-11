<?php

namespace RZP\Listeners;

use RZP\Models\Terminal;
use RZP\Trace\TraceCode;

class TerminalsEventListener
{
    public function onRetrieved(Terminal\EventRetrieved $event)
    {
        $entity = $event->entity;

        (new Terminal\Service())->logRouteName($entity->getId());
    }

    public function onSaved(Terminal\EventSaved $event)
    {
        $entity = $event->entity;

        (new Terminal\Service())->logRouteName($entity->getId());
    }

}
