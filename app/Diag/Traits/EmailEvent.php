<?php

namespace RZP\Diag\Traits;

use RZP\Diag\Event\EmailEvent as E;

trait EmailEvent
{
    public function trackEmailEvent(
        array $eventData,
        array $customProperties = [],
        \Throwable $ex = null)
    {
        $event = new E($ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(E::EVENT_TYPE, E::EVENT_VERSION, $eventData, $properties);
    }
}
