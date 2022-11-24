<?php

namespace RZP\Exception;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/*
    This exception class has been written to cut short the
    code execution for workflows so that the response
    which contains the workflow action details can be sent right
    then and there without further execution.

    Further execution could call slack, zapier, etc. facades leading
    to realtime notifications which won't be required in case of
    a maker (workflow) request. These notifications or any sort of
    further processing at Service, Controller, etc. level should
    happen only during an execute call.
*/

class EarlyWorkflowResponse extends \RuntimeException implements HttpExceptionInterface
{
    private $statusCode;
    private $headers;

    public function __construct(
        $statusCode, $message = null, \Exception $previous = null,
        array $headers = [], $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
