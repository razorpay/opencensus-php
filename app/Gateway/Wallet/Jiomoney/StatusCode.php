<?php

namespace RZP\Gateway\Wallet\Jiomoney;

class StatusCode
{
    const SUCCESS        = '000';
    const INTERNAL_ERROR = '500';
    const UNAUTHORIZED   = '401';

    const FAILURE_STATUSES = [
        self::INTERNAL_ERROR,
        self::UNAUTHORIZED,
    ];

    public static function getFailureStatuses()
    {
        return self::FAILURE_STATUSES;
    }
}
