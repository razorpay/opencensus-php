<?php

namespace RZP\Diag\Traits;

use App;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Diag\Event\OnBoardingEvent as OE;
use RZP\Models\Merchant;
use RZP\Models\Partner;

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

        $partnerDomainProperties = (new Partner\Core())->getPartnerDomainProperties($merchant);

        $customProperties +=  $partnerDomainProperties;

        $event = new OE($merchant, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(OE::EVENT_TYPE, OE::EVENT_VERSION, $eventData, $properties);
    }
}
