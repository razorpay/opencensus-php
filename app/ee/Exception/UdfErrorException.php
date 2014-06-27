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
        $code = '',
        \Exception $previous = null)
    {
        $message = $this->constructStringMessage($messsage);

        parent::__construct($message, $code, $previous);

        $this->constructError($message, $code);
    }
}