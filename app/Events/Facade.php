<?php

namespace RZP\Events;

use Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Testing\Fakes\EventFake;
use Illuminate\Support\Facades\Facade as BaseFacade;

/**
 * The event facade is overwritten, because the base implementation
 * of fake, also fakes eloquent model events. Hence any listeners, on
 * eloqouent events (set in query cache events etc), can't be tested.
 * This implementation, controls faking of model events based on a flag.
 */
class Facade extends BaseFacade
{
    public static function fake(bool $fakeModelEvents = true, array $eventsToFake = [])
    {
        $originalDispatcher = Event::getFacadeRoot();

        static::swap($fake = new EventFake($originalDispatcher, $eventsToFake));

        if ($fakeModelEvents === true)
        {
            Model::setEventDispatcher($fake);
        }

        Cache::refreshEventDispatcher();
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'events';
    }
}
