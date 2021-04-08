<?php

namespace RZP\Models\Partner\Activation;

class Action
{
    const HOLD_COMMISSIONS                          = 'hold_commissions';
    const RELEASE_COMMISSIONS                       = 'release_commissions';

    public static function exists($action)
    {
        return defined(get_class() . '::' . strtoupper($action));
    }
}
