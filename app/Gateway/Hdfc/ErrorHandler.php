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

    public static function getErrorMessage($code)
    {
        return Hdfc\ErrorCodes\ErrorCodeDescriptions::getGatewayErrorDescription(['code' => $code]);
    }

    public static function isValidErrorCode($code)
    {
        return (defined(ErrorCodes\ErrorCodes::class.'::'.$code));
    }

    public static function checkErrorCode($code)
    {
        if (defined(ErrorCodes\ErrorCodes::class.'::'.$code) === false)
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
        return self::getErrorDetails(Hdfc\ErrorCodes\ErrorCodes::RP00002);
    }

    public static function getErrorDetails($code)
    {
        $text = Hdfc\ErrorHandler::getErrorMessage($code);

        return array('code' => $code, 'text' => $text);
    }

    public static function setErrorInResponse(array & $response, $code)
    {
        $response['error'] = self::getErrorDetails($code);
    }

    public static function setTimeoutError(array & $response, $curlMessage = null)
    {
        $code = Hdfc\ErrorCodes\ErrorCodes::RP00003;

        if ((empty($curlMessage) === false) and
            (strpos($curlMessage, 'operation timed out') !== false))
        {
            $code = Hdfc\ErrorCodes\ErrorCodes::RP00013;
        }

        $response['error'] = self::getErrorDetails($code);
    }

    public static function setRequestError(array & $response, $curlMessage = null)
    {
        $code = Hdfc\ErrorCodes\ErrorCodes::RP00014;

        $response['error'] = self::getErrorDetails($code);
        $response['text'] = $curlMessage;
    }

    public static function setGatewayWrongStatusCode(array & $response, $status_code)
    {
        $code = Hdfc\ErrorCodes\ErrorCodes::RP00008;

        $response['error'] = self::getErrorDetails($code);

        $response['error']['text'] .= ' status_code: ' . $status_code;
    }

    public static function setGatewayWrongContentType(array & $response, $contentType)
    {
        $code = Hdfc\ErrorCodes\ErrorCodes::RP00009;

        $response['error'] = self::getErrorDetails($code);

        $response['error']['text'] .= ' content-type: ' . $contentType;
    }
}
