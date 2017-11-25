<?php

namespace RZP\Listeners;

use App;
use RZP\Trace\TraceCode;

class LogCache
{
    public function handle()
    {
        $trace = App::getFacadeRoot();

        $trace->info(TraceCode::CACHE_LOG, func_get_args());
    }
}