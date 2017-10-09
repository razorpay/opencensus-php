<?php

namespace RZP\Gateway\Netbanking\Axis\Emandate;

class StatusCode
{
    const SUCCESS = '000';
    const PENDING = '101';
    const FAILED  = '111';

    const EMANDATE_FAILURE = '0';

    public static function isStatusCodeSuccess(string $statusCode)
    {
        return ($statusCode === self::SUCCESS);
    }

    public static function isEmandateRegistrationSuccess(string $statusCode)
    {
        return ($statusCode !== self::EMANDATE_FAILURE);
    }
}
