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

        // Gives the priority fields array
        $errorCodeFieldNameList = $errorFieldsClass::getErrorCodeFields();

        foreach ($errorCodeFieldNameList as $index => $fieldName)
        {
            // returns the actual field name, for example for hitachi we have split the errors in 2 parts coming
            // from same field name. But gateway in response sends us pRespCode, so this is pRespCode.
            $gatewayFieldName = static::getErrorFieldName($fieldName);

            if (empty($content[$gatewayFieldName]) === true)
            {
                continue;
            }

            // Returns the relevant gateway error code, for example axis migs sends an error code
            // vpc_Message as E5154-3524663-some_message but relevant error code in this is just 5154, so for such
            // cases we can override this method.
            $gatewayErrorCode = static::getRelevantGatewayErrorCode($gatewayFieldName, $content);

            // Returns the error code/description map array
            // errorType is just to know that this is an errorCodeMap or errorDescriptionMap
            $internalErrorMap = $errorFieldsClass::$$errorType[$fieldName];

            if (isset(static::$$internalErrorMap[$gatewayErrorCode]) === false)
            {
                continue;
            }

            // Actual error code that we were looking for
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

    public static function getRelevantGatewayErrorCode($errorFieldName, $content)
    {
        return $content[$errorFieldName];
    }
}
