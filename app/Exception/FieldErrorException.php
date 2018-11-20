<?php

namespace RZP\Exception;

class FieldErrorException extends RecoverableException
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

        parent::__construct($message, $code, $previous);

        $this->constructError($message, $code);
    }
}
