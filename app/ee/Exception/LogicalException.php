<?php

namespace EE\Exception;

class LogicException extends RazorpayException
{
    public function __construct($message, $code = 0 , Exception $previous = NULL)
    {
        parent::__construct('Server Error', $message, $code, $previous);
    }
}