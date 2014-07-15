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
        }
        else
        {
            $appErrorCode = Hdfc\ErrorCode::$errorMap[$code];
        }

        return $appErrorCode;
    }
}