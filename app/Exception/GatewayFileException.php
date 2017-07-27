<?php

namespace RZP\Exception;

use RZP\Error\Error;
use RZP\Error\ErrorCode;

/**
 * This exception class is created to uniformly handle any exceptions
 * while processing the gateway_file entity. The exception object stores
 * the relevant failure reason (currently at what stage of processing the failure
 * occurred). It extends RecoverableException as we don't want to interrupt / fail
 * the processing,but update the entity with details of the failure.
 * @todo discuss once if this way of handling is correct or can be done better
 */
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
