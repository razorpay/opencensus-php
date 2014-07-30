<?php

namespace EE\Exception;

use DB;
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

        $message = $this->constructMessage($data);

        parent::__construct($message, $code, $data, $previous);
    }

    protected function constructMessage(array $data)
    {
        $message = 'DB query failed to execute successfully';

        foreach ($this->fields as $field)
        {
            if (isset($data[$field]))
            {
                $message .= PHP_EOL . ucfirst($field) . ': ' . $data[$field];
            }
        }

        $lastQuery = $this->getLastQuery();

        $message .= PHP_EOL . 'Last query: ' . $lastQuery;

        return $message;
    }

    protected function getLastQuery()
    {
        $queries = DB::getQueryLog();

        $lastQuery = end($queries);

        ob_start();
        print_r($lastQuery);
        $query = ob_get_clean();

        return $query;
    }
}