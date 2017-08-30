<?php

namespace RZP\Exception;

use RZP\Error\ErrorCode;
use RZP\Error\ErrorClass;

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
        $data = [],
        \Exception $previous = null)
    {
        $this->initError($code);

        $this->setData($data);

        $this->setGatewayErrorCodeAndDesc(
            $gatewayErrorCode,
            $gatewayErrorDesc);
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

    public function setGatewayErrorCodeAndDesc($code, $desc)
    {
        $this->error->setGatewayErrorCodeAndDesc($code, $desc);

        $message = $this->error->getDescription();

        $message .= PHP_EOL . 'Gateway Error Code: ' . $code .
                    PHP_EOL . 'Gateway Error Desc: ' . $desc;

        $this->message = $message;
    }

    public function isCritical()
    {
        return (ErrorClass::isCritical($this->getError()->getClass()) === true);
    }

    protected function isTwoFaError($errorCode)
    {
        return in_array($errorCode, $this->twoFaErrorCodes);
    }
}
