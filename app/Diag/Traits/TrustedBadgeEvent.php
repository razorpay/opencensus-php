<?php

namespace RZP\Diag\Traits;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Diag\Event\TrustedBadgeEvent as RTBEvent;
use RZP\Trace\TraceCode;

trait TrustedBadgeEvent
{
    public function trackTrustedBadgeEvent(
        array $eventData,
        array $customProperties = []
    ): void
    {
        try
        {
            $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

            $customProperties += ['timestamp' => $timestamp];

            $this->trackEvent(RTBEvent::EVENT_TYPE, RTBEvent::EVENT_VERSION, $eventData, $customProperties);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, null, null, TraceCode::TRUSTED_BADGE_EVENT_FAILURE);
        }
    }
}
