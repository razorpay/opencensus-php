<?php

namespace RZP\Listeners;

use App;
use RZP\Trace\TraceCode;

class LogCache
{
    public function handle()
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->info(TraceCode::CACHE_EVENT, [
            'arguments' => func_get_args()
        ]);
    }
}
