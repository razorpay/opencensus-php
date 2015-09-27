<?php

namespace EE\Error;

use EE\Exception;

class Map
{
    public static $map = array(
        PublicErroCode::GATEWAY_ERROR       => Exception\GatewayErrorException::class,
        PublicErroCode::BAD_REQUEST_ERROR   => Exception\BadRequestException::class,
        PublicErroCode::SERVER_ERROR        => Exception\ServerErrorException::class);

    public static function throwExceptionFromErrorDetails($publicCode, $internalCode, $desc)
    {
        $class = null;

        if (in_array($publicCode, self::$map))
        {
            $class = $map[$publicCode];
        }

        if ($internalCode === ErrorCode::BAD_REQUEST_VALIDATION_FAILURE)
        {
            throw new Exception\BadRequestValidationFailureException($desc);
        }
        else if ($internalCode === ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT)
        {
            throw new Exception\GatewayTimeoutException('Gateway request timed out');
        }
        else if ($class === Exception\BadRequestException::class)
        {
            throw new Exception\BadRequestException($internalCode);
        }
        else if ($class === SERVER_ERROR)
        {
            throw new Exception\ServerErrorException(
                'Server error getting repeated for payment callback',
                ErrorCode::SERVER_ERROR);
        }
        else if ($class === GATEWAY_ERROR)
        {
            throw new Exception\GatewayErrorException($internalCode);
        }
    }
}
