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

        parent::__construct($message, $code, $data, $previous);
    }

    protected function constructMessage(array $data, & $message)
    {
        $message = 'DB query failed to execute successfully';

        foreach ($this->fields as $field)
        {
            if (isset($data[$field]))
            {
                $message .= PHP_EOL . ucfirst($field) . ': ' . $data['field'];
            }
        }

        $message .= PHP_EOL . 'Last query: ' . $lastQuery;
    }

    protected function getLastQuery()
    {
        $queries = DB::getQueryLog();

        $lastQuery = end($queries);

        return $lastQuery;
    }
}