<?php

namespace RZP\Exception;

use RZP\Error\Error;
use RZP\Error\ErrorCode;

class GatewayRequestException extends RecoverableException
{
    protected $safeRetry;
    
    public function __construct($curlErrorMessage, \Exception $previous = null, $safeRetry = false)
    {
        $code = ErrorCode::GATEWAY_ERROR_REQUEST_ERROR;

        $this->error = new Error($code);

        $this->message = $curlErrorMessage;

        $this->safeRetry = $safeRetry;
    }

    public function markSafeRetryTrue()
    {
        $this->safeRetry = true;
    }

    public function isSafeRetryTrue()
    {
        return $this->safeRetry;
    }
}