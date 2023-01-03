<?php

namespace RZP\Gateway\P2p\Upi\AxisOlive;

use RZP\Error\P2p\ErrorCode;

use RZP\Gateway\P2p\Upi\ErrorCodes;

class ErrorMap
{
    const INVALID_CALLBACK                              = 'INVALID_CALLBACK';

    public static $errorMap = [
        self::INVALID_CALLBACK                          => ErrorCode::GATEWAY_ERROR_INVALID_CALLBACK_URL,
    ];

    public static function map(string $gatewayCode)
    {
        if (isset(self::$errorMap[$gatewayCode]) === true)
        {
            return self::$errorMap[$gatewayCode];
        }

        return ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE;
    }

    public static function gatewayMap(string $gatewayCode)
    {
        if (isset(ErrorCodes::$errorCodeMap[$gatewayCode]) === true)
        {
            return ErrorCodes::$errorCodeMap[$gatewayCode];
        }

        return ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE;
    }
}
