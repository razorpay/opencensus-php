<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Error\P2p\ErrorCode;

use RZP\Gateway\P2p\Upi\ErrorCodes;

class ErrorMap
{
    const NOT_AVAILABLE                                 = 'NOT_AVAILABLE';
    const INVALID_CALLBACK                              = 'INVALID_CALLBACK';
    const INACTIVE_DEVICE                               = 'INACTIVE_DEVICE';
    const UNAUTHORIZED                                  = 'UNAUTHORIZED';
    const SESSION_EXPIRED                               = 'SESSION_EXPIRED';
    const INVALID_DATA                                  = 'INVALID_DATA';
    const INVALID_SIGNATURE                             = 'INVALID_SIGNATURE';
    const NETWORK_ERROR                                 = 'NETWORK_ERROR';
    const SMS_SENDING_FAILED                            = 'SMS_SENDING_FAILED';
    const SMS_VERIFICATION_EXPIRED                      = 'SMS_VERIFICATION_EXPIRED';
    const SDK_CHECKSUM_MISMATCH                         = 'SDK_CHECKSUM_MISMATCH';
    const SDK_HASH_MISSING                              = 'SDK_HASH_MISSING';
    const SDK_HASH_MISMATCH                             = 'SDK_HASH_MISMATCH';

    public static $errorMap = [
        self::NOT_AVAILABLE                             => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::INVALID_CALLBACK                          => ErrorCode::GATEWAY_ERROR_INVALID_CALLBACK_URL,
        self::UNAUTHORIZED                              => ErrorCode::GATEWAY_ERROR_DEVICE_INVALID_TOKEN,
        self::SESSION_EXPIRED                           => ErrorCode::GATEWAY_ERROR_DEVICE_INVALID_TOKEN,
        self::INVALID_DATA                              => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::INVALID_SIGNATURE                         => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
        self::INACTIVE_DEVICE                           => ErrorCode::BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE,
        self::NETWORK_ERROR                             => ErrorCode::GATEWAY_ERROR_CONNECTION_ERROR,
        self::SMS_SENDING_FAILED                        => ErrorCode::BAD_REQUEST_SMS_FAILED,
        self::SMS_VERIFICATION_EXPIRED                  => ErrorCode::BAD_REQUEST_SMS_FAILED,
        self::SDK_CHECKSUM_MISMATCH                     => ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
        self::SDK_HASH_MISSING                          => ErrorCode::GATEWAY_ERROR_TIMED_OUT,
        self::SDK_HASH_MISMATCH                         => ErrorCode::BAD_REQUEST_FORBIDDEN,
    ];

    public static $pendingErrors = [
        'RB',
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
        if (isset(ErrorCodes::$errorCodeMap[$gatewayCode]) === true)
        {
            return ErrorCodes::$errorCodeMap[$gatewayCode];
        }

        return ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE;
    }
}
