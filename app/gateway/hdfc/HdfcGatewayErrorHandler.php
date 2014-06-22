<?php

namespace Gateway\HdfcGateway;

use Exceptions\Status;

class HdfcGatewayErrorHandler
{

    public static function parseErrorStr($error)
    {
        //
        // All error codes returned by hdfc gateway start with !ERROR!
        // Let's make sure it's present here
        //

        $str = substr($error, 0, 7);

        if ($str !== '!ERROR!')
        {
            return static::$invalidErrorCode;
        }

        $errorCode = substr($error, 7);

        if (! array_key_exists($this->error, $errorCode))
        {
            return $this->invalidErrorCode;
        }

        return $errorCode;
    }

    public static function translateError($hdfcErrorCode)
    {
        $apiErrorCode = static::translateErrorCode($hdfcErrorCode);

        $error = new \EE\Error\Error($apiErrorCode, $data);

        return $error;
    }

    public static function translateErrorCode($errorCode)
    {
        if (! defined(__NAMESPACE__.'\ErrorCode::'.$errorCode))
        {
            $errorCode = ErrorCode::$invalidErrorCode;
        }

        if (isset(ErrorMap::$errorMap[$errorCode]) === false)
        {
            throw new \InvalidArgumentException('Error mapping for this code not defined. code: '.$errorCode);
        }

        return ErrorCode::$errorMap[$errorCode];
    }

    public static function unknownError()
    {
        return static::$invalidErrorCode;
    }

    public static function translateEnrollError($response)
    {
    	return $response['error'];
    }

    public static function parseErrorInString($code)
    {
        if (defined('HdfcGatewayErrorCode::'.$code) === false)
            return false;

        $error['code'] = $code;
        $error['message'] = HdfcGatewayErrorHandler::$errorMessages[$code];

        return $error;
    }

    public static function getInvalidEnrollCodeError()
    {
        $error['code'] = HdfcGatewayErrorCode::RP00003;
        $error['message'] = HdfcGatewayErrorCode::$errorMessages[HdfcGatewayErrorCode::RP00003];

        return $error;
    }

    public static function getError($code)
    {
        $error['code'] = constant(__NAMESPACE__.'\HdfcGatewayErrorCode::'.$code);
        $error['message'] = static::$errorMessages[$error['code']];

        return $error;
    }
}