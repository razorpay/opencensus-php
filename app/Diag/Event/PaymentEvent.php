<?php

namespace RZP\Diag\Event;

class PaymentEvent extends Event
{
    const EVENT_TYPE = 'payment_events';
    const EVENT_VERSION = 'v1';

    public function getProperties()
    {
        
    }
}
