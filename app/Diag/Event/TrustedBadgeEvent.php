<?php

namespace RZP\Diag\Event;

class TrustedBadgeEvent extends Event
{
    const EVENT_TYPE        = 'payment-events';

    const EVENT_VERSION     = 'v2';

    protected function getEventProperties()
    {
        return [];
    }
}
