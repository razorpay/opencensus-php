<?php

namespace RZP\Exception;

use RZP\Error\Error;

class MethodInstrumentsTerminalsSyncException extends RecoverableException
{
    private $statusCode;

    public function __construct(
        $code,
        $data,
        $statusCode,
        $description = null)
    {
        $this->error = new Error($code, $description, "", $data);

        $this->data = $data;

        $this->statusCode = $statusCode;

        $message = $this->error->getDescription();

        parent::__construct($message, $code);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
