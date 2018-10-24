<?php

namespace RZP\Gateway\Base\ErrorCodes;

use RZP\Gateway\Base\ErrorCodes\ErrorCodesTrait;

class BaseCodeDescriptions
{
    use ErrorCodesTrait;

    public static $errorDescriptionMap = [];

    public static function getGatewayErrorDescription($content)
    {
        $errorDescription = static::getInternalCode($content, 'errorDescriptionMap');

        return $errorDescription;
    }

    public static function getErrorFieldName($fieldName)
    {
        return $fieldName;
    }
}
