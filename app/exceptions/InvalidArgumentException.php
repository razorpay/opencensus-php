<?php

namespace Exceptions;

class InvalidArgumentException extends RazorpayException
{
    public function __construct($message, $code = 0 , Exception $previous = null)
    {
        parent::__construct('Invalid Arguments', $message, $code, $previous);
    }
}