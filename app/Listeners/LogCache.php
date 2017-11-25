<?php

namespace RZP\Listeners;

use App;
use RZP\Trace\TraceCode;

class LogCache
{
    public function handle($event)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->info(TraceCode::CACHE_EVENT, [
            'event'     => get_class($event),
            'arguments' => func_get_args(),
        ]);
    }
}
