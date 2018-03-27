<?php

namespace RZP\Jobs\Extended;

class PendingDispatch extends \Illuminate\Foundation\Bus\PendingDispatch
{
    use Routeable;

    public function __destruct()
    {
        $this->routeJobPerConfig();

        parent::__destruct();
    }
}
