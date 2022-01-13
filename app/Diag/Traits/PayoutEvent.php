<?php

namespace RZP\Diag\Traits;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Application;
use RZP\Diag\Event\PayoutEvent as PE;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Payout;

trait PayoutEvent
{
    public function trackPayoutApproveRejectActionEvents(
        array $eventData,
        Payout\Entity $payout = null,
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

        if ($this->shouldTrackPayoutEvent($customProperties) === false)
        {
            return;
        }

        $event = new PE($payout, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(PE::EVENT_TYPE, PE::EVENT_VERSION, $eventData, $properties);
    }

    public function trackPayoutsFetchEvent(
        array $eventData,
        Payout\Entity $payout = null,
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

        if ($this->shouldTrackPayoutEvent($customProperties) === false)
        {
            return;
        }

        $event = new PE($payout, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(PE::EVENT_TYPE, PE::EVENT_VERSION, $eventData, $properties);
    }

    public function shouldTrackPayoutEvent($customProperties)
    {
        if (isset($customProperties['channel']) === true and
            $customProperties['channel'] === Application\Entity::SLACK_APP)
        {
            return true;
        }

        return false;
    }
}
