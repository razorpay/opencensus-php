<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Error\P2p\ErrorCode;

use RZP\Gateway\Base\ErrorCodes\Upi;

class ErrorMap
{
    const NOT_AVAILABLE                                 = 'NOT_AVAILABLE';
    const INVALID_CALLBACK                              = 'INVALID_CALLBACK';
    const INACTIVE_DEVICE                               = 'INACTIVE_DEVICE';
    const UNAUTHORIZED                                  = 'UNAUTHORIZED';
    const SESSION_EXPIRED                               = 'SESSION_EXPIRED';
    const INVALID_DATA                                  = 'INVALID_DATA';
    const INVALID_SIGNATURE                             = 'INVALID_SIGNATURE';

    public static $errorMap = [
        self::NOT_AVAILABLE                             => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::INVALID_CALLBACK                          => ErrorCode::GATEWAY_ERROR_INVALID_CALLBACK_URL,
        self::UNAUTHORIZED                              => ErrorCode::GATEWAY_ERROR_DEVICE_INVALID_TOKEN,
        self::SESSION_EXPIRED                           => ErrorCode::GATEWAY_ERROR_DEVICE_INVALID_TOKEN,
        self::INVALID_DATA                              => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::INVALID_SIGNATURE                         => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
        self::INACTIVE_DEVICE                           => ErrorCode::BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE,
    ];

    public static $pendingErrors = [
        'BT',
        '01',
    ];

    public static $expiredErrors = [
        'U69',
    ];

    public static $rejectedErrors = [
        'ZA',
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
}
