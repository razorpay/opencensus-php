<?php

namespace EE\Exception;

use Exception;
use Http\ApiResponse;

class BaseException extends Exception
{
    protected $error = null;

    /**
     * Constructor for base exception of the
     * application
     *
     * @param string    $message
     * @param string    $code
     * @param Exception $previous
     */
    public function __construct(
        /* string */ $message,
        /* string */ $code = '',
        Exception $previous = null)
    {
        $this->message = $message;
        $this->previous = $previous;
        $this->code = $code;
    }

    protected function setError($error)
    {
        $this->error = $error;
    }

    public function setGatewayErrorCodeAndDesc($code, $desc)
    {
        $this->error->setGatewayErrorCodeAndDesc($code, $desc);
    }

    public function getError()
    {
        return $this->error;
    }

    public function getErrorArray()
    {
        return $this->error->toArray();
    }

    public function getPublicError()
    {
        return $this->error->getPublicError();
    }

    public function generatePublicJsonResponse()
    {
        $error = $this->getPublicError();

        $httpStatusCode = $error->getHttpStatusCode();

        return ApiResponse::json($error->toArray(), $httpStatusCode);
    }

    public function generateDebugJsonResponse()
    {
        $error = $this->getError();

        $httpStatusCode = $error->getPublicError()->getHttpStatusCode();

        return ApiResponse::json($error->toArray(), $httpStatusCode);
    }
}