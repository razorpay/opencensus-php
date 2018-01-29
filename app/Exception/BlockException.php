<?php

namespace RZP\Exception;

use RZP\Error\ErrorCode;

class BlockException extends RecoverableException
{
    public function __construct()
    {
        $message = 'The request has been blocked access temporarily';
        $code    = ErrorCode::SERVER_ERROR_LOGICAL_ERROR;

        parent::__construct($message, $code);
    }
}
