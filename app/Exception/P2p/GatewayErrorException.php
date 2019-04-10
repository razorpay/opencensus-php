<?php

namespace RZP\Exception\P2p;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\ErrorClass;

class GatewayErrorException extends Exception\GatewayErrorException implements ExceptionInterface
{
    use ErrorTrait;

    public function __construct(
        $code,
        $gatewayErrorCode = null,
        $gatewayErrorDesc = null,
        $data = [],
        \Exception $previous = null)
    {
        parent::__construct(ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE);

        $this->error = $this->newError($code, $data);

        $this->setGatewayErrorCodeAndDesc(
            $gatewayErrorCode,
            $gatewayErrorDesc);
    }
}
