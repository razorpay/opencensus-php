<?php

namespace EE\Exception;

use EE\Error\Error;

class ServerErrorException extends BaseException
{
    /**
     * Aim should be to fill the value of these attributes.
     * The child classes should provide the field values
     * and 'data' variable should store values corresponding
     * to those fields. Note that it's not binding though
     *
     * @var array
     */
    protected $fields = array();

    protected $data = null;

    protected $code = null;

    public function __construct(
        $message,
        $code,
        $data = null,
        Exception $previous = null)
    {
        $this->data = $data;

        $this->code = $code;

        $this->previous = $previous;

        $error = new \EE\Error\Error($code, null, null, $data, $message);

        $this->error = $error;

        parent::__construct($message, $code, $previous);
    }

    public function getData()
    {
        return $this->data;
    }
}