<?php

namespace EE\Exception;

use DB;
use EE\Error\Error;
use EE\Error\ErrorCode;
use Razorpay\Spine\Exception\DbQueryExceptionTrait;

class DbQueryException extends ServerErrorException
{
    use DbQueryExceptionTrait;

    public function __construct(array $data, Exception $previous = null)
    {
        $code = ErrorCode::SERVER_ERROR_DB_QUERY_FAILED;

        $message = $this->constructMessage($data);

        parent::__construct($message, $code, $data, $previous);
    }
}