<?php

namespace EE\Exception;

class BadRequestException extends RecoverableException
{
    use MessageFormats;

    public function __construct(
        $message = null,
        $code = 0,
        $field = null,
        \Exception $previous = null)
    {
        if ($this->decideFormat($message, $code, $field, $previous))
            return;

        $message = $this->constructStringMessage($message);

        $this->constructError($message, $code);

        parent::__construct($message, $code, $previous);
    }
}