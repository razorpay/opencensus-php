<?php

namespace RZP\Gateway\Base\ErrorCodes;

use RZP\Error\ErrorCode;
use RZP\Gateway\Base\ErrorCodes\ErrorCodesTrait;

class BaseCodes
{
    use ErrorCodesTrait;

    public static $map = [];

    public static function getRelevantGatewayErrorCode($errorFieldName, $content)
    {
        return $content[$errorFieldName];
    }

    public static function getInternalErrorCode($content)
    {
        $errorCode = static::getInternalCode($content, 'errorCodeMap');

        return $errorCode;
    }
}
