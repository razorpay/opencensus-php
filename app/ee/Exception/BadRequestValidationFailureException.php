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

        $this->constructError($message, null);

        $code = ErrorCode::BAD_REQUEST_VALIDATION_FAILURE;

        parent::__construct($message, $code);
    }
}