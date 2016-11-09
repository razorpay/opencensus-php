<?php

namespace RZP\Exception;

use RZP\Error\ErrorCode;

class AssertionException extends ServerErrorException
{
    public function __construct(
        $message = null,
        $data = null,
        \Exception $previous = null)
    {
        $code = ErrorCode::SERVER_ERROR_ASSERTION_ERROR;

        if ($message === null)
        {
            $message = 'Assert error occurred';
        }

        parent::__construct($message, $code, $data, $previous);
    }
}
