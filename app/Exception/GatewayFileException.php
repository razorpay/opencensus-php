<?php

namespace RZP\Exception;

use RZP\Error\Error;
use RZP\Error\ErrorCode;

class GatewayFileException extends RecoverableException
{
    protected $failureCode;

    public function __construct(string $failureCode, \Exception $previous = null)
    {
        $this->failureCode = $failureCode;

        $this->data = [
            'failure_code' => $failureCode
        ];

        $code = ErrorCode::SERVER_ERROR_GATEWAY_FILE_GENERATION_ERROR;

        $message = 'Error processing gateway file';

        $this->error = new Error($code, $message);

        parent::__construct($message, $code, $previous);
    }

    public function getFailureCode(): string
    {
        return $this->failureCode;
    }
}
