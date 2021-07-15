<?php

namespace RZP\Services\Kafka\Exceptions;

use throwable;
use RZP\Exception\BaseException;

class InvalidModeException extends BaseException
{
    public function __construct(
        $message = 'Invalid mode',
        $code = 0,
        Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
