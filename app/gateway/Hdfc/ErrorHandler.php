<?php

namespace Gateway\Hdfc;

use EE\Error\Error;
use EE\Exception;
use Gateway\Hdfc;

class ErrorHandler
{
    public static function unknownError()
    {
        return static::$invalidErrorCode;
    }

    public static function getInvalidError()
    {
        return self::getErrorDetailsHdfc(ErrorCode::$invalidErrorCode);
    }

    public static function getErrorMessage($code)
    {
        return Hdfc\ErrorCode::$errorMessages[$code];
    }

    public static function getMappedError($code)
    {
        $appErrorCode = null;

        if (defined(__NAMESPACE__.'\ErrorCode::'.$code) === false)
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
        if (defined(__NAMESPACE__.'\ErrorCode::'.$code) === false)
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

    public static function setErrorInResponse(array & $response, $code)
    {
        self::checkErrorCode($code);

        $response['error'] = self::getErrorDetails($code);
    }

    public static function setTimeoutError(array & $response)
    {
        $code = Hdfc\ErrorCode::RP00003;

        $response['error'] = self::getErrorDetails($code);
    }

    public static function setGatewayWrongStatusCode(array & $response, $status_code)
    {
        $code = Hdfc\ErrorCode::RP00008;

        $response['error'] = self::getErrorDetails($code);

        $response['error']['text'] .= ' stautus_code: ' . $status_code;
    }

    public static function setGatewayWrongContentType(array & $response, $contentType)
    {
        $code = Hdfc\ErrorCode::RP00009;

        $response['error'] = self::getErrorDetails($code);

        $response['error']['text'] .= ' content-type: ' . $contentType;
    }
}