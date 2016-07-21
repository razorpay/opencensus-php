<?php

namespace RZP\Exception;

use RZP\Error\Error;
use RZP\Error\ErrorCode;

class GatewayErrorException extends RecoverableException
{
    const TWO_FA_ERROR = '2fa_error';

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

    public function mark2faError()
    {
        $this->data[self::TWO_FA_ERROR] = true;
    }

    public function has2faError()
    {
        $data = $this->getData();
        return (($data !== null) and ($data[self::TWO_FA_ERROR] === true));
    }
}