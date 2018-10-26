<?php

namespace RZP\Gateway\Base\ErrorCodes;

use RZP\Error\ErrorCode;
use RZP\Gateway\Base\ErrorCodes\ErrorCodesTrait;

class BaseCodes
{
    use ErrorCodesTrait;

    public static $errorCodeMap = [];

    public static function getInternalErrorCode($content)
    {
        $errorCode = static::getInternalCode($content, 'errorCodeMap');

        return $errorCode;
    }

    public static function getErrorFieldName($fieldName)
    {
        return $fieldName;
    }
}
