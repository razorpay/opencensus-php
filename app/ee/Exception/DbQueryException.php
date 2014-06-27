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

    public function __construct(array $data, Exception $previous = null)
    {
        $code = ErrorCode::SERVER_ERROR_DB_QUERY_FAILED;

        $message = 'DB query failed to execute successfully';

        parent::__construct($message, $code, $data, $previous);
    }
}