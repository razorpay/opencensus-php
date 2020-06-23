<?php

namespace RZP\Diag\Event;

class EmailEvent extends Event
{
    const EVENT_TYPE = 'email-events';
    const EVENT_VERSION = 'v1';

    public function __construct(\Throwable $ex = null, array $customProperties = [])
    {
        parent::__construct(null, $ex, $customProperties);
    }
}
