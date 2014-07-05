<?php

namespace EE\Exception;

class FieldErrorException extends BaseException
{
    use MessageFormats;

    public function __construct(
        $message = '',
        $code = '',
        \Exception $previous = null)
    {
        if ($this->onlyCode($message, $code, $previous))
            return;

        $message = $this->constructStringMessage($message);

        parent::__construct($message, $code, $previous);

        $this->constructError($message, $code);
    }
}