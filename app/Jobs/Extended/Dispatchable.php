<?php

namespace RZP\Jobs\Extended;

/**
 * Replacement for L5's Dispatchable trait. Returns overridden implementation of PendingDispatch & PendingChain.
 */
trait Dispatchable
{
    public static function dispatch(): PendingDispatch
    {
        return new PendingDispatch(new static(...func_get_args()));
    }

    public static function withChain(array $chain): PendingChain
    {
        return new PendingChain(get_called_class(), $chain);
    }
}
