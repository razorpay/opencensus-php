<?php

namespace RZP\Events;

use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Testing\Fakes\EventFake;
use Illuminate\Support\Facades\Event as BaseEventFacade;

/**
 * The event facade is overwritten, because the base implementation
 * of fake, also fakes eloquent model events. Hence any listeners, on
 * eloqouent events (set in query cache events etc), can't be tested.
 * This implementation, controls faking of model events based on a flag.
 */
class Facade extends BaseEventFacade
{
    public static function fake(bool $fakeModelEvents = true)
    {
        $originalDispatcher = Event::getFacadeRoot();

        static::swap($fake = new EventFake);

        if ($fakeModelEvents === true)
        {
            Model::setEventDispatcher($fake);
        }
    }
}
