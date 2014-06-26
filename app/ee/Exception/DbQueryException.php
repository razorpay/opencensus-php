<?php

namespace EE\Exception;

use EE\Error\Error;
use EE\Error\ErrorCode;

class DbQueryException extends ServerErrorException
{
    /**
     * Aim should be to fill the value of these attributes.
     * @var array
     */
    protected $fields = array(
        'operation',
        'model',
        'attributes',
        'query');

    protected $data = array();

    public function __construct(array $data, Exception $previous = null)
    {
        $code = ErrorCode::DB_QUERY_FATAL_ERROR;

        parent::__construct($code, $data, $previous);
    }
}