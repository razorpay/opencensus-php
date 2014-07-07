<?php

namespace Gateway\Hdfc;

use EE\Error\Error;
use EE\Exception;
use Gateway\Hdfc;

class ErrorHandler
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
            throw new Exception\InvalidArgumentException(
                'Error mapping for this code not defined. code: '.$errorCode);
        }

        return ErrorCode::$errorMap[$errorCode];
    }

    public static function unknownError()
    {
        return static::$invalidErrorCode;
    }

    public static function getInvalidEnrollCodeError()
    {
        $error['code'] = Hdfc\ErrorCode::RP00003;
        $error['text'] = Hdfc\ErrorCode::$errorMessages[Hdfc\ErrorCode::RP00003];

        return $error;
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
            $appErrorCode = self::getInvalidEnrollCodeError();
            // $appErrorMessage = Hdfc\
        }
        else
        {
            $appErrorCode = Hdfc\ErrorCode::$errorMap[$code];
        }

        return $appErrorCode;
    }
}