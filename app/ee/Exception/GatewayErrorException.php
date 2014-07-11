<?php

namespace EE\Exception;

use EE\Error\Error;
use EE\Error\ErrorCode;

class GatewayErrorException extends BaseException
{
    public function __construct(
        $code,
        $gatewayErrorCode = null,
        $gatewayErrorDesc = null,
        \Exception $previous = null)
    {
        if (defined('\EE\Error\ErrorCode::'.$code) === false)
        {
            throw new \InvalidArgumentException($code . ' is not a valid code');
        }

        $error = new \EE\Error\Error($code);

        $this->setError($error);

        $desc = $error->getPublicErrorDescription();

        $this->setGatewayErrorCodeAndDesc(
            $gatewayErrorCode,
            $gatewayErrorDesc);

        if (\App::environment('production') === false)
        {
            $desc .= PHP_EOL . 'Gateway Error Code: ' . $gatewayErrorCode .
                     PHP_EOL . 'Gateway Error Desc: ' . $gatewayErrorDesc;
        }

        parent::__construct($desc, $code, $previous);
    }
}