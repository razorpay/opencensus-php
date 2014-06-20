<?php

namespace Exceptions;

use Exception;

class RazorpayException extends Exception
{
    protected $category;

    protected $error = null;

    public function __construct($category, $message, $code = 0 , Exception $previous = null)
    {
        $this->category = $category;

        parent::__construct($message, $code, $previous);
    }

    public function getCategory()
    {
        return $this->category;
    }

    protected function setError($error)
    {
        $this->error = $error;
    }

    public function getError()
    {
        return $this->error;
    }
}