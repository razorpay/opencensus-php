<?php

namespace EE\Exception;

use EE\Error\Error;
use EE\Error\ErrorCode;

class GatewayTimeoutException extends RazorpayException
{
    public function __construct($curlErrorMessage, \Exception $previous = null)
    {
        $code = ErrorCode::GATEWAY_REQUEST_TIMEOUT;

        $this->error = new Error($code, $curlErrorMessage);

        parent::__construct($curlErrorMessage, 0, $previous);
    }
}