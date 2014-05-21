<?php

namespace Exceptions;

class ServerErrorException extends RazorpayException
{
	protected $publicMessage;

    public function __construct($message, $code = 0 , Exception $previous = NULL)
    {
        parent::__construct('Server Error', $message, $code, $previous);
    }

    public function setPublicMessage($publicMessage)
    {
    	$this->publicMessage = $publicMessage;
    }

    public function getPublicMessage()
    {
    	return $this->publicMessage;
    }
}