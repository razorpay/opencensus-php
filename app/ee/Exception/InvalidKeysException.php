<?php

namespace Exceptions;

class InvalidKeysException extends InvalidRequestException
{
    protected $keys;

    public function __construct(array $keys, $code = 0 , Exception $previous = null)
    {
    	$n = count($keys);

        $invalid_keys = implode(', ', $keys);

        $message = $n . ' key(s) is/are invalid => ' . $invalid_keys;

        $this->keys = $keys;

        parent::__construct($message, $code, $previous);
    }

    public function getInvalidKeys()
    {
        return $this->keys;
    }
}