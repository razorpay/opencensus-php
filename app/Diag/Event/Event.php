<?php

namespace RZP\Diag\Event;

abstract class Event
{
    const EVENT_TYPE = 'default';
    const EVENT_VERSION = 'v1';

    abstract function getProperties();

}
