<?php

namespace RZP\Exception;

use RZP\Error\Error;

class ServerNotFoundException extends RecoverableException
{
    use MessageFormats;

    public function __construct(
        $message,
        $code,
        $data = null,
        \Throwable $previous = null)
    {
        $this->data = $data;

        $error = new Error($code, null, null, $data);

        $this->error = $error;

        parent::__construct($message, $code, $previous);
    }
}
