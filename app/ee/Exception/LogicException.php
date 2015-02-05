<?php

namespace EE\Exception;

use EE\Error\Error;
use EE\Error\ErrorCode;

class LogicException extends ServerErrorException
{
    public function __construct(
        $message = null,
        $data = null,
        \Exception $previous = null)
    {
        $code = ErrorCode::SERVER_ERROR_LOGICAL_ERROR;

        if ($message === null)
        {
            $message = 'Logical error occurred';
        }

        parent::__construct($message, $code, $data, $previous);
    }
}