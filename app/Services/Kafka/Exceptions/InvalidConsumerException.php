<?php

namespace RZP\Services\Kafka\Exceptions;

use throwable;
use RZP\Exception\BaseException;

class InvalidConsumerException extends BaseException
{
    public function __construct(
        $message = 'Invalid consumer',
        $code = 0,
        Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
