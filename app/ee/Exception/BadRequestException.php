<?php

namespace EE\Exception;

class BadRequestException extends BaseException
{
    use MessageFormats;

    public function __construct($message, $code = 0, \Exception $previous = null)
    {

        if ($this->onlyCode($message, $code, $previous))
            return;

        $message = $this->constructStringMessage($message);

        $intcode = 0;

        parent::__construct($message, $intcode, $previous);

        $this->constructError($message, $code);
    }
}