<?php

namespace RZP\Exception;

use RZP\Error\Error;

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

    protected $code = null;

    // To the clients, SERVER_ERROR code is used to display.
    // The trace will have a different error code and description,
    // which is provided at the place where ServerErrorException is thrown in the code.
    // We do not want the clients to know about our internal server error descriptions.

    public function __construct(
        $message,
        $code,
        $data = null,
        \Throwable $previous = null)
    {
        $this->data = $data;

        $error = new Error($code, null, null, $data);

        $this->error = $error;

        parent::__construct($message, $code, $previous);
    }
}
