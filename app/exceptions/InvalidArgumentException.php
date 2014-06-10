<?php

namespace Exceptions;

class InvalidArgumentException extends RazorpayException
{
    protected $messageArray = array();

    public function __construct($message, $code = 0 , Exception $previous = null)
    {
        if (is_array($message))
        {
            $this->messageArray = $message;

            $messages = implode('\n ', $message);
            
            parent::__construct($message, $code, $previous);
        }

        parent::__construct('Invalid Arguments', $message, $code, $previous);
    }
}