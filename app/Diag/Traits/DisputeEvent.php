<?php

namespace RZP\Diag\Traits;

use RZP\Diag\Event\DisputeEvent as DE;
use RZP\Models\Dispute;

trait DisputeEvent
{
    public function trackDisputeEvent(
        array $eventData,
        Dispute\Entity $dispute = null,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        $event = new DE($dispute, $ex, $customProperties);

        $properties = $event->getProperties();

        return $this->trackEvent(DE::EVENT_TYPE, DE::EVENT_VERSION, $eventData, $properties);
    }
}
