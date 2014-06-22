<?php

namespace EE\Exception;

use Illuminate\Support\MessageBag;

class UdfErrorException extends RazorpayException
{
    use MessageFormats;

    /**
     * Card field for which the public error will be shown
     * @var string
     */
    protected $cardField = null;

    public function __construct(
        $message = '',
        $code = 0,
        \Exception $previous = null)
    {
        $intcode = 0;

        $message = $this->constructStringMessage($messsage);

        parent::__construct($message, $intcode, $previous);

        $this->constructError($message, $code);
    }
}