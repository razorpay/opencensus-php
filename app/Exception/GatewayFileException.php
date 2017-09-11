<?php

namespace RZP\Exception;

use RZP\Error\Error;
use RZP\Error\ErrorCode;

/**
 * This exception class is created to uniformly handle any exceptions
 * while processing the gateway_file entity. The exception object stores
 * the relevant error code (currently at what stage of processing the error
 * occurred) and error description. It extends RecoverableException as we don't
 * want to interrupt / fail the processing,but update the entity with details of
 *  the failure.
 */
class GatewayFileException extends RecoverableException
{
    const PREFIX = 'SERVER_ERROR_GATEWAY_FILE_';

    public function __construct(string $code, \Exception $previous = null)
    {
        $this->error = new Error($code);

        $message = $this->error->getDescription();

        parent::__construct($message, $code, $previous);
    }

    public function getErrorCode()
    {
        return strtolower(substr($this->getCode(), strlen(self::PREFIX)));
    }
}
