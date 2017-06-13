<?php

namespace RZP\Gateway\Netbanking\Axis;

class Status
{
    const YES            = 'Y';
    const NO             = 'N';
    const SUCCESS        = 'S';
    const FAILURE        = 'F';

    public static function getAuthSuccessStatus()
    {
        return self::YES;
    }
}
