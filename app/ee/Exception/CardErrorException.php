<?php

namespace EE\Exception;

use Illuminate\Support\MessageBag;
use EE\Error\Error;
use EE\Error\PublicErrorDescription;

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
        $intcode = 0;

        if ($this->onlyCode($message, $code, $previous))
            return;

        $message = $this->constructStringMessage($message);

        parent::__construct($message, $intcode, $previous);

        $this->constructError($message, $code);
    }
}