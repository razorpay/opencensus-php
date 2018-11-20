<?php

namespace RZP\Exception;

class CardErrorException extends RecoverableException
{
    use MessageFormats;

    /**
     * Card field for which the public error will be shown
     * @var string
     */
    protected $cardField = null;

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
    }
}
