<?php

namespace RZP\Exception;

use Exception;
use ApiResponse;
use RZP\Error\Error;

class BaseException extends Exception
{
    /**
     * @var null|Error
     */
    protected $error = null;

    protected $data = [];

    /**
     * Constructor for base exception of the
     * application
     *
     * @param string    $message
     * @param string    $code
     * @param Exception $previous
     */
    public function __construct(
        $message,
        $code = '',
        \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);

        $this->code = $code;
    }

    protected function initError($code)
    {
        Error::checkErrorCode($code);

        $error = new Error($code);

        $this->setError($error);
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

    public function generatePublicJsonResponse()
    {
        $error = $this->error;

        $httpStatusCode = $error->getHttpStatusCode();

        return ApiResponse::json($this->error->toPublicArray(), $httpStatusCode);
    }

    public function generateDebugJsonResponse()
    {
        $error = $this->error;

        $httpStatusCode = $error->getHttpStatusCode();

        return ApiResponse::json($error->toDebugArray(), $httpStatusCode);
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getDataAsString()
    {
        $data = $this->data;

        if ($data === null)
        {
            return '';
        }

        $json = json_encode($data);

        if ($json !== false)
        {
            return $json;
        }

        return get_var_as_string($this->data);
    }
}
