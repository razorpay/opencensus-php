<?php

namespace RZP\Gateway\Hdfc;

use RZP\Exception;
use RZP\Gateway\Hdfc;

class ErrorHandler
{
    public static function getErrorMessage($code)
    {
        return Hdfc\ErrorCode::$errorMessages[$code];
    }

    public static function isValidErrorCode($code)
    {
        return (defined(ErrorCode::class.'::'.$code));
    }

    public static function getMappedError($code)
    {
        $appErrorCode = null;

        if (self::isValidErrorCode($code) === false)
        {
            throw new Exception\InvalidArgumentException(
                'should not reach here for now' . $code);
        }
        else
        {
            $appErrorCode = Hdfc\ErrorCode::$errorMap[$code];
        }

        return $appErrorCode;
    }

    public static function checkErrorCode($code)
    {
        if (defined(ErrorCode::class.'::'.$code) === false)
        {
            throw new Exception\LogicException(
                'Invalid Hdfc Error Code provided. Code: ' . $code);
        }
    }

    public static function getInvalidResultCodeError()
    {
        return self::getErrorDetails(Hdfc\ErrorCode::RP00002);
    }

    public static function getErrorDetails($code)
    {
        self::checkErrorCode($code);

        $text = Hdfc\ErrorHandler::getErrorMessage($code);

        return array('code' => $code, 'text' => $text);
    }

    public static function setTimeoutError($curlMessage = null)
    {
        $code = Hdfc\ErrorCode::RP00003;

        if ((empty($curlMessage) === false) and
            (strpos($curlMessage, 'operation timed out') !== false))
        {
            $code = Hdfc\ErrorCode::RP00013;
        }

        return self::getErrorDetails($code);
    }

    public static function setRequestError()
    {
        $code = Hdfc\ErrorCode::RP00014;

        return self::getErrorDetails($code);
    }

    public static function getGatewayWrongStatusCodeError($statusCode)
    {
        $code = Hdfc\ErrorCode::RP00008;

        $error = self::getErrorDetails($code);

        $error['text'] .= ' status_code: ' . $statusCode;

        return $error;
    }

    public static function getGatewayWrongContentTypeError($contentType)
    {
        $code = Hdfc\ErrorCode::RP00009;

        $error = self::getErrorDetails($code);

        $error['text'] .= ' content-type: ' . $contentType;

        return $error;
    }
}
