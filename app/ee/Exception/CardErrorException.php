<?php

namespace EE\Exception;

class CardErrorException extends BaseException
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
        \Exception $previous = null)
    {
        if ($this->onlyCode($message, $code, $previous))
            return;

        $message = $this->constructStringMessage($message);

        $this->constructError($message, $code);
    }
}