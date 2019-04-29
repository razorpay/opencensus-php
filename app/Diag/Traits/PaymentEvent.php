<?php

namespace RZP\Diag\Traits;

use RZP\Diag\Event\PaymentEvent as PE;

trait PaymentEvent
{
    public function trackOrderEvent(string $code, array $customProperties)
    {
        $this->trackEvent(PE::EVENT_TYPE, PE::EVENT_VERSION, $code, $customProperties);
    }

    public function trackPaymentEvent(string $code, Payment\Entity $payment = null, array $customProperties)
    {
        $event = new PE($payment, $customProperties);

        $properties = $event->getFormattedProterites();

        $this->trackEvent(PE::EVENT_TYPE, PE::EVENT_VERSION, $code, $properties);
    }
}
