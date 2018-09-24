<?php

namespace RZP\Gateway\Base\ErrorCodes;

use RZP\Error\ErrorCode;

trait ErrorCodesTrait
{
    public static function getInternalCode($content, $errorType)
    {
        $errorCode = null;

        $errorFieldsClass = static::getGatewayFieldClass();

        if (class_exists($errorFieldsClass) === false)
        {
            return ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR;
        }

        $errorCodeFieldNameList = $errorFieldsClass::getErrorCodeFields();

        foreach ($errorCodeFieldNameList as $index => $fieldName)
        {
            if (empty($content[$fieldName]) === true)
            {
                continue;
            }

            $gatewayErrorCode = static::getRelevantGatewayErrorCode($fieldName, $content);

            $internalErrorMap = $errorFieldsClass::$$errorType[$fieldName];

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
