<?php

namespace RZP\Diag\Traits;

use RZP\Diag\Event\OrderEvent as OE;
use RZP\Models\Order;

trait OrderEvent
{
    public function trackOrderEvent(
        array $eventDetails,
        Order\Entity $order = null,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        $event = new OE($order, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(OE::EVENT_TYPE, 'v2', $eventDetails, $properties);
    }
}
