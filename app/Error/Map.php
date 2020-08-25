<?php

namespace RZP\Error;

use RZP\Exception;

class Map
{
    const MAP = array(
        PublicErrorCode::GATEWAY_ERROR      => Exception\GatewayErrorException::class,
        PublicErrorCode::BAD_REQUEST_ERROR  => Exception\BadRequestException::class,
        PublicErrorCode::SERVER_ERROR       => Exception\ServerErrorException::class);

    public static function throwExceptionFromErrorDetails($publicCode, $internalCode, $desc, $data)
    {
        $class = null;

        if (in_array($publicCode, array_keys(self::MAP)))
        {
            $class = self::MAP[$publicCode];
        }

        if ($internalCode === ErrorCode::BAD_REQUEST_VALIDATION_FAILURE)
        {
            throw new Exception\BadRequestValidationFailureException($desc, null, $data);
        }
        else if ($publicCode === ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,null, $data);
        }
        else if ($internalCode === ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT)
        {
            throw new Exception\GatewayTimeoutException('Gateway request timed out');
        }
        else if ($class === Exception\BadRequestException::class)
        {
            throw new Exception\BadRequestException($internalCode, null, $data);
        }
        else if ($publicCode === PublicErrorCode::SERVER_ERROR)
        {
            throw new Exception\ServerErrorException(
                'Server error getting repeated for payment callback',
                ErrorCode::SERVER_ERROR,
                $data);
        }
        else if ($publicCode === PublicErrorCode::GATEWAY_ERROR)
        {
            throw new Exception\GatewayErrorException($internalCode, null, null,
                $data);
        }
    }
}
