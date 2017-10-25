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

    protected static $errorDescriptionMap = [
        self::FAILED  => 'Failed',
        self::PENDING => 'Pending',
    ];

    public static function isSuccess(string $statusCode)
    {
        return ($statusCode === self::SUCCESS);
    }

    public static function isEmandateRegistrationSuccess(string $statusCode)
    {
        return ($statusCode !== self::EMANDATE_FAILURE);
    }

    public static function getErrorCodeMap($errorCode)
    {
        return self::$errorCodeMap[$errorCode] ?? ErrorCode::GATEWAY_ERROR_REQUEST_ERROR;
    }

    public static function getErrorDescriptionMap($errorCode)
    {
        return self::$errorDescriptionMap[$errorCode] ?? null;
    }
}
