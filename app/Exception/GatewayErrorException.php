<?php

namespace RZP\Exception;

use RZP\Error\Error;
use RZP\Error\ErrorCode;

class GatewayErrorException extends RecoverableException
{
    protected $twoFaError = false;

    public function __construct(
        $code,
        $gatewayErrorCode = null,
        $gatewayErrorDesc = null,
        \Exception $previous = null)
    {
        Error::checkErrorCode($code);

        $error = new Error($code);

        $this->setError($error);

        $this->setGatewayErrorCodeAndDesc(
            $gatewayErrorCode,
            $gatewayErrorDesc);

        $desc = $error->getDescription();

        $desc .= PHP_EOL . 'Gateway Error Code: ' . $gatewayErrorCode .
                 PHP_EOL . 'Gateway Error Desc: ' . $gatewayErrorDesc;

        $this->message = $desc;
    }

    public function markTwoFaError()
    {
        $this->twoFaError = true;
    }

    public function hasTwoFaError()
    {
        return $this->twoFaError ;
    }
}