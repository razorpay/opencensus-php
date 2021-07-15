<?php

namespace RZP\Diag\Traits;

use RZP\Models\Card\IIN;
use RZP\Diag\Event\IINEvent as IE;

trait IINEvent
{
    public function trackIINEvent(
        array $eventData,
        IIN\Entity $iin = null,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        $event = new IE($iin, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(IE::EVENT_TYPE, IE::EVENT_VERSION, $eventData, $properties);
    }
}
