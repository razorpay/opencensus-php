<?php

namespace EE\Exception;

use EE\Error\Error;
use EE\Error\ErrorCode;

class GatewayErrorException extends RecoverableException
{
    public function __construct(
        $code,
        $gatewayErrorCode = null,
        $gatewayErrorDesc = null,
        \Exception $previous = null)
    {
        if (defined('\EE\Error\ErrorCode::'.$code) === false)
        {
            throw new InvalidArgumentException($code . ' is not a valid code');
        }

        $error = new \EE\Error\Error($code);

        $this->setError($error);

        $this->setGatewayErrorCodeAndDesc(
            $gatewayErrorCode,
            $gatewayErrorDesc);

        $desc = $error->getDescription();

        if (\App::environment('dev'))
        {
            $desc .= PHP_EOL . 'Gateway Error Code: ' . $gatewayErrorCode .
                     PHP_EOL . 'Gateway Error Desc: ' . $gatewayErrorDesc;
        }

        $this->message = $desc;
    }
}