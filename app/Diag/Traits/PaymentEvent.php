<?php

namespace RZP\Diag\Traits;

use RZP\Diag\Event\PaymentEvent as PE;
use RZP\Models\Payment;

trait PaymentEvent
{
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

    public function trackVerifyPaymentEvent(
        string $code, 
        Payment\Entity $payment = null, 
        \Throwable $ex = null)
    {
        $customProperties = [
            'status'    => $payment->getStatus(),
            'bucket'    => $payment->getVerifyBucket()
        ];

        $this->trackPaymentEvent($code, $payment, $ex, $customProperties);
    }
}
