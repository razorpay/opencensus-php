<?php

namespace RZP\Modules\Acs;

use RZP\Events\Event;
use RZP\Jobs\Extended\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueryShadowModeEvent extends Event
{
    use Dispatchable;

    public $queryString;
    public $functionName;
    public $queryBindings;

    public function __construct($functionName, $queryString, $queryBindings)
    {
        $this->queryString = $queryString;
        $this->functionName = $functionName;
        $this->queryBindings = $queryBindings;
    }

}