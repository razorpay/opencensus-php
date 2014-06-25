<?php

namespace EE\Exception;

use Exception;
use Response;

class BaseException extends Exception
{
    protected $error = null;

    public function __construct($message, $code = 0 , Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    protected function setError($error)
    {
        $this->error = $error;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getPublicError()
    {
        return $this->error->getPublicError();
    }

    public function generateJsonResponse()
    {
        $error = $this->getPublicError();

        $httpStatusCode = $error->getHttpStatusCode();

        return Response::json($error->toArray(), $httpStatusCode);
    }

    public function setGatewayErrorCodeAndDesc($code, $desc)
    {
        $this->error->setGatewayErrorCodeAndDesc($code, $desc);
    }
}