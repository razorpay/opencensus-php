<?php

namespace RZP\Models\PaperMandate;

class Frequency
{
    const AS_AND_WHEN_PRESENTED = 'as_and_when_presented';
    const HOURLY                = 'hourly';
    const MONTHLY               = 'monthly';
    const QUARTERLY             = 'quarterly';
    CONST YEARLY                = 'yearly';

    public static function isValidType($type)
    {
        return (defined(__CLASS__.'::'.strtoupper($type)));
    }
}
