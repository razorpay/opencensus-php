<?php

namespace RZP\Services;

use App;
use Exception;
use Carbon\Carbon;
use RZP\Diag\Traits;

class DiagClient extends EventTrackerClient
{
    use Traits\IINEvent;
    use Traits\EmailEvent;
    use Traits\OrderEvent;
    use Traits\RewardEvent;
    use Traits\PayoutEvent;
    use Traits\BalanceEvent;
    use Traits\PaymentEvent;
    use Traits\DisputeEvent;
    use Traits\SettlementEvent;
    use Traits\OnBoardingEvent;
    use Traits\PaymentPageEvent;
    use Traits\UpiTransferEvent;
    use Traits\BankTransferEvent;
    use Traits\PaymentFraudEvent;
    use Traits\TrustedBadgeEvent;
    use Traits\PaymentConfigEvent;
    use Traits\VirtualAccountEvent;
    use Traits\VirtualVpaPrefixEvent;
    use Traits\TokenisationEvent;

    public function trackEvent(string $eventType, string $eventVersion, array $event,
                               array $properties,
                               array $metaData = null,
                               array $readKey = [] ,
                               string $writeKey = null)
    {
        $event = [
            'event_type'    => $eventType,
            'event_version' => $eventVersion,
            'event_group'   => $event['group'],
            'event'         => $event['name'],
            'timestamp'     => (int)(microtime(true) * 1000000),
            'properties'    => $properties,
        ];


        if (($eventVersion === 'v2') === true)
        {
            $event['event_trackId']  = $this->app['req.context']->getTrackId();
            $event['metadata']       = $metaData;
            $event['read_key']       = $readKey;
            $event['write_key']      = $writeKey;
        }

        $this->events[] = $event;

        return $event;
    }

    protected function getEventContext()
    {
        return [
            'task_id'    => $this->request->getTaskId(),
            'request_id' => $this->request->getId()
        ];
    }
}
