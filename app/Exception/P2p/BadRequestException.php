<?php

namespace RZP\Exception\P2p;

use RZP\Error\Error;
use RZP\Exception\RecoverableException;

class BadRequestException extends RecoverableException implements ExceptionInterface
{
    use ErrorTrait;

    public function __construct($code, array $data = [])
    {
        $this->error = $this->newError($code, $data);

        $message = $this->error->getDescription();

        parent::__construct($message, $code);
    }
}
