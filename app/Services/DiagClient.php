<?php

namespace RZP\Services;

use App;
use Exception;
use Carbon\Carbon;
use RZP\Diag\Traits;

class DiagClient extends EventTrackerClient
{
    use Traits\OrderEvent;
    use Traits\PaymentEvent;

    public function trackEvent(string $eventType, string $eventVersion, string $eventName, array $properties)
    {
        $event = [
            'event_type'    => $eventType,
            'event_version' => $eventVersion,
            'event'         => $eventName,
            'timestamp'     => (int)(microtime(true) * 1000),
            'properties'    => $properties,
        ];

        $this->events[] = $event;
    }

    protected function getEventContext()
    {
        return [
            'task_id'    => $this->request->getTaskId(),
            'request_id' => $this->request->getId()
        ];
    }
}
