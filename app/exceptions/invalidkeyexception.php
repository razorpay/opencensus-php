<?php

class InvalidKeyException extends Exception
{
    protected $_keys;

    public function __construct(array $keys, $code = 0 , Exception $previous = NULL)
    {
        $invalid_keys = implode(', ', $keys);

        $message = $invalid_keys . ' is/are not valid key(s).';

        $this->_keys = $keys;

        parent::__construct($message, $code, $previous);
    }

    public function getInvalidKeys()
    {
        return $this->_keys;
    }
}