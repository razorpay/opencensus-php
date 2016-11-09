<?php

namespace RZP\Exception;

use RZP\Error\Error;

class GatewayErrorException extends RecoverableException
{

    protected $twoFaError = false;

    protected $twoFaErrorCodes = [
        ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
        ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED,
        ErrorCode::BAD_REQUEST_PAYMENT_OTP_EXPIRED,
    ];

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
        if ($this->twoFaError === true)
        {
            return true;
        }

        $errorCode = $this->getError()->getInternalErrorCode();

        if ($this->isTwoFaError($errorCode) === true)
        {
            $this->markTwoFaError();

            return true;
        }

        return false;
    }

    protected function isTwoFaError($errorCode)
    {
        return in_array($errorCode, $this->twoFaErrorCodes);
    }
}