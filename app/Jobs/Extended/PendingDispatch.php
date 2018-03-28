<?php

namespace RZP\Jobs\Extended;

/**
 * Overridden: Just before destruction calls Routeable's method to set proper connection and queue name, Ref Routeable.
 */
class PendingDispatch extends \Illuminate\Foundation\Bus\PendingDispatch
{
    use Routeable;

    public function __destruct()
    {
        $this->routeJobPerConfig();

        parent::__destruct();
    }
}
