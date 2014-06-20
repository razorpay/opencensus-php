<?php

namespace EE\Exception;

class InvalidRequestException extends RazorpayException
{
    public function __construct($message, $code = 0 , Exception $previous = null)
    {
        parent::__construct('Invalid Request', $message, $code, $previous);
    }
}