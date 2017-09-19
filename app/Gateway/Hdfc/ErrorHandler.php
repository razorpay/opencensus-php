<?php

namespace RZP\Gateway\Hdfc;

use RZP\Exception;
use RZP\Gateway\Hdfc;

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
                'Invalid Hdfc Error Code provided.',
                null,
                [
                    'code' => $code,
                ]);
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

    public static function setTimeoutError(array & $response, $curlMessage = null)
    {
        $code = Hdfc\ErrorCode::RP00003;

        if ((empty($curlMessage) === false) and
            (strpos($curlMessage, 'operation timed out') !== false))
        {
            $code = Hdfc\ErrorCode::RP00013;
        }

        $response['error'] = self::getErrorDetails($code);
    }

    public static function setRequestError(array & $response, $curlMessage = null)
    {
        $code = Hdfc\ErrorCode::RP00014;

        $response['error'] = self::getErrorDetails($code);
        $response['text'] = $curlMessage;
    }

    public static function setGatewayWrongStatusCode(array & $response, $status_code)
    {
        $code = Hdfc\ErrorCode::RP00008;

        $response['error'] = self::getErrorDetails($code);

        $response['error']['text'] .= ' status_code: ' . $status_code;
    }

    public static function setGatewayWrongContentType(array & $response, $contentType)
    {
        $code = Hdfc\ErrorCode::RP00009;

        $response['error'] = self::getErrorDetails($code);

        $response['error']['text'] .= ' content-type: ' . $contentType;
    }
}
