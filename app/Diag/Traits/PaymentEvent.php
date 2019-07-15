<?php

namespace RZP\Diag\Traits;

use RZP\Diag\Event\PaymentEvent as PE;
use RZP\Models\Payment;

trait PaymentEvent
{
    public function trackPaymentEvent(
        array $eventData,
        Payment\Entity $payment = null,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        $event = new PE($payment, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(PE::EVENT_TYPE, PE::EVENT_VERSION, $eventData, $properties);
    }

    public function trackVerifyPaymentEvent(
        array $event,
        Payment\Entity $payment = null,
        \Throwable $ex = null)
    {
        $customProperties = [
            'status'    => $payment->getStatus(),
            'bucket'    => $payment->getVerifyBucket()
        ];

        $this->trackPaymentEvent($event, $payment, $ex, $customProperties);
    }

    public function trackGatewayPaymentEvent(
        array $eventData,
        array $gatewayInput,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        $event = new PE(null, $ex, $customProperties);

        $properties = $event->parseGatewayProperties($gatewayInput);

        $this->trackEvent(PE::EVENT_TYPE, PE::EVENT_VERSION, $eventData, $properties);
    }
}
