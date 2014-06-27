<?php

namespace EE\Exception;

class UdfErrorException extends BaseException
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