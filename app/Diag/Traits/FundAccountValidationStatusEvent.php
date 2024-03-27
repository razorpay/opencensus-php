<?php

namespace RZP\Diag\Traits;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\FundAccount\Validation;
use RZP\Diag\Event\FundAccountValidationStatusEvent as FE;

trait FundAccountValidationStatusEvent
{
    public function trackFundAccountValidationStatusEvent(
        array $eventData,
        Validation\Entity $fav = null,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        $requestId = $this->app['request']->getTaskId();

        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $customProperties +=
            [
                'timestamp'     => $timestamp,
                'requestId'     => $requestId
            ];

        $event = new FE($fav, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(FE::EVENT_TYPE, FE::EVENT_VERSION, $eventData, $properties);
    }
}
