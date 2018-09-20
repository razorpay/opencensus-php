<?php

namespace RZP\Gateway\Base\ErrorCodes;

class BaseCodeDescriptions
{
    public static $errorDescriptionMap = [];

    public static function getRelevantGatewayErrorCode($errorFieldName, $content)
    {
        return $content[$errorFieldName];
    }

    public static function getGatewayErrorDescription($content)
    {
        $gatewayErrorCode = $errorCodeClass = $errorCodeFieldName = $errorDescription = null;

        $errorFieldsClass = static::getGatewayFieldClass();

        $errorCodeFieldNameList = $errorFieldsClass::getErrorCodeFields();

        foreach ($errorCodeFieldNameList as $index => $fieldName)
        {
            if (empty($content[$fieldName]) === true)
            {
                continue;
            }

            $gatewayErrorCode = static::getRelevantGatewayErrorCode($fieldName, $content);


            $internalErrorMap = $errorFieldsClass::$errorDescriptionMap[$fieldName];

            $errorDescription = static::$$internalErrorMap[$gatewayErrorCode];


            if (empty($errorDescription) === false)
            {
                break;
            }
        }

        if (empty($errorDescription) === true)
        {
            $errorDescription = "Unknown Error";
        }

        return $errorDescription;
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