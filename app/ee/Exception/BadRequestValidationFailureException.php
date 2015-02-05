<?php

namespace EE\Exception;

use EE\Error\ErrorCode;

class BadRequestValidationFailureException extends RecoverableException
{
    use MessageFormats;

    public function __construct(
        $message = null,
        $field = null)
    {
        $message = $this->constructStringMessage($message);

        $code = ErrorCode::BAD_REQUEST_VALIDATION_FAILURE;

        $this->constructError($code, $message, $field);

        // parent is being called in constructError only.
    }
}