<?php

namespace EE\Error;

class PublicErrorCode
{
    const GATEWAY_ERROR = 'gateway_error';

    const BAD_REQUEST_ERROR = 'bad_request_error';

    const SERVER_ERROR = 'server_error';

    public static function verify($errorClass)
    {
        if (! is_string($class))
        {
            throw new \InvalidArgumentException('Invalid error class provided');
        }

        switch ($class)
        {
            case self::BAD_REQUEST:
            case self::PAYMENT_ERROR:
            case self::SERVER_ERROR:
                return;
            default:
                throw new \InvalidArgumentException('Invalid error class provided');
        }
    }
}