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

        $this->constructError($message, $code);

        parent::__construct($message, $code, $previous);
    }
}