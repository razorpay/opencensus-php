<?php

namespace RZP\Exception;

class AssertionException extends RuntimeException
{
    public function __construct(
        $message = null,
        $data = null,
        \Exception $previous = null)
    {
        if ($message === null)
        {
            $message = 'Assert error occurred';
        }

        parent::__construct($message, $data, $previous);
    }
}
