<?php

namespace RZP\Exception;

use RZP\Error\ErrorCode;

/**
 * Thrown when callee has been blocked requests to given resource.
 */
class BlockException extends RecoverableException
{
    public function __construct()
    {
        $message = 'The request has been blocked access temporarily';
        $code    = ErrorCode::BAD_REQUEST_BLOCKED;

        parent::__construct($message, $code);
    }
}
