<?php

namespace RZP\Diag\Traits;

use Carbon\Carbon;
use RZP\Models\PaymentLink;
use RZP\Constants\Timezone;
use RZP\Diag\Event\PaymentPageEvent as PPE;

trait PaymentPageEvent
{
    public function trackPaymentPageEvent(
        array $eventData,
        PaymentLink\Entity $paymentPage = null,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $customProperties += ['timestamp' => $timestamp];

        $event = new PPE($paymentPage, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(PPE::EVENT_TYPE, PPE::EVENT_VERSION, $eventData, $properties);
    }
}
