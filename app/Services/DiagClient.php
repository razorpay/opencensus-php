<?php

namespace RZP\Services;

use App;
use Exception;
use Carbon\Carbon;
use RZP\Diag\Traits\Payment;
use RZP\Diag\Traits\PaymentEvent;

class DiagClient extends EventTrackerClient
{
    use Payment;
    use PaymentEvent;

    public function trackEvent(string $eventType, string $eventVersion, string $eventName, array $properties))
    {
        $event = [
            'event_type'    => $eventType,
            'event_version' => $eventVersion,
            'event_name'    => $eventName,
            'timestamp'     => (int)(microtime(true) * 1000),
            'properties'    => $properties,
        ];

        $this->events[] = $event;
    }
}
