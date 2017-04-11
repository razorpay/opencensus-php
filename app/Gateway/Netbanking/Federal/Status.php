<?php

namespace RZP\Gateway\Netbanking\Federal;

class Status
{
    const YES = 'Y';
    const NO  = 'N';

    const SUCCESS = 'S';

    public static function getAuthSuccessStatus()
    {
        return self::YES;
    }
}
