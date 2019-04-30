<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Error\P2p\ErrorCode;
use RZP\Gateway\Base\ErrorCodes\Upi;

class ErrorMap
{
    const NOT_AVAILABLE                                 = 'NOT_AVAILABLE';
    const INVALID_CALLBACK                              = 'INVALID_CALLBACK';
    const INVALID_RESPONSE                              = 'INVALID_RESPONSE';
    const INACTIVE_DEVICE                               = 'INACTIVE_DEVICE';
    const UNAUTHORIZED                                  = 'UNAUTHORIZED';
    const SESSION_EXPIRED                               = 'SESSION_EXPIRED';
    const INVALID_DATA                                  = 'INVALID_DATA';

    public static $errorMap = [
        self::NOT_AVAILABLE                             => ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
        self::INVALID_CALLBACK                          => ErrorCode::GATEWAY_ERROR_INVALID_CALLBACK_URL,
        self::INVALID_RESPONSE                          => ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
        self::INACTIVE_DEVICE                           => ErrorCode::BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE,
        self::UNAUTHORIZED                              => ErrorCode::GATEWAY_ERROR_DEVICE_INVALID_TOKEN,
        self::SESSION_EXPIRED                           => ErrorCode::GATEWAY_ERROR_DEVICE_INVALID_TOKEN,
        self::INVALID_DATA                              => ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
    ];

    public static $deemedErrors = [
        'BT',

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
        if (isset(Upi\ErrorCodes::$errorCodeMap[$gatewayCode]) === true)
        {
            return Upi\ErrorCodes::$errorCodeMap[$gatewayCode];
        }

        return ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE;
    }

    public static function isDeemedError(string $gatewayCode)
    {
        return in_array($gatewayCode, self::$deemedErrors, true);
    }
}
