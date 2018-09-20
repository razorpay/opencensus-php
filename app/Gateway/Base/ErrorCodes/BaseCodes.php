<?php

namespace RZP\Gateway\Base\ErrorCodes;

use RZP\Error\ErrorCode;

class BaseCodes
{
    public static $map = [];

    public static function getRelevantGatewayErrorCode($errorFieldName, $content)
    {
        return $content[$errorFieldName];
    }

    public static function getInternalErrorCode($content)
    {
        $gatewayErrorCode = null;

        $errorFieldsClass = static::getGatewayFieldClass();

        $errorCodeFieldNameList = $errorFieldsClass::getErrorCodeFields();

        foreach ($errorCodeFieldNameList as $index => $fieldName)
        {
            if (empty($content[$fieldName]) === true)
            {
                continue;
            }

            $gatewayErrorCode = static::getRelevantGatewayErrorCode($fieldName, $content);

            $internalErrorMap = $errorFieldsClass::$errorCodeMap[$fieldName];

            if (isset(static::$$internalErrorMap[$gatewayErrorCode]) === false)
            {
                continue;
            }

            $errorCode = static::$$internalErrorMap[$gatewayErrorCode];

            if (empty($errorCode) === false)
            {
                break;
            }
        }

        if (empty($errorCode) === true)
        {
            $errorCode = ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR;
        }

        return $errorCode;
    }

    public static function getCalledClassDirectory()
    {
        return substr(get_called_class(), 0, strrpos(get_called_class(), '\\'));
    }

    public static function getGatewayFieldClass()
    {
        $directory = static::getCalledClassDirectory();

        return $directory.'\\'.'ErrorFields'::class;
    }
}
