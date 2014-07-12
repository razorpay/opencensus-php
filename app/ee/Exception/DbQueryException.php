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

        if (isset($data['operation']))
        {
            $message .= PHP_EOL . 'Operation: ' . $data['operation'];
        }

        if (isset($data['model']))
        {
            $message .= PHP_EOL . 'Model: ' . $data['model'];
        }

        if (isset($data['attributes']))
        {
            $message .= PHP_EOL . 'Attributes: ' . (json_encode($data['attributes']));
        }

        parent::__construct($message, $code, $data, $previous);
    }
}