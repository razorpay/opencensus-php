<?php

namespace RZP\Gateway\Fss;

use RZP\Error\ErrorCode;

class ErrorCodes
{
    protected static $reasonCodes = [
        'IPAY0100254'   => 'Merchant not enabled for performing transaction.',
    ];

    protected static $errorCodeMap = [
        'IPAY0100254'   => ErrorCode::BAD_REQUEST_MERCHANT_NO_TERMINAL_ASSIGNED,
    ];

    public static function getMappedCode($code = null)
    {
        if (isset(self::$errorCodeMap[$code]))
        {
            return self::$errorCodeMap[$code];
        }

        return ErrorCode::GATEWAY_ERROR_REQUEST_ERROR;
    }

    public static function getErrorDesc($code = null)
    {
        if (isset(self::$reasonCodes[$code]))
        {
            return self::$reasonCodes[$code];
        }

        return 'General Error';
    }
}