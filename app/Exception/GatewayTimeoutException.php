<?php

namespace RZP\Exception;

use RZP\Error\Error;
use RZP\Error\ErrorCode;

class GatewayTimeoutException extends GatewayRequestException
{
    const ERROR_CODE = ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT;

    public function __construct($curlErrorMessage, \Exception $previous = null, $safeRetry = false,$data = [])
    {
        parent::__construct($curlErrorMessage, $previous, $safeRetry,$data);

        $this->message = 'Gateway request timed out';
    }
}
