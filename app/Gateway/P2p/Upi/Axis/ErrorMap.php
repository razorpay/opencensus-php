<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Error\P2p\ErrorCode;

class ErrorMap
{
    const NOT_AVAILABLE = 'NOT_AVAILABLE';
    const UNAUTHORIZED  = 'UNAUTHORIZED';

    public static $errorMap = [
        self::NOT_AVAILABLE     => ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
        self::UNAUTHORIZED      => ErrorCode::GATEWAY_ERROR_DEVICE_INVALID_TOKEN,
    ];

    public static function map(string $gatewayCode)
    {
        if (isset(self::$errorMap[$gatewayCode]) === true)
        {
            return self::$errorMap[$gatewayCode];
        }

        return ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE;
    }
}
