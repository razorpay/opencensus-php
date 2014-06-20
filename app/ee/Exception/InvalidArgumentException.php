<?php

namespace Exceptions;

use Illuminate\Support\MessageBag;

class InvalidArgumentException extends RazorpayException
{
    protected $messagebag = null;

    public function __construct($message, $code = 0 , Exception $previous = null)
    {
        if (is_array($message))
        {
            $this->messagebag = $message;

            $message = implode('\n ', $message);
        }
        else if ($message instanceof MessageBag)
        {
            $this->messagebag = $message;

            $message = implode('\n', join("\n",$validation->messages()->all()));
        }

        parent::__construct('Invalid Arguments', $message, $code, $previous);
    }
}