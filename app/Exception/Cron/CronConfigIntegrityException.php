<?php


namespace RZP\Exception\Cron;

use RZP\Error\ErrorCode;
use RZP\Exception\MessageFormats;
use RZP\Exception\RecoverableException;

class CronConfigIntegrityException extends RecoverableException
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
