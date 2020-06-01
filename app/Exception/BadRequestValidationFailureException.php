<?php

namespace RZP\Exception;

use RZP\Error\ErrorCode;

class BadRequestValidationFailureException extends RecoverableException
{
    use MessageFormats;

    public function __construct(
        $message = null,
        $field = null,
        $data = null)
    {
        $message = $this->constructStringMessage($message);

        $code = ErrorCode::BAD_REQUEST_VALIDATION_FAILURE;

        $this->constructError($code, $message, $field, $data);

        $this->data = $data;
    }
}
