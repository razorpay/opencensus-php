<?php

namespace RZP\Diag\Traits;

use RZP\Diag\Event\OrderEvent as OE;
use RZP\Models\Order;

trait OrderEvent
{
    public function trackOrderEvent(
        array $event,
        Order\Entity $order = null,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        $event = new OE($order, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(OE::EVENT_TYPE, OE::EVENT_VERSION, $event, $properties);
    }
}
