<?php

namespace Exceptions;

class InvalidRequestException extends RazorpayException
{
    public function __construct($message, $code = 0 , Exception $previous = NULL)
    {
        parent::__construct('Invalid Request', $message, $code, $previous);
    }
}