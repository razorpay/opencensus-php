<?php

namespace RZP\Exception;

use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class ThrottleException extends TooManyRequestsHttpException
{
    /**
     * @var string
     */
    protected $message = 'Rate limit exceeded!';

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param int|null $retryAfter Minimum near time when retry would succeed
     * @param array    $data       Additional contextual data
     */
    public function __construct(int $retryAfter = null, array $data = [])
    {
        $this->data = $data;

        parent::__construct($retryAfter, $this->message);
    }

    public function getData()
    {
        return $this->data;
    }
}
