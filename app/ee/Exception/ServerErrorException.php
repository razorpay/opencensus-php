<?php

namespace EE\Exception;

use EE\Error\Error;

class ServerErrorException extends BaseException
{
    protected $data = null;

    protected $code = null;

    public function __construct($code, $data = null, Exception $previous = null)
    {
        $this->data = $data;

        $this->code = $code;

        $this->previous = $previous;

        $error = new \EE\Error\Error($code, null, null, $data);
    }

    public function getData()
    {
        return $this->data;
    }
}