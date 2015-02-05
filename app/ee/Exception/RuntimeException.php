<?php

namespace EE\Exception;

use EE\Error\ErrorCode;

class RuntimeException extends ServerErrorException
{
    public function __construct(
        $message = null,
        $data = null,
        \Exception $previous = null)
    {
        $code = ErrorCode::SERVER_ERROR_RUNTIME_ERROR;

        if ($message === null)
        {
            $message = 'Runtime error occurred';
        }

        parent::__construct($message, $code, $data, $previous);
    }
}