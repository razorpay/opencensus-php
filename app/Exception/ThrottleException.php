<?php

namespace RZP\Exception;

use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class ThrottleException extends TooManyRequestsHttpException
{
    protected $data = [];

    public function __construct(
        $retryAfter,
        $data = [])
    {
        $this->data = $data;

        $message = 'Rate limit exceeded.';

        parent::__construct($retryAfter, $message);
    }

    public function getData()
    {
        return $this->data;
    }
}
