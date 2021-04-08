<?php


namespace RZP\Diag\Traits;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Reward;
use RZP\Diag\Event\RewardEvent as REvent;

trait RewardEvent
{
    public function trackRewardUpdateEvent(
        array $eventData,
        Reward\Entity $Reward = null,
        \Throwable $ex = null,
        array $customProperties = []
    )
    {

        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $customProperties += ['timestamp' => $timestamp];

        $event = new REvent($Reward, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(REvent::EVENT_TYPE, REvent::EVENT_VERSION, $eventData, $properties);
    }
}
