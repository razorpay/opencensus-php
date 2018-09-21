<?php

namespace RZP\Exception;

use RZP\Error\Error;

class BadRequestException extends RecoverableException
{
    use MessageFormats;

    public function __construct(
        $code = 0,
        $field = null,
        $data = null,
        $description = null)
    {
        $this->error = new Error($code, $description, $field, $data);

        $this->data = $data;

        $message = $this->error->getDescription();

        parent::__construct($message, $code);
    }
}
