<?php

namespace RZP\Diag\Traits;

use RZP\Models\Application;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Diag\Event\BalanceEvent as BE;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Balance;

trait BalanceEvent
{
    public function trackBalanceEvents(
        array $eventData,
        Balance\Entity $entity = null,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        if ($this->shouldTrackBalanceEvent($customProperties) === false)
        {
            return;
        }

        $requestId = $this->app['request']->getTaskId();

        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $customProperties +=
            [
                'timestamp'     => $timestamp,
                'requestId'     => $requestId
            ];

        $event = new BE($entity, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(BE::EVENT_TYPE, BE::EVENT_VERSION, $eventData, $properties);
    }

    public function shouldTrackBalanceEvent($customProperties)
    {
        if (isset($customProperties['channel']) === true and
            $customProperties['channel'] === Application\Entity::SLACK_APP)
        {
            return true;
        }

        return false;
    }
}
