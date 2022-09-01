<?php

namespace RZP\Listeners;

use RZP\Models\Terminal;
use RZP\Trace\TraceCode;

class TerminalsEventListener
{
    public function onRetrieved(Terminal\EventRetrieved $event)
    {
        $entity = $event->entity;

        app('trace')->info(TraceCode::TERMINALS_RETRIEVAL_EVENT, ['terminal_id' => $entity->getId(),]);
    }

    public function onSaved(Terminal\EventSaved $event)
    {
        $entity = $event->entity;

        app('trace')->info(TraceCode::TERMINALS_SAVED_EVENT, ['terminal_id' => $entity->getId(),]);
    }

}
