<?php

namespace EE\Exception;

use EE\Error\Error;
use EE\Error\ErrorCode;

class ReconciliationException extends RecoverableException
{
    public function __construct($errorDesc, $data = [])
    {
        $code = ErrorCode::BAD_REQUEST_RECONCILIATION;

        $this->error = new Error($code, $errorDesc, null, $data);

        $this->data = $data;

        $message = json_encode(['error_data' => $data, 'error_description' => $errorDesc]);

        parent::__construct($message, $code);
    }
}