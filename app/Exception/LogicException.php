<?php

namespace RZP\Exception;

use RZP\Error\ErrorCode;

class LogicException extends ServerErrorException
{
    public function __construct(
        $message = null,
        $code = null,
        $data = null)
    {
        if ($message === null)
        {
            $message = 'Logical error occurred';
        }

        if ($code === null)
        {
            $code = ErrorCode::SERVER_ERROR_LOGICAL_ERROR;
        }

        parent::__construct($message, $code, $data);
    }
}
