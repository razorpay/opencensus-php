<?php

namespace RZP\Listeners;

use App;
use RZP\Trace\TraceCode;

class CacheEventLogger
{
    const REMEMBERABLE_KEY = 'rememberable';

    public function handle($event)
    {
        if (property_exists($event, 'key') === false)
        {
            return;
        }

        $isRememberableEvent = strpos($event->key, self::REMEMBERABLE_KEY);

        //
        // Only trace rememberable events
        // https://github.com/razorpay/api/pull/6256
        //
        if ($isRememberableEvent === false)
        {
            return;
        }

        $trace = App::getFacadeRoot()['trace'];

        $trace->info(TraceCode::CACHE_EVENT, [
            'event'     => get_class($event),
            'arguments' => func_get_args(),
        ]);
    }
}
