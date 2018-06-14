<?php

namespace RZP\Exception;

use RZP\Error\Error;
use RZP\Error\ErrorCode;

class GatewayRequestException extends GatewayErrorException
{
    protected $safeRetry;

    public function __construct($curlErrorMessage = null, \Exception $previous = null, $safeRetry = false)
    {
        $code = ErrorCode::GATEWAY_ERROR_REQUEST_ERROR;

        $this->error = new Error($code);

        $this->code = $code;

        $this->message = 'Gateway request failed with error';

        $this->data['message'] = $curlErrorMessage;

        $this->safeRetry = $safeRetry;
    }

    public function markSafeRetryTrue()
    {
        $this->safeRetry = true;
    }

    public function getSafeRetry()
    {
        return $this->safeRetry;
    }
}