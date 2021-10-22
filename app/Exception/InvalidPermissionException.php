<?php

namespace RZP\Exception;

use RZP\Error\ErrorCode;

class InvalidPermissionException extends RecoverableException
{
    use MessageFormats;

    public function __construct(
        $message = null,
        $field = null,
        $data = null)
    {
        $message = $this->constructStringMessage($message);

        $code = ErrorCode::BAD_REQUEST_INVALID_PERMISSION;

        $this->constructError($code, $message, $field, $data);

        $this->data = $data;
    }
}
