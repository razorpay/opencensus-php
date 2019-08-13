<?php

namespace RZP\Diag\Traits;

use RZP\Diag\Event\SettlementEvent as SE;
use RZP\Models\Settlement;

trait SettlementEvent
{
    public function trackSettlementEvent(
        array $eventDetails,
        Settlement\Entity $settlement = null,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        $event = new SE($settlement, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(SE::EVENT_TYPE, SE::EVENT_VERSION, $eventDetails, $properties);
    }
}
