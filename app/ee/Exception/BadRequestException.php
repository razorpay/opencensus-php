<?php

namespace EE\Exception;

use Illuminate\Support\messageBag;

class BadRequestException extends RazorpayException
{
    use MessageFormats;

    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        $message = $this->constructStringMessage($message);

        $intcode = 0;

        parent::__construct($message, $intcode, $previous);

        $this->constructError($message, $code);
    }
}