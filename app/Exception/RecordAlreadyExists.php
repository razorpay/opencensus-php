<?php

namespace RZP\Exception;

use RZP\Error\ErrorCode;

class RecordAlreadyExists extends ServerErrorException
{
    public function __construct(
        $message = null,
        $code = null,
        $data = null)
    {
        if ($message === null)
        {
            $message = 'record already exists';
        }

        if ($code === null)
        {
            $code = ErrorCode::SERVER_ERROR_LOGICAL_ERROR;
        }

        parent::__construct($message, $code, $data);
    }
}
