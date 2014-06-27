<?php

namespace EE\Exception;

class LogicException extends ServerErrorException
{
    public function __construct($message, $code = '' , Exception $previous = NULL)
    {
        parent::__construct('Server Error', $message, $code, $previous);
    }
}