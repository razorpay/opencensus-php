<?php

namespace RZP\Jobs\Extended;

class PendingChain extends \Illuminate\Foundation\Bus\PendingChain
{
    /**
     * @return PendingDispatch
     */
    public function dispatch()
    {
        return (new PendingDispatch(new $this->class(...func_get_args())))->chain($this->chain);
    }
}
