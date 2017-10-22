<?php

namespace RZP\Gateway\Netbanking\Axis\Emandate;

use RZP\Error\ErrorCode;

class StatusCode
{
    const SUCCESS = '000';
    const PENDING = '101';
    const FAILED  = '111';

    const EMANDATE_FAILURE = '0';

    protected static $errorCodeMap = [
        self::FAILED  => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::PENDING => ErrorCode::BAD_REQUEST_PAYMENT_PENDING,
    ];

    public static function isStatusCodeSuccess(string $statusCode)
    {
        return ($statusCode === self::SUCCESS);
    }

    public static function isEmandateRegistrationSuccess(string $statusCode)
    {
        return ($statusCode !== self::EMANDATE_FAILURE);
    }

    public static function getErrorCodeMap($errorCode)
    {
        if (isset(self::$errorCodeMap[$errorCode]) === true)
        {
            return self::$errorCodeMap[$errorCode];
        }

        return ErrorCode::GATEWAY_ERROR_REQUEST_ERROR;
    }
}
