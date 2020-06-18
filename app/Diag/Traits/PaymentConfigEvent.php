<?php

namespace RZP\Diag\Traits;

use RZP\Diag\Event\PaymentConfigEvent as PE;
use RZP\Models\Payment\Config\Entity;


trait PaymentConfigEvent
{
    public function trackPaymentConfigEvent(
        array $eventDetails,
        Entity $config = null,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        $event = new PE($config, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(PE::EVENT_TYPE, PE::EVENT_VERSION, $eventDetails, $properties);
    }
}
