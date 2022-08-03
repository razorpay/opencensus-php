<?php

namespace RZP\Diag\Traits;

use RZP\Diag\Event\PaymentFraudEvent as PFE;
use RZP\Models\Payment\Fraud;

trait PaymentFraudEvent
{
    public function trackPaymentFraudEvent(
        array $eventData,
        Fraud\Entity $paymentFraud = null,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        $event = new PFE($paymentFraud, $ex, $customProperties);

        $properties = $event->getProperties();

        return $this->trackEvent(PFE::EVENT_TYPE, PFE::EVENT_VERSION, $eventData, $properties);
    }
}
