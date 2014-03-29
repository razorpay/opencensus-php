<?php

namespace Exceptions;

class CardErrorException extends RazorpayException
{
	protected $field;

    public function __construct($field, $message, $code = 0 , Exception $previous = NULL)
    {
        parent::__construct('Card Error', $message, $code, $previous);
    }

    public function getField()
    {
    	return $this->field;
    }
}