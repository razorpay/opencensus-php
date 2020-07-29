<?php

namespace RZP\Models\UpiMandate;

class Frequency
{
    const DAILY        = 'daily';
    const AS_PRESENTED = 'as_presented';
    const WEEKLY       = 'weekly';
    const BIMONTHLY    = 'bimonthly';
    const MONTHLY      = 'monthly';
    const QUARTERLY    = 'quarterly';
    const HALF_YEARLY  = 'half_yearly';
    const YEARLY       = 'yearly';

    public static $frequencyToRecurringValueMap = [
        self::WEEKLY       => 7,
        self::BIMONTHLY    => 15,
        self::MONTHLY      => 31,
        // For these frequencies, we have been given the following recur values. There is still some confusion around
        // these. Will change once more clarity is there.
        self::QUARTERLY    => 31,
        self::HALF_YEARLY  => 31,
        self::YEARLY       => 31,
    ];


    public static function isValid($frequency)
    {
        return (defined(Frequency::class.'::'.strtoupper($frequency)));
    }

    public static function shouldSkipNotify(string $gateway, string $frequency): bool
    {
        // As per banks notification is made mandatory for all frequencies
        return false;
    }
}
