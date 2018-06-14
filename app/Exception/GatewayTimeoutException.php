<?php

namespace RZP\Exception;

use RZP\Error\Error;
use RZP\Error\ErrorCode;

class GatewayTimeoutException extends GatewayRequestException
{
    public function __construct($curlErrorMessage, \Exception $previous = null, $safeRetry = false)
    {
        $code = ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT;

        $this->error = new Error($code);

        $this->code = $code;

        $this->message = 'Gateway request timed out';

        $this->data['message'] = $curlErrorMessage;

        $this->safeRetry = $safeRetry;
    }
}