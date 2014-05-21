<?php

namespace Exceptions;

use Exception;

class RazorpayException extends Exception
{
	protected $category;

    public function __construct($category, $message, $code = 0 , Exception $previous = NULL)
    {
    	$this->category = $category;

        parent::__construct($message, $code, $previous);
    }

    public function getCategory()
    {
    	return $this->category;
    }
}