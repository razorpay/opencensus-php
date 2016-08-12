<?php

namespace RZP\Exception;

use RZP\Error\Error;
use RZP\Error\ErrorCode;

class GatewayTimeoutException extends RecoverableException
{
    public function __construct($curlErrorMessage, \Exception $previous = null, $safeRetry = false)
    {
        $code = ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT;

        $this->error = new Error($code);

        $this->message = $curlErrorMessage;

        $this->safeRetry = $safeRetry;

        // parent::__construct($curlErrorMessage, $code, $previous);

    }
}