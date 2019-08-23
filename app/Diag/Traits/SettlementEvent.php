<?php

namespace RZP\Diag\Traits;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Diag\Event\SettlementEvent as SettlEvent;
use RZP\Models\Settlement;

trait SettlementEvent
{
    public function trackSettlementEvent(
        array $eventDetails,
        Settlement\Entity $settlement = null,
        \Throwable $ex = null,
        array $customProperties = [])
    {

        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $customProperties += ['timestamp' => $timestamp];

        s($customProperties);

        $event = new SettlEvent($settlement, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(
            SettlEvent::EVENT_TYPE,
            SettlEvent::EVENT_VERSION,
            $eventDetails,
            $properties);
    }
}
