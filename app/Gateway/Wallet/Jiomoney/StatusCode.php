<?php

namespace RZP\Gateway\Wallet\Jiomoney;

class StatusCode
{
    const SUCCESS        = '000';
    const INTERNAL_ERROR = '500';
    const UNAUTHORIZED   = '401';

    public static function isSuccessStatus($statusCode)
    {
        return ($statusCode === self::SUCCESS);
    }
}
