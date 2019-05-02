<?php

namespace RZP\Diag\Traits;

use RZP\Diag\Event\PaymentEvent as PE;
use RZP\Models\Payment;

trait PaymentEvent
{
    public function trackOrderEvent(string $code, array $customProperties = [])
    {
        $this->trackEvent(PE::EVENT_TYPE, PE::EVENT_VERSION, $code, $customProperties);
    }

    public function trackPaymentEvent(
        string $code, 
        Payment\Entity $payment = null, 
        \Throwable $ex = null, 
        array $customProperties = [])
    {
        $event = new PE($payment, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(PE::EVENT_TYPE, PE::EVENT_VERSION, $code, $properties);
    }
}
