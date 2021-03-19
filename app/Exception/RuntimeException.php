<?php

namespace RZP\Exception;

use RZP\Error\ErrorCode;

class RuntimeException extends ServerErrorException
{
    public function __construct(
        $message = null,
        $data = null,
        \Exception $previous = null,
        $code = null)
    {
        $code = $code ?? ErrorCode::SERVER_ERROR_RUNTIME_ERROR;

        $message = $message ?? 'Runtime error occurred';

        parent::__construct($message, $code, $data, $previous);
    }
}
