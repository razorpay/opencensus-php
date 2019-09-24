<?php

namespace RZP\Diag\Traits;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Diag\Event\OnBoardingEvent as OE;
use RZP\Models\Merchant;

trait OnBoardingEvent
{
    public function trackOnboardingEvent(
        array $eventData,
        Merchant\Entity $merchant = null,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $customProperties += ['timestamp' => $timestamp];

        $event = new OE($merchant, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(OE::EVENT_TYPE, OE::EVENT_VERSION, $eventData, $properties);
    }
}
