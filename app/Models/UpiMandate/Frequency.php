<?php

namespace RZP\Models\UpiMandate;

class Frequency
{
    const ONE_TIME     = 'one_time';
    const DAILY        = 'daily';
    const AS_PRESENTED = 'as_presented';
    const WEEKLY       = 'weekly';
    const FORTNIGHTLY  = 'fortnightly';
    const MONTHLY      = 'monthly';
    const BIMONTHLY    = 'bimonthly';
    const QUARTERLY    = 'quarterly';
    const HALF_YEARLY  = 'half_yearly';
    const YEARLY       = 'yearly';

    public static function isValid($frequency)
    {
        return (defined(Frequency::class.'::'.strtoupper($frequency)));
    }
}
