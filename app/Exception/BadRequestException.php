<?php

namespace RZP\Exception;

use RZP\Error\Error;

class BadRequestException extends RecoverableException
{
    use MessageFormats;

    public function __construct(
        $code = 0,
        $field = null,
        \Exception $previous = null)
    {
        $this->error = new Error($code, null, $field);

        $message = $this->error->getDescription();

        parent::__construct($message, $code);
    }

    public function setData($data)
    {
        $this->data = $data;
    }
}