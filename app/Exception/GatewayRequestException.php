<?php

namespace RZP\Exception;

use RZP\Error\Error;
use RZP\Error\ErrorCode;

class GatewayRequestException extends GatewayErrorException
{
    const ERROR_CODE = ErrorCode::GATEWAY_ERROR_REQUEST_ERROR;

    public function __construct($curlErrorMessage = null, \Exception $previous = null, $safeRetry = false, $data = [])
    {
        $data['message'] = $curlErrorMessage;

        parent::__construct(static::ERROR_CODE, null, null, $data, $previous, null, $safeRetry);

        $this->message = 'Gateway request failed with error';
    }
}
